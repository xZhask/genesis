@php
    $tabs = [
        'admin.people.students.index' => ['Estudiantes', 'admin.people.students.*'],
        'admin.people.guardians.index' => ['Acudientes', 'admin.people.guardians.*'],
        'admin.people.staff.index' => ['Docentes y administración', 'admin.people.staff.*'],
        'admin.people.import.create' => ['Importar', 'admin.people.import.*'],
    ];
@endphp

<nav class="tabs" aria-label="Secciones de Personas">
    @foreach ($tabs as $route => [$label, $pattern])
        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

@error('account')
    <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
@enderror

@if (session('credentials'))
    @include('admin.people.partials.slips', ['slips' => session('credentials')])
@endif
