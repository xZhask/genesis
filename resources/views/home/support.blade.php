@php($email = config('school.contact.email'))

<section class="support" id="apoyanos" aria-labelledby="apoyanos-title">
    <div class="wrap">
        <x-section-head id="apoyanos-title" title="Apóyanos"
            lead="Cada aporte y cada hora de voluntariado se convierte en materiales y mejores espacios para los niños." />

        <div class="support-grid">
            <div class="give">
                <h3>Haz una donación</h3>
                @if ($support['accounts'])
                    <p>Puedes transferir directamente a la cuenta del colegio. Si quieres más información sobre tu aporte, escríbenos.</p>
                    <ul class="acct">
                        @foreach ($support['accounts'] as $account)
                            <li class="row">
                                <div><small>{{ $account['label'] }}</small><b>{{ $account['value'] }}</b></div>
                                <button type="button" class="copy" data-copy="{{ $account['value'] }}">
                                    Copiar<span class="sr-only"> {{ $account['label'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>Escríbenos y te contamos cómo puedes aportar al colegio.</p>
                    <a class="btn btn-line give-cta" href="mailto:{{ $email }}?subject={{ rawurlencode('Quiero hacer una donación') }}">
                        <x-icon name="mail" /> Escribir al colegio
                    </a>
                @endif
            </div>

            <div class="volunteer on-dark">
                @if ($support['volunteer_photos'])
                    <div class="pics" aria-hidden="true">
                        @foreach (array_slice($support['volunteer_photos'], 0, 3) as $photo)
                            <x-photo :src="$photo" />
                        @endforeach
                    </div>
                @endif
                <div class="body">
                    <h3>Súmate como voluntario</h3>
                    @if ($support['testimonial'])
                        <blockquote>
                            <p>“{{ $support['testimonial']['quote'] }}”</p>
                            <cite>{{ $support['testimonial']['author'] }}</cite>
                        </blockquote>
                    @else
                        <p>Pinta un aula, acompaña una salida pedagógica o comparte tu oficio con los estudiantes.</p>
                    @endif
                    <a class="btn btn-sol" href="{{ route('support') }}#voluntariado">Quiero ser voluntario</a>
                </div>
            </div>
        </div>

        @if ($support['donors'])
            <div class="donors">
                <p>Gracias a quienes creen en nuestra misión</p>
                <ul class="row">
                    @foreach ($support['donors'] as $donor)
                        <li>{{ $donor }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
