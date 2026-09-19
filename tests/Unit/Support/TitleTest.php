<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\Title;

it('appends the suffix with the configured separator', function (): void {
    expect(Title::withSuffix('Laravel Development', 'Basekit'))
        ->toBe('Laravel Development | Basekit')
        ->and(Title::withSuffix('Laravel Development', 'Basekit', ' - '))
        ->toBe('Laravel Development - Basekit');
});

it('does not duplicate an existing suffix', function (): void {
    expect(Title::withSuffix('Laravel Development | Basekit', 'Basekit'))
        ->toBe('Laravel Development | Basekit');
});

it('ignores blank suffixes and returns blank titles unchanged', function (): void {
    expect(Title::withSuffix('A title', ''))->toBe('A title')
        ->and(Title::withSuffix('A title', '  '))->toBe('A title')
        ->and(Title::withSuffix(null, 'Basekit'))->toBeNull()
        ->and(Title::withSuffix('', 'Basekit'))->toBe('');
});

it('trims surrounding whitespace from titles and suffixes', function (): void {
    expect(Title::withSuffix('  A title  ', '  Basekit  '))->toBe('A title | Basekit');
});
