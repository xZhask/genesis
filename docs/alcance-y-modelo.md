# Alcance, roles y modelo de datos

Basado en la solicitud original del colegio, con los ajustes acordados. Cuando la solicitud original y este documento difieran, **manda este documento**.

## Ajustes acordados respecto de la solicitud original

| El colegio pidió | Se implementa así | Motivo |
|---|---|---|
| Carrusel en el inicio | Hero fijo + galería en cuadrícula con visor | Casi nadie ve más allá de la primera diapositiva de un carrusel |
| Página de organigrama aparte | Sección "Nuestro equipo" dentro de Nosotros | Es más cercano y útil para los acudientes |
| Donadores, cuenta bancaria y voluntarios por separado | Una sola página: **Apóyanos** | Un solo llamado a colaborar |
| Notificaciones del calendario | Botón "Agregar a mi calendario" (.ics / Google) + correo en fase 3 | Las notificaciones push web no son confiables en celulares |
| Formulario de matrícula | **Solicitud de pre-inscripción** con estados | El colegio conserva el control de la matrícula real |
| Solo dos tipos de usuario (docente y estudiante) | Cuatro roles: admin, docente, estudiante y acudiente | Los niños pequeños no inician sesión; quien entra es el acudiente |
| Portal para padres público | Recursos generales públicos + información personal dentro del portal del acudiente | Protección de datos de menores |

## Roles

| Rol (`users.role`) | Qué hace |
|---|---|
| `admin` | Gestiona el contenido web, los usuarios, la estructura académica, los periodos (abrir/cerrar), las asignaciones docentes y la pre-inscripción |
| `teacher` | Registra asistencia, notas y objetivos **solo** de sus asignaciones; genera boletines; ve alertas y gráficos de sus grupos |
| `student` | Consulta su perfil, asistencia y notas (desde primaria en adelante, si el colegio lo decide) |
| `guardian` (acudiente) | Ve la información de todos sus acudidos, descarga boletines, recursos y horarios, y solicita la actualización de sus datos de contacto |

Un acudiente puede tener varios estudiantes, y un estudiante puede tener varios acudientes.

## Web pública (fase 1)

- **Inicio:** hero, accesos rápidos, niveles, noticias y próximos eventos, galería, Apóyanos, llamado a admisiones y footer (horarios, contacto, mapa y Facebook).
- **Nosotros:** quiénes somos, misión, visión, valores, principios, propuesta educativa, fundadores y "Nuestro equipo".
- **Niveles:** una sección por nivel con sus grados.
- **Admisiones:** pasos del proceso, costos (editables, con año lectivo), requisitos y formulario de pre-inscripción.
- **Noticias:** listado y detalle (foto, fecha, resumen y cuerpo).
- **Calendario escolar:** vista de lista y mes, con "Agregar a mi calendario".
- **Recursos para acudientes (públicos):** uniformes y proveedores, listas de útiles, horarios generales y circulares públicas.
- **Apóyanos:** voluntariado (fotos, testimonios y formulario), donantes y datos bancarios con botón de copiar.
- **Política de tratamiento de datos** (Ley 1581 de 2012).

### Estados de la pre-inscripción
`received` (recibida) → `in_review` (en revisión) → `interview_scheduled` (entrevista agendada) → `accepted` (aceptada) / `rejected` (no admitida) / `withdrawn` (retirada).

## Portal académico (fase 2)

### Docente
- Selector de asignatura, grado/sección y fecha. Se recuerda la última selección.
- Asistencia del día: presente, ausente, tarde o excusa. Un botón guarda todo el grupo.
- Notas por periodo y asignatura, con promedio automático por periodo y promedio acumulado.
- Objetivos del periodo por asignatura (aparecen en el boletín).
- Boletín en PDF por estudiante y por grupo: logo, datos del estudiante, notas, desempeños, objetivos, promedios, asistencia y observaciones.
- **Fase 3:** alertas de cumpleaños, bajo rendimiento e inasistencias acumuladas; gráficos de asistencia del mes, aprobados/reprobados y bajo rendimiento.

### Estudiante / acudiente
- Perfil: datos básicos, grado actual y asignaturas.
- Asistencia: historial visual y total de faltas.
- Notas por periodo y asignatura, más la descarga del boletín cuando el periodo esté cerrado.
- Solicitud de actualización de teléfono y correo del acudiente. Queda pendiente hasta que el admin la apruebe; no se sobrescribe directo.

### Evaluación (Colombia)
- Básica primaria y secundaria: escala numérica **configurable** (mínimo, máximo, nota aprobatoria y decimales) y su equivalencia con la escala nacional del Decreto 1290 de 2009: **Superior, Alto, Básico y Bajo**. Los rangos se configuran según el SIEE del colegio; ver pendientes.
- **Preescolar:** evaluación **cualitativa y descriptiva**, sin notas numéricas. Su boletín usa descripciones por dimensión del desarrollo, no promedios.
- Número de periodos por año: configurable.

## Modelo de datos (borrador; confirmar antes de migrar)

```
school_years        id, year, starts_on, ends_on, is_current
periods             id, school_year_id, number, name, starts_on, ends_on, weight, status [open|closed], closed_by, closed_at
levels              id, name [Preescolar|Básica primaria|Básica secundaria], color, evaluation_type [qualitative|numeric], order
grades              id, level_id, name [Párvulos … 9.°], order
sections            id, grade_id, school_year_id, name [A|B|única], homeroom_teacher_id
subjects            id, name, area
grade_subject       grade_id, subject_id, weekly_hours
teacher_assignments id, teacher_id(users), section_id, subject_id, school_year_id
students            id, user_id (nullable), document_type, document_number, first_names, last_names, birth_date, photo, status
enrollments         id, student_id, section_id, school_year_id, status
guardians           id, user_id, document_number, full_name, phone, email, relationship
guardian_student    guardian_id, student_id, is_primary
attendances         id, enrollment_id, subject_id (nullable = día completo), date, status [present|absent|late|excused], recorded_by
grade_items         id, teacher_assignment_id, period_id, name, weight   (actividades evaluadas)
scores              id, grade_item_id, enrollment_id, value, comment
period_results      id, enrollment_id, subject_id, period_id, average, performance [superior|alto|basico|bajo], descriptive_text
period_objectives   id, teacher_assignment_id, period_id, text
grading_scale       id, school_year_id, min, max, passing, decimals + ranges per performance
contact_update_requests id, guardian_id, field, old_value, new_value, status, reviewed_by

posts, events, gallery_albums, gallery_photos, resources, donors, volunteers_testimonials,
admission_requests, settings (costos, datos bancarios, horarios, redes)
```

El promedio del periodo se calcula en el servidor y se guarda en `period_results` al cerrar el periodo. Mientras el periodo está abierto se muestra calculado en vivo.
