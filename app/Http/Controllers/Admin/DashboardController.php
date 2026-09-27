<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Enums\ResourceType;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionRequest;
use App\Models\ContactUpdateRequest;
use App\Models\DonationAccount;
use App\Models\Event;
use App\Models\FeedbackMessage;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Models\Resource;
use App\Models\SchoolYear;
use App\Models\SentNotification;
use App\Models\VolunteerApplication;
use App\Support\AcademicAlerts;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', AdmissionRequest::class);

        $counts = AdmissionRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'counts' => $counts,
            'pending' => ($counts[AdmissionStatus::Received->value] ?? 0),
            'newVolunteers' => VolunteerApplication::where('status', VolunteerStatus::New)->count(),
            'contactChanges' => ContactUpdateRequest::pending()->count(),
            // Solo quien lee el buzón ve cuántos mensajes esperan
            'openFeedback' => auth()->user()->canReviewFeedback() ? FeedbackMessage::open()->count() : 0,
            'mailQueue' => SentNotification::whereNull('sent_at')->count(),
            'mailToday' => SentNotification::where('sent_at', '>=', today())->count(),
            'alertStudents' => ($year = SchoolYear::current()) ? AcademicAlerts::forSections($year, $year->sections()->with('grade')->get())->studentCount() : 0,
            'upcomingInterviews' => AdmissionRequest::where('status', AdmissionStatus::InterviewScheduled)
                ->where('interview_at', '>=', now()->startOfDay())
                ->orderBy('interview_at')
                ->limit(5)
                ->get(),
            'latest' => AdmissionRequest::latest('id')->limit(5)->get(),
            'events' => Event::upcoming()->limit(4)->get(),
            'checklist' => $this->checklist(),
        ]);
    }

    /**
     * Lo que le falta al sitio para verse completo. Cada punto enlaza a
     * donde se resuelve; desaparece de "pendientes" cuando se cumple.
     *
     * @return list<array{done: bool, label: string, url: string, hint?: string}>
     */
    private function checklist(): array
    {
        $resources = Resource::published()->pluck('type')->map->value->unique();
        $undescribed = GalleryPhoto::whereNull('alt')->count();

        $items = [
            [
                'done' => (bool) config('school.office_hours'),
                'label' => 'Horarios de atención en el pie de página',
                'url' => route('admin.settings.edit'),
            ],
            [
                'done' => (bool) config('school.whatsapp.enabled'),
                'label' => 'Confirmar el WhatsApp del colegio',
                'url' => route('admin.settings.edit'),
            ],
            [
                'done' => Post::published()->where('published_at', '>=', now()->subMonth())->exists(),
                'label' => 'Una noticia publicada en el último mes',
                'url' => route('admin.posts.create'),
            ],
            [
                'done' => Event::upcoming()->exists(),
                'label' => 'Próximos eventos en el calendario',
                'url' => route('admin.events.create'),
            ],
            [
                'done' => GalleryAlbum::published()->exists(),
                'label' => 'Un álbum de fotos publicado',
                'url' => route('admin.albums.create'),
            ],
            [
                'done' => $resources->contains(ResourceType::Schedule->value) && $resources->contains(ResourceType::Uniform->value),
                'label' => 'Horarios y uniformes en Recursos',
                'url' => route('admin.resources.index', ['tipo' => 'horarios']),
            ],
            [
                'done' => $resources->contains(ResourceType::Supplies->value),
                'label' => 'Listas de útiles',
                'url' => route('admin.resources.index', ['tipo' => 'utiles']),
            ],
            [
                'done' => DonationAccount::visible()->exists(),
                'label' => 'Cuentas para donar en Apóyanos',
                'url' => route('admin.accounts.index'),
            ],
        ];

        // Solo cuando ya hay fotos: sin ellas, el punto del álbum ya lo cubre
        if (GalleryPhoto::exists()) {
            $items[] = [
                'done' => $undescribed === 0,
                'label' => 'Describir todas las fotos de la galería',
                'url' => route('admin.albums.index'),
                'hint' => $undescribed ? "{$undescribed} sin descripción" : null,
            ];
        }

        return $items;
    }
}
