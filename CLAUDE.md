# Centro Educativo Cristiano Génesis — Plataforma web

Sitio web público + portal académico para un colegio privado cristiano en **Zambrano, Bolívar (Colombia)**.
Niveles: **Preescolar (Párvulos, Prejardín, Jardín, Transición), Básica primaria (1.° a 5.°) y Básica secundaria (6.° a 9.°)**. El colegio **no** ofrece media (10.° y 11.°): nunca menciones "bachillerato", "grado 11", "media" ni "Saber 11".

Documentos que debes leer según la tarea:
- @docs/contenido-institucional.md — declaraciones formales del colegio (misión, visión, valores, contacto, costos) y contexto. Ver "Textos de la web" más abajo.
- @docs/alcance-y-modelo.md — módulos, roles, modelo de datos y reglas del portal.
- @docs/pendientes.md — decisiones que el colegio aún no toma. Si una tarea depende de una de ellas, usa un valor provisional razonable, déjalo configurable y **regístralo en la tabla de provisionales** de ese archivo (sin inventar cifras, testimonios ni cuentas).
- `referencia/mockup-inicio/index.html` — diseño aprobado de la página de inicio. Es la guía visual: respeta colores, tipografía, espaciados y estructura.
- `referencia/folleto-*.png` — folleto oficial del colegio. Es contexto y referencia de tono, no una regla de diseño ni de redacción.

---

## Stack (decidido)

- **Backend:** Laravel (última versión estable), PHP 8.3+, MySQL/MariaDB. Entorno local: Laragon.
- **Vistas:** Blade + CSS propio con variables (portado del mockup) + JavaScript vanilla. **No usar Tailwind, Bootstrap, React, Vue ni Livewire.**
- **Autenticación:** Laravel Fortify (solo backend) con vistas Blade propias. Recuperación de contraseña por correo.
- **Roles:** columna `role` en `users` (enum) + Gates/Policies de Laravel. No instalar paquetes de permisos para cuatro roles fijos.
- **PDF (boletines):** `barryvdh/laravel-dompdf`.
- **Gráficos del portal:** Chart.js vía Vite.
- **Imágenes:** almacenamiento en `storage/app/public` con `php artisan storage:link`; generar versiones redimensionadas al subir.
- **Hosting objetivo:** hosting compartido con PHP y MySQL. Nada que requiera Node en producción, Redis, websockets ni workers permanentes. Colas con driver `database` y tareas programadas con el `schedule` de Laravel vía cron.

**No agregues dependencias nuevas (Composer o npm) sin preguntar primero y explicar por qué.**

## Convenciones

- Código (clases, tablas, columnas, rutas internas): **inglés**, siguiendo las convenciones de Laravel.
- Textos visibles: **español de Colombia** (`es_CO`), tuteo amable ("Solicita la matrícula", "Ingresa al portal"). Nada de voseo.
- Comentarios y mensajes de commit: español.
- Fechas en pantalla: "18 de septiembre de 2026"; horas: "7:00 a. m."; moneda: `$ 99.900` (COP, punto como separador de miles, sin decimales).
- Nombres de grados: "Párvulos", "Prejardín", "Jardín", "Transición", "1.°" … "9.°".
- Término para padre/madre/tutor: **acudiente**.
- Nombre del colegio en textos: "Centro Educativo Cristiano Génesis" (con tilde). El logo es una imagen y no se redibuja.

### Textos de la web
- **Declaraciones formales, literales:** misión, visión, valores, principios, objetivos, propuesta educativa, contacto y costos. Solo se corrige ortografía evidente; sí se decide cómo presentarlas (jerarquía, orden, extractos).
- **Textos de la web, con criterio de UX:** hero, introducciones de sección, descripciones de niveles, botones y microcopy. Se redactan pensando en el acudiente que entra desde el celular; el folleto solo aporta el tono. Se publican como borrador revisable por el colegio, sin bloquear el desarrollo.
- **Nunca** se publican cifras ni promesas no confirmadas (tamaño de grupos, años de trayectoria, resultados en pruebas, etc.).

## Diseño (decidido)

Toma los valores exactos del mockup de referencia. Resumen:

