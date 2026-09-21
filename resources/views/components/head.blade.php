{{--
    Orchestrates the SEO head output. See the Head component, which pre-formats
    the title, the Twitter card default and the JSON-LD script blocks.

    This view is rendered through the BasekitLaravelSeo\Components\Head class
    component, which always supplies the resolved SeoData as $seo.

    @var \BasekitLaravel\BasekitLaravelSeo\SeoData $seo
    @var string|null $title
    @var string|null $twitterCard
    @var array<int, string> $schemas
--}}
@if ($seo !== null)
    @include('basekit-laravel-seo::partials.title')
    @include('basekit-laravel-seo::partials.description')
    @include('basekit-laravel-seo::partials.robots')
    @include('basekit-laravel-seo::partials.canonical')
    @include('basekit-laravel-seo::partials.alternates')
    @include('basekit-laravel-seo::partials.open-graph')
    @include('basekit-laravel-seo::partials.twitter')
    @include('basekit-laravel-seo::partials.json-ld')
@endif