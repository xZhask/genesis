<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EvaluationComponent;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\SchoolYear;
use App\Models\Score;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\PeriodResults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Docentes FICTICIOS, sus asignaciones y asistencia de muestra para
 * desarrollo local (nunca en producción). Edgar Ojoalegre
 * (docente@genesis.test) dicta las matemáticas de secundaria y dirige 7.° 1.
 */
class TeachingDemoSeeder extends Seeder
{
    /** Especialistas de secundaria por materia. */
    private const SECONDARY = [
        'docente@genesis.test' => ['Matemáticas', 'Álgebra', 'Geometría', 'Estadística'],
        'Rocío Barrios Julio' => ['Español', 'Filosofía', 'Cátedra de Educación Emocional'],
        'Álvaro Castro Meza' => ['Biología', 'Química', 'Física'],
        'Candelaria Ortega Ruiz' => ['Sociales e historia', 'Constitución Política y Democracia', 'Educación financiera'],
        'Yuleidis Pájaro Herrera' => ['Inglés'],
        'Wilmer Arrieta Salgado' => ['Educación Física', 'Música', 'Artística'],
        'Katherine Villalba Torres' => ['Informática', 'Educación cristiana'],
    ];

    public function run(): void
    {
        $year = SchoolYear::current();
        if (! $year) {
            return;
        }

        mt_srand(1997);
        $password = Hash::make('password');
        $document = 32000100;

        $teacher = function (string $name) use ($password, &$document): User {
            $user = User::where('email', $name)->first()
                ?? User::firstWhere('name', $name)
                ?? tap(new User(['name' => $name, 'document_number' => (string) $document++, 'password' => $password]), function (User $u) {
                    $u->role = Role::Teacher;
                    $u->save();
                });

            return $user;
        };

        $sections = $year->sections()->with('grade.subjects')->get()->sortBy(fn (Section $s) => [$s->grade->position, $s->name]);

        // Preescolar y primaria: una docente por sección dicta casi todo; inglés y educación física aparte
        $homeroomNames = ['Luz Marina Salgado', 'Deisy Mercado Cantillo', 'Mónica Gómez Padilla', 'Rosa Herrera Julio', 'Yaneth Torres Meza',
            'Orlando Villalba Ruiz', 'Hernán Cantillo Ortega', 'Libardo Meza Arrieta', 'Candelaria Julio Pájaro', 'Édgar Padilla Castro', 'Katherine Ruiz Barrios'];
        $english = $teacher('Yuleidis Pájaro Herrera');
        $sports = $teacher('Wilmer Arrieta Salgado');
        $i = 0;

        foreach ($sections as $section) {
            if ($section->grade->position <= 9) {
                $main = $teacher($homeroomNames[$i++ % count($homeroomNames)]);
                $section->update(['homeroom_teacher_id' => $main->id]);
                foreach ($section->grade->subjects as $subject) {
                    $owner = match ($subject->name) {
                        'Inglés' => $english,
                        'Educación Física' => $sports,
                        default => $main,
                    };
                    TeacherAssignment::create(['teacher_id' => $owner->id, 'section_id' => $section->id, 'subject_id' => $subject->id]);
                }

                continue;
            }

            foreach ($section->grade->subjects as $subject) {
                $owner = collect(self::SECONDARY)->search(fn ($subjects) => in_array($subject->name, $subjects, true));
                if ($owner) {
                    TeacherAssignment::create(['teacher_id' => $teacher($owner)->id, 'section_id' => $section->id, 'subject_id' => $subject->id]);
                }
            }
        }

        // Directores de grupo de secundaria
        $directors = array_keys(self::SECONDARY);
        foreach ($sections->filter(fn ($s) => $s->grade->position > 9)->values() as $n => $section) {
            $name = $section->label() === '7.° 1' ? 'docente@genesis.test' : $directors[($n + 1) % count($directors)];
            $section->update(['homeroom_teacher_id' => $teacher($name)->id]);
        }

        $this->seedAttendance($year);
        $this->seedGrades($year);
    }

