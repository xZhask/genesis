<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Support\ImageResizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);

        $status = PostStatus::tryFrom((string) $request->query('estado'));

        return view('admin.posts.index', [
            'posts' => Post::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw('published_at is null desc')
                ->latest('published_at')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Post::class);

        return view('admin.posts.form', ['post' => new Post(['status' => PostStatus::Draft])]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = new Post($request->safe()->only(['title', 'excerpt', 'body', 'cover_alt', 'status', 'published_at']));
        $post->author_id = $request->user()->id;

        if ($request->hasFile('cover')) {
            $post->cover_path = ImageResizer::store($request->file('cover'), 'posts');
        }

        $post->save();

        return redirect()->route('admin.posts.index')->with('status_message', $this->savedMessage($post));
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->fill($request->safe()->only(['title', 'excerpt', 'body', 'cover_alt', 'status', 'published_at']));

        if ($request->hasFile('cover') || $request->boolean('remove_cover')) {
            ImageResizer::delete($post->cover_path);
            $post->cover_path = $request->hasFile('cover') ? ImageResizer::store($request->file('cover'), 'posts') : null;
            if (! $post->cover_path) {
                $post->cover_alt = null;
            }
        }

        $post->save();

        return redirect()->route('admin.posts.index')->with('status_message', $this->savedMessage($post));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('admin.posts.index')->with('status_message', "Se eliminó la noticia «{$post->title}».");
    }

    private function savedMessage(Post $post): string
    {
        return match (true) {
            $post->status === PostStatus::Draft => "Se guardó el borrador «{$post->title}».",
            $post->published_at->isFuture() => "«{$post->title}» se publicará el {$post->published_at->longDate()} a las {$post->published_at->shortTime()}.",
            default => "Se publicó «{$post->title}».",
        };
    }
}
