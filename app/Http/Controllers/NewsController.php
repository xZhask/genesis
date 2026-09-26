<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('news.index', [
            'posts' => Post::published()->latest('published_at')->paginate(9),
            'events' => Event::publicWeb()->upcoming()->limit(4)->get(),
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        // Borradores y programadas: 404 para el público; el admin puede previsualizarlas
        $preview = ! $post->isVisible();
        abort_if($preview && ! $request->user()?->isAdmin(), 404);

        return view('news.show', [
            'post' => $post,
            'preview' => $preview,
            'others' => Post::published()->whereKeyNot($post->id)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
