<x-layouts.public title="Circulares" description="Circulares del Centro Educativo Cristiano Génesis para las familias.">
    <x-page-head eyebrow="Acudientes" title="Circulares" lead="Todas las circulares publicadas, de la más reciente a la más antigua." />

    <section class="resources-page">
        <div class="wrap narrow">
            <p class="back"><a href="{{ route('resources') }}">← Recursos para acudientes</a></p>

            @if ($circulars->isEmpty())
                <p class="cal-empty">Todavía no hay circulares publicadas.</p>
            @else
                <ul class="circulars">
                    @foreach ($circulars as $circular)
                        <x-circular-item :circular="$circular" />
                    @endforeach
                </ul>
                {{ $circulars->links('partials.pagination') }}
            @endif
        </div>
    </section>
</x-layouts.public>
