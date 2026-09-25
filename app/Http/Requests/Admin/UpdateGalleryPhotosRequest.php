<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guarda las descripciones de todas las fotos del álbum de una vez y,
 * opcionalmente, aplica una acción sobre una foto (botón pulsado):
 * "up:ID", "down:ID", "cover:ID" o "delete:ID". Así no se pierde lo
 * escrito en otras fotos al mover o eliminar una.
 */
class UpdateGalleryPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('album'));
    }

    public function rules(): array
    {
        return [
            'photos' => ['array'],
            'photos.*.alt' => ['nullable', 'string', 'max:200'],
            'photos.*.caption' => ['nullable', 'string', 'max:200'],
            'do' => ['nullable', 'regex:/^(up|down|cover|delete):\d+$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'photos.*.alt' => 'descripción',
            'photos.*.caption' => 'pie de foto',
        ];
    }

    /** @return array{0: string, 1: int}|null */
    public function action(): ?array
    {
        if (! $this->validated('do')) {
            return null;
        }

        [$action, $id] = explode(':', $this->validated('do'));

        return [$action, (int) $id];
    }
}
