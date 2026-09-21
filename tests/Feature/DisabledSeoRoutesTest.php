<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Tests\DisablesSeo;

uses(DisablesSeo::class);

it('does not register the sitemap route when the package is disabled', function (): void {
    $this->get('/sitemap.xml')->assertStatus(404);
});

it('does not register the robots route when the package is disabled', function (): void {
    $this->get('/robots.txt')->assertStatus(404);
});
