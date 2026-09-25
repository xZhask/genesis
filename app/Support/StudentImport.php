<?php

namespace App\Support;

use App\Enums\DocumentType;
use App\Enums\GuardianRelationship;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importación de estudiantes y acudientes desde un CSV (Excel → Guardar como
 * → CSV). Una fila por estudiante y acudiente: si un estudiante tiene dos
 * acudientes, se repite en dos filas. Primero se valida todo el archivo y
 * solo se guarda si no hay errores.
 */
class StudentImport
{
    public const MAX_ROWS = 2000;

    /** Columnas de la plantilla, en orden. */
    public const COLUMNS = [
        'tipo_documento', 'documento', 'nombres', 'apellidos', 'fecha_nacimiento', 'grado', 'seccion',
        'acudiente_tipo_documento', 'acudiente_documento', 'acudiente_nombres', 'acudiente_apellidos',
        'parentesco', 'acudiente_telefono', 'acudiente_correo', 'acudiente_principal',
    ];

    private const REQUIRED = ['documento', 'nombres', 'apellidos', 'grado'];

    /** Otros encabezados que se aceptan. */
    private const ALIASES = [
        'tipo' => 'tipo_documento',
        'tipo_de_documento' => 'tipo_documento',
        'numero_documento' => 'documento',
        'numero_de_documento' => 'documento',
        'documento_estudiante' => 'documento',
        'fecha_de_nacimiento' => 'fecha_nacimiento',
        'nacimiento' => 'fecha_nacimiento',
        'grupo' => 'seccion',
        'telefono' => 'acudiente_telefono',
        'celular' => 'acudiente_telefono',
        'correo' => 'acudiente_correo',
        'principal' => 'acudiente_principal',
    ];

    private const ORDINALS = [
        'primero' => '1', 'segundo' => '2', 'tercero' => '3', 'cuarto' => '4', 'quinto' => '5',
        'sexto' => '6', 'septimo' => '7', 'octavo' => '8', 'noveno' => '9',
    ];

    /** @var array<int, array<string, mixed>> filas válidas, normalizadas */
    public array $rows = [];

    /** @var array<int, string> "Fila 4: …" */
    public array $errors = [];

    /** @var array<string, array<string, string>> acudientes con nombre en alguna fila, por documento */
    private array $namedGuardians = [];

    public function __construct(private SchoolYear $year) {}

    /** Lee y valida el archivo. No guarda nada. */
    public function parse(string $path): static
    {
        $this->rows = [];
        $this->errors = [];

        $lines = $this->readLines($path);
        if (count($lines) < 2) {
            $this->errors[] = 'El archivo está vacío o solo tiene la fila de encabezados.';

            return $this;
        }
        if (count($lines) - 1 > self::MAX_ROWS) {
            $this->errors[] = 'El archivo tiene más de '.self::MAX_ROWS.' filas. Divídelo en varios archivos.';

            return $this;
        }

        $delimiter = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';
        $headers = array_map(fn ($h) => $this->headerKey($h), str_getcsv($lines[0], $delimiter, '"', ''));

        $missing = array_diff(self::REQUIRED, $headers);
        if ($missing) {
            $this->errors[] = 'Faltan columnas obligatorias: '.implode(', ', $missing).'. Descarga la plantilla para ver el formato.';

            return $this;
        }

        $sections = Section::where('school_year_id', $this->year->id)->with('grade')->get()
            ->keyBy(fn (Section $s) => $s->grade_id.'|'.Str::upper($s->name));
        $grades = Grade::ordered();
        $students = [];

        $rawRows = [];
        foreach (array_slice($lines, 1) as $i => $line) {
            $cells = str_getcsv($line, $delimiter, '"', '');
            if (! array_filter($cells, fn ($c) => trim($c) !== '')) {
                continue; // fila vacía
            }

            $raw = [];
            foreach ($headers as $index => $key) {
                $raw[$key] = trim($cells[$index] ?? '');
            }
            $rawRows[$i + 2] = $raw; // fila en Excel (la 1 son los encabezados)
        }

        // Un acudiente de varios hermanos puede traer sus datos en una sola fila
        $this->namedGuardians = [];
        foreach ($rawRows as $raw) {
            $doc = DocumentType::normalize($raw['acudiente_documento'] ?? '');
            if ($doc !== '' && ($raw['acudiente_nombres'] ?? '') !== '' && ($raw['acudiente_apellidos'] ?? '') !== '') {
                $this->namedGuardians[$doc] ??= $raw;
            }
        }

        foreach ($rawRows as $number => $raw) {
            $row = $this->validateRow($raw, $grades, $sections, $number);
            if ($row === null) {
                continue;
            }

            // El mismo estudiante en varias filas (una por acudiente) debe estar en la misma sección
            $doc = $row['student']['document_number'];
            if (isset($students[$doc]) && $students[$doc] !== $row['section_id']) {
                $this->errors[] = "Fila {$number}: el estudiante {$doc} aparece en otra fila con una sección distinta.";

                continue;
            }
            $students[$doc] = $row['section_id'];

            $this->rows[] = $row;
        }

        if (! $this->rows && ! $this->errors) {
            $this->errors[] = 'El archivo no tiene filas con datos.';
        }

        return $this;
    }

