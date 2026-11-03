@php
    $tabs = [
        'admin.people.students.index' => ['Estudiantes', 'admin.people.students.*'],
        'admin.people.guardians.index' => ['Acudientes', 'admin.people.guardians.*'],
        'admin.people.staff.index' => ['Docentes y administración', 'admin.people.staff.*'],
        'admin.people.birthdays' => ['Cumpleaños', 'admin.people.birthdays'],
        'admin.people.contact-requests.index' => ['Cambios de contacto', 'admin.people.contact-requests.*'],
        'admin.people.import.create' => ['Importar', 'admin.people.import.*'],
    ];
    $pendingContacts = App\Models\ContactUpdateRequest::pending()->count();
@endphp

<nav class="tabs" aria-label="Secciones de Personas">
    @foreach ($tabs as $route => [$label, $pattern])
        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}@if ($route === 'admin.people.contact-requests.index' && $pendingContacts) <span class="nav-count">{{ $pendingContacts }}</span>@endif</a>
    @endforeach
</nav>

<p class="people-export">
    <a class="btn btn-line btn-sm" href="{{ route('admin.people.export') }}">Descargar Excel con todas las personas</a>
    <span class="hint">Tiene datos de menores: guárdalo con cuidado y no lo compartas.</span>
</p>

@error('account')
    <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
@enderror

@if (session('credentials'))
    @include('admin.people.partials.slips', ['slips' => session('credentials')])
@endif
