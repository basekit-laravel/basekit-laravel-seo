@foreach ($seo->alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate->hreflang }}" href="{{ $alternate->url }}">
@endforeach