{{-- Autorización para publicar datos de una persona: se pide una vez y queda la fecha --}}
@if ($record->consent_at)
    <p class="muted">Autorización registrada el {{ $record->consent_at->longDate() }}.</p>
@else
    <div @class(['field', 'consent', 'has-error' => $errors->has('consent')])>
        <label class="check">
            <input id="consent" type="checkbox" name="consent" value="1" @checked(old('consent'))>
            <span>{{ $text }}</span>
        </label>
        @error('consent')
            <p class="error" id="consent-error">{{ $message }}</p>
        @enderror
    </div>
@endif
