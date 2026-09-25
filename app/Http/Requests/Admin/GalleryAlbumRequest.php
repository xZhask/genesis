<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Models\GalleryAlbum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $album = $this->route('album');

        return $album
            ? $this->user()->can('update', $album)
            : $this->user()->can('create', GalleryAlbum::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'taken_on' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'nombre del álbum',
            'description' => 'descripción',
            'taken_on' => 'fecha de la actividad',
        ];
    }

    public function messages(): array
    {
        return [
            'taken_on.before_or_equal' => 'La fecha de la actividad no puede ser futura.',
        ];
    }
}
