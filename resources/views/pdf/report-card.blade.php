{{-- Boletín en PDF (dompdf: CSS 2.1, solo tablas). Tamaño carta, legible en blanco y negro. --}}
@php
    $font = fn ($file) => str_replace('\\', '/', resource_path("fonts/{$file}"));
    $logo = str_replace('\\', '/', public_path('images/logo.png'));
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <title>Boletín · {{ $school['name'] }}</title>
    <style>
        @font-face { font-family: 'Figtree'; font-weight: normal; src: url('{{ $font('Figtree-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Figtree'; font-weight: bold; src: url('{{ $font('Figtree-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Nunito'; font-weight: bold; src: url('{{ $font('Nunito-ExtraBold.ttf') }}') format('truetype'); }

        @page { margin: 14mm 12mm 14mm 12mm; }
        body { font-family: 'Figtree', sans-serif; font-size: 8.6pt; color: #1b2733; line-height: 1.3; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        h1, .title { font-family: 'Nunito', sans-serif; font-weight: bold; color: #104976; }

        .head td { vertical-align: top; }
        .head .logo { width: 58px; }
        .head .logo img { width: 52px; }
        .head h1 { font-size: 14pt; margin: 0 0 2px; }
        .head .meta { font-size: 7.6pt; color: #4a5866; }
        .head .badge { width: 150px; text-align: right; }
        .head .badge div { border: 1.5px solid #104976; border-radius: 6px; padding: 6px 8px; text-align: center; }
        .head .badge b { display: block; font-family: 'Nunito'; font-size: 12pt; color: #104976; }

        .student { margin: 8px 0 8px; border-top: 2px solid #104976; border-bottom: 1px solid #b9c6d3; }
        .student td { padding: 5px 4px; font-size: 8.2pt; }
        .student .label { color: #4a5866; font-size: 7pt; display: block; text-transform: uppercase; letter-spacing: .03em; }

        .grades th { background: #e8eef5; color: #104976; font-size: 7.2pt; text-transform: uppercase; padding: 4px 3px; border-bottom: 1px solid #b9c6d3; }
        .grades td { padding: 3px 3px; border-bottom: 1px solid #e1e7ee; vertical-align: top; }
        .grades .num { text-align: center; width: 30px; }
        .grades .wide { width: 58px; text-align: center; }
        .grades tr.area td { background: #f3f6f9; font-weight: bold; color: #104976; border-bottom: 1px solid #b9c6d3; padding-top: 5px; }
        .grades .subject { font-weight: bold; }
        .grades .teacher { color: #5b6875; font-size: 7pt; font-weight: normal; }
        .grades tr.objectives td { font-size: 7.4pt; color: #33414e; padding: 0 3px 5px 12px; }
        .grades tr.objectives ul { margin: 0; padding-left: 10px; }
        .current { font-weight: bold; }
        .low { color: #b42318; font-weight: bold; }

        .summary { margin-top: 10px; }
        .summary td { vertical-align: top; padding: 0 6px 0 0; }
        .box { border: 1px solid #b9c6d3; border-radius: 5px; padding: 6px 8px; }
        .box .label { color: #4a5866; font-size: 7pt; text-transform: uppercase; letter-spacing: .03em; }
        .box b { font-size: 11pt; font-family: 'Nunito'; color: #104976; }

        .legend { margin-top: 8px; font-size: 7pt; color: #4a5866; }
        .signatures { margin-top: 34px; }
        .signatures td { width: 50%; text-align: center; padding: 0 30px; }
        .signatures .line { border-top: 1px solid #1b2733; padding-top: 3px; }
        .signatures .role { font-size: 7.4pt; color: #4a5866; }
    </style>
</head>
<body>
@foreach ($cards as $card)
    @php
        $student = $card->enrollment->student;
        $section = $card->enrollment->section;
        $scale = $card->scale;
        $allPeriods = $card->enrollment->schoolYear->periods;
        $report = $card->report();
        $attendance = $card->attendance();
        $final = $card->isFinal();
        $f = fn ($v) => $scale->format($v);
    @endphp
    <div class="card">
        <table class="head">
            <tr>
                <td class="logo"><img src="{{ $logo }}" alt=""></td>
                <td>
                    <h1>{{ $school['name'] }}</h1>
                    <div class="meta">
                        @if ($school['report_card']['approval']){{ $school['report_card']['approval'] }}<br>@endif
                        @if ($school['report_card']['nit'])NIT {{ $school['report_card']['nit'] }} · @endif
                        @if ($school['report_card']['campus'])Sede {{ $school['report_card']['campus'] }} · @endif
                        {{ $school['contact']['address'] }}, {{ $school['contact']['city'] }}<br>
                        {{ $school['contact']['phone'] }} · {{ $school['contact']['email'] }}
                    </div>
                </td>
                <td class="badge">
                    <div>
                        {{ $final ? 'Informe final' : 'Boletín' }}
                        <b>{{ $final ? 'Año '.$card->period->schoolYear->year : $card->period->name() }}</b>
                        Año lectivo {{ $card->period->schoolYear->year }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="student">
            <tr>
                <td style="width: 38%"><span class="label">Estudiante</span><b>{{ $student->last_names }}, {{ $student->first_names }}</b></td>
                <td style="width: 18%"><span class="label">Documento</span>{{ $student->documentLabel() }}</td>
                <td style="width: 12%"><span class="label">Grupo</span>{{ $section->label() }}</td>
                <td style="width: 32%"><span class="label">Director de grupo</span>{{ $section->homeroomTeacher?->name ?? '—' }}</td>
            </tr>
        </table>

        <table class="grades">
            <thead>
                <tr>
                    <th style="text-align: left">Área / materia</th>
                    <th class="num">IH</th>
                    @foreach ($allPeriods as $p)
                        <th class="num">{{ $p->number }}P</th>
                    @endforeach
                    <th class="num">Acum.</th>
                    <th class="wide">Desempeño</th>
                    <th class="num">Faltas</th>
                    <th class="wide">{{ $final ? 'Situación' : 'Para aprobar' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($card->areas() as $area)
                    <tr class="area">
                        <td>{{ $area['name'] }}</td>
                        <td class="num">{{ $area['hours'] ?: '' }}</td>
                        <td colspan="{{ $allPeriods->count() + 4 }}">
                            @if ($area['subjects']->count() > 1 && $area['score'] !== null)
                                Nota del área en el periodo: {{ $f($area['score']) }} ({{ $scale->performanceFor($area['score'])->label() }})
                            @endif
                        </td>
                    </tr>
                    @foreach ($area['subjects'] as $subject)
                        <tr>
                            <td>
                                <span class="subject">{{ $subject['name'] }}</span>
                                @if ($subject['teacher'])<br><span class="teacher">{{ $subject['teacher'] }}</span>@endif
                            </td>
                            <td class="num">{{ $subject['hours'] ?: '' }}</td>
                            @foreach ($allPeriods as $p)
                                @php $value = $subject['scores'][$p->number] ?? null; @endphp
                                <td class="num {{ $p->is($card->period) ? 'current' : '' }} {{ $value !== null && $value < $scale->passing_score ? 'low' : '' }}">
                                    {{ $value !== null ? $f($value) : '' }}
                                </td>
                            @endforeach
                            <td class="num current">{{ $subject['accumulated'] !== null ? $f($subject['accumulated']) : '' }}</td>
                            <td class="wide">{{ $subject['performance']?->label() ?? '—' }}</td>
                            <td class="num">{{ $subject['absences'] }}</td>
                            <td class="wide">
                                @switch($subject['status']['key'])
                                    @case('reached') {{ $final ? 'Aprobó' : 'Alcanzado' }} @break
                                    @case('needs') {{ $f($subject['status']['needed']) }} @break
                                    @case('support') Acompañamiento @break
                                    @default No aprobó
                                @endswitch
                            </td>
                        </tr>
                        @if ($subject['objectives'])
                            <tr class="objectives">
                                <td colspan="{{ $allPeriods->count() + 6 }}">
                                    <ul>
                                        @foreach ($subject['objectives'] as $text)
                                            <li>{{ $text }}.</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td style="width: 22%"><div class="box"><div class="label">Promedio del periodo</div><b>{{ $card->average() !== null ? $f($card->average()) : '—' }}</b></div></td>
                <td style="width: 22%"><div class="box"><div class="label">Comportamiento</div><b>{{ $report?->behavior !== null ? $f($report->behavior) : '—' }}</b></div></td>
                <td style="width: 56%; padding-right: 0">
                    <div class="box">
                        <div class="label">Asistencia del periodo</div>
                        {{ $attendance['absent'] }} {{ $attendance['absent'] === 1 ? 'falta' : 'faltas' }} ·
                        {{ $attendance['late'] }} {{ $attendance['late'] === 1 ? 'llegada tarde' : 'llegadas tarde' }} ·
                        {{ $attendance['excused'] }} {{ $attendance['excused'] === 1 ? 'excusa' : 'excusas' }}
                    </div>
                </td>
            </tr>
        </table>

        @if ($report?->observations)
            <div class="box" style="margin-top: 8px">
                <div class="label">Observaciones del director de grupo</div>
                {{ $report->observations }}
            </div>
        @endif

        <div class="legend">
            Escala: Bajo {{ $f($scale->min_score) }}–{{ $f($scale->basic_from - 0.01) }} · Básico {{ $f($scale->basic_from) }}–{{ $f($scale->high_from - 0.01) }} ·
            Alto {{ $f($scale->high_from) }}–{{ $f($scale->superior_from - 0.01) }} · Superior {{ $f($scale->superior_from) }}–{{ $f($scale->max_score) }}.
            Nota para aprobar: {{ $f($scale->passing_score) }}. El acumulado suma cada periodo según su peso.
            @unless ($final)
                «Para aprobar»: promedio que necesita en los periodos que faltan.
            @endunless
            Faltas: inasistencias de la materia en el periodo.
        </div>

        <table class="signatures">
            <tr>
                <td>
                    <div class="line">@if ($section->homeroomTeacher){{ $section->homeroomTeacher->name }}@else&nbsp;@endif</div>
                    <div class="role">Director(a) de grupo</div>
                </td>
                <td>
                    <div class="line">@if ($school['report_card']['rector']){{ $school['report_card']['rector'] }}@else&nbsp;@endif</div>
                    <div class="role">Rectoría</div>
                </td>
            </tr>
        </table>
        <div class="legend" style="text-align: right">Expedido el {{ now()->longDate() }}</div>
    </div>
@endforeach
</body>
</html>
