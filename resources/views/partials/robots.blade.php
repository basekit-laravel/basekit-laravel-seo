@if ($seo->robots !== null && $seo->robots->directives !== [])
    <meta name="robots" content="{{ $seo->robots->toString() }}">
@endif