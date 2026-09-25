<x-layouts.portal title="Mis acudidos">
    <div class="admin-head">
        <h1>Hola, {{ Str::before(auth()->user()->name, ' ') }}</h1>
        <p class="lead-sm">Elige a quién quieres consultar.</p>
    </div>

    @if ($overviews->isEmpty())
        <p class="empty panel">Tu cuenta todavía no tiene estudiantes vinculados. Comunícate con el colegio:
            <a href="tel:{{ config('school.contact.phone_link') }}">{{ config('school.contact.phone') }}</a>.</p>
    @else
        <ul class="class-grid">
            @foreach ($overviews as $overview)
                @php($enrollment = $overview->enrollment())
                @php($totals = $overview->totals())
                <li class="class-card">
                    <p class="class-section">{{ $enrollment ? $enrollment->section->label() : 'Sin matrícula en '.($year?->year ?? 'el año actual') }}</p>
                    <h2>{{ $overview->student->fullName() }}</h2>
                    <p class="class-status">{{ trans_choice(':count falta en el año|:count faltas en el año', $totals['absent']) }}@if ($totals['late']) · {{ trans_choice(':count llegada tarde|:count llegadas tarde', $totals['late']) }}@endif</p>
                    <a class="btn btn-azul btn-sm" href="{{ route('portal.guardian.student', $overview->student) }}">Ver información</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.portal>
