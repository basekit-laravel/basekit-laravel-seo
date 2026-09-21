<?php

declare(strict_types=1);

it('serves robots.txt as plain text with the expected directives', function (): void {
    $response = $this->get('/robots.txt');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $content = $response->getContent();

    expect($content)->toContain('User-agent: *')
        ->toContain('Allow: /')
        ->toContain('Sitemap: https://example.test/sitemap.xml')
        ->not->toContain('<html');
});

it('derives the sitemap declaration from the trusted canonical origin', function (): void {
    config()->set('basekit-laravel-seo.canonical.base_url', 'https://canonical.test');

    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertSeeInOrder(['User-agent: *', 'Allow: /', 'Sitemap: https://canonical.test/sitemap.xml']);
});

it('honours an explicitly configured sitemap declaration', function (): void {
    config()->set('basekit-laravel-seo.robots.sitemap', 'https://cdn.example.test/sitemap-index.xml');

    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertSee('Sitemap: https://cdn.example.test/sitemap-index.xml');
});

it('renders configured disallow rules', function (): void {
    config()->set('basekit-laravel-seo.robots.disallow', ['/admin', '/private']);

    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertSee("Disallow: /admin\nDisallow: /private", false);
});

it('omits the sitemap declaration when no trusted origin can be established', function (): void {
    config()->set('app.url', '');
    config()->set('basekit-laravel-seo.canonical.base_url', null);

    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertDontSee('Sitemap:');
});

it('never emits attacker-controlled directives through configuration', function (): void {
    config()->set('basekit-laravel-seo.robots', [
        'user_agents' => ['*'],
        'allow' => ['/'],
        'disallow' => ["/admin\r\nDisallow: /everything"],
        'sitemap' => null,
    ]);

    $content = $this->get('/robots.txt')->getContent();

    expect($content)->toContain('Disallow: /admin')
        ->not->toContain('Disallow: /everything')
        ->toContain('User-agent: *');
});

it('uses the configured origin even under a poisoned Host header', function (): void {
    $content = $this->get('/robots.txt', ['Host' => 'attacker.example'])->getContent();

    expect($content)->toContain('Sitemap: https://example.test/sitemap.xml')
        ->not->toContain('attacker.example');
});

it('uses an allow-listed request host as the sitemap origin', function (): void {
    config()->set('app.url', '');
    config()->set('basekit-laravel-seo.canonical.trusted_hosts', ['example.test']);

    $this->get('http://example.test/robots.txt')
        ->assertStatus(200)
        ->assertSee('Sitemap: http://example.test/sitemap.xml', false);

    $this->get('http://attacker.example/robots.txt')
        ->assertStatus(200)
        ->assertDontSee('Sitemap:');
});