    public function isValid(): bool
    {
        return $this->errors === [] && $this->rows !== [];
    }

    /** Resumen para la vista previa: nuevos y existentes. */
    public function summary(): array
    {
        $studentDocs = collect($this->rows)->pluck('student.document_number')->unique();
        $guardianDocs = collect($this->rows)->pluck('guardian.document_number')->filter()->unique();
        $existingStudents = Student::whereIn('document_number', $studentDocs)->count();
        $existingGuardians = Guardian::whereIn('document_number', $guardianDocs)->count();

        return [
            'rows' => count($this->rows),
            'students_new' => $studentDocs->count() - $existingStudents,
            'students_existing' => $existingStudents,
            'guardians_new' => $guardianDocs->count() - $existingGuardians,
            'guardians_existing' => $existingGuardians,
            'by_section' => collect($this->rows)->unique('student.document_number')->countBy('section_label')->sortKeys(SORT_NATURAL)->all(),
        ];
    }

    /**
     * Guarda todo en una transacción: crea o actualiza estudiantes y
     * acudientes, matricula en la sección y vincula con su parentesco.
     */
    public function apply(): array
    {
        $counts = ['students' => 0, 'guardians' => 0, 'links' => 0];

        DB::transaction(function () use (&$counts) {
            foreach ($this->rows as $row) {
                $student = Student::firstOrNew(['document_number' => $row['student']['document_number']]);
                $student->fill(array_filter($row['student'], fn ($v) => $v !== null));
                if (! $student->exists) {
                    $counts['students']++;
                }
                $student->save();

                Enrollment::place($student, Section::find($row['section_id']));

                if (! $row['guardian']) {
                    continue;
                }

                $guardian = Guardian::firstOrNew(['document_number' => $row['guardian']['document_number']]);
                $guardian->fill(array_filter($row['guardian'], fn ($v) => $v !== null));
                if (! $guardian->exists) {
                    $counts['guardians']++;
                }
                $guardian->save();

                $link = $student->guardians()->whereKey($guardian->id)->first();
                $hasPrimary = $student->guardians()->wherePivot('is_primary', true)->exists();
                $primary = $row['is_primary'] ?? ! $hasPrimary;

                if ($primary && $hasPrimary) {
                    $student->guardians()->updateExistingPivot($student->guardians()->pluck('guardians.id'), ['is_primary' => false]);
                }

                if ($link) {
                    $student->guardians()->updateExistingPivot($guardian->id, array_filter([
                        'relationship' => $row['relationship'],
                        'is_primary' => $row['is_primary'] ?? null,
                    ], fn ($v) => $v !== null));
                } else {
                    $student->guardians()->attach($guardian->id, [
                        'relationship' => $row['relationship'] ?? GuardianRelationship::Other,
                        'is_primary' => $primary,
                    ]);
                    $counts['links']++;
                }
            }
        });

        return $counts;
    }

    /** Plantilla vacía: encabezados separados por punto y coma (Excel en español). */
    public static function template(): string
    {
        return "\u{FEFF}".implode(';', self::COLUMNS)."\r\n";
    }

