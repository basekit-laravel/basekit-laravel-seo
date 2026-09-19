<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\Robots;

it('builds robots.txt content from defaults', function (): void {
    $robots = Robots::make()
        ->disallow(['/private'])
        ->sitemap('https://example.test/sitemap.xml');

    expect($robots->toString())->toBe(implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /private',
        '',
        'Sitemap: https://example.test/sitemap.xml',
        '',
    ]));
});

it('honours config-driven robots directives', function (): void {
    config()->set('basekit-laravel-seo.robots', [
        'user_agents' => ['Googlebot'],
        'allow' => ['/'],
        'disallow' => ['/admin'],
    ]);

    $robots = Robots::make(config('basekit-laravel-seo.robots'));

    expect($robots->toString())->toContain('User-agent: Googlebot')
        ->toContain('Disallow: /admin')
        ->toContain('Allow: /');
});

it('returns a text/plain response', function (): void {
    $response = Robots::make()->sitemap('https://example.test/sitemap.xml')->response();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('text/plain')
        ->and($response->getContent())->toContain('User-agent: *');
});
