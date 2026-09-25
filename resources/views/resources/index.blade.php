@use('App\Enums\ResourceType')
@use('App\Models\Resource')
@php
    $sections = collect([
        ResourceType::Circular->anchor() => $circulars->isNotEmpty(),
        ResourceType::Supplies->anchor() => $supplies->isNotEmpty(),
        ResourceType::Uniform->anchor() => $uniforms->isNotEmpty(),
        ResourceType::Schedule->anchor() => $schedules->isNotEmpty(),
    ])->filter();
    $labels = collect(ResourceType::cases())->mapWithKeys(fn ($t) => [$t->anchor() => $t->plural()]);
    $contact = config('school.contact');
@endphp

<x-layouts.public title="Recursos para acudientes" description="Circulares, listas de útiles, uniformes y horarios del Centro Educativo Cristiano Génesis.">
    <x-page-head eyebrow="Acudientes" title="Recursos para acudientes" lead="Circulares, listas de útiles, uniformes y horarios del colegio, siempre a la mano." />

    @if ($sections->isEmpty())
        <section class="resources-page">
            <div class="wrap narrow">
                <div class="cal-empty">
                    <p>Pronto publicaremos aquí las circulares, las listas de útiles, los uniformes y los horarios. Mientras tanto, escríbenos o llámanos y con gusto te ayudamos.</p>
                    <p class="contact-links">
                        <a href="tel:{{ $contact['phone_link'] }}"><x-icon name="phone" /> {{ $contact['phone'] }}</a>
                        <a href="mailto:{{ $contact['email'] }}"><x-icon name="mail" /> {{ $contact['email'] }}</a>
                    </p>
                </div>
            </div>
        </section>
    @else
        <nav class="resource-nav" aria-label="Secciones de la página">
            <div class="wrap">
                @foreach ($sections->keys() as $anchor)
                    <a href="#{{ $anchor }}">{{ $labels[$anchor] }}</a>
                @endforeach
            </div>
        </nav>

        <div class="resources-page">
            @if ($circulars->isNotEmpty())
                <section class="resource-section" id="circulares" aria-labelledby="circulares-title">
                    <div class="wrap narrow">
                        <h2 id="circulares-title">Circulares</h2>
                        <ul class="circulars">
                            @foreach ($circulars as $circular)
                                <x-circular-item :circular="$circular" />
                            @endforeach
                        </ul>
                        @if ($moreCirculars)
                            <a class="all" href="{{ route('resources.circulars') }}">Ver circulares anteriores</a>
                        @endif
                    </div>
                </section>
            @endif

            @if ($supplies->isNotEmpty())
                <section class="resource-section" id="utiles" aria-labelledby="utiles-title">
                    <div class="wrap narrow">
                        <h2 id="utiles-title">Listas de útiles</h2>
                        <p class="section-lead">Elige el grado de tu acudido.</p>

                        <div class="supplies" data-supplies>
                            <div class="grade-picker">
                                @foreach ($levels as $level)
                                    <div class="grade-group lvl-{{ $level['key'] }}">
                                        <span class="grade-level">{{ $level['short'] }}</span>
                                        <div class="grade-chips">
                                            @foreach ($level['grades'] as $grade)
                                                @if ($supplies->has($grade))
                                                    <a class="grade-chip" href="#{{ Resource::gradeAnchor($grade) }}" data-grade="{{ Resource::gradeAnchor($grade) }}">{{ $grade }}</a>
                                                @else
                                                    <span class="grade-chip is-empty" title="Aún no publicada">{{ $grade }}<span class="sr-only"> (aún no publicada)</span></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ($supplies as $grade => $list)
                                <article class="supply-panel" id="{{ $list->anchor() }}" data-grade-panel tabindex="-1">
                                    <h3>{{ $grade }} <span>· Año lectivo {{ $list->school_year }}</span></h3>
                                    @if ($list->summary)
                                        <p>{{ $list->summary }}</p>
                                    @endif
                                    @if ($list->file_path)
                                        <a class="btn btn-sm btn-azul" href="{{ $list->fileUrl() }}" target="_blank" rel="noopener">
                                            <x-icon name="download" /> Ver lista en PDF <span class="size">({{ $list->fileSizeLabel() }})</span>
                                        </a>
                                    @endif
                                    @if ($list->body)
                                        <div class="prose">{{ $list->bodyHtml() }}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($uniforms->isNotEmpty())
                <section class="resource-section" id="uniformes" aria-labelledby="uniformes-title">
                    <div class="wrap">
                        <h2 id="uniformes-title">Uniformes</h2>
                        <div class="resource-cards">
                            @foreach ($uniforms as $uniform)
                                <article class="resource-card" id="{{ $uniform->anchor() }}">
                                    @if ($uniform->image_path)
                                        <img class="media" src="{{ $uniform->imageUrl('sm') }}" alt="{{ $uniform->image_alt }}" loading="lazy" decoding="async">
                                    @endif
                                    <div class="body">
                                        <h3>{{ $uniform->title }}</h3>
                                        @if ($uniform->summary)
                                            <p class="summary">{{ $uniform->summary }}</p>
                                        @endif
                                        <div class="prose">{{ $uniform->bodyHtml() }}</div>
                                        @if ($uniform->file_path)
                                            <a class="pdf-link" href="{{ $uniform->fileUrl() }}" target="_blank" rel="noopener">
                                                <x-icon name="download" /> Ver PDF ({{ $uniform->fileSizeLabel() }})
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($schedules->isNotEmpty())
                <section class="resource-section" id="horarios" aria-labelledby="horarios-title">
                    <div class="wrap">
                        <h2 id="horarios-title">Horarios</h2>
                        <div class="resource-cards">
                            @foreach ($schedules as $schedule)
                                <article class="resource-card" id="{{ $schedule->anchor() }}">
                                    <div class="body">
                                        <h3><x-icon name="clock" /> {{ $schedule->title }}</h3>
                                        @if ($schedule->summary)
                                            <p class="summary">{{ $schedule->summary }}</p>
                                        @endif
                                        <div class="prose">{{ $schedule->bodyHtml() }}</div>
                                        @if ($schedule->file_path)
                                            <a class="pdf-link" href="{{ $schedule->fileUrl() }}" target="_blank" rel="noopener">
                                                <x-icon name="download" /> Ver PDF ({{ $schedule->fileSizeLabel() }})
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>
    @endif
</x-layouts.public>
