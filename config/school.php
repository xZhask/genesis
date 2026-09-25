<?php

/*
|--------------------------------------------------------------------------
| Datos del colegio
|--------------------------------------------------------------------------
|
| Fuente: docs/contenido-institucional.md. Lo que aparece vacío o en false
| depende de una decisión pendiente (docs/pendientes.md) y no se muestra en
| la web hasta que el colegio lo confirme. Más adelante, lo que el colegio
| deba editar (horarios, costos, datos bancarios) pasará al panel admin.
|
*/

$address = 'Carrera 18 #16-22, Barrio Buenos Aires';
$city = 'Zambrano, Bolívar';

return [

    'name' => 'Centro Educativo Cristiano Génesis',
    'short_name' => 'Génesis',
    'motto' => 'Comprometidos con Dios y la sociedad',
    'description' => 'Colegio cristiano privado en Zambrano, Bolívar. Preescolar, básica primaria y básica secundaria.',

    'contact' => [
        'address' => $address,
        'city' => $city,
        'phone' => '+57 321 797 5579',
        'phone_link' => '+573217975579',
        'email' => 'cecgenesis16@gmail.com',
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode("{$address}, {$city}, Colombia"),
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/cecgenesis',
    ],

    // Pendiente: confirmar que el número tiene WhatsApp.
    'whatsapp' => [
        'enabled' => false,
        'number' => '573217975579',
    ],

    // Pendiente: horarios reales. Lista vacía = no se muestran.
    // Ejemplo: ['Secretaría: lunes a viernes, 7:00 a. m. – 3:00 p. m.']
    'office_hours' => [],

    // Etiqueta del hero. null = no se muestra. Ejemplo: 'Matrículas 2027 abiertas'
    'admissions_badge' => null,

    // Cuatro fotos reales del colegio (rutas dentro de public/), con autorización
    // de los acudientes. Vacío = el escudo muestra los símbolos del logo.
    'hero_photos' => [],

    /*
    | Niveles. Los textos (tag, topics, summary) son borradores de UX
    | pendientes de revisión por el colegio.
    */
    'levels' => [
        [
            'key' => 'preschool',
            'name' => 'Preescolar',
            'icon' => 'blocks',
            'tone' => 't-verde',
            'tag' => 'Primera infancia',
            'topics' => ['Párvulos', 'Prejardín', 'Jardín', 'Transición'],
            'summary' => 'Aprenden jugando, explorando y creando, en un ambiente seguro y afectuoso.',
        ],
        [
            'key' => 'primary',
            'name' => 'Básica primaria',
            'icon' => 'book',
            'tone' => 't-sol',
            'tag' => '1.° a 5.°',
            'topics' => ['Lectura y escritura', 'Matemáticas', 'Inglés'],
            'summary' => 'Bases sólidas en lectura, pensamiento lógico e inglés, con proyectos que despiertan la curiosidad por la ciencia y el entorno.',
        ],
        [
            'key' => 'secondary',
            'name' => 'Básica secundaria',
            'icon' => 'cap',
            'tone' => 't-azul',
            'tag' => '6.° a 9.°',
            'topics' => ['Ciencias', 'Tecnología', 'Inglés'],
            'summary' => 'Formación académica exigente, proyecto de vida y liderazgo, para que cada joven avance con propósito y valores firmes.',
        ],
    ],

    /*
    | Apóyanos. Pendiente: si el colegio mantiene la sección y sus datos
    | bancarios reales. Lo que esté vacío no se muestra.
    */
    'support' => [
        'accounts' => [],        // [['label' => 'Bancolombia, cuenta de ahorros', 'value' => '…'], …]
        'testimonial' => null,   // ['quote' => '…', 'author' => 'Nombre, relación con el colegio']
        'volunteer_photos' => [], // rutas dentro de public/
        'donors' => [],          // nombres de donantes o aliados
    ],

    // Contenido de ejemplo para revisar el diseño en local. Nunca en producción.
    'demo_content' => env('DEMO_CONTENT', false),

];
