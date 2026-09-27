@use('App\Enums\FeedbackType')
@use('App\Models\FeedbackMessage')
@php
    $single = $topics->count() === 1;
    $selected = old('about', request('sobre'));
@endphp

<x-layouts.portal title="Buzón de sugerencias">
    <div class="admin-head">
        <h1>Buzón de sugerencias</h1>
        <p class="lead-sm">{{ $isStudent
            ? 'Cuéntale al colegio una idea, algo que no está bien o algo que te gusta de tus clases.'
            : 'Cuéntale al colegio una idea, una inquietud o algo que valoras de las clases de tus acudidos.' }}</p>
    </div>

    <p class="notice feedback-urgent"><strong>¿Es urgente o alguien está en riesgo?</strong> No esperes la respuesta del buzón: habla hoy con el director de grupo o con coordinación, o llama al {{ config('school.contact.phone') }}.</p>

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="nuevo-title">
            <h2 id="nuevo-title">Escribir un mensaje</h2>
            <p class="muted">Lo leen solo las directivas del colegio, no el docente de la clase. Tu nombre sí les llega, para poder darte respuesta.</p>

            <form class="form compact feedback-form" method="POST" action="{{ route('portal.feedback.store') }}" novalidate data-form>
                @csrf
                <fieldset @class(['field', 'choices', 'plain-fieldset', 'has-error' => $errors->has('type')])>
                    <legend class="label">¿Qué quieres contar?</legend>
                    @foreach (FeedbackType::cases() as $type)
                        <label class="check">
                            <input type="radio" name="type" value="{{ $type->value }}" @checked(old('type') === $type->value)>
                            <span><strong>{{ $type->label() }}</strong> · {{ $type->hint() }}</span>
                        </label>
                    @endforeach
                    @error('type')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-field name="about" label="¿Sobre qué es?">
                    <select id="about" name="about" @error('about') aria-invalid="true" aria-describedby="about-error" @enderror>
                        <option value="">Elige una opción</option>
                        @foreach ($topics as $topic)
                            @php
                                $sid = $topic['student']->id;
                            @endphp
                            @unless ($single)
                                <optgroup label="{{ $topic['student']->fullName() }}{{ $topic['section'] ? ' · '.$topic['section']->label() : '' }}">
                            @endunless
                            <option value="{{ $sid }}:0" @selected($selected === "{$sid}:0")>El colegio en general{{ $single ? '' : ' ('.$topic['student']->first_names.')' }}</option>
                            @foreach ($topic['assignments'] as $assignment)
                                <option value="{{ $sid }}:{{ $assignment->id }}" @selected($selected === "{$sid}:{$assignment->id}")>
                                    {{ $assignment->subject->name }} · {{ $assignment->teacher->name }}{{ $single ? '' : ' ('.$topic['student']->first_names.')' }}
                                </option>
                            @endforeach
                            @unless ($single)
                                </optgroup>
                            @endunless
                        @endforeach
                    </select>
                </x-field>

                <x-field name="body" label="Mensaje" hint="Cuenta qué pasó o qué propones, con respeto. Máximo {{ number_format(FeedbackMessage::MAX_LENGTH, 0, ',', '.') }} caracteres.">
                    <textarea id="body" name="body" rows="5" maxlength="{{ FeedbackMessage::MAX_LENGTH }}"
                        aria-describedby="body-hint @error('body') body-error @enderror" @error('body') aria-invalid="true" @enderror>{{ old('body') }}</textarea>
                </x-field>

                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Enviando…"><span>Enviar mensaje</span></button>
            </form>
        </section>

        <section class="panel" aria-labelledby="enviados-title">
            <h2 id="enviados-title">Mis mensajes</h2>
            @forelse ($messages as $message)
                <article class="feedback-item feedback-sent">
                    <header class="contact-request-head">
                        <h3 class="feedback-title">
                            <span class="badge badge-type">{{ $message->type->label() }}</span>
                            {{ $message->aboutLabel() }}
                        </h3>
                        <span class="badge badge-{{ $message->status->badge() }}">{{ $message->status->label() }}</span>
                    </header>
                    <small class="block">{{ $message->created_at->longDate() }}@unless ($isStudent) · sobre {{ $message->student->first_names }}@endunless</small>
                    <p class="feedback-body">{{ $message->body }}</p>
                    @if ($message->isAnswered())
                        <div class="feedback-reply">
                            <p class="feedback-reply-head">Respuesta del colegio · {{ $message->replied_at->longDate() }}</p>
                            <p>{{ $message->reply }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <p class="muted">Todavía no has enviado mensajes.</p>
            @endforelse

            {{ $messages->links('partials.pagination') }}
        </section>
    </div>
</x-layouts.portal>
