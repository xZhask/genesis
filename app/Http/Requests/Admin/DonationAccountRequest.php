<?php

namespace App\Http\Requests\Admin;

use App\Models\DonationAccount;
use Illuminate\Foundation\Http\FormRequest;

class DonationAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account
            ? $this->user()->can('update', $account)
            : $this->user()->can('create', DonationAccount::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'number' => trim(preg_replace('/\s+/', ' ', (string) $this->input('number'))),
            'is_visible' => $this->boolean('is_visible'),
            'position' => (int) $this->input('position'),
        ]);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'number' => ['required', 'string', 'max:60', 'regex:/^[0-9 .\-]+$/'],
            'holder' => ['nullable', 'string', 'max:120'],
            'holder_document' => ['nullable', 'string', 'max:40'],
            'is_visible' => ['boolean'],
            'position' => ['integer', 'between:0,999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'banco y tipo de cuenta',
            'number' => 'número',
            'holder' => 'titular',
            'holder_document' => 'NIT o documento',
        ];
    }

    public function messages(): array
    {
        return [
            'number.regex' => 'El número solo puede tener dígitos, espacios, puntos o guiones.',
        ];
    }
}
