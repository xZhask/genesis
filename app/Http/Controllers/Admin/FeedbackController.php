<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\FeedbackMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bandeja del buzón de sugerencias. Solo la abren los admins autorizados
 * (gate review-feedback, también en la ruta); el resto del panel no la ve.
 */
class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('review-feedback');

        $filters = $request->validate([
            'estado' => ['nullable', Rule::in(['abiertos', 'respondidos', 'todos'])],
            'tipo' => ['nullable', Rule::enum(FeedbackType::class)],
            'docente' => ['nullable', 'integer'],
        ]);
        $status = $filters['estado'] ?? 'abiertos';

        $messages = FeedbackMessage::query()
            ->with(['author', 'student', 'section.grade', 'subject', 'teacher', 'replier'])
            ->when($status === 'abiertos', fn ($q) => $q->open())
            ->when($status === 'respondidos', fn ($q) => $q->where('status', FeedbackStatus::Answered))
            ->when($filters['tipo'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['docente'] ?? null, fn ($q, $teacher) => $q->where('teacher_id', $teacher))
            // Abiertos: primero los más antiguos (los que más esperan)
            ->when($status === 'abiertos', fn ($q) => $q->oldest('id'), fn ($q) => $q->latest('id'))
            ->paginate(15)
            ->withQueryString();

        return view('admin.feedback.index', [
            'messages' => $messages,
            'status' => $status,
            'filters' => $filters,
            'openCount' => FeedbackMessage::open()->count(),
            'teachers' => User::where('role', Role::Teacher)
                ->whereIn('id', FeedbackMessage::whereNotNull('teacher_id')->select('teacher_id'))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function review(FeedbackMessage $feedback): RedirectResponse
    {
        Gate::authorize('review-feedback');

        $feedback->markInReview();

        return back()->with('status_message', 'El mensaje quedó en revisión. Quien lo escribió verá ese estado en el portal.');
    }

    public function answer(Request $request, FeedbackMessage $feedback): RedirectResponse
    {
        Gate::authorize('review-feedback');

        // Una bolsa de errores por mensaje: la página tiene un formulario por cada uno
        $data = $request->validateWithBag(
            "reply{$feedback->id}",
            ['reply' => ['required', 'string', 'min:5', 'max:1500']],
            ['reply.required' => 'Escribe la respuesta.'],
            ['reply' => 'respuesta'],
        );

        $feedback->answer($request->user(), $data['reply']);

        return back()->with('status_message', 'Se envió la respuesta. Quien escribió la verá en su buzón del portal.');
    }
}
