# Decisiones pendientes del colegio

**Regla para Claude Code:** si una tarea depende de un punto de esta lista, usa el valor provisional indicado o, si no hay, uno razonable; déjalo configurable y **anótalo en la tabla de abajo**. La web se construye como quedará terminada. Excepción: nunca se inventan datos que se presenten como hechos verificables (cifras, testimonios, cuentas bancarias, donantes); esos siguen ocultos o solo en el contenido de ejemplo (`DEMO_CONTENT`). Cuando el colegio decida, se marca aquí con ✅ y la fecha.

## Publicado con valores provisionales

Datos que ya se ven en la web y que el colegio debe confirmar antes de salir a producción.

| Dato | Valor provisional | Dónde se cambia |
|---|---|---|
| Textos del hero, niveles, pasos de admisión, títulos e introducciones de Nosotros, Recursos y Apóyanos, y microcopy | Borradores de UX (las declaraciones formales son literales) | Vistas en `resources/views/home/`, `resources/views/admissions/`, `resources/views/resources/`, `resources/views/support/` y `resources/views/pages/about.blade.php` |
| Formas de ayudar como voluntario y disponibilidad | Mejorar espacios, apoyar eventos y salidas, compartir un oficio, leer con los niños, otra forma; entre semana mañana/tarde, fines de semana, actividades puntuales | `app/Enums/VolunteerArea.php` y `app/Enums/VolunteerAvailability.php` |
| Correo que recibe los avisos de voluntariado | cecgenesis16@gmail.com | Admin → Configuración |
| Año lectivo de la pre-inscripción y de los costos | 2027 | Admin → Configuración |
| Costos | Los del folleto: preescolar y primaria $ 111.000 / $ 99.900; secundaria $ 299.700 / $ 244.200 | Admin → Configuración |
| Requisitos y documentos de matrícula | Lista habitual en Colombia (registro civil, documentos del acudiente, certificados, paz y salvo, EPS, vacunas, fotos) | Admin → Configuración |
| Tiempo de respuesta a una solicitud | "3 días hábiles" | Admin → Configuración |
| Correo que recibe los avisos de pre-inscripción | cecgenesis16@gmail.com | Admin → Configuración |
| Portal de familias: notas y «lo que falta para aprobar» | Solo periodos cerrados; acumulado = suma de periodos según su peso; mensaje «Necesita en promedio X en los periodos que faltan» o «Requiere acompañamiento: habla con el docente» si ya no alcanza. Validar la redacción con el colegio | `resources/views/portal/student/show.blade.php` y `app/Support/StudentOverview.php` |
| Alertas del portal (docente, director de grupo y admin; las familias no las ven) | Inasistencia: 3 o más faltas sin excusa en una materia dentro del periodo. Bajo rendimiento: nota del periodo en curso por debajo de la aprobatoria con al menos 2 actividades calificadas, o un acumulado con el que ya no se alcanza el año. Cumpleaños: próximos 7 días | Faltas: Admin → Configuración → Alertas del portal. Lo demás: `config/school.php` → `alerts` |
| Gráficos del portal (docente: Resumen; admin: Académico → Indicadores) | Asistencia = presentes + llegadas tarde sobre el total de registros (la falta con excusa cuenta como falta). Notas del periodo en curso: nota hasta hoy de cada estudiante en cada clase. Aprobación por grado: porcentaje de notas de materia aprobadas en cada periodo cerrado. Validar las definiciones con el colegio | `app/Support/Indicators.php` |
| Correos a las familias | Recordatorio de los eventos marcados 2 días antes (6:00 a. m.); «boletín disponible» al cerrar un periodo (casilla marcada por defecto); circular nueva si el admin marca «Avisar por correo»; resultado del cambio de contacto y aviso de seguridad al correo anterior. Solo a acudientes con cuenta activa y correo; nunca llevan notas; cada familia puede desactivarlos (salvo seguridad y la respuesta a sus solicitudes). Tope provisional: 400 correos al día | `config/school.php` → `family_mail` y `FAMILY_MAIL_DAILY_LIMIT` en `.env` |
| Eventos: quién los ve | Cada evento es «solo familias del portal» (opción por defecto) o «público en la web»; los de familias pueden dirigirse a ciertos grados. La web pública (calendario, inicio, noticias y el .ics) solo muestra los públicos; en el portal, los acudientes ven los suyos y los docentes todos. Los estudiantes no tienen calendario en el portal | Admin → Eventos |
| Circulares: quién las ve | Cada circular es «solo familias del portal» (opción por defecto) o «pública en la web»; las de familias pueden dirigirse a ciertos grados (se ven en el portal de los acudientes con estudiantes matriculados en ellos este año) y su PDF no queda en el disco público. Útiles, uniformes y horarios generales siempre son públicos. Los estudiantes no ven circulares (van dirigidas a las familias) | Admin → Recursos → Circulares |
| Buzón de sugerencias (se agregó al portal el 27 de septiembre de 2026, a pedido del proyecto) | Estudiantes (desde 6.°) y acudientes envían sugerencias, quejas, observaciones o reconocimientos sobre una de sus clases o sobre el colegio en general, hasta 5 al día. Solo los leen y responden las cuentas de administración autorizadas (quien ya lo lee autoriza a otras); el docente mencionado no los ve. No es anónimo para quien lo revisa. Aviso por correo a esas cuentas, sin el contenido. Los mensajes se conservan sin fecha de borrado. Confirmar con el colegio quién lo revisa, el tiempo de respuesta y cuánto tiempo se guardan los mensajes | Admin → Personas → cuenta de administración → «Puede leer el buzón»; textos en `resources/views/portal/feedback.blade.php` |
| Política de tratamiento de datos | Borrador basado en la Ley 1581 de 2012 y el Decreto 1377 de 2013, sin NIT ni representante legal. **Requiere revisión legal** | `resources/views/pages/privacy.blade.php` y `config/school.php` → `privacy_policy_version` |

