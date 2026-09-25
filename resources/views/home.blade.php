<x-layouts.public :demo="$demo">
    @include('home.hero')
    @include('home.quick-links')
    @include('home.levels')

    @if ($posts->isNotEmpty() || $events->isNotEmpty())
        @include('home.news')
    @endif

    @if ($photos->isNotEmpty())
        @include('home.gallery')
    @endif

    @include('home.support')
    @include('home.admissions')
</x-layouts.public>