    private function validateRow(array $raw, $grades, $sections, int $number): ?array
    {
        $errors = [];

        foreach (self::REQUIRED as $key) {
            if (($raw[$key] ?? '') === '') {
                $errors[] = "falta «{$key}»";
            }
        }

        $document = DocumentType::normalize($raw['documento'] ?? '');
        $grade = ($raw['grado'] ?? '') !== '' ? $this->grade($raw['grado'], $grades) : null;

        // Sin tipo: se conserva el del estudiante ya registrado; si es nuevo,
        // registro civil en preescolar y tarjeta de identidad desde 1.°
        $documentType = $this->documentType($raw['tipo_documento'] ?? '');
        if (($raw['tipo_documento'] ?? '') === '' && ! Student::where('document_number', $document)->exists()) {
            $documentType = $grade?->isPreschool() ? DocumentType::CivilRegistry : DocumentType::IdentityCard;
        } elseif (($raw['tipo_documento'] ?? '') !== '' && ! $documentType) {
            $errors[] = "tipo de documento «{$raw['tipo_documento']}» no reconocido (usa RC, TI, CC, CE, PPT o PA)";
        }
        if ($document !== '' && ! preg_match('/^[A-Z0-9]{3,20}$/', $document)) {
            $errors[] = "documento «{$raw['documento']}» no válido";
        }

        $birthDate = null;
        if (($raw['fecha_nacimiento'] ?? '') !== '') {
            $birthDate = $this->date($raw['fecha_nacimiento']);
            if (! $birthDate) {
                $errors[] = "fecha de nacimiento «{$raw['fecha_nacimiento']}» no válida (usa 14/03/2015)";
            }
        }

        $section = null;
        if (($raw['grado'] ?? '') !== '' && ! $grade) {
            $errors[] = "grado «{$raw['grado']}» no reconocido";
        } elseif ($grade) {
            $sectionName = Str::upper(($raw['seccion'] ?? '') !== '' ? $raw['seccion'] : '1');
            $section = $sections[$grade->id.'|'.$sectionName] ?? null;
            if (! $section) {
                $errors[] = "la sección {$grade->name} {$sectionName} no existe en {$this->year->year} (créala en Académico → Grados y secciones)";
            }
        }

        // Acudiente (opcional, pero si hay documento se piden nombre y apellidos)
        $guardian = null;
        $relationship = null;
        $isPrimary = null;
        $guardianDoc = DocumentType::normalize($raw['acudiente_documento'] ?? '');
        if ($guardianDoc !== '') {
            $existing = Guardian::where('document_number', $guardianDoc)->exists();
            // Si en esta fila faltan sus datos pero están en otra fila del archivo, se toman de allá
            if (! $existing && ($raw['acudiente_nombres'] ?? '') === '' && isset($this->namedGuardians[$guardianDoc])) {
                $named = $this->namedGuardians[$guardianDoc];
                foreach (['acudiente_nombres', 'acudiente_apellidos', 'acudiente_tipo_documento'] as $key) {
                    $raw[$key] = ($raw[$key] ?? '') !== '' ? $raw[$key] : ($named[$key] ?? '');
                }
            }

            // Sin tipo: cédula si es nuevo; si ya existe, se conserva el suyo
            $guardianType = ($raw['acudiente_tipo_documento'] ?? '') === ''
                ? ($existing ? null : DocumentType::Citizenship)
                : $this->documentType($raw['acudiente_tipo_documento']);
            if (($raw['acudiente_tipo_documento'] ?? '') !== '' && ! $guardianType) {
                $errors[] = "tipo de documento del acudiente «{$raw['acudiente_tipo_documento']}» no reconocido";
            }
            if (! preg_match('/^[A-Z0-9]{3,20}$/', $guardianDoc)) {
                $errors[] = "documento del acudiente «{$raw['acudiente_documento']}» no válido";
            }
            if ($guardianDoc === $document) {
                $errors[] = 'el documento del acudiente es igual al del estudiante';
            }

            foreach (['acudiente_nombres', 'acudiente_apellidos'] as $key) {
                if (! $existing && ($raw[$key] ?? '') === '') {
                    $errors[] = "falta «{$key}»";
                }
            }

            $email = $raw['acudiente_correo'] ?? '';
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "correo del acudiente «{$email}» no válido";
            }

            $relationship = $this->relationship($raw['parentesco'] ?? '');
            $isPrimary = $this->yesNo($raw['acudiente_principal'] ?? '');

            $guardian = [
                'document_type' => $guardianType,
                'document_number' => $guardianDoc,
                'first_names' => $this->name($raw['acudiente_nombres'] ?? ''),
                'last_names' => $this->name($raw['acudiente_apellidos'] ?? ''),
                'phone' => ($raw['acudiente_telefono'] ?? '') ?: null,
                'email' => $email !== '' ? mb_strtolower($email) : null,
            ];
        }

