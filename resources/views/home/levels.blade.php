<section id="niveles" aria-labelledby="niveles-title">
    <div class="wrap">
        <x-section-head id="niveles-title" title="Nuestros niveles"
            lead="Un camino completo, desde los primeros pasos hasta el grado noveno, con la misma familia educativa." />

        <div class="levels">
            @foreach ($levels as $level)
                <article class="level level-{{ $level['key'] }}">
                    <x-photo :src="$level['image'] ?? null" :tone="$level['tone']" :icon="$level['icon']" />
                    <div class="body">
                        <span class="tag">{{ $level['tag'] }}</span>
                        <h3>{{ $level['name'] }}</h3>
                        <ul>
                            @foreach ($level['topics'] as $topic)
                                <li>{{ $topic }}</li>
                            @endforeach
                        </ul>
                        <p>{{ $level['summary'] }}</p>
                        <a href="{{ route('about') }}#{{ $level['slug'] }}">Conocer {{ mb_strtolower($level['name']) }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