## Antes de salir a producción (técnico)

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `DEMO_CONTENT=false`, SMTP real y `APP_URL` con el dominio.
- [ ] No subir `public/demo/` (fotos provisionales del mockup).
- [ ] **No ejecutar `db:seed`** (crea usuarios con contraseñas conocidas). Crear el admin con `php artisan app:create-admin correo "Nombre"`.
- [ ] `php artisan migrate --force`, `php artisan storage:link`, `php artisan config:cache route:cache view:cache`.
- [ ] Cron cada minuto: `php /ruta/al/proyecto/artisan schedule:run` (procesa la cola de correos, envía la bandeja de correos a las familias y a las 6:00 a. m. anota los recordatorios de eventos). Sin este cron no sale ningún correo.
- [ ] La raíz pública del dominio debe apuntar a `public/`, nunca a la raíz del proyecto.
- [ ] **PHP del hosting para las fotos** (noticias y galería): extensión GD con soporte WebP y extensión `exif` (corrige la rotación de las fotos del celular). Verificar con `php -i` o `phpinfo()`.
- [ ] **Límites de PHP del hosting:** `memory_limit` ≥ 128M (redimensionar una foto de 12 MP con GD usa unos 60–80 MB), `upload_max_filesize` ≥ 10M y `post_max_size` ≥ 12M (la galería acepta fotos de hasta 10 MB y el panel las sube una por una). Si el hosting no permite subirlos, bajar el máximo en `StoreGalleryPhotosRequest::MAX_KB`.
- [ ] **Boletines en PDF (dompdf):** extensiones PHP `dom` y `mbstring`, y la carpeta `storage/fonts` con permiso de escritura (ahí dompdf guarda la caché de las tipografías). Un grupo de ~35 estudiantes tarda unos 10 s: `max_execution_time` ≥ 60.
- [ ] **Espacio en disco:** cada foto ocupa unos 150–300 KB (versiones de 1200 y 600 px en WebP; el original no se guarda). Revisar la cuota del plan de hosting según cuántas fotos se publiquen al año.

