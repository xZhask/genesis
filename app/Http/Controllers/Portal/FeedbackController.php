<?php

namespace App\Http\Controllers\Portal;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\FeedbackRequest;
use App\Mail\FeedbackReceived;
use App\Models\FeedbackMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Buzón de sugerencias del portal: estudiantes y acudientes escriben y ven
 * sus propios mensajes con la respuesta del colegio. Nadie más los ve aquí.
 */
class FeedbackController extends Controller
{
    /** Mensajes por cuenta en 24 horas. */
    public const DAILY_LIMIT = 5;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(FeedbackMessage::authorRoleOf($user), 403);

        $messages = FeedbackMessage::where('user_id', $user->id)
            ->with(['student', 'subject', 'teacher'])
            ->latest('id')
            ->paginate(10);

        // Las respuestas que se muestran quedan vistas (el contador del menú se apaga)
        FeedbackMessage::unseenReplies($user)->whereIn('id', $messages->pluck('id'))->update(['reply_seen_at' => now()]);

        return view('portal.feedback', [
            'topics' => FeedbackMessage::topicsFor($user),
            'messages' => $messages,
            'isStudent' => FeedbackMessage::authorRoleOf($user) === Role::Student,
        ]);
    }

    public function store(FeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();
        $key = "feedback:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, self::DAILY_LIMIT)) {
            return back()->withInput()->withErrors(['body' => 'Ya enviaste varios mensajes hoy. Podrás escribir de nuevo mañana; si es urgente, comunícate con el colegio.']);
        }

        $topic = $request->topic();
        $message = new FeedbackMessage($request->safe()->only(['type', 'body']));
        $message->forceFill([
            'user_id' => $user->id,
            'author_role' => FeedbackMessage::authorRoleOf($user),
            'student_id' => $topic['student']->id,
            'section_id' => $topic['section']?->id,
            'teacher_assignment_id' => $topic['assignment']?->id,
            'subject_id' => $topic['assignment']?->subject_id,
            'teacher_id' => $topic['assignment']?->teacher_id,
        ])->save();
        RateLimiter::hit($key, 60 * 60 * 24);

        // Aviso sin contenido a cada persona autorizada (por separado: nadie ve a quién más llega)
        User::feedbackReviewers()->whereNotNull('email')->pluck('email')
            ->each(fn (string $email) => Mail::to($email)->queue(new FeedbackReceived($message->type)));

        return redirect()->route('portal.feedback')->with('status_message', 'Recibimos tu mensaje. Aquí verás cuando el colegio lo responda.');
    }
}