    /**
     * Notas de ejemplo: una actividad por componente en cada periodo que ya
     * empezó (dos en el periodo en curso), logros de las clases de Edgar
     * Ojoalegre y comportamiento. Luego se congelan los periodos cerrados.
     */
    private function seedGrades(SchoolYear $year): void
    {
        $scale = GradingScale::forYear($year);
        $periods = $year->periods()->where('starts_on', '<=', today())->get();
        $sections = $year->sections()->with(['grade.subjects', 'enrollments'])->get()->reject(fn ($s) => $s->grade->isPreschool());
        $edgar = User::where('email', 'docente@genesis.test')->first();

        // Cada estudiante tiene un nivel base para que sus notas sean coherentes
        $base = [];
        foreach ($sections as $section) {
            foreach ($section->enrollments as $enrollment) {
                $base[$enrollment->id] = mt_rand(300, 480) / 100;
            }
        }

        $items = [
            EvaluationComponent::Knowing->value => ['Evaluación escrita', 'Quiz'],
            EvaluationComponent::Doing->value => ['Taller en clase', 'Exposición'],
            EvaluationComponent::Being->value => ['Participación', 'Trabajo en equipo'],
        ];

        $scores = [];
        foreach ($sections as $section) {
            foreach ($section->grade->subjects as $subject) {
                foreach ($periods as $period) {
                    $count = $period->isClosed() ? 1 : 2;
                    foreach ($items as $component => $names) {
                        for ($n = 0; $n < $count; $n++) {
                            $item = GradeItem::create([
                                'section_id' => $section->id, 'subject_id' => $subject->id, 'period_id' => $period->id,
                                'component' => $component, 'name' => $names[$n], 'position' => $n,
                            ]);
                            foreach ($section->enrollments as $enrollment) {
                                $value = max($scale->min_score, min($scale->max_score, $base[$enrollment->id] + mt_rand(-60, 50) / 100));
                                $scores[] = ['grade_item_id' => $item->id, 'enrollment_id' => $enrollment->id, 'value' => round($value, 1), 'created_at' => now(), 'updated_at' => now()];
                            }
                        }
                    }
                }
            }

            foreach ($periods as $period) {
                foreach ($section->enrollments as $enrollment) {
                    PeriodReport::create(['enrollment_id' => $enrollment->id, 'period_id' => $period->id, 'behavior' => mt_rand(38, 50) / 10]);
                }
            }
        }

        foreach (array_chunk($scores, 1000) as $chunk) {
            Score::insert($chunk);
        }

        // Logros de las clases de Edgar (Matemáticas)
        $objectives = [
            EvaluationComponent::Knowing->value => 'resolver problemas con números racionales en situaciones cotidianas',
            EvaluationComponent::Doing->value => 'representar datos en tablas y gráficas de barras de forma ordenada',
            EvaluationComponent::Being->value => 'respetar el turno de la palabra y las ideas de los compañeros al trabajar en grupo',
        ];
        foreach (TeacherAssignment::where('teacher_id', $edgar?->id)->get() as $assignment) {
            foreach ($periods as $period) {
                foreach ($objectives as $component => $text) {
                    PeriodObjective::create([
                        'section_id' => $assignment->section_id, 'subject_id' => $assignment->subject_id,
                        'period_id' => $period->id, 'component' => $component, 'text' => $text, 'updated_by' => $edgar->id,
                    ]);
                }
            }
        }

        foreach ($periods->filter->isClosed() as $period) {
            PeriodResults::freeze($period);
        }
    }

    /**
     * Asistencia de muestra de los días hábiles desde agosto hasta ayer, para
     * que el portal se vea con datos (incluye días de un periodo cerrado).
     */
    private function seedAttendance(SchoolYear $year): void
    {
        $days = collect();
        for ($day = Carbon::create($year->year, 8, 3); $day->lt(today()) && $day->lte($year->ends_on); $day->addDay()) {
            if ($day->isWeekday()) {
                $days->push($day->copy());
            }
        }

        $rows = [];
        $assignments = TeacherAssignment::with('section.enrollments')->get();
        foreach ($assignments as $assignment) {
            $enrollments = $assignment->section->enrollments->where('status', EnrollmentStatus::Active);
            foreach ($days as $day) {
                // No todas las materias tienen clase todos los días
                if (mt_rand(1, 5) > 2) {
                    continue;
                }
                $period = $year->periodFor($day);
                foreach ($enrollments as $enrollment) {
                    $roll = mt_rand(1, 100);
                    $rows[] = [
                        'enrollment_id' => $enrollment->id,
                        'subject_id' => $assignment->subject_id,
                        'period_id' => $period->id,
                        'date' => $day->toDateString(),
                        'status' => match (true) {
                            $roll <= 4 => AttendanceStatus::Absent->value,
                            $roll <= 7 => AttendanceStatus::Late->value,
                            $roll <= 8 => AttendanceStatus::Excused->value,
                            default => AttendanceStatus::Present->value,
                        },
                        'recorded_by' => $assignment->teacher_id,
                        'created_at' => $day->copy()->setTime(9, 0),
                        'updated_at' => $day->copy()->setTime(9, 0),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            Attendance::insert($chunk);
        }
    }
}
