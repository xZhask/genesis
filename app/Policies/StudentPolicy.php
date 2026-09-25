<?php

namespace App\Policies;

/**
 * Por ahora solo el admin gestiona estudiantes. En la capa 1c se agregan las
 * consultas del docente (sus secciones), del acudiente (sus acudidos) y del
 * propio estudiante.
 */
class StudentPolicy extends AdminOnlyPolicy {}
