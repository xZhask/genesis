@php
    $tabs = [
        'admin.academic.years.index' => ['Años y periodos', 'admin.academic.years.*'],
        'admin.academic.sections.index' => ['Grados y secciones', 'admin.academic.sections.*'],
        'admin.academic.subjects.index' => ['Áreas y materias', 'admin.academic.subjects.*'],
        'admin.academic.curriculum.index' => ['Plan de estudios', 'admin.academic.curriculum.*'],
        'admin.academic.assignments.index' => ['Asignaciones docentes', 'admin.academic.assignments.*'],
        'admin.academic.schedule.index' => ['Horarios', 'admin.academic.schedule.*'],
        'admin.academic.alerts' => ['Alertas', 'admin.academic.alerts'],
        'admin.academic.indicators' => ['Indicadores', 'admin.academic.indicators'],
    ];
    // Errores de acciones sin formulario propio (eliminar, validar periodos)
    $general = collect(['subject', 'area', 'periods', 'section'])->map(fn ($key) => $errors->first($key))->filter();
@endphp

<nav class="tabs" aria-label="Secciones de Académico">
    @foreach ($tabs as $route => [$label, $pattern])
        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

@if ($general->isNotEmpty())
    <div class="error-summary" role="alert">
        <ul>
            @foreach ($general as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
