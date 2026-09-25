# Decisiones pendientes del colegio

**Regla para Claude Code:** si una tarea depende de un punto de esta lista, usa el valor provisional indicado o, si no hay, uno razonable; déjalo configurable y **anótalo en la tabla de abajo**. La web se construye como quedará terminada. Excepción: nunca se inventan datos que se presenten como hechos verificables (cifras, testimonios, cuentas bancarias, donantes); esos siguen ocultos o solo en el contenido de ejemplo (`DEMO_CONTENT`). Cuando el colegio decida, se marca aquí con ✅ y la fecha.

## Publicado con valores provisionales

Datos que ya se ven en la web y que el colegio debe confirmar antes de salir a producción.

| Dato | Valor provisional | Dónde se cambia |
|---|---|---|
| Textos del hero, niveles, pasos de admisión, títulos e introducciones de Nosotros y microcopy | Borradores de UX (las declaraciones formales son literales) | Vistas en `resources/views/home/`, `resources/views/admissions/` y `resources/views/pages/about.blade.php` |
| Año lectivo de la pre-inscripción y de los costos | 2027 | `config/school.php` → `admissions.school_year` |
| Costos | Los del folleto: preescolar y primaria $ 111.000 / $ 99.900; secundaria $ 299.700 / $ 244.200 | `config/school.php` → `admissions.costs` |
| Requisitos y documentos de matrícula | Lista habitual en Colombia (registro civil, documentos del acudiente, certificados, paz y salvo, EPS, vacunas, fotos) | `config/school.php` → `admissions.requirements` |
| Tiempo de respuesta a una solicitud | "3 días hábiles" | `config/school.php` → `admissions.response_time` |
| Correo que recibe los avisos de pre-inscripción | cecgenesis16@gmail.com | `config/school.php` → `admissions.notify_email` |
| Política de tratamiento de datos | Borrador basado en la Ley 1581 de 2012 y el Decreto 1377 de 2013, sin NIT ni representante legal. **Requiere revisión legal** | `resources/views/pages/privacy.blade.php` y `config/school.php` → `privacy_policy_version` |

## Antes de salir a producción (técnico)

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `DEMO_CONTENT=false`, SMTP real y `APP_URL` con el dominio.
- [ ] No subir `public/demo/` (fotos provisionales del mockup).
- [ ] **No ejecutar `db:seed`** (crea usuarios con contraseñas conocidas). Crear el admin con `php artisan app:create-admin correo "Nombre"`.
- [ ] `php artisan migrate --force`, `php artisan storage:link`, `php artisan config:cache route:cache view:cache`.
- [ ] Cron cada minuto: `php /ruta/al/proyecto/artisan schedule:run` (procesa la cola de correos).
- [ ] La raíz pública del dominio debe apuntar a `public/`, nunca a la raíz del proyecto.

## Diseño y contenido
- [ ] **Tipografía de títulos:** Baloo 2, Baloo 2 ligera, Nunito o Poppins (hay un selector en el mockup para comparar). *Provisional: Nunito.*
- [ ] **Fotos reales** del colegio: fachada, aulas, patio, actividades y eventos. Se necesita autorización escrita de los acudientes para publicar fotos de menores.
- [ ] **Fotos y reseña de los fundadores** para Nosotros.
- [ ] **Equipo:** nombres, cargos y fotos (reemplaza al organigrama).
- [ ] **Confesión del colegio** (evangélica, católica, interdenominacional): define qué símbolos religiosos usar. *Provisional: solo los símbolos del escudo.*
- [ ] **Año de fundación y cifras reales**, si se quieren destacar (estudiantes, años, docentes). *Provisional: no mostrar cifras.*
- [ ] **Nombre oficial con o sin tilde** (el folleto dice "Génesis" y el logo "GENESIS"). *Provisional: "Génesis" en los textos.*
- [ ] **Edades por grado de preescolar**, ahora que incluye Párvulos.

## Admisiones y pagos
- [ ] **Año lectivo** de los costos del folleto y si se publican en la web. *Provisional: 2027, visibles en Admisiones (ver tabla de provisionales).*
- [ ] **Requisitos y documentos** de matrícula. *Provisional: lista habitual en Colombia (ver tabla de provisionales).*
- [ ] **Tiempo de respuesta** a una pre-inscripción y **quién recibe los avisos**. *Provisional: 3 días hábiles; cecgenesis16@gmail.com.*
- [ ] **Revisión legal de la política de tratamiento de datos** (borrador publicado en `/politica-de-datos`).
- [ ] **Datos bancarios reales** para donaciones (banco, tipo y número de cuenta, titular, NIT, Nequi o Daviplata).
- [ ] **¿Se mantiene la sección de donaciones y voluntariado?** Estaba en la solicitud original, pero no aparece en el folleto.
- [ ] **Pagos en línea** (PSE): solo en fase 3 y si el colegio lo confirma.

## Académico (necesario antes de la fase 2)
- [ ] **SIEE:** escala numérica (1,0–5,0, 1–10, 1–100…), nota mínima aprobatoria y rangos de Superior, Alto, Básico y Bajo.
- [ ] **Número de periodos** por año y peso de cada uno.
- [ ] **Asignaturas por grado** e intensidad horaria.
- [ ] **Secciones por grado** (¿una sola o A/B?).
- [ ] **¿Los estudiantes tienen cuenta propia, o solo el acudiente?** *Provisional: acudiente para todos; cuenta de estudiante desde 4.° o 6.°, a confirmar.*
- [ ] **Modelo del boletín actual** (el colegio debe compartir uno como referencia).
- [ ] **Asistencia por día o por clase.** *Provisional: por día, con opción por asignatura.*

## Infraestructura
- [ ] **Dominio** (por ejemplo, colegiogenesis.edu.co) y hosting.
- [ ] **Correo para envíos automáticos.** *Provisional: SMTP de cecgenesis16@gmail.com con contraseña de aplicación; ideal migrar a un correo con el dominio.*
- [ ] **¿El +57 321 797 5579 tiene WhatsApp** para el botón flotante?
- [ ] **Horarios de atención** reales (en el mockup son de ejemplo).
- [ ] **Quién administrará el contenido** y con qué frecuencia se publican noticias.
