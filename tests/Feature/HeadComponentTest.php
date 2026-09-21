<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\SeoData;
use BasekitLaravel\BasekitLaravelSeo\Support\OrganizationSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

it('renders the default metadata without explicit values', function (): void {
    $view = $this->blade('<x-basekit-laravel-seo::head />');

    $view->assertSee('<link rel="canonical" href="https://example.test/">', false)
        ->assertSee('<meta property="og:site_name" content="Basekit">', false)
        ->assertDontSee('<title>', false)
        ->assertDontSee('<meta name="description"', false)
        ->assertDontSee('<meta name="robots"', false)
        ->assertDontSee('rel="alternate"', false)
        ->assertDontSee('property="og:title"', false)
        ->assertDontSee('property="og:url"', false)
        ->assertDontSee('property="og:image"', false)
        ->assertDontSee('name="twitter:', false)
        ->assertDontSee('application/ld+json', false);
});

it('renders the title with the configured suffix and escapes its value', function (): void {
    config()->set('basekit-laravel-seo.defaults.title_suffix', 'Basekit');

    seo()->title('Shop <Now>');

    $view = $this->blade('<x-basekit-laravel-seo::head />');

    $view->assertSee('<title>Shop &lt;Now&gt; | Basekit</title>', false)
        ->assertDontSee('<Now>', false);

    expect(seo()->data()->title)->toBe('Shop <Now>');
});

it('does not duplicate the title suffix', function (): void {
    config()->set('basekit-laravel-seo.defaults.title_suffix', 'Basekit');

    seo()->title('About | Basekit');

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSee('<title>About | Basekit</title>', false)
        ->assertDontSee('| Basekit | Basekit', false);
});

it('omits the title and description tags when they are not set', function (): void {
    $view = $this->blade('<x-basekit-laravel-seo::head />');

    $view->assertDontSee('<title>', false)
        ->assertDontSee('<meta name="description"', false);
});

it('renders the meta description and escapes its value', function (): void {
    seo()->description('A <b>bold</b> description');

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSee('<meta name="description" content="A &lt;b&gt;bold&lt;/b&gt; description">', false)
        ->assertDontSee('<b>bold</b>', false);
});

