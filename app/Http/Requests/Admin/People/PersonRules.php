<?php

namespace App\Http\Requests\Admin\People;

use App\Enums\DocumentType;
use Illuminate\Validation\Rule;

/** Reglas comunes de nombre y documento para estudiantes y acudientes. */
trait PersonRules
{
    protected function normalizeDocument(string $key = 'document_number'): void
    {
        if ($this->filled($key)) {
            $this->merge([$key => DocumentType::normalize($this->input($key))]);
        }
    }

    protected function personRules(string $table, ?int $ignoreId): array
    {
        return [
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{3,20}$/', Rule::unique($table)->ignore($ignoreId)],
            'first_names' => ['required', 'string', 'max:80'],
            'last_names' => ['required', 'string', 'max:80'],
        ];
    }

    protected function personAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'first_names' => 'nombres',
            'last_names' => 'apellidos',
            'birth_date' => 'fecha de nacimiento',
            'phone' => 'teléfono',
            'email' => 'correo',
        ];
    }

    protected function personMessages(): array
    {
        return [
            'document_number.regex' => 'Escribe solo letras y números (por ejemplo, 1045678901).',
            'document_number.unique' => 'Ya hay una persona registrada con ese documento.',
        ];
    }
}
