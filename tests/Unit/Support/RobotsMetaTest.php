<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\RobotsMeta;

it('normalizes directives to lowercase, trimmed and deduplicated', function (): void {
    $robots = RobotsMeta::from([' NOINDEX ', 'nofollow', 'noindex']);

    expect($robots->directives)->toBe(['noindex', 'nofollow'])
        ->and($robots->toString())->toBe('noindex, nofollow');
});

it('builds from a comma-separated string', function (): void {
    $robots = RobotsMeta::from('noindex, nofollow');

    expect($robots->toString())->toBe('noindex, nofollow');
});

it('defaults to an empty directive set', function (): void {
    expect((new RobotsMeta)->toString())->toBe('')
        ->and(RobotsMeta::make()->directives)->toBe([])
        ->and(RobotsMeta::from()->toString())->toBe('');
});

it('supports fluent directive composition and removal', function (): void {
    $robots = RobotsMeta::make()->withDirective('noindex')->withDirective('nofollow');

    expect($robots->has('noindex'))->toBeTrue()
        ->and($robots->isNoindex())->toBeTrue()
        ->and($robots->isNofollow())->toBeTrue()
        ->and($robots->without('noindex')->toString())->toBe('nofollow');
});

it('ignores blank directives', function (): void {
    $robots = RobotsMeta::from(['', ' ', 'noindex', '']);

    expect($robots->directives)->toBe(['noindex']);
});

it('is immutable', function (): void {
    $base = RobotsMeta::from('index');

    $changed = $base->withDirective('nofollow');

    expect($base->toString())->toBe('index')
        ->and($changed->toString())->toBe('index, nofollow');
});

it('serializes to a directive list', function (): void {
    expect(RobotsMeta::from('noindex, nofollow')->toArray())->toBe(['noindex', 'nofollow']);
});
