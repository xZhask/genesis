{{--
    BORRADOR pendiente de revisión legal (docs/pendientes.md).
    Basado en la Ley 1581 de 2012 y el Decreto 1377 de 2013 (compilado en el Decreto 1074 de 2015).
    Si cambia el contenido, actualiza config('school.privacy_policy_version').
--}}
@php($contact = config('school.contact'))

<x-layouts.public title="Política de tratamiento de datos" description="Cómo el Centro Educativo Cristiano Génesis recolecta, usa y protege los datos personales, conforme a la Ley 1581 de 2012.">
    <x-page-head title="Política de tratamiento de datos personales"
        lead="Cómo recolectamos, usamos y protegemos tus datos y los de tus hijos, conforme a la Ley 1581 de 2012." />

    <section class="legal">
        <div class="wrap narrow prose">
            <nav class="toc" aria-label="Contenido de la política">
                <ol>
                    <li><a href="#responsable">Responsable del tratamiento</a></li>
                    <li><a href="#datos">Datos que recolectamos</a></li>
                    <li><a href="#finalidades">Para qué usamos los datos</a></li>
                    <li><a href="#menores">Datos de niños, niñas y adolescentes</a></li>
                    <li><a href="#derechos">Tus derechos</a></li>
                    <li><a href="#consultas">Consultas y reclamos</a></li>
                    <li><a href="#seguridad">Seguridad y conservación</a></li>
                    <li><a href="#vigencia">Vigencia</a></li>
                </ol>
            </nav>

            <h2 id="responsable">1. Responsable del tratamiento</h2>
            <p>
                El responsable del tratamiento de los datos personales es el <strong>{{ config('school.name') }}</strong>,
                institución educativa cristiana de carácter privado, ubicada en {{ $contact['address'] }}, {{ $contact['city'] }}, Colombia.
            </p>
            <ul>
                <li>Correo: <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></li>
                <li>Teléfono: <a href="tel:{{ $contact['phone_link'] }}">{{ $contact['phone'] }}</a></li>
            </ul>

            <h2 id="datos">2. Datos que recolectamos</h2>
            <p>Solo pedimos los datos necesarios para cada finalidad:</p>
            <ul>
                <li><strong>Solicitud de pre-inscripción:</strong> nombres, apellidos y fecha de nacimiento del estudiante, grado al que aspira y colegio actual; nombre, parentesco, celular y correo del acudiente, y los comentarios que quieras compartir.</li>
                <li><strong>Voluntariado:</strong> nombre, celular, correo, formas de ayuda y disponibilidad que indiques, y el mensaje que quieras compartir.</li>
                <li><strong>Matrícula y vida escolar:</strong> datos de identificación, contacto y salud necesarios para la prestación del servicio educativo, que se entregan en el proceso de matrícula.</li>
                <li><strong>Portal académico:</strong> notas, asistencia, observaciones y boletines del estudiante. Solo pueden verlos el propio estudiante, sus acudientes y el personal autorizado del colegio.</li>
                <li><strong>Navegación:</strong> la página usa cookies técnicas indispensables para su funcionamiento (por ejemplo, para mantener la sesión en el portal). No usamos cookies de publicidad.</li>
            </ul>

            <h2 id="finalidades">3. Para qué usamos los datos</h2>
            <ul>
                <li>Gestionar las solicitudes de pre-inscripción y comunicarnos con la familia para agendar entrevistas.</li>
                <li>Contactar a quienes se ofrecen como voluntarios y coordinar su participación.</li>
                <li>Realizar la matrícula y prestar el servicio educativo.</li>
                <li>Registrar y comunicar el desempeño académico, la asistencia y los boletines.</li>
                <li>Enviar información institucional: circulares, calendario escolar, eventos y avisos importantes.</li>
                <li>Cumplir las obligaciones legales ante las autoridades educativas.</li>
            </ul>
            <p>No vendemos ni cedemos tus datos a terceros. Pueden tratarlos, por encargo del colegio y bajo acuerdos de confidencialidad, los proveedores tecnológicos que alojan la página y envían los correos.</p>

            <h2 id="menores">4. Datos de niños, niñas y adolescentes</h2>
            <p>
                Los datos de los estudiantes se tratan respetando su interés superior y sus derechos fundamentales, como exige el
                artículo 7 de la Ley 1581 de 2012. La autorización la da su representante legal o acudiente, y se tiene en cuenta la
                opinión del menor según su madurez. <strong>Ningún dato de los estudiantes se publica en la página web.</strong>
                Las fotos de actividades escolares solo se publican con la autorización escrita de los acudientes.
            </p>

            <h2 id="derechos">5. Tus derechos</h2>
            <p>Como titular de los datos (o representante del menor) puedes:</p>
            <ul>
                <li>Conocer, actualizar y rectificar tus datos.</li>
                <li>Pedir prueba de la autorización que nos diste.</li>
                <li>Saber qué uso le hemos dado a tus datos.</li>
                <li>Revocar la autorización o pedir que eliminemos tus datos, cuando no exista un deber legal o contractual de conservarlos.</li>
                <li>Acceder gratuitamente a tus datos.</li>
                <li>Presentar quejas ante la Superintendencia de Industria y Comercio, después de haber hecho tu consulta o reclamo ante el colegio.</li>
            </ul>

            <h2 id="consultas">6. Consultas y reclamos</h2>
            <p>
                Escríbenos a <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a> o acércate a la secretaría del colegio.
                Indica tu nombre, tu documento, el estudiante relacionado y lo que solicitas.
            </p>
            <ul>
                <li><strong>Consultas:</strong> te respondemos en máximo 10 días hábiles (prorrogables 5 días hábiles más, con aviso).</li>
                <li><strong>Reclamos</strong> (corrección, actualización o supresión): te respondemos en máximo 15 días hábiles (prorrogables 8 días hábiles más, con aviso).</li>
            </ul>

            <h2 id="seguridad">7. Seguridad y conservación</h2>
            <p>
                Protegemos los datos con medidas técnicas y administrativas: acceso al portal con usuario y contraseña, permisos
                según el rol de cada persona y conexiones cifradas. Conservamos los datos mientras exista la relación con la familia
                y durante el tiempo que exijan las normas educativas y de archivo. Las solicitudes de pre-inscripción que no terminan
                en matrícula se eliminan cuando dejan de ser necesarias.
            </p>

            <h2 id="vigencia">8. Vigencia</h2>
            <p>Esta política rige desde su publicación. Si la cambiamos, publicaremos la nueva versión en esta página.</p>
            <p class="version">Versión {{ config('school.privacy_policy_version') }}</p>
        </div>
    </section>
</x-layouts.public>
