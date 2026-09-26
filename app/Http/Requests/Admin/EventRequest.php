<?php

namespace App\Http\Requests\Admin;

use App\Enums\ResourceVisibility;
use App\Models\Event;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event
            ? $this->user()->can('update', $event)
            : $this->user()->can('create', Event::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['all_day' => $this->boolean('all_day'), 'send_reminder' => $this->boolean('send_reminder')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'date' => ['required', 'date'],
            'all_day' => ['boolean'],
            'send_reminder' => ['boolean'],
            'visibility' => ['required', Rule::enum(ResourceVisibility::class)],
            'audience' => ['nullable', 'array'],
            'audience.*' => [Rule::in(Resource::grades())],
            'start_time' => ['nullable', Rule::requiredIf(! $this->boolean('all_day')), 'date_format:H:i'],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                // Solo se compara con la hora de inicio si termina el mismo día
                Rule::when(! $this->filled('end_date') || $this->input('end_date') === $this->input('date'), 'after:start_time'),
            ],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'location' => ['nullable', 'string', 'max:160'],
            'level' => ['nullable', Rule::in(collect(config('school.levels'))->pluck('key'))],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'date' => 'fecha',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'end_date' => 'fecha de fin',
            'location' => 'lugar',
            'level' => 'nivel',
            'description' => 'descripción',
            'visibility' => 'quién lo ve',
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'Indica la hora de inicio o marca "Todo el día".',
            'end_time.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'visibility.required' => 'Elige quién ve el evento.',
            'audience.*.in' => 'Elige grados del colegio.',
        ];
    }

    /** Convierte fecha + horas del formulario en los campos del modelo. */
    public function eventData(): array
    {
        $allDay = $this->boolean('all_day');
        $date = $this->date('date');
        $endDate = $this->filled('end_date') ? $this->date('end_date') : null;

        $startsAt = $allDay ? $date->copy()->startOfDay() : Carbon::parse($date->toDateString().' '.$this->input('start_time'));

        $endTime = $allDay ? '00:00' : ($this->input('end_time') ?: $this->input('start_time'));

        $endsAt = match (true) {
            // Evento de varios días (receso, semana cultural…)
            $endDate && ! $endDate->isSameDay($date) => Carbon::parse($endDate->toDateString().' '.$endTime),
            ! $allDay && $this->filled('end_time') => Carbon::parse($date->toDateString().' '.$this->input('end_time')),
            default => null,
        };

        return [
            ...$this->safe()->only(['title', 'location', 'level', 'description']),
            'all_day' => $allDay,
            'send_reminder' => $this->boolean('send_reminder'),
            'visibility' => ResourceVisibility::from($this->validated('visibility')),
            // Los grados solo aplican a los eventos de solo familias
            'audience' => $this->validated('visibility') === ResourceVisibility::Families->value && $this->validated('audience')
                ? array_values($this->validated('audience'))
                : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
    }
}
