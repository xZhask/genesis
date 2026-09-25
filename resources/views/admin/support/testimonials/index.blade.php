<x-layouts.admin title="Testimonios">
    <div class="admin-head row">
        <h1>Apóyanos</h1>
        <a class="btn btn-sol" href="{{ route('admin.testimonials.create') }}">+ Nuevo testimonio</a>
    </div>

    @include('admin.support.partials.tabs')

    <p class="lead-sm">Voces de voluntarios y aliados. En la página se muestran hasta 3; en el inicio, el primero.</p>

    @if ($testimonials->isEmpty())
        <p class="empty panel">No hay testimonios. Mientras tanto, la web muestra una invitación a ser voluntario.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Testimonio</th>
                        <th scope="col">De</th>
                        <th scope="col">En la web</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testimonials as $testimonial)
                        <tr>
                            <td data-label="Testimonio"><a href="{{ route('admin.testimonials.edit', $testimonial) }}">“{{ Str::limit($testimonial->quote, 90) }}”</a></td>
                            <td data-label="De">{{ $testimonial->signature() }}</td>
                            <td data-label="En la web">@include('admin.support.partials.visible', ['visible' => $testimonial->is_visible])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
