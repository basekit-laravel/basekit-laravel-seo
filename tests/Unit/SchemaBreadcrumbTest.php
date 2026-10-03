<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

it('accepts schema objects as breadcrumb list items', function (): void {
    $schema = WebPageSchema::make()->breadcrumb([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home'],
        WebPageSchema::make()->name('Nested'),
    ]);

    $items = $schema->toArray()['breadcrumb']['itemListElement'];

    // Plain arrays pass through untouched, schema objects are serialised.
    expect($items[0])->toBe(['@type' => 'ListItem', 'position' => 1, 'name' => 'Home'])
        ->and($items[1]['@type'])->toBe('WebPage')
        ->and($items[1]['name'])->toBe('Nested');
});
