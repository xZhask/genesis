<?php

namespace App\Models;

use App\Enums\ContactField;
use App\Enums\ContactRequestStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Solicitud del acudiente para cambiar su teléfono o su correo. El dato
 * solo cambia cuando el admin la aprueba; queda quién decidió y cuándo.
 */
class ContactUpdateRequest extends Model
{
    protected $fillable = ['guardian_id', 'field', 'old_value', 'new_value'];

    protected function casts(): array
    {
        return [
            'field' => ContactField::class,
            'status' => ContactRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', ContactRequestStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === ContactRequestStatus::Pending;
    }

    /** El dato cambió (por ejemplo, el admin lo editó en la ficha) después de pedirse. */
    public function isStale(): bool
    {
        return $this->isPending() && (string) $this->guardian->{$this->field->value} !== (string) $this->old_value;
    }

    /**
     * Crea la solicitud; si ya había una pendiente del mismo dato, la
     * reemplaza (no se acumulan). Volver al valor actual la cancela.
     */
    public static function submit(Guardian $guardian, ContactField $field, string $value): ?self
    {
        $current = $guardian->{$field->value};

        return DB::transaction(function () use ($guardian, $field, $value, $current) {
            $pending = $guardian->contactRequests()->pending()->where('field', $field)->first();
            // El formulario muestra lo pedido: reenviarlo igual no cambia nada
            if ($pending?->new_value === $value) {
                return null;
            }
            $pending?->delete();

            if ((string) $current === $value) {
                return null;
            }

            return $guardian->contactRequests()->create([
                'field' => $field, 'old_value' => $current, 'new_value' => $value,
            ]);
        });
    }

    /**
     * Aplica el cambio. Un correo aprobado también pasa a la cuenta del
     * acudiente, porque es el que sirve para recuperar la contraseña.
     *
     * @throws ValidationException si el correo ya lo usa otra cuenta
     */
    public function approve(User $admin): void
    {
        DB::transaction(function () use ($admin) {
            $request = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $request->isPending()) {
                return;
            }

            $guardian = $this->guardian;
            // Si la cuenta es también de un docente, su correo de ingreso se conserva
            $account = $guardian->user?->role === Role::Guardian ? $guardian->user : null;

            if ($this->field === ContactField::Email && $account
                && User::where('email', mb_strtolower($this->new_value))->whereKeyNot($account->id)->exists()) {
                throw ValidationException::withMessages([
                    'request' => "El correo {$this->new_value} ya lo usa otra cuenta del portal. Recházala y pídele al acudiente otro correo.",
                ]);
            }

            $guardian->update([$this->field->value => $this->new_value]);
            if ($this->field === ContactField::Email && $account) {
                $account->forceFill(['email' => $this->new_value])->save();
            }

            $this->finish(ContactRequestStatus::Approved, $admin);
        });
    }

    public function reject(User $admin, ?string $note): void
    {
        if ($this->isPending()) {
            $this->note = filled($note) ? trim($note) : null;
            $this->finish(ContactRequestStatus::Rejected, $admin);
        }
    }

    private function finish(ContactRequestStatus $status, User $admin): void
    {
        $this->forceFill([
            'status' => $status,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }
}
