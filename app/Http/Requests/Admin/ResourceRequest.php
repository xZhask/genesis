<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Enums\ResourceVisibility;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('resource');

        return $resource
            ? $this->user()->can('update', $resource)
            : $this->user()->can('create', Resource::class);
    }

    protected function prepareForValidation(): void
    {
        // Las listas de útiles se titulan solas si se deja el título vacío
        if ($this->input('type') === ResourceType::Supplies->value && ! $this->filled('title') && $this->filled('grade')) {
            $this->merge(['title' => trim("Lista de útiles {$this->input('grade')} {$this->input('school_year')}")]);
        }
    }

    public function rules(): array
    {
        $type = ResourceType::tryFrom((string) $this->input('type'));
        $resource = $this->route('resource');
        $hasFile = $this->hasFile('file') || ($resource?->file_path && ! $this->boolean('remove_file'));
        $isUniform = $type === ResourceType::Uniform;
        $hasImage = $isUniform && ($this->hasFile('image') || ($resource?->image_path && ! $this->boolean('remove_image')));

        // Circulares y listas: PDF o texto. Uniformes y horarios: siempre texto.
        $bodyRequired = in_array($type, [ResourceType::Uniform, ResourceType::Schedule], true) || ! $hasFile;

        return [
            'type' => ['required', Rule::enum(ResourceType::class)],
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:280'],
            'body' => [$bodyRequired ? 'required' : 'nullable', 'string', 'max:20000'],
            // Tipo real del contenido, no solo la extensión
            'file' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:5120'],
            'remove_file' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'image_alt' => [$hasImage ? 'required' : 'nullable', 'string', 'max:200'],
            // Ley 1581: fotos de menores solo con autorización escrita de los acudientes
            'minors_consent' => [$isUniform && $this->hasFile('image') ? 'accepted' : 'nullable'],
            'remove_image' => ['boolean'],
            'grade' => [$type === ResourceType::Supplies ? 'required' : 'nullable', Rule::in(Resource::grades())],
            'school_year' => [$type === ResourceType::Supplies ? 'required' : 'nullable', 'integer', 'between:2020,2100'],
            'published_on' => ['required', 'date'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            // Solo circulares: la web pública o solo las familias (y a qué grados)
            'visibility' => [$type === ResourceType::Circular ? 'required' : 'nullable', Rule::enum(ResourceVisibility::class)],
            'audience' => ['nullable', 'array'],
            'audience.*' => [Rule::in(Resource::grades())],
            'position' => ['nullable', 'integer', 'between:0,999'],
        ];
    }

    /** Datos del modelo según el tipo (se descartan los campos que no aplican). */
    public function resourceData(): array
    {
        $data = $this->safe()->only(['type', 'title', 'summary', 'body', 'image_alt', 'grade', 'school_year', 'published_on', 'status', 'visibility']);
        $type = ResourceType::from($data['type']);

        // Útiles, uniformes y horarios siempre son públicos
        $families = $type === ResourceType::Circular && $data['visibility'] === ResourceVisibility::Families->value;
        $data['visibility'] = $families ? ResourceVisibility::Families : ResourceVisibility::Public;
        $data['audience'] = $families && $this->validated('audience') ? array_values($this->validated('audience')) : null;

        $data['position'] = (int) $this->validated('position');

        if ($type !== ResourceType::Supplies) {
            $data['grade'] = null;
        }
        if ($type !== ResourceType::Uniform) {
            $data['image_alt'] = null;
        }

        return $data;
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'summary' => 'resumen',
            'body' => 'texto',
            'file' => 'archivo PDF',
            'image' => 'foto',
            'image_alt' => 'descripción de la foto',
            'grade' => 'grado',
            'school_year' => 'año lectivo',
            'published_on' => 'fecha',
            'position' => 'orden',
            'visibility' => 'quién la ve',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => in_array($this->input('type'), ['circular', 'supplies'], true)
                ? 'Adjunta un PDF o escribe el texto.'
                : 'Escribe el texto.',
            'file.mimetypes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El PDF pesa más de 5 MB. Usa uno más liviano.',
            'file.uploaded' => 'El PDF no se pudo subir. Puede que pese demasiado para el servidor.',
            'grade.in' => 'Elige un grado del colegio.',
            'audience.*.in' => 'Elige grados del colegio.',
            'visibility.required' => 'Elige quién ve la circular.',
            'image_alt.required' => 'Describe la foto en pocas palabras (la leen las personas con discapacidad visual).',
            'minors_consent.accepted' => 'Confirma que tienes la autorización de los acudientes para publicar la foto.',
            'image.image' => 'La foto debe ser JPG, PNG o WebP.',
            'image.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'image.max' => 'La foto pesa más de 8 MB. Usa una más liviana.',
        ];
    }
}
