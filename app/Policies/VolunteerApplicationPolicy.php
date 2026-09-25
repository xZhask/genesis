<?php

namespace App\Policies;

/** Las solicitudes de voluntariado (datos personales) solo las ve el admin. */
class VolunteerApplicationPolicy extends AdminOnlyPolicy {}
