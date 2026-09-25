<x-layouts.admin title="Nuevo álbum">
    <p class="back"><a href="{{ route('admin.albums.index') }}">← Galería</a></p>
    <div class="admin-head">
        <h1>Nuevo álbum</h1>
        <p class="lead-sm">Primero crea el álbum; en el siguiente paso subes las fotos.</p>
    </div>

    <form class="form admin-form narrow-form" method="POST" action="{{ route('admin.albums.store') }}" novalidate data-form>
        @csrf
        <section class="panel">
            @include('admin.gallery.partials.album-fields')
            <button type="submit" class="btn btn-azul" data-submit data-loading-text="Creando…"><span>Crear y subir fotos</span></button>
        </section>
    </form>
</x-layouts.admin>
