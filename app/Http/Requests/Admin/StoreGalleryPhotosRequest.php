<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Subida de fotos a un álbum. Con JavaScript llega una foto por petición
 * (evita el límite de tamaño por envío del hosting); sin él, varias juntas.
 */
class StoreGalleryPhotosRequest extends FormRequest
{
    /** Tamaño máximo por foto, en KB. */
    public const MAX_KB = 10240;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('album'));
    }

    public function rules(): array
    {
        return [
            // PHP recibe como máximo 20 archivos por envío (max_file_uploads)
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB],
            // Ley 1581: fotos de menores solo con autorización escrita de los acudientes
            'minors_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Elige al menos una foto.',
            'photos.max' => 'Sube como máximo 20 fotos a la vez.',
            'photos.*.uploaded' => 'La foto no se pudo subir. Puede que pese demasiado para el servidor.',
            'photos.*.image' => 'Solo se aceptan fotos JPG, PNG o WebP.',
            'photos.*.mimes' => 'Solo se aceptan fotos JPG, PNG o WebP.',
            'photos.*.max' => 'La foto pesa más de 10 MB. Usa una más liviana.',
            'minors_consent.accepted' => 'Confirma que tienes la autorización de los acudientes para publicar las fotos.',
        ];
    }
}
