<?php

use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Autenticación (Fortify solo como backend; las vistas son Blade propias)
|--------------------------------------------------------------------------
|
| Las cuentas las crea el colegio: no hay registro público. Solo se activan
| el inicio de sesión y la recuperación de contraseña por correo.
| La redirección después de ingresar depende del rol (App\Http\Responses\LoginResponse).
|
*/

return [

    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    'home' => '/admin',

    'prefix' => '',

    'domain' => null,

    'middleware' => ['web'],

    // Direcciones en español, visibles para los acudientes.
    // Fortify las lee con notación de puntos: "password.reset" va anidado.
    'paths' => [
        'login' => '/ingresar',
        'logout' => '/salir',
        'password' => [
            'request' => '/recuperar-contrasena',
            'email' => '/recuperar-contrasena',
            'reset' => '/restablecer-contrasena/{token}',
            'update' => '/restablecer-contrasena',
            'confirm' => '/confirmar-contrasena',
        ],
    ],

    'limiters' => [
        'login' => 'login',
    ],

    'views' => true,

    'features' => [
        Features::resetPasswords(),
    ],

];