        if ($errors) {
            $this->errors[] = "Fila {$number}: ".implode('; ', $errors).'.';

            return null;
        }

        return [
            'student' => [
                'document_type' => $documentType,
                'document_number' => $document,
                'first_names' => $this->name($raw['nombres']),
                'last_names' => $this->name($raw['apellidos']),
                'birth_date' => $birthDate,
            ],
            'section_id' => $section->id,
            'section_label' => $section->label(),
            'guardian' => $guardian,
            'relationship' => $relationship,
            'is_primary' => $isPrimary,
        ];
    }

    /** Líneas del archivo en UTF-8 (Excel suele guardar en Windows-1252). */
    private function readLines(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // Separa por saltos de línea respetando los que van entre comillas
        $lines = [];
        $buffer = null;
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            $buffer = $buffer === null ? $line : $buffer."\n".$line;
            if (substr_count($buffer, '"') % 2 === 0) {
                $lines[] = $buffer;
                $buffer = null;
            }
        }

        // Excel deja filas finales vacías (";;;;")
        while ($lines && trim(end($lines), " \t;,") === '') {
            array_pop($lines);
        }

        return $lines;
    }

    private function headerKey(string $header): string
    {
        $key = Str::of($header)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

        return self::ALIASES[$key] ?? $key;
    }

    private function documentType(string $value): ?DocumentType
    {
        $value = Str::of($value)->ascii()->upper()->replace(['.', ' '], '')->value();

        return DocumentType::tryFrom($value) ?? match ($value) {
            'NUIP', 'REGISTROCIVIL' => DocumentType::CivilRegistry,
            'TARJETADEIDENTIDAD' => DocumentType::IdentityCard,
            'CEDULA', 'CEDULADECIUDADANIA' => DocumentType::Citizenship,
            'CEDULADEEXTRANJERIA' => DocumentType::Foreigner,
            'PASAPORTE' => DocumentType::Passport,
            default => null,
        };
    }

    private function grade(string $value, $grades): ?Grade
    {
        $key = Str::of($value)->ascii()->lower()->replace(['grado', '°', 'º', '.', ' '], '')->value();
        $key = self::ORDINALS[$key] ?? $key;

        return $grades->first(function (Grade $grade) use ($key) {
            $name = Str::of($grade->name)->ascii()->lower()->replace(['°', 'º', '.', ' '], '')->value();

            return $name === $key;
        });
    }

    private function date(string $value): ?string
    {
        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (Throwable) {
                continue;
            }
            if ($date && $date->format($format) === $value && $date->year > 1990 && $date->isPast()) {
                return $date->toDateString();
            }
        }

        return null;
    }

    private function relationship(string $value): ?GuardianRelationship
    {
        $value = Str::of($value)->ascii()->lower()->trim()->value();

        return match (true) {
            $value === '' => null,
            in_array($value, ['madre', 'mama', 'mother']) => GuardianRelationship::Mother,
            in_array($value, ['padre', 'papa', 'father']) => GuardianRelationship::Father,
            str_starts_with($value, 'abuel') => GuardianRelationship::Grandparent,
            in_array($value, ['tia', 'tio']) => GuardianRelationship::UncleAunt,
            str_starts_with($value, 'herman') => GuardianRelationship::Sibling,
            default => GuardianRelationship::Other,
        };
    }

    private function yesNo(string $value): ?bool
    {
        $value = Str::of($value)->ascii()->lower()->trim()->value();

        return match ($value) {
            'si', 's', 'x', '1', 'yes' => true,
            'no', 'n', '0' => false,
            default => null,
        };
    }

    /**
     * Nombres en mayúsculas sostenidas o en minúsculas se pasan a formato
     * nombre ("ANDRÉS DE LA HOZ" → "Andrés de la Hoz"); si ya vienen con
     * mayúsculas y minúsculas, se respetan.
     */
    private function name(string $value): ?string
    {
        $value = Str::squish($value);
        if ($value === '') {
            return null;
        }
        if ($value !== mb_strtoupper($value) && $value !== mb_strtolower($value)) {
            return $value;
        }

        $words = explode(' ', mb_convert_case($value, MB_CASE_TITLE));
        foreach ($words as $i => $word) {
            if ($i > 0 && in_array(mb_strtolower($word), ['de', 'del', 'la', 'las', 'los', 'y'], true)) {
                $words[$i] = mb_strtolower($word);
            }
        }

        return implode(' ', $words);
    }
}
