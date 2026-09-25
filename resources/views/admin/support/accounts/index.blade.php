<x-layouts.admin title="Cuentas para donar">
    <div class="admin-head row">
        <h1>Apóyanos</h1>
        <a class="btn btn-sol" href="{{ route('admin.accounts.create') }}">+ Nueva cuenta</a>
    </div>

    @include('admin.support.partials.tabs')

    <p class="lead-sm">Aparecen en la página Apóyanos y en el inicio, con un botón para copiar el número.</p>

    @if ($accounts->isEmpty())
        <p class="empty panel">No hay cuentas. Mientras tanto, la web invita a escribir al colegio para donar.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Cuenta</th>
                        <th scope="col">Número</th>
                        <th scope="col">Titular</th>
                        <th scope="col">En la web</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr>
                            <td data-label="Cuenta"><a class="strong" href="{{ route('admin.accounts.edit', $account) }}">{{ $account->label }}</a></td>
                            <td data-label="Número" class="nowrap">{{ $account->number }}</td>
                            <td data-label="Titular">{{ $account->holderLine() ?? '—' }}</td>
                            <td data-label="En la web">@include('admin.support.partials.visible', ['visible' => $account->is_visible])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
