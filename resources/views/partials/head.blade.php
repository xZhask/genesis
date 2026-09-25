{{-- <head> común a todos los layouts. Parámetros: $title, $description, $entries (Vite) --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
{{-- Aplica el tema antes de pintar para evitar el destello blanco --}}
<script>
    (function () {
        var t;
        try { t = localStorage.getItem('genesis-theme'); } catch (e) {}
        if (t !== 'light' && t !== 'dark') {
            t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', t);
    })();
</script>
<title>{{ $title ? $title.' · ' : '' }}{{ config('school.name') }}</title>
@isset($description)
    <meta name="description" content="{{ $description ?? config('school.description') }}">
@endisset
<meta name="theme-color" content="#104976">
<link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
@vite($entries)
