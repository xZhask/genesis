# Manual de pruebas (entorno local)

Guía corta para recorrer el sitio y el portal con los **datos de prueba**. Todo es ficticio y existe solo en local (`http://genesis.test`), después de ejecutar `php artisan migrate:fresh --seed`. **En producción estas cuentas no existen.**

## Cómo ingresar

- Entra en **Ingresar al portal** (`/ingresar`).
- Usa el **número de documento** o el **correo** de la cuenta.
- La contraseña de todas las cuentas de prueba es **`password`**.

## Cuentas de prueba

| Rol | Usuario | Quién es | Qué probar |
|---|---|---|---|
| **Administración** | `admin@genesis.test` | Administración Génesis | Todo el panel `/admin`: contenido de la web, pre-inscripciones, personas, estructura académica, cierre de periodos, horarios, alertas e indicadores |
| **Docente** | `22334455` | Edgar Ojoalegre: Matemáticas, Álgebra, Geometría y Estadística de 6.° a 9.°; director de grupo de 7.° 1 | Asistencia, notas y logros, «Mi grupo», horario, resumen con gráficos, alertas y calendario |
| **Docente de primaria** | `32000108` | Hernán Cantillo Ortega, director de 3.° 1 | Una clase de primaria y su grupo |
| **Docente de preescolar** | `32000105` | Rosa Herrera Julio, directora de Transición 1 | Evaluación descriptiva (sin notas) |
| **Acudiente** | `45123456` | Marta Díaz, madre de Andrés (7.° 1) y Lucía (3.° 1) | Información de cada acudido, boletines, horario, circulares, calendario y «Mis datos» |
| **Estudiante** | `1047999001` | Andrés Pérez, 7.° 1 | Su información, notas de periodos cerrados y horario |

> Los documentos de los docentes de primaria y preescolar salen de los datos de prueba: si estos cambian, búscalos en Admin → Personas → Docentes y administración.

## Recorridos sugeridos

**Web pública (sin iniciar sesión).** Revisa el inicio, Nosotros, Admisiones (envía una pre-inscripción de prueba), Noticias, Calendario, Galería, Acudientes y Apóyanos. Las circulares y los eventos marcados «solo familias» **no** deben aparecer aquí.

**Administración**
1. **Panel:** pendientes del día y alertas.
2. **Académico → Años y periodos:** cierra el periodo 4 con «Avisar a las familias por correo».
3. **Académico → Horarios:** edita una sección y prueba a poner a un docente en dos clases a la misma hora; el sistema no lo deja guardar.
4. **Personas → Cambios de contacto:** aprueba o rechaza la solicitud de teléfono de Marta.
5. **Recursos y Eventos:** crea una circular o un evento «solo familias» para un grado.

**Docente (Edgar).** Toma la asistencia de hoy. Registra notas en una clase del periodo abierto. En «Mi grupo», escribe el comportamiento y descarga en PDF los boletines del grupo de un periodo cerrado. Revisa «Horario» y «Resumen».

**Acudiente (Marta).** Cambia entre Andrés y Lucía. Descarga un boletín de un periodo cerrado. Revisa «Ver horario», «Circulares» y «Calendario». En «Mis datos», pide un cambio de teléfono.

**Estudiante (Andrés).** Solo ve su propia información. Si escribe a mano una dirección del docente o del admin (por ejemplo `/admin`), recibe un error 403.

## Correos de prueba

Los correos que envía el sistema (pre-inscripción, recuperación de contraseña, avisos a las familias) llegan a **Mailpit**: `http://localhost:8025`. En el hosting, los avisos a las familias salen solos cada minuto gracias al cron. En local no hay cron, así que para enviarlos ejecuta:

```
php artisan app:deliver-family-mail
```

## Si algo no cuadra

- **Volver al estado inicial:** `php artisan migrate:fresh --seed` borra todo y vuelve a crear los datos de prueba (tarda cerca de un minuto).
- **La cuenta pide cambiar la contraseña:** pasa con las cuentas creadas desde el panel, que entregan una contraseña temporal. Las de esta tabla no la piden.
