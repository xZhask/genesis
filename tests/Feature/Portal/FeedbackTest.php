<?php

namespace Tests\Feature\Portal;

use App\Enums\FeedbackStatus;
use App\Enums\Role;
use App\Mail\FeedbackReceived;
use App\Models\FeedbackMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Buzón de sugerencias: estudiantes y acudientes escriben; solo los admins
 * autorizados leen. El docente mencionado y el resto del panel no lo ven.
 */
class FeedbackTest extends PortalTestCase
{
    private User $studentUser;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->studentUser = User::factory()->role(Role::Student)->create();
        $this->andres->user()->associate($this->studentUser)->save();
        $this->reviewer = User::factory()->feedbackReviewer()->create(['email' => 'rectoria@example.com']);
    }

    private function send(User $user, array $data)
    {
        return $this->actingAs($user)->post(route('portal.feedback.store'), $data + [
            'type' => 'suggestion',
            'body' => 'Sería bueno tener más ejercicios de práctica.',
        ]);
    }

    public function test_student_writes_about_one_of_their_classes(): void
    {
        $this->actingAs($this->studentUser)->get(route('portal.feedback'))
            ->assertOk()->assertSee('Matemáticas · Edgar Ojoalegre')->assertSee('¿Es urgente');

        $this->send($this->studentUser, ['about' => "{$this->andres->id}:{$this->assignment->id}", 'type' => 'complaint'])
            ->assertRedirect(route('portal.feedback'))->assertSessionHasNoErrors();

        $message = FeedbackMessage::sole();
        $this->assertSame(Role::Student, $message->author_role);
        $this->assertSame($this->teacher->id, $message->teacher_id);
        $this->assertSame($this->math->id, $message->subject_id);
        $this->assertSame($this->seventh->id, $message->section_id);
        $this->assertSame(FeedbackStatus::Received, $message->status);

        $this->actingAs($this->studentUser)->get(route('portal.feedback'))
            ->assertSee('Sería bueno tener más ejercicios')->assertSee('Recibido');
    }

    public function test_only_authorized_admins_get_a_notice_without_the_content(): void
    {
        User::factory()->admin()->create(['email' => 'secretaria@example.com']);
        User::factory()->feedbackReviewer()->inactive()->create(['email' => 'antigua@example.com']);

        $this->send($this->studentUser, ['about' => "{$this->andres->id}:0"]);

        Mail::assertQueued(FeedbackReceived::class, 1);
        Mail::assertQueued(FeedbackReceived::class, fn ($mail) => $mail->hasTo('rectoria@example.com'));
        $html = (new FeedbackReceived(FeedbackMessage::sole()->type))->render();
        $this->assertStringNotContainsString('ejercicios de práctica', $html);
        $this->assertStringNotContainsString('Andrés', $html);
    }

    public function test_classes_and_students_outside_the_account_are_rejected(): void
    {
        // Clase de otro grupo y otro estudiante
        $this->send($this->studentUser, ['about' => "{$this->andres->id}:{$this->otherAssignment->id}"])->assertSessionHasErrors('about');
        $this->send($this->studentUser, ['about' => "{$this->sara->id}:0"])->assertSessionHasErrors('about');
        $this->send($this->studentUser, ['about' => 'cualquier cosa', 'type' => 'otro', 'body' => 'corto'])
            ->assertSessionHasErrors(['about', 'type', 'body']);

        $this->assertSame(0, FeedbackMessage::count());
        Mail::assertNothingQueued();
    }

    public function test_guardian_writes_about_each_of_their_children(): void
    {
        $guardian = $this->guardianOf($this->andres, $this->lucia);

        $this->actingAs($guardian)->get(route('portal.feedback'))
            ->assertOk()->assertSee('Andrés Pérez Díaz · 7.° 1')->assertSee('Lucía Pérez Díaz · 3.° 1');

        $this->send($guardian, ['about' => "{$this->lucia->id}:{$this->otherAssignment->id}"])->assertSessionHasNoErrors();
        $this->send($guardian, ['about' => "{$this->sara->id}:0"])->assertSessionHasErrors('about');
        // La clase de Lucía no vale para Andrés
        $this->send($guardian, ['about' => "{$this->andres->id}:{$this->otherAssignment->id}"])->assertSessionHasErrors('about');

        $message = FeedbackMessage::sole();
        $this->assertSame(Role::Guardian, $message->author_role);
        $this->assertSame('Acudiente de Lucía Pérez Díaz (3.° 1)', $message->authorLabel());
    }

    public function test_teachers_without_children_and_admins_cannot_use_the_portal_mailbox(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.feedback'))->assertForbidden();
        $this->actingAs($this->teacher)->post(route('portal.feedback.store'), ['about' => "{$this->andres->id}:0"])->assertForbidden();
        $this->actingAs($this->reviewer)->get(route('portal.feedback'))->assertForbidden();
    }

    public function test_only_authorized_admins_open_the_mailbox(): void
    {
        $this->send($this->studentUser, ['about' => "{$this->andres->id}:{$this->assignment->id}"]);
        $message = FeedbackMessage::sole();
        $otherAdmin = User::factory()->admin()->create();
        $guardian = $this->guardianOf($this->andres);

        foreach ([$otherAdmin, $this->teacher, $this->studentUser, $guardian] as $user) {
            $this->actingAs($user)->get(route('admin.feedback.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.feedback.answer', $message), ['reply' => 'Hola, gracias.'])->assertForbidden();
            $this->actingAs($user)->post(route('admin.feedback.review', $message))->assertForbidden();
        }

        // El admin sin permiso no ve el buzón en el menú ni en el panel
        $this->actingAs($otherAdmin)->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.feedback.index'));
        $this->actingAs($this->reviewer)->get(route('admin.dashboard'))->assertSee(route('admin.feedback.index'))->assertSee('1 mensaje del buzón');

        $this->actingAs($this->reviewer)->get(route('admin.feedback.index'))
            ->assertOk()->assertSee('Sería bueno tener más ejercicios')->assertSee('Estudiante de 7.° 1')->assertSee('Matemáticas · Edgar Ojoalegre');
    }

    public function test_reviewer_answers_and_the_author_sees_the_reply(): void
    {
        $this->send($this->studentUser, ['about' => "{$this->andres->id}:0"]);
        $message = FeedbackMessage::sole();

        $this->actingAs($this->reviewer)->post(route('admin.feedback.review', $message))->assertRedirect();
        $this->assertSame(FeedbackStatus::InReview, $message->fresh()->status);

        $this->actingAs($this->reviewer)->post(route('admin.feedback.answer', $message), ['reply' => ''])
            ->assertSessionHasErrorsIn("reply{$message->id}", 'reply');
        $this->actingAs($this->reviewer)->post(route('admin.feedback.answer', $message), ['reply' => 'Gracias, lo revisamos con el área.'])
            ->assertSessionHasNoErrors();

        $message->refresh();
        $this->assertSame(FeedbackStatus::Answered, $message->status);
        $this->assertTrue($message->replier->is($this->reviewer));
        $this->assertSame(0, FeedbackMessage::open()->count());

        // Aviso en el menú hasta que lo ve
        $this->actingAs($this->studentUser)->get(route('portal.student.home'))->assertSee('respuestas nuevas');
        $this->actingAs($this->studentUser)->get(route('portal.feedback'))->assertSee('Gracias, lo revisamos con el área.');
        $this->assertNotNull($message->fresh()->reply_seen_at);
        $this->actingAs($this->studentUser)->get(route('portal.student.home'))->assertDontSee('respuestas nuevas');
    }

    public function test_authors_only_see_their_own_messages(): void
    {
        $this->send($this->studentUser, ['about' => "{$this->andres->id}:0", 'body' => 'Mensaje privado de Andrés sobre el colegio.']);

        // Ni su acudiente ve lo que escribió el estudiante, ni al revés
        $guardian = $this->guardianOf($this->andres);
        $this->actingAs($guardian)->get(route('portal.feedback'))->assertOk()->assertDontSee('Mensaje privado de Andrés');
    }

    public function test_daily_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->send($this->studentUser, ['about' => "{$this->andres->id}:0"])->assertSessionHasNoErrors();
        }
        $this->send($this->studentUser, ['about' => "{$this->andres->id}:0"])->assertSessionHasErrors('body');
        $this->assertSame(5, FeedbackMessage::count());
    }

    public function test_only_a_reviewer_grants_access_to_the_mailbox(): void
    {
        $secretary = User::factory()->admin()->create(['document_number' => '1001', 'email' => 'secre@example.com']);
        $data = fn (User $user, array $extra = []) => $extra + [
            'name' => $user->name, 'document_number' => $user->document_number ?? '9'.$user->id.'00', 'email' => $user->email, 'role' => 'admin',
        ];

        // Un admin sin permiso no puede dárselo a sí mismo
        $this->actingAs($secretary)->put(route('admin.people.staff.update', $secretary), $data($secretary, ['can_review_feedback' => '1']))->assertSessionHasNoErrors();
        $this->assertFalse($secretary->fresh()->can_review_feedback);

        // Quien lee el buzón sí lo da
        $this->actingAs($this->reviewer)->put(route('admin.people.staff.update', $secretary), $data($secretary, ['can_review_feedback' => '1']));
        $this->assertTrue($secretary->fresh()->can_review_feedback);

        // Pasar a docente lo quita
        $this->actingAs($this->reviewer)->put(route('admin.people.staff.update', $secretary), $data($secretary, ['role' => 'teacher', 'can_review_feedback' => '1']));
        $this->assertFalse($secretary->fresh()->can_review_feedback);

        // La única cuenta autorizada no se lo quita a sí misma
        $this->actingAs($this->reviewer)->put(route('admin.people.staff.update', $this->reviewer), $data($this->reviewer, ['can_review_feedback' => '0']))
            ->assertSessionHasErrors('can_review_feedback');
        $this->assertTrue($this->reviewer->fresh()->can_review_feedback);
    }
}
