<?php

namespace App\Policies;

/** Solo el admin revisa las solicitudes de cambio de datos de contacto. */
class ContactUpdateRequestPolicy extends AdminOnlyPolicy {}
