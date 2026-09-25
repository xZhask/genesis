<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post
            ? $this->user()->can('update', $post)
            : $this->user()->can('create', Post::class);
    }

    protected function prepareForValidation(): void
    {
        // Al publicar sin fecha, se publica ahora
        if ($this->input('status') === PostStatus::Published->value && ! $this->filled('published_at')) {
            $this->merge(['published_at' => now()->format('Y-m-d\TH:i')]);
        }
    }

    public function rules(): array
    {
        $post = $this->route('post');
        $hasCover = $this->hasFile('cover') || ($post?->cover_path && ! $this->boolean('remove_cover'));

        return [
            'title' => ['required', 'string', 'max:160'],
            'excerpt' => ['required', 'string', 'max:280'],
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'cover_alt' => [$hasCover ? 'required' : 'nullable', 'string', 'max:200'],
            // Ley 1581: fotos de menores solo con autorización escrita de los acudientes
            'minors_consent' => [$this->hasFile('cover') ? 'accepted' : 'nullable'],
            'remove_cover' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'excerpt' => 'resumen',
            'body' => 'texto de la noticia',
            'published_at' => 'fecha de publicación',
            'cover' => 'foto de portada',
            'cover_alt' => 'descripción de la foto',
        ];
    }

    public function messages(): array
    {
        return [
            'cover_alt.required' => 'Describe la foto en pocas palabras (la leen las personas con discapacidad visual).',
            'minors_consent.accepted' => 'Confirma que tienes la autorización de los acudientes para publicar la foto.',
            'cover.image' => 'La portada debe ser una imagen (JPG, PNG o WebP).',
            'cover.mimes' => 'La portada debe ser una imagen JPG, PNG o WebP.',
            'cover.max' => 'La foto pesa más de 8 MB. Usa una más liviana.',
        ];
    }
}
