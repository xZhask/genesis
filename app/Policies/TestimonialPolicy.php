<?php

namespace App\Policies;

/** Los testimonios solo los gestiona el admin. */
class TestimonialPolicy extends AdminOnlyPolicy {}
