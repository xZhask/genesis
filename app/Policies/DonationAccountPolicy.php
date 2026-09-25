<?php

namespace App\Policies;

/** Las cuentas para donar solo las gestiona el admin. */
class DonationAccountPolicy extends AdminOnlyPolicy {}
