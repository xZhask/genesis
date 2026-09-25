{{--
    Nosotros. Las declaraciones formales (quiénes somos, misión, visión, valores, principios,
    objetivos y propuesta) son literales de docs/contenido-institucional.md.
    Títulos de sección e introducciones: borradores de UX (docs/pendientes.md).
--}}
@php
    $values = ['Amor', 'Respeto', 'Responsabilidad', 'Solidaridad', 'Creatividad', 'Gratitud', 'Integridad', 'Servicio', 'Liderazgo'];

    $principles = [
        ['puzzle', 'Educación integral'],
        ['bible', 'Fe y valores cristianos'],
        ['tree', 'Creatividad y liderazgo'],
        ['globe', 'Convivencia y servicio'],
    ];

    $objectives = [
        'Formar estudiantes con principios y valores cristianos fundamentados en el modelo de Jesús.',
        'Promover la excelencia académica y el desarrollo integral.',
        'Fomentar el aprendizaje del inglés como segunda lengua.',
        'Desarrollar competencias tecnológicas, científicas y ambientales.',
        'Crear espacios que fortalezcan la creatividad, el liderazgo y la sana convivencia.',
    ];

    $proposal = [
        'Formación académica con enfoque constructivista.',
        'Educación basada en principios bíblicos.',
        'Intensidad fortalecida en inglés.',
        'Proyectos ambientales, científicos y comunitarios.',
        'Acompañamiento integral a estudiantes y familias.',
    ];

    $levels = config('school.levels');
@endphp

<x-layouts.public title="Nosotros" description="Conoce el Centro Educativo Cristiano Génesis: misión, visión, valores, propuesta educativa y niveles de preescolar, primaria y secundaria en Zambrano, Bolívar.">
    <x-page-head eyebrow="Nosotros" title="Formando generaciones de cambio"
        lead="Somos una institución educativa cristiana de carácter privado que ofrece formación en los niveles de preescolar, primaria y secundaria. Nuestro compromiso es brindar una educación integral fundamentada en principios y valores cristianos, promoviendo el desarrollo académico, espiritual, emocional y social de nuestros estudiantes." />

    <nav class="subnav" aria-label="En esta página">
        <div class="wrap">
            <a href="#mision">Misión y visión</a>
            <a href="#valores">Valores</a>
            <a href="#propuesta">Propuesta educativa</a>
            <a href="#niveles">Niveles</a>
        </div>
    </nav>

    {{-- Misión y visión --}}
    <section id="mision" class="about-mv" aria-labelledby="mision-title">
        <div class="wrap">
            <h2 id="mision-title" class="sr-only">Misión y visión</h2>
            <div class="mv-grid">
                <article class="mv-card">
                    <span class="mv-icon ic-verde"><x-icon name="tree" /></span>
                    <h3>Misión</h3>
                    <p>Formar niños y niñas con valores y principios cristianos, autónomos, íntegros e innovadores, comprometidos con su entorno social y natural, fortaleciendo sus habilidades cognitivas, afectivas y motoras mediante procesos educativos de calidad en conjunto con la familia.</p>
                </article>
                <article class="mv-card">
                    <span class="mv-icon ic-azul"><x-icon name="globe" /></span>
                    <h3>Visión <small>2030</small></h3>
                    <p>Para el año 2030 ser una institución líder en educación preescolar, primaria y secundaria, reconocida por la formación en principios y valores cristianos, el fortalecimiento de habilidades académicas, tecnológicas, científicas, ambientales y el aprendizaje del idioma inglés.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Valores y principios --}}
    <section id="valores" class="about-values" aria-labelledby="valores-title">
        <div class="wrap">
            <x-section-head id="valores-title" title="Nuestros valores"
                lead="Lo que vivimos cada día en el aula, en el patio y con las familias." />
            <ul class="values">
                @foreach ($values as $value)
                    <li>{{ $value }}</li>
                @endforeach
            </ul>

            <h3 class="sub-title">Principios para crecer</h3>
            <ul class="principles">
                @foreach ($principles as [$icon, $text])
                    <li>
                        <span class="ic ic-{{ ['puzzle' => 'sol', 'bible' => 'navy', 'tree' => 'verde', 'globe' => 'azul'][$icon] }}"><x-icon :name="$icon" /></span>
                        <strong>{{ $text }}</strong>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Propuesta y objetivos --}}
    <section id="propuesta" class="about-proposal" aria-labelledby="propuesta-title">
        <div class="wrap proposal-grid">
            <div>
                <x-section-head id="propuesta-title" title="Nuestra propuesta educativa" />
                <ul class="checklist">
                    @foreach ($proposal as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="objectives">
                <h3>Nuestros objetivos</h3>
                <ol>
                    @foreach ($objectives as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Niveles --}}
    <section id="niveles" class="about-levels" aria-labelledby="niveles-title">
        <div class="wrap">
            <x-section-head id="niveles-title" title="Niveles educativos"
                lead="Acompañamos a cada estudiante desde sus primeros pasos hasta el grado noveno." />

            <div class="level-rows">
                @foreach ($levels as $level)
                    <article id="{{ $level['slug'] }}" class="level-row level-{{ $level['key'] }}" aria-labelledby="{{ $level['slug'] }}-title">
                        <x-photo :src="$level['image'] ?? null" :tone="$level['tone']" :icon="$level['icon']" />
                        <div class="body">
                            <span class="tag">{{ $level['tag'] }}</span>
                            <h3 id="{{ $level['slug'] }}-title">{{ $level['name'] }}</h3>
                            <p>{{ $level['summary'] }}</p>

                            <p class="label">Grados</p>
                            <ul class="grades">
                                @foreach ($level['grades'] as $grade)
                                    <li>{{ $grade }}</li>
                                @endforeach
                            </ul>

                            @if ($level['topics'] !== $level['grades'])
                                <p class="label">Énfasis</p>
                                <ul class="topics">
                                    @foreach ($level['topics'] as $topic)
                                        <li>{{ $topic }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="levels-cta">
                <a class="btn btn-line" href="{{ route('admissions') }}">Ver costos y proceso de admisión</a>
            </p>
        </div>
    </section>

    @include('home.admissions')
</x-layouts.public>
