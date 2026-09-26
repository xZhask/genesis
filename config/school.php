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
            'slug' => 'preescolar',
            'name' => 'Preescolar',
            'short' => 'Preescolar',
            'icon' => 'blocks',
            'tone' => 't-verde',
            'tag' => 'Primera infancia',
            'grades' => ['Párvulos', 'Prejardín', 'Jardín', 'Transición'],
            'topics' => ['Párvulos', 'Prejardín', 'Jardín', 'Transición'],
            'summary' => 'Aprenden jugando, explorando y creando, en un ambiente seguro y afectuoso.',
        ],
        [
            'key' => 'primary',
            'slug' => 'primaria',
            'name' => 'Básica primaria',
            'short' => 'Primaria',
            'icon' => 'book',
            'tone' => 't-sol',
            'tag' => '1.° a 5.°',
            'grades' => ['1.°', '2.°', '3.°', '4.°', '5.°'],
            'topics' => ['Lectura y escritura', 'Matemáticas', 'Inglés'],
            'summary' => 'Bases sólidas en lectura, pensamiento lógico e inglés, con proyectos que despiertan la curiosidad por la ciencia y el entorno.',
        ],
        [
            'key' => 'secondary',
            'slug' => 'secundaria',
            'name' => 'Básica secundaria',
            'short' => 'Secundaria',
            'icon' => 'cap',
            'tone' => 't-azul',
            'tag' => '6.° a 9.°',
            'grades' => ['6.°', '7.°', '8.°', '9.°'],
            'topics' => ['Ciencias', 'Tecnología', 'Inglés'],
            'summary' => 'Formación académica exigente, proyecto de vida y liderazgo, para que cada joven avance con propósito y valores firmes.',
        ],
    ],

    /*
    | Apóyanos. Las cuentas, los donantes y los testimonios se gestionan en
    | /admin/apoyanos; lo que esté vacío no se muestra. Pendiente: si el
    | colegio mantiene la sección y sus datos bancarios reales.
    */
    'support' => [
        // Correo que recibe los avisos de nuevos voluntarios (provisional)
        'notify_email' => 'cecgenesis16@gmail.com',
        'volunteer_photos' => [], // rutas dentro de public/ (fotos del voluntariado en el inicio)
    ],

    /*
    | Admisiones. Valores provisionales registrados en docs/pendientes.md.
    | Más adelante los costos y requisitos serán editables desde el panel admin.
    */
    'admissions' => [
        'school_year' => 2027,
        'notify_email' => 'cecgenesis16@gmail.com',
        'response_time' => '3 días hábiles',
        'costs' => [
            [
                'label' => 'Preescolar y primaria',
                'levels' => ['preschool', 'primary'],
                'enrollment' => 111000,
                'monthly' => 99900,
            ],
            [
                'label' => 'Secundaria',
                'levels' => ['secondary'],
                'enrollment' => 299700,
                'monthly' => 244200,
            ],
        ],
        'requirements' => [
            'Registro civil de nacimiento del estudiante (y tarjeta de identidad desde los 7 años).',
            'Copia del documento de identidad del acudiente.',
            'Certificados de estudio o boletines de los años cursados (desde 1.°).',
            'Paz y salvo del colegio anterior, si aplica.',
            'Certificado de afiliación a la EPS.',
            'Carné de vacunación (preescolar).',
            'Dos fotos tamaño documento.',
        ],
    ],

    /*
    | Portal académico (fase 2). Confirmado el 25/09/2026: escala de 1 a 5,
    | 4 periodos, cuentas de estudiante desde 6.° e ingreso con documento.
    | Pesos de los periodos y rangos de desempeño: provisionales (pendientes.md).
    */
    'academic' => [
        'periods' => 4,
        'student_accounts_from' => '6.°',

        // Escala con la que se crea cada año lectivo (luego se edita en Admin → Académico).
        // Rangos deducidos del boletín actual; frase de Bajo y pesos: provisionales.
        'grading' => [
            'min_score' => 1.0,
            'max_score' => 5.0,
            'passing_score' => 3.0,
            'decimals' => 2,
            'basic_from' => 3.0,
            'high_from' => 4.2,
            'superior_from' => 4.8,
            'phrases' => [
                'low' => 'Estoy en proceso de',
                'basic' => 'Soy capaz de',
                'high' => 'Tengo muy buenas habilidades para',
                'superior' => 'Demuestro habilidades superiores para',
            ],
            'weights' => ['knowing' => 33.33, 'doing' => 33.33, 'being' => 33.34],
        ],
    ],

    // Encabezado y firmas del boletín (confirmados el 25/09/2026; editables en Admin → Configuración).
    // No se muestran en la web pública.
    'report_card' => [
        'approval' => 'Aprobado mediante resolución No. 3332 del 6 de diciembre de 2024',
        'nit' => '1102821784-1',
        'campus' => 'Principal',
        // Vacío: la firma dice solo «Rectoría»
        'rector' => '',
    ],

    // Fase 3: alertas del portal docente y del admin (provisionales, ver docs/pendientes.md)
    'alerts' => [
        // Faltas sin excusa en una materia dentro del periodo (editable en Admin → Configuración)
        'absences' => 3,
        // Bajo rendimiento solo con al menos estas actividades calificadas en el periodo
        'min_graded_items' => 2,
        // Cumpleaños: días hacia adelante, contando hoy
        'birthday_days' => 7,
    ],

    // Correos a las familias (fase 3). Gmail permite unos 500 destinatarios al
    // día: lo que pase del tope sale al día siguiente.
    'family_mail' => [
        'daily_limit' => (int) env('FAMILY_MAIL_DAILY_LIMIT', 400),
        // Correos por minuto (el cron ejecuta el envío cada minuto)
        'per_minute' => 20,
        // Recordatorio de eventos: días antes
        'reminder_days' => 2,
    ],

    // Horario de clases, fijo para el año (provisional: lunes a viernes, una jornada)
    'schedule' => [
        // Días con clase (ISO: 1 = lunes … 6 = sábado)
        'days' => [1, 2, 3, 4, 5],
    ],

    // Versión de la política aceptada en los formularios (se guarda con cada solicitud).
    'privacy_policy_version' => '2026-09-borrador',

    // Contenido de ejemplo para revisar el diseño en local. Nunca en producción.
    'demo_content' => env('DEMO_CONTENT', false),

];
