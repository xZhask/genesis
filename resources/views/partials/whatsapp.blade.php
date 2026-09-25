{{-- Oculto hasta confirmar que el número tiene WhatsApp (docs/pendientes.md) --}}
@if (config('school.whatsapp.enabled'))
    <a class="wa" href="https://wa.me/{{ config('school.whatsapp.number') }}" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
        <x-icon name="whatsapp" />
    </a>
@endif
