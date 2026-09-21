@if ($seo->openGraph !== null)
    @if ($seo->openGraph->title !== null)
        <meta property="og:title" content="{{ $seo->openGraph->title }}">
    @endif
    @if ($seo->openGraph->description !== null)
        <meta property="og:description" content="{{ $seo->openGraph->description }}">
    @endif
    @if ($seo->openGraph->type !== null)
        <meta property="og:type" content="{{ $seo->openGraph->type }}">
    @endif
    @if ($seo->openGraph->url !== null)
        <meta property="og:url" content="{{ $seo->openGraph->url }}">
    @endif
    @if ($seo->openGraph->image !== null)
        <meta property="og:image" content="{{ $seo->openGraph->image }}">
    @endif
    @if ($seo->openGraph->siteName !== null)
        <meta property="og:site_name" content="{{ $seo->openGraph->siteName }}">
    @endif
@endif