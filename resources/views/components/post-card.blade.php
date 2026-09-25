@props(['post', 'feature' => false])

<article @class(['post', 'feature' => $feature])>
    <x-photo :src="$post->coverUrl($feature ? 'lg' : 'sm')" :tone="$post->tone()" icon="image" :alt="$post->cover_alt ?? ''" />
    <div class="body">
        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->longDate() }}</time>
        <h3><a href="{{ $post->url() }}">{{ $post->title }}</a></h3>
        <p>{{ $post->excerpt }}</p>
        <span class="more" aria-hidden="true">Ver noticia</span>
    </div>
</article>
