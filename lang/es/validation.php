<?php

// Mensajes generales. Los formularios públicos definen mensajes propios en su Form Request.
return [
    'accepted' => 'Debes aceptar :attribute.',
    'after' => ':Attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute debe ser una fecha igual o posterior a :date.',
    'before' => ':Attribute debe ser una fecha anterior a :date.',
    'boolean' => ':Attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':Attribute no es una fecha válida.',
    'email' => ':Attribute debe ser un correo válido.',
    'enum' => 'El valor elegido en :attribute no es válido.',
    'exists' => 'El valor elegido en :attribute no es válido.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute puede tener máximo :max caracteres.',
    ],
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => ':Attribute debe tener al menos una letra.',
        'mixed' => ':Attribute debe tener mayúsculas y minúsculas.',
        'numbers' => ':Attribute debe tener al menos un número.',
        'symbols' => ':Attribute debe tener al menos un símbolo.',
        'uncompromised' => 'Esta contraseña apareció en una filtración de datos. Elige otra.',
    ],
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Escribe :attribute.',
    'required_if' => 'Escribe :attribute.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',

    'attributes' => [
        'email' => 'correo',
        'password' => 'contraseña',
        'name' => 'nombre',
        'status' => 'estado',
        'note' => 'nota',
        'interview_at' => 'fecha de la entrevista',
    ],
];