## Diseño y contenido
- [ ] **Tipografía de títulos:** Baloo 2, Baloo 2 ligera, Nunito o Poppins (hay un selector en el mockup para comparar). *Provisional: Nunito.*
- [ ] **Fotos reales** del colegio: fachada, aulas, patio, actividades y eventos. Se necesita autorización escrita de los acudientes para publicar fotos de menores.
- [ ] **Fotos y reseña de los fundadores** para Nosotros.
- [ ] **Equipo:** nombres, cargos y fotos (reemplaza al organigrama).
- [ ] **Confesión del colegio** (evangélica, católica, interdenominacional): define qué símbolos religiosos usar. *Provisional: solo los símbolos del escudo.*
- [ ] **Año de fundación y cifras reales**, si se quieren destacar (estudiantes, años, docentes). *Provisional: no mostrar cifras.*
- [ ] **Nombre oficial con o sin tilde** (el folleto dice "Génesis" y el logo "GENESIS"). *Provisional: "Génesis" en los textos.*
- [ ] **Edades por grado de preescolar**, ahora que incluye Párvulos.
- [ ] **Recursos para acudientes** (se cargan desde el admin en `/admin/recursos`; mientras no haya, la página invita a llamar o escribir):
  - [ ] Horarios de entrada y salida por nivel (y si hay jornada de la tarde).
  - [ ] Listas de útiles por grado del año lectivo, en PDF o texto.
  - [ ] Uniformes (diario y educación física): descripción, fotos de las prendas y proveedores con su teléfono.
  - [ ] Circulares vigentes que se puedan publicar (solo información general, sin datos de estudiantes).

## Admisiones y pagos
- [ ] **Año lectivo** de los costos del folleto y si se publican en la web. *Provisional: 2027, visibles en Admisiones (ver tabla de provisionales).*
- [ ] **Requisitos y documentos** de matrícula. *Provisional: lista habitual en Colombia (ver tabla de provisionales).*
- [ ] **Tiempo de respuesta** a una pre-inscripción y **quién recibe los avisos**. *Provisional: 3 días hábiles; cecgenesis16@gmail.com.*
- [ ] **Revisión legal de la política de tratamiento de datos** (borrador publicado en `/politica-de-datos`).
- [ ] **¿Se mantiene la sección de donaciones y voluntariado?** Estaba en la solicitud original, pero no aparece en el folleto. *Provisional: la página `/apoyanos` está construida; en local se ve con datos ficticios marcados "(ejemplo)" y en producción arranca vacía.*
- [ ] **Apóyanos: datos que el colegio debe entregar** (se cargan en `/admin/apoyanos`; lo que falte no se muestra):
  - [ ] **Cuentas para donar:** banco, tipo y número de cuenta, titular, NIT; Nequi o Daviplata si aplica. Verificar el número con el banco antes de publicar.
  - [ ] **Donantes y aliados** que autoricen aparecer con su nombre (y su página web, si quieren).
  - [ ] **Testimonios reales** de voluntarios o aliados, con autorización escrita para publicar su nombre y sus palabras.
  - [ ] **Fotos del voluntariado** (jornadas, arreglos), con autorización si aparecen menores. Se configuran en `config/school.php` → `support.volunteer_photos`.
  - [ ] **¿Se emite certificado de donación** (beneficio tributario)? La web no lo promete hasta confirmarlo.
  - [ ] **Formas reales de voluntariado** que el colegio acepta y **quién responde** a los voluntarios y en cuánto tiempo (la web solo dice "pronto te llamaremos").
  - [ ] **¿El +57 321 797 5579 tiene WhatsApp?** Si sí, se activa el botón "¿Ya donaste? Cuéntanos por WhatsApp" (Admin → Configuración).
- [ ] **Pagos en línea** (PSE): solo en fase 3 y si el colegio lo confirma.

