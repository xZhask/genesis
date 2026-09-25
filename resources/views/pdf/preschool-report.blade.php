{{-- Boletín de preescolar (provisional): descripciones por dimensión, sin notas. dompdf: CSS 2.1. --}}
@php
    $font = fn ($file) => str_replace('\\', '/', resource_path("fonts/{$file}"));
    $logo = str_replace('\\', '/', public_path('images/logo.png'));
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <title>Informe de preescolar · {{ $school['name'] }}</title>
    <style>
        @font-face { font-family: 'Figtree'; font-weight: normal; src: url('{{ $font('Figtree-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Figtree'; font-weight: bold; src: url('{{ $font('Figtree-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Nunito'; font-weight: bold; src: url('{{ $font('Nunito-ExtraBold.ttf') }}') format('truetype'); }

        @page { margin: 12mm 14mm; }
        body { font-family: 'Figtree', sans-serif; font-size: 9.2pt; color: #1b2733; line-height: 1.32; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-family: 'Nunito'; font-weight: bold; color: #104976; font-size: 14pt; margin: 0 0 2px; }
        .head td { vertical-align: top; }
        .head .logo { width: 58px; }
        .head .logo img { width: 52px; }
        .head .meta { font-size: 7.6pt; color: #4a5866; }
        .head .badge { width: 150px; }
        .head .badge div { border: 1.5px solid #3FA64A; border-radius: 6px; padding: 6px 8px; text-align: center; }
        .head .badge b { display: block; font-family: 'Nunito'; font-size: 12pt; color: #2B7A34; }
        .student { margin: 8px 0 10px; border-top: 2px solid #3FA64A; border-bottom: 1px solid #b9c6d3; }
        .student td { padding: 5px 4px; font-size: 8.6pt; }
        .label { color: #4a5866; font-size: 7pt; display: block; text-transform: uppercase; letter-spacing: .03em; }
        .dimension { border-bottom: 1px solid #e1e7ee; padding: 5px 0; page-break-inside: avoid; }
        .dimension h2 { font-family: 'Nunito'; font-size: 10.5pt; color: #2B7A34; margin: 0 0 2px; }
        .dimension .teacher { font-size: 7.4pt; color: #5b6875; }
        .dimension p { margin: 3px 0 0; }
        .pending { color: #5b6875; font-style: italic; }
        .box { border: 1px solid #b9c6d3; border-radius: 5px; padding: 6px 8px; margin-top: 10px; }
        .signatures { margin-top: 28px; page-break-inside: avoid; }
        .signatures td { width: 50%; text-align: center; padding: 0 30px; }
        .signatures .line { border-top: 1px solid #1b2733; padding-top: 3px; }
        .signatures .role { font-size: 7.4pt; color: #4a5866; }
        .legend { margin-top: 8px; font-size: 7pt; color: #4a5866; }
    </style>
</head>
<body>
@foreach ($cards as $card)
    @php
        $student = $card->enrollment->student;
        $section = $card->enrollment->section;
        $report = $card->report();
        $attendance = $card->attendance();
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
                        {{ $card->isFinal() ? 'Informe final' : 'Informe descriptivo' }}
                        <b>{{ $card->isFinal() ? 'Año '.$card->period->schoolYear->year : $card->period->name() }}</b>
                        Preescolar · {{ $card->period->schoolYear->year }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="student">
            <tr>
                <td style="width: 40%"><span class="label">Estudiante</span><b>{{ $student->last_names }}, {{ $student->first_names }}</b></td>
                <td style="width: 20%"><span class="label">Documento</span>{{ $student->documentLabel() }}</td>
                <td style="width: 15%"><span class="label">Grado</span>{{ $section->label() }}</td>
                <td style="width: 25%"><span class="label">Docente</span>{{ $section->homeroomTeacher?->name ?? '—' }}</td>
            </tr>
        </table>

        @foreach ($card->dimensions() as $dimension)
            <div class="dimension">
                <h2>{{ $dimension['name'] }}</h2>
                @if ($dimension['text'])
                    <p>{{ $dimension['text'] }}</p>
                @else
                    <p class="pending">Sin descripción en este periodo.</p>
                @endif
            </div>
        @endforeach

        @if ($report?->observations)
            <div class="box"><span class="label">Observaciones</span>{{ $report->observations }}</div>
        @endif

        <div class="box">
            <span class="label">Asistencia del periodo</span>
            {{ $attendance['absent'] }} {{ $attendance['absent'] === 1 ? 'día con falta' : 'días con falta' }} ·
            {{ $attendance['late'] }} {{ $attendance['late'] === 1 ? 'llegada tarde' : 'llegadas tarde' }} ·
            {{ $attendance['excused'] }} {{ $attendance['excused'] === 1 ? 'excusa' : 'excusas' }}
        </div>

        <div class="legend">En preescolar la evaluación es cualitativa y descriptiva (Decreto 2247 de 1997): no se asignan notas numéricas.</div>

        <table class="signatures">
            <tr>
                <td>
                    <div class="line">@if ($section->homeroomTeacher){{ $section->homeroomTeacher->name }}@else&nbsp;@endif</div>
                    <div class="role">Docente</div>
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
