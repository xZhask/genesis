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

];
