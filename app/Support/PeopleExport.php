<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;

/**
 * Excel con las personas registradas hoy. La hoja de estudiantes usa las mismas
 * columnas de la plantilla de importación, más dos al final solo informativas.
 * Nunca lleva contraseñas.
 */
class PeopleExport
{
    private const EXTRA_COLUMNS = ['estudiante_correo', 'estado_matricula'];

    private const STAFF_COLUMNS = ['Nombre', 'Documento', 'Correo', 'Rol', 'Fecha de nacimiento', 'Cuenta', 'Clases'];

    public function __construct(private ?SchoolYear $year) {}

    public function build(): string
    {
        return SpreadsheetWriter::build([
            'Estudiantes y acudientes' => [
                'headers' => $this->studentHeaders(),
                'rows' => $this->studentRows(),
            ],
            'Docentes y administración' => [
                'headers' => self::STAFF_COLUMNS,
                'rows' => $this->staffRows(),
            ],
        ]);
    }

    /** @return list<string> */
    public function studentHeaders(): array
    {
        return [...StudentImport::COLUMNS, ...self::EXTRA_COLUMNS];
    }

    /**
     * Una fila por estudiante y acudiente, como en la importación; quien no
     * tiene acudiente registrado también aparece.
     *
     * @return list<list<string|null>>
     */
    public function studentRows(): array
    {
        $rows = [];

        Student::alphabetical()
            ->with(['user', 'guardians', 'enrollments' => fn ($q) => $q->where('school_year_id', $this->year?->id)->with('section.grade')])
            ->chunk(200, function ($students) use (&$rows) {
                foreach ($students as $student) {
                    $enrollment = $student->enrollments->first();
                    $base = [
                        $student->document_type?->value,
                        $student->document_number,
                        $student->first_names,
                        $student->last_names,
                        $student->birth_date?->format('d/m/Y'),
                        $enrollment?->section->grade->name,
                        $enrollment?->section->name,
                    ];
                    $extra = [$student->user?->email, $enrollment?->status->label() ?? 'Sin matrícula este año'];

                    foreach ($student->guardians->all() ?: [null] as $guardian) {
                        $rows[] = [...$base, ...$this->guardianCells($guardian), ...$extra];
                    }
                }
            });

        return $rows;
    }

    /** @return list<string|null> */
    private function guardianCells(?Guardian $guardian): array
    {
        if (! $guardian) {
            return array_fill(0, 8, null);
        }

        return [
            $guardian->document_type?->value,
            $guardian->document_number,
            $guardian->first_names,
            $guardian->last_names,
            $guardian->pivot->relationship?->label(),
            $guardian->phone,
            $guardian->email,
            $guardian->pivot->is_primary ? 'Sí' : 'No',
        ];
    }

    /** @return list<list<string|null>> */
    public function staffRows(): array
    {
        return User::whereIn('role', [Role::Teacher, Role::Admin])
            ->with(['assignments' => fn ($q) => $q
                ->whereHas('section', fn ($s) => $s->where('school_year_id', $this->year?->id))
                ->with('section.grade', 'subject')])
            ->orderBy('role', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                $user->name,
                $user->document_number,
                $user->email,
                $user->role->label(),
                $user->birth_date?->format('d/m/Y'),
                $user->is_active ? 'Activa' : 'Desactivada',
                $user->assignments->map(fn (TeacherAssignment $a) => $a->label())->implode('; '),
            ])
            ->all();
    }
}
