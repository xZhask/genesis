<x-layouts.public title="Galería" description="Fotos de las actividades del Centro Educativo Cristiano Génesis: celebraciones, salidas pedagógicas y vida escolar.">
    <x-page-head title="Galería" lead="Celebraciones, salidas pedagógicas y el día a día de nuestra comunidad educativa." />

    <section class="gallery-page">
        <div class="wrap">
            @if ($albums->isEmpty())
                <p class="cal-empty">Pronto publicaremos las primeras fotos del colegio.</p>
            @else
                <div class="albums">
                    @foreach ($albums as $album)
                        <x-album-card :album="$album" />
                    @endforeach
                </div>
                {{ $albums->links('partials.pagination') }}
            @endif
        </div>
    </section>
</x-layouts.public>
