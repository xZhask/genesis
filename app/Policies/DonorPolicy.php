<?php

namespace App\Policies;

/** Los donantes y aliados solo los gestiona el admin. */
class DonorPolicy extends AdminOnlyPolicy {}