- **Paleta (del logo):** navy `#104976`, azul `#1D5FA8`, verde `#3FA64A`, acento amarillo `#F6B91C` (usar con moderación, sobre todo en botones principales).
- **Color por nivel:** Preescolar verde `#3FA64A`, Primaria naranja `#D9800A`, Secundaria azul `#1D5FA8`.
- **Tipografía:** cuerpo **Figtree**; títulos con una variable `--font-d`. Opción recomendada: **Nunito** (redondeada y sobria, coherente con el folleto). La elección final está en `docs/pendientes.md`: mientras tanto usa Nunito y deja la fuente en una sola variable CSS para cambiarla en un solo lugar.
- **Modo claro y oscuro:** la primera visita sigue la preferencia del sistema; un botón (luna/sol, 44 × 44 px, junto al menú) permite cambiarlo y la elección se recuerda en el navegador. Ambos temas deben cumplir contraste AA: los colores de marca se usan como relleno y, para texto, sus variantes `*-ink`.
- **Web pública:** alegre y cálida (esquinas redondeadas, color por nivel, fotos reales). **Portal y panel admin:** sobrios y funcionales, con los mismos colores y la misma tipografía, pero densos en información y sin decoración.
- Diseño **primero para celular**; los acudientes entran casi siempre desde el teléfono.
- Accesibilidad: contraste AA, foco visible, `alt` en imágenes, respetar `prefers-reduced-motion`.
- El selector de tipografía y la franja "Mockup de propuesta" del mockup son **solo del mockup**: no los portes.

### Qué NO hacer en el diseño
- No usar carrusel en el hero de inicio: va un hero fijo con los botones "Solicitar matrícula" e "Ingresar al portal". La galería es una cuadrícula con visor (lightbox), no un carrusel.
- No usar fotos de stock ni imágenes generadas con IA de niños como si fueran del colegio. Las fotos del mockup (`referencia/mockup-inicio/img/`) son **provisionales**: en producción solo van fotos reales del colegio, con autorización de los acudientes.
- No publicar cifras no confirmadas (por ejemplo "1:18 docente por estudiante" o "25 años"): están en el mockup como ejemplo.
- No usar el crucifijo como imagen institucional hasta confirmar la confesión del colegio (ver pendientes). Los símbolos seguros son los del escudo: árbol, rompecabezas, mundo y Biblia.

## Arquitectura

Una sola aplicación Laravel con cuatro zonas:

| Zona | Prefijo de ruta | Acceso |
|---|---|---|
| Web pública | `/` | Todos |
| Panel admin (CMS + gestión académica) | `/admin` | `admin` |
| Portal docente | `/portal/docente` | `teacher` |
| Portal estudiante / acudiente | `/portal/estudiante`, `/portal/acudiente` | `student`, `guardian` |

## Reglas de seguridad (no negociables)

1. Cada grupo de rutas lleva middleware de rol. Además, **cada acción** se autoriza con una Policy: un docente solo ve y edita las secciones y asignaturas que tiene asignadas; un acudiente solo ve a sus propios acudidos; un estudiante solo se ve a sí mismo.
2. Un estudiante o acudiente **nunca** puede acceder a URLs de edición del docente o del admin, ni siquiera escribiéndolas a mano. Debe responder 403 y hay que probarlo con tests.
3. **Periodo cerrado = solo lectura.** Si el periodo académico tiene estado `closed`, el servidor rechaza cualquier escritura de notas, asistencia u objetivos de ese periodo. No basta con ocultar botones. Solo `admin` puede reabrirlo, y queda registrado quién lo hizo y cuándo.
4. Contraseñas con hash de Laravel, límite de intentos en el login y enlaces de recuperación con vencimiento.
5. Datos de menores (Ley 1581 de 2012): ningún dato de estudiantes es público. La web pública solo muestra contenido institucional. El formulario de pre-inscripción pide aceptar la política de tratamiento de datos.
6. Validación en el servidor con Form Requests; nunca confiar en la validación del navegador.

## Fases (seguir en orden; no adelantar funciones de fases posteriores)

1. **Fase 1 — Web pública + panel admin de contenido:** inicio, nosotros, niveles, admisiones (pre-inscripción con estados), noticias, calendario, galería, apóyanos (voluntariado, donantes, datos bancarios), recursos para acudientes y footer con contacto y mapa. El admin gestiona noticias, eventos, galería, recursos y solicitudes de pre-inscripción.
2. **Fase 2 — Portal académico:** estructura académica, usuarios y roles, asistencia, notas por periodo, objetivos del periodo, cierre de periodos, boletín en PDF, consultas del estudiante y del acudiente.
3. **Fase 3 — Extras:** alertas (cumpleaños, bajo rendimiento, inasistencias), gráficos, correos automáticos, "Agregar a mi calendario" (.ics) y, solo si el colegio lo confirma, pagos en línea (PSE).

**Fuera de alcance (no implementar):** notificaciones push, app móvil, chat en vivo y pagos en línea antes de la fase 3.

## Forma de trabajo

- Antes de un cambio grande (nueva tabla, nuevo módulo o nueva dependencia), explica el plan y espera aprobación.
- Crea migraciones, seeders con datos de prueba realistas (grados y niveles reales del colegio) y tests de funcionalidad para permisos y cierre de periodos.
- Si algo del pedido contradice este archivo, señala la contradicción en lugar de elegir en silencio.
- Si encuentras información nueva del colegio, no la agregues a `docs/contenido-institucional.md` sin confirmación.
