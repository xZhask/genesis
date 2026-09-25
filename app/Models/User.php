<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// El rol no es asignable en masa: solo el admin lo cambia de forma explícita.
#[Fillable(['name', 'document_number', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** Se ingresa con el documento: se guarda sin puntos ni espacios. */
    protected function documentNumber(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => filled($value) ? DocumentType::normalize($value) : null);
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => filled($value) ? mb_strtolower(trim($value)) : null);
    }

    /** Materias y secciones que dicta (docentes). */
    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    public function homeroomSections(): HasMany
    {
        return $this->hasMany(Section::class, 'homeroom_teacher_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** Un docente también puede ser acudiente: su cuenta se vincula a ambos. */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Zona de cada rol después de ingresar. */
    public function homeUrl(): string
    {
        return match ($this->role) {
            Role::Admin => route('admin.dashboard'),
            Role::Teacher => route('portal.teacher.home'),
            Role::Student => route('portal.student.home'),
            Role::Guardian => route('portal.guardian.home'),
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Correo de recuperación de contraseña en español. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
