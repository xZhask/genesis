@use('App\Enums\PostStatus')

<x-layouts.admin title="Noticias">
    <div class="admin-head row">
        <h1>Noticias</h1>
        <a class="btn btn-sol" href="{{ route('admin.posts.create') }}">+ Nueva noticia</a>
    </div>

    <nav class="tabs" aria-label="Filtrar por estado">
        <a href="{{ route('admin.posts.index') }}" @if (! $status) aria-current="page" @endif>Todas</a>
        @foreach (PostStatus::cases() as $case)
            <a href="{{ route('admin.posts.index', ['estado' => $case->value]) }}" @if ($status === $case) aria-current="page" @endif>{{ $case === PostStatus::Draft ? 'Borradores' : 'Publicadas' }}</a>
        @endforeach
    </nav>

    @if ($posts->isEmpty())
        <p class="empty panel">Todavía no hay noticias. <a href="{{ route('admin.posts.create') }}">Escribe la primera</a>.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Título</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Publicación</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td data-label="Título"><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a></td>
                            <td data-label="Estado">
                                @if ($post->status === PostStatus::Published && $post->published_at?->isFuture())
                                    <span class="badge badge-interview_scheduled">Programada</span>
                                @else
                                    <span class="badge badge-{{ $post->status === PostStatus::Published ? 'accepted' : 'withdrawn' }}">{{ $post->status->label() }}</span>
                                @endif
                            </td>
                            <td data-label="Publicación" class="nowrap">{{ $post->published_at?->longDate() ?? '—' }}</td>
                            <td class="row-actions">
                                <a href="{{ route('news.show', $post) }}" target="_blank" rel="noopener">{{ $post->isVisible() ? 'Ver' : 'Vista previa' }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $posts->links('partials.pagination') }}
    @endif
</x-layouts.admin>
