<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\ArticleSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\OrganizationSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\WebSiteSchema;

it('renders an Organization schema as valid JSON-LD markup', function (): void {
    $html = OrganizationSchema::make()
        ->name('Acme')
        ->description('We make things.')
        ->url('https://acme.test')
        ->email('hello@acme.test')
        ->render();

    $json = decode_ld_json($html);

    expect($json['@context'])->toBe('https://schema.org')
        ->and($json['@type'])->toBe('Organization')
        ->and($json['name'])->toBe('Acme')
        ->and($json['email'])->toBe('hello@acme.test')
        ->and($html)->toStartWith('<script type="application/ld+json">')
        ->and($html)->toEndWith('</script>');
});

it('omits unset optional organization attributes', function (): void {
    $json = decode_ld_json(OrganizationSchema::make()->name('Acme')->render());

    expect(isset($json['email']))->toBeFalse()
        ->and(isset($json['logo']))->toBeFalse()
        ->and(isset($json['sameAs']))->toBeFalse();
});

it('builds an Article schema and supports overriding its type', function (): void {
    $article = ArticleSchema::make()
        ->type('BlogPosting')
        ->headline('A headline')
        ->description('A description')
        ->datePublishedFrom(now()->toDateTimeString())
        ->author('Ada Lovelace')
        ->publisher(OrganizationSchema::make()->name('Acme')->url('https://acme.test'));

    $json = decode_ld_json($article->render());

    expect($json['@type'])->toBe('BlogPosting')
        ->and($json['headline'])->toBe('A headline')
        ->and($json['author']['@type'])->toBe('Person')
        ->and($json['publisher']['@type'])->toBe('Organization');
});

it('builds a WebPage schema with a breadcrumb list', function (): void {
    $json = decode_ld_json(
        WebPageSchema::make()
            ->name('Services')
            ->url('https://acme.test/services')
            ->breadcrumb([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://acme.test'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => 'https://acme.test/services'],
            ])
            ->render(),
    );

    expect($json['breadcrumb']['@type'])->toBe('BreadcrumbList')
        ->and($json['breadcrumb']['itemListElement'])->toHaveCount(2);
});

it('builds a WebSite schema with a publisher and search action', function (): void {
    $json = decode_ld_json(
        WebSiteSchema::make()
            ->name('Acme')
            ->url('https://acme.test')
            ->publisher(OrganizationSchema::make()->name('Acme'))
            ->potentialAction([
                'target' => 'https://acme.test/search?q={search_term_string}',
                'query_input' => 'required name=search_term_string',
            ])
            ->render(),
    );

    expect($json['publisher']['@type'])->toBe('Organization')
        ->and($json['potentialAction']['@type'])->toBe('SearchAction');
});
