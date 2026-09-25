<?php

namespace App\Policies;

/** Acudientes: solo el admin los gestiona (datos personales de contacto). */
class GuardianPolicy extends AdminOnlyPolicy {}