## Académico (necesario antes de la fase 2)
- [ ] **SIEE:** ✅ escala de 1 a 5 (confirmado el 25 de septiembre de 2026). Falta confirmar: nota mínima aprobatoria (el boletín muestra 3,0) y rangos de desempeño. *Provisional, deducido del boletín: Bajo 1,00–2,99; Básico 3,00–4,19; Alto 4,20–4,79; Superior 4,80–5,00.*
- [ ] **Número de periodos:** ✅ 4 por año (confirmado el 25 de septiembre de 2026). Falta confirmar el peso de cada uno. *Provisional: 25 % cada uno (el acumulado del boletín es la suma de los periodos ÷ 4).*
- [ ] **Asignaturas por grado** e intensidad horaria. ✅ Lista de materias recibida el 25 de septiembre de 2026 (Español, Matemáticas, Álgebra, Geometría, Estadística, Inglés, Informática, Música, Artística, Física, Biología, Química, Sociales e historia, Constitución Política y Democracia, Filosofía, Educación Física, Educación cristiana, Educación financiera y Cátedra de Educación Emocional). Falta: **qué grados ven cada materia, su intensidad horaria y su área**. *Provisional: áreas según la Ley 115, intensidades del boletín de 3.°; todo editable en Admin → Académico.*
- [x] **Secciones por grado:** ✅ puede haber más de una (25 de septiembre de 2026).
- [x] **Cuentas de estudiante:** ✅ desde 6.°; antes entra solo el acudiente (25 de septiembre de 2026).
- [x] **Ingreso al portal:** ✅ con número de documento y contraseña; el correo es opcional y sirve para recuperarla (25 de septiembre de 2026).
- [x] **Plataforma actual (cecgenesis.com):** ✅ el portal nuevo opera desde el año lectivo 2027; después se evaluará importar los registros de la plataforma antigua (25 de septiembre de 2026).
- [ ] **Preescolar con notas (aviso del 27 de septiembre de 2026):** comentaron que en preescolar también se manejan notas, lo que contradice la evaluación solo descriptiva de `CLAUDE.md` y `docs/alcance-y-modelo.md` (y el carácter cualitativo del Decreto 2247 de 1997). Preguntar al colegio: 1) qué ponen (números 1–5, desempeños Superior/Alto/Básico/Bajo o letras); 2) sobre qué (por materia o por dimensión del desarrollo); 3) si además escriben una descripción por niño. Pedir un boletín de preescolar de muestra con los datos tapados. *Propuesta pendiente de aprobar: tipo de evaluación configurable por nivel (descriptiva, numérica o mixta), sin tablas nuevas; hoy la decisión está en 17 llamadas a `Grade::isPreschool()`. Mientras no se confirme, preescolar sigue descriptivo.*
- [ ] **Preescolar:** dimensiones del desarrollo que evalúa el colegio y modelo de su boletín. *Provisional: las siete dimensiones del Decreto 2247 de 1997; la docente escribe una descripción por niño en cada dimensión y periodo, más observaciones generales; informe en PDF de una página sin notas (`resources/views/pdf/preschool-report.blade.php`). Se ajusta cuando llegue el modelo del colegio.*
- [x] **Encabezado del boletín:** ✅ resolución No. 3332 del 6 de diciembre de 2024, NIT 1102821784-1, Sede Principal (confirmado el 25 de septiembre de 2026; editable en Admin → Configuración, no se publica en la web).
- [ ] **Nombre de quien firma como rector o rectora.** *Provisional: la firma dice solo «Rectoría» (Admin → Configuración).*
- [x] **Modelo del boletín actual:** ✅ recibido el 25 de septiembre de 2026 (`referencia/Maximos-IETA.mht`, excluido de git porque tiene datos reales de un estudiante). Falta el de **preescolar** (evaluación cualitativa).
- [ ] **Pesos de saber, hacer y ser** en la nota del periodo. *Provisional: iguales (33,33 % / 33,33 % / 33,34 %); se editan en Admin → Académico → Año lectivo → Escala de valoración.*
- [ ] **Frase del desempeño Bajo** en los logros (el boletín actual no trae ejemplo). *Provisional: «Estoy en proceso de…». Las de Superior, Alto y Básico se tomaron del boletín.*
- [ ] **Escala del comportamiento.** *Provisional: la misma de las notas (1 a 5), registrada por el director de grupo cada periodo.*
- [ ] **Nivelaciones:** cómo se registran las recuperaciones de quien queda en Bajo y si cambian la nota del periodo o solo la final.
- [ ] **Puestos (grupo, grado, institución)** del boletín actual. *Provisional: no se muestran a las familias (decisión del 25 de septiembre de 2026); si el colegio los quiere, irían solo en el boletín.*
- [ ] **¿Las familias ven notas del periodo en curso?** ✅ Solo de periodos cerrados (25 de septiembre de 2026).
- [ ] **Datos que se registran de cada estudiante.** *Provisional: solo lo mínimo para el portal (tipo y número de documento, nombres, apellidos y fecha de nacimiento), por minimización de datos (Ley 1581). Si el colegio necesita más (EPS, dirección, género), se agregan.*
- [ ] **Datos de estudiantes y acudientes para cargar al portal 2027:** exportarlos de la plataforma actual o del SIMAT en Excel con la plantilla de Admin → Personas → Importar (una fila por estudiante y acudiente).
- [ ] **Cómo se entregan las cuentas.** *Provisional: el admin imprime una hoja con fichas recortables (documento y contraseña temporal del tipo «tamo-4827»); al primer ingreso cada persona crea la suya. Quien no tiene correo pide una contraseña temporal nueva al colegio.*
- [ ] **Asistencia por día o por clase.** *Provisional: por asignatura, como en el boletín actual (columna «Inas» por materia). En preescolar, donde una docente dicta todas las dimensiones, esto obliga a tomarla por dimensión: confirmar si allí se prefiere una sola asistencia diaria.*
- [ ] **Docentes y asignaciones 2027:** lista de docentes (nombre, documento, correo), qué materia dicta cada uno en cada sección y quién es el director de cada grupo. Se cargan en Admin → Personas y Admin → Académico → Asignaciones docentes.
- [ ] **Horario de clases 2027** (se agregó al portal el 25 de septiembre de 2026, a pedido del proyecto): franjas de cada nivel (hora de inicio y fin de cada clase y de los descansos) y el horario de cada sección. Se cargan en Admin → Académico → Horarios. *Provisional: lunes a viernes, una sola jornada (la de la mañana); en local hay franjas y horarios ficticios. Si hay clases el sábado, se cambia en `config/school.php` → `schedule.days`.*
- [ ] **¿Los docentes pueden ver los datos de contacto de los acudientes de sus grupos?** *Provisional: no; el portal docente solo muestra nombres de estudiantes y su asistencia.*
- [ ] **Cambios de datos de contacto desde el portal.** *Provisional: solo el acudiente pide cambiar su teléfono o su correo (en «Mis datos»); los estudiantes no. El cambio queda pendiente hasta que el admin lo aprueba en Admin → Personas → Cambios de contacto; el acudiente ve el estado en el portal (sin aviso por correo hasta la fase 3). Un correo aprobado también cambia el de su cuenta del portal. Nombre y documento solo los cambia el colegio.*

## Infraestructura
- [ ] **Dominio** (por ejemplo, colegiogenesis.edu.co) y hosting.
- [ ] **Correo para envíos automáticos.** *Provisional: SMTP de cecgenesis16@gmail.com con contraseña de aplicación; ideal migrar a un correo con el dominio.* Gmail permite unos 500 destinatarios al día: el portal envía como máximo 400 al día (`FAMILY_MAIL_DAILY_LIMIT`) y deja el resto para el día siguiente. Con muchas familias, un correo con el dominio o un servicio de envío evita la espera.
- [ ] **¿El +57 321 797 5579 tiene WhatsApp** para el botón flotante? Se activa en Admin → Configuración.
- [ ] **Horarios de atención** reales para el pie de página. Se escriben en Admin → Configuración (vacío = no se muestran).
- [ ] **Quién administrará el contenido** y con qué frecuencia se publican noticias.
