@if ($seo->twitter !== null)
    @if (!empty($twitterCard))
        <meta name="twitter:card" content="{{ $twitterCard }}">
    @endif
    @if ($seo->twitter->site !== null)
        <meta name="twitter:site" content="{{ $seo->twitter->site }}">
    @endif
    @if ($seo->twitter->creator !== null)
        <meta name="twitter:creator" content="{{ $seo->twitter->creator }}">
    @endif
    @if ($seo->twitter->title !== null)
        <meta name="twitter:title" content="{{ $seo->twitter->title }}">
    @endif
    @if ($seo->twitter->description !== null)
        <meta name="twitter:description" content="{{ $seo->twitter->description }}">
    @endif
    @if ($seo->twitter->image !== null)
        <meta name="twitter:image" content="{{ $seo->twitter->image }}">
    @endif
@endif