it('renders page-level robots directives', function (): void {
    seo()->robots('noindex, nofollow');

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('omits the robots tag when no directives are configured', function (): void {
    seo()->robots([]);

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertDontSee('<meta name="robots"', false);
});

it('omits the canonical tag when no safe canonical can be established', function (): void {
    config()->set('app.url', '');

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertDontSee('rel="canonical"', false);
});

it('renders hreflang alternates in registration order', function (): void {
    seo()
        ->alternate('en', 'https://example.test/en')
        ->alternate('hu', 'https://example.test/hu')
        ->alternate('x-default', 'https://example.test/');

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSeeInOrder([
            '<link rel="alternate" hreflang="en" href="https://example.test/en">',
            '<link rel="alternate" hreflang="hu" href="https://example.test/hu">',
            '<link rel="alternate" hreflang="x-default" href="https://example.test/">',
        ], false);
});

it('renders each Open Graph property that is present', function (): void {
    seo()->openGraph([
        'title' => 'OG title',
        'description' => 'OG description',
        'type' => 'website',
        'url' => 'https://example.test/path?page=1',
        'image' => 'https://example.test/og.png',
        'site_name' => 'Acme',
    ]);

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSeeInOrder([
            '<meta property="og:title" content="OG title">',
            '<meta property="og:description" content="OG description">',
            '<meta property="og:type" content="website">',
            '<meta property="og:url" content="https://example.test/path?page=1">',
            '<meta property="og:image" content="https://example.test/og.png">',
            '<meta property="og:site_name" content="Acme">',
        ], false);
});

it('renders each Twitter property that is present with the configured card', function (): void {
    seo()->twitter([
        'site' => '@acme',
        'creator' => '@gergo',
        'title' => 'TW title',
        'description' => 'TW description',
        'image' => 'https://example.test/tw.png',
    ]);

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSeeInOrder([
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:site" content="@acme">',
            '<meta name="twitter:creator" content="@gergo">',
            '<meta name="twitter:title" content="TW title">',
            '<meta name="twitter:description" content="TW description">',
            '<meta name="twitter:image" content="https://example.test/tw.png">',
        ], false);
});

it('honours an explicit Twitter card type', function (): void {
    seo()->twitter(['card' => 'summary', 'title' => 'TW title']);

    $this->blade('<x-basekit-laravel-seo::head />')
        ->assertSee('<meta name="twitter:card" content="summary">', false);
});

it('renders a single JSON-LD schema object', function (): void {
    seo()->schema(WebPageSchema::make()->name('Home'));

    $html = (string) $this->blade('<x-basekit-laravel-seo::head />');

    expect(substr_count($html, '<script type="application/ld+json">'))->toBe(1)
        ->and(decode_ld_json($html)['@type'])->toBe('WebPage')
        ->and(decode_ld_json($html)['name'])->toBe('Home');
});

it('renders every registered schema as its own script block', function (): void {
    seo()
        ->schema(WebPageSchema::make()->name('Home'))
        ->schema(OrganizationSchema::make()->name('Acme'));

    $html = (string) $this->blade('<x-basekit-laravel-seo::head />');

    expect(substr_count($html, '<script type="application/ld+json">'))->toBe(2)
        ->and(substr_count($html, '</script>'))->toBe(2);
});

it('escapes array-based schemas so they cannot break out of the script element', function (): void {
    seo()->schema([
        '@type' => 'Thing',
        'name' => '</script><script>alert(1)</script>',
    ]);

    $html = (string) $this->blade('<x-basekit-laravel-seo::head />');

    expect($html)->not->toContain('</script><script>alert(1)</script>')
        ->and(decode_ld_json($html)['name'])->toBe('</script><script>alert(1)</script>');
});

it('escapes schema objects so they cannot break out of the script element', function (): void {
    seo()->schema(WebPageSchema::make()->headline('</script><script>alert(2)</script>'));

    $html = (string) $this->blade('<x-basekit-laravel-seo::head />');

    expect($html)->not->toContain('</script><script>alert(2)</script>')
        ->and(decode_ld_json($html)['headline'])->toBe('</script><script>alert(2)</script>');
});

it('renders an explicit SeoData attribute instead of resolving the manager', function (): void {
    $seo = SeoData::make()
        ->withTitle('Override')
        ->withDescription('Explicit');

    $this->blade('<x-basekit-laravel-seo::head :data="$seo" />', ['seo' => $seo])
        ->assertSee('<title>Override</title>', false)
        ->assertSee('<meta name="description" content="Explicit">', false)
        ->assertDontSee('rel="canonical"', false);
});

it('renders a complete head in the documented order', function (): void {
    config()->set('basekit-laravel-seo.defaults.title_suffix', 'Basekit');

    seo()
        ->title('Shop <Now>')
        ->description('Everything')
        ->robots('noindex, nofollow')
        ->canonicalUrl('https://acme.test/products')
        ->alternate('en', 'https://acme.test/en')
        ->alternate('x-default', 'https://acme.test/')
        ->openGraph([
            'title' => 'OG title',
            'description' => 'OG description',
            'type' => 'website',
            'url' => 'https://acme.test/products',
            'image' => 'https://acme.test/og.png',
            'site_name' => 'Acme',
        ])
        ->twitter([
            'site' => '@acme',
            'creator' => '@gergo',
            'title' => 'TW title',
            'description' => 'TW description',
            'image' => 'https://acme.test/tw.png',
        ])
        ->schema(WebPageSchema::make()->name('Home'));

    $view = $this->blade('<x-basekit-laravel-seo::head />');

    $view->assertSeeInOrder([
        '<title>Shop &lt;Now&gt; | Basekit</title>',
        '<meta name="description" content="Everything">',
        '<meta name="robots" content="noindex, nofollow">',
        '<link rel="canonical" href="https://acme.test/products">',
        '<link rel="alternate" hreflang="en" href="https://acme.test/en">',
        '<link rel="alternate" hreflang="x-default" href="https://acme.test/">',
        '<meta property="og:title" content="OG title">',
        '<meta property="og:description" content="OG description">',
        '<meta property="og:type" content="website">',
        '<meta property="og:url" content="https://acme.test/products">',
        '<meta property="og:image" content="https://acme.test/og.png">',
        '<meta property="og:site_name" content="Acme">',
        '<meta name="twitter:card" content="summary_large_image">',
        '<meta name="twitter:site" content="@acme">',
        '<meta name="twitter:creator" content="@gergo">',
        '<meta name="twitter:title" content="TW title">',
        '<meta name="twitter:description" content="TW description">',
        '<meta name="twitter:image" content="https://acme.test/tw.png">',
        '<script type="application/ld+json">',
    ], false);

    $view->assertDontSee('<Now>', false);
});

it('emits nothing when the package is disabled', function (): void {
    config()->set('basekit-laravel-seo.enabled', false);

    $html = (string) $this->blade('<x-basekit-laravel-seo::head />');

    expect($html)->toBe('');
});

it('emits nothing when disabled even for an explicit SeoData', function (): void {
    config()->set('basekit-laravel-seo.enabled', false);

    $seo = SeoData::make()->withTitle('Hidden');

    $html = (string) $this->blade('<x-basekit-laravel-seo::head :data="$seo" />', ['seo' => $seo]);

    expect($html)->toBe('');
});

it('renders a custom head view configured through views.head', function (): void {
    $name = 'custom-head-'.uniqid();
    $path = sys_get_temp_dir().'/'.$name.'.blade.php';

    file_put_contents($path, '<custom-head>{{ $seo !== null ? "ok" : "fail" }}</custom-head>');

    try {
        config()->set('basekit-laravel-seo.views.head', $name);

        $this->blade('<x-basekit-laravel-seo::head />')
            ->assertSee('<custom-head>ok</custom-head>', false)
            ->assertDontSee('<title>', false);
    } finally {
        @unlink($path);
    }
});

it('lets published partials override the packaged head views', function (): void {
    $vendorDir = resource_path('views/vendor/basekit-laravel-seo/partials');

    try {
        if (! is_dir($vendorDir)) {
            mkdir($vendorDir, 0755, true);
        }

        file_put_contents($vendorDir.'/title.blade.php', '<published>{{ $title }}</published>');

        $this->app->forgetInstance('view');
        $this->app->forgetInstance('view.finder');

        seo()->title('Published Title');

        $this->blade('<x-basekit-laravel-seo::head />')
            ->assertSee('<published>Published Title</published>', false)
            ->assertDontSee('<title>Published Title</title>', false);
    } finally {
        $path = resource_path('views/vendor/basekit-laravel-seo');

        if (is_dir($path)) {
            unlink($path.'/partials/title.blade.php');
            rmdir($path.'/partials');
            rmdir($path);
        }
    }
});
