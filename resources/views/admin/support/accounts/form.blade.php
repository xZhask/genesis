@php
    $editing = $account->exists;
    $title = $editing ? 'Editar cuenta' : 'Nueva cuenta';
@endphp

<x-layouts.admin :title="$title">
    <p class="back"><a href="{{ route('admin.accounts.index') }}">← Cuentas para donar</a></p>
    <div class="admin-head">
        <h1>{{ $title }}</h1>
    </div>

    <form class="form admin-form narrow-form" method="POST" novalidate data-form
        action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="panel">
            <p class="notice">Revisa el número dos veces: las familias lo copian tal cual en su app del banco.</p>

            <x-field name="label" label="Banco y tipo de cuenta" hint="Ejemplo: «Bancolombia · Cuenta de ahorros» o «Nequi».">
                <input id="label" name="label" type="text" maxlength="120" value="{{ old('label', $account->label) }}">
            </x-field>

            <x-field name="number" label="Número" hint="Se copia tal como lo escribas.">
                <input id="number" name="number" type="text" inputmode="numeric" maxlength="60" value="{{ old('number', $account->number) }}">
            </x-field>

            <div class="field-group">
                <x-field name="holder" label="Titular" optional>
                    <input id="holder" name="holder" type="text" maxlength="120" value="{{ old('holder', $account->holder) }}">
                </x-field>
                <x-field name="holder_document" label="NIT o documento" optional>
                    <input id="holder_document" name="holder_document" type="text" maxlength="40" value="{{ old('holder_document', $account->holder_document) }}">
                </x-field>
            </div>

            @include('admin.support.partials.listing', ['model' => $account])

            <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
        </section>
    </form>

    @if ($editing)
        <form class="danger-zone" method="POST" action="{{ route('admin.accounts.destroy', $account) }}" data-confirm="¿Eliminar la cuenta «{{ $account->label }}»?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar cuenta</button>
        </form>
    @endif
</x-layouts.admin>
