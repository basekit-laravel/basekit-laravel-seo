# Architecture Audit — basekit-laravel-seo

> Status: **proposal — no code changes made**
> Date: 2026-09-19
> Scope: inspection of the current repository and a target architecture for the Basekit Laravel ecosystem.

This document is the result of a read-only audit. It documents what exists today, classifies the
current public API, and proposes a target architecture. Nothing in this file has been implemented;
design snippets are illustrative and are not part of the package yet.

---

## 1. Current architecture

### 1.1 Repository state

- Single commit ("Initial commit"). Working tree clean.
- No `README.md`, no `LICENSE`, no `CHANGELOG`, no `docs/`, no GitHub Actions workflow, no
  `pint.json` (Pint runs with its defaults).
- `routes/`, `database/migrations/`, `tests/Feature/`, `tests/TestSupport/` exist but are empty.
- Composer: `basekit-laravel/basekit-laravel-seo`, MIT, PHP `^8.3|^8.4|^8.5`, Laravel `^13`.

### 1.2 Runtime dependencies

`require`:

| Package | Purpose |
|---|---|
| `illuminate/contracts` | `Response`, container contracts, framework hooks |
| `illuminate/http` | `Illuminate\Http\Response` |
| `illuminate/support` | config/`env()` helpers, service provider base |

`require-dev`: `laravel/pint`, `larastan/larastan`, `mockery/mockery`, `nunomaduro/collision`,
`orchestra/testbench` 11.x, `pestphp/pest` 5.x, `pestphp/pest-plugin-laravel`,
`phpstan/extension-installer`, `phpstan/phpstan-deprecation-rules`.

The dependency philosophy is intentionally minimal and third-party-free. This is the right stance
and should be preserved (see §13).

### 1.3 Classes

```
src/
├── BasekitLaravelSeoServiceProvider.php
├── Services/
│   └── Sitemap.php                 # array-of-URLs → rendered XML sitemap/response
└── Support/
    ├── Schema.php                  # abstract JSON-LD base (type, toArray/toJson/render)
    ├── ArticleSchema.php           # Article / BlogPosting / CreativeWork
    ├── OrganizationSchema.php      # Organization
    ├── WebPageSchema.php           # WebPage + inline BreadcrumbList
    └── WebSiteSchema.php           # WebSite + SearchAction
```

`Robots.php`, `Sitemap.php`, and the schema builders are the entire surface. There are **no**
`Metadata`/`SeoData` types, no head renderer, no resolver pipeline, no facade/helper, no routes,
no commands, no migrations, no contracts/interfaces.

### 1.4 Service provider

- `register()`: merges `config/basekit-laravel-seo.php` (`basekit-laravel-seo` key), binds
  `Sitemap` as a singleton.
- `boot()`: loads the `basekit-laravel-seo` view namespace; publishes views
  (`basekit-laravel-seo-views`) and config (`basekit-laravel-seo-config`).
- Registers **no routes**, commands, middleware, or Blade components.

### 1.5 Config (`config/basekit-laravel-seo.php`)

- `enabled` — documented as a master switch that also gates "automatic sitemap/robots routes". **No
  such routes exist today**, so the flag currently does nothing.
- `defaults` — `site_name`, `title_suffix`, `description`, `og_image`, `locale`, `url`. These are
  intended as metadata fallbacks, but nothing consumes them (no metadata rendering exists).
- `robots` — `user_agents`, `allow`, `disallow`, `sitemap`; consumed by `Robots::make()`.
- `views` — `sitemap` view name (`seo.sitemap`), overridable.

### 1.6 Views

Single view `resources/views/seo/sitemap.blade.php` — a static `<urlset>` template iterating
`$urls` (arrays with `loc`, optional `lastmod`, `changefreq`, `priority`). No robots template.

### 1.7 Tests

Pest, 11 tests / 33 assertions, all green (verified: `composer test`), PHPStan level 6 clean
(`composer analyse`), Pint clean (`composer lint`). Suites: `tests/Unit` (Sitemap, Schema, Robots).
`tests/Feature` is empty. Helper `decode_ld_json()` in `tests/Pest.php`.

- Sitemap: well-formed urlset, default priority, optional-field omission, `application/xml`.
- Schema: Organization, Article type override, WebPage breadcrumb, WebSite publisher/SearchAction.
- Robots: defaults, config-driven directives, `text/plain` response.

### 1.8 Tooling / agent configuration

- `phpstan.neon.dist`: level 6, paths `src`, cache `.phpstan.cache`.
- `phpunit.xml`: Unit + Feature suites, array cache, sqlite-default env for Testbench.
- `testbench.yaml`: registers the package provider.
- `AGENTS.md`, `CLAUDE.md`, `.github/copilot-instructions.md`: consistent package-development rules
  (inspect first, minimal change, run the real checks, report honestly).
- `.opencode/agents/*` (architect, package-developer, performance, reviewer, security, testing) and
  `.opencode/skills/*` (code-review, laravel-package, package-development, release, testing).

---

## 2. Current public API

### 2.1 Classification

| Symbol | Location | Kind |
|---|---|---|
| `BasekitLaravelSeoServiceProvider` | `src/` | **public API** (package discovery entry point) |
| `Services\Sitemap` | `src/Services/Sitemap.php` | **public API** |
| `Sitemap::__construct(?string $view)` | — | **public API** |
| `Sitemap::view(array): View` | — | **public API** |
| `Sitemap::response(array): Response` | — | **public API** |
| `Support\Robots` | `src/Support/Robots.php` | **public API** |
| `Robots::__construct(...)` / `make()` / `disallow()` / `sitemap()` / `toString()` / `response()` | — | **public API** |
| `Support\Schema` (abstract) | `src/Support/Schema.php` | **public API** (extension base) |
| `Schema::make()` / `toArray()` / `toJson()` / `render()` / `__toString()` | — | **public API** |
| `Schema::attributes()` / `mapNested()` / `$type` | — | protected, **internal** |
| `ArticleSchema`, `OrganizationSchema`, `WebPageSchema`, `WebSiteSchema` | `src/Support/` | **public API** |
| Config key `basekit-laravel-seo.*` | `config/` | **public API** |
| View namespace `basekit-laravel-seo::` + `seo.sitemap` view + publish tags | provider | **public API** |
| Container binding `Sitemap::class` (singleton) | provider | public extension point (implementation detail of provider) |
| `decode_ld_json()` helper | `tests/Pest.php` | test-only |

### 2.2 What is *not* public today

No facades (`src/Facades/` absent), no helper functions (`Helper::` absent), no Blade
directives/components, no routes, no contracts/interfaces, no events, no commands.

### 2.3 Contract obligations

Because the package will become an ecosystem dependency, existing public symbols are frozen. New
work must add API alongside the current surface rather than redefine it. Removing or renaming
`Sitemap`, `Robots`, `Schema`, or the four schema classes requires a major version and a migration
path.

---

## 3. Package responsibility

### 3.1 Mandate

The package must provide **generic, framework-native SEO infrastructure**:

- a metadata model (`SeoData`) for title/description/canonical/robots/OG/Twitter/alternates,
- layered, pluggable resolution,
- safe head rendering (tags + JSON-LD),
- structured-data primitives that third parties can extend,
- XML sitemap infrastructure with provider-based URL contribution,
- robots.txt infrastructure that is configurable and never domain-specific.

Domain knowledge about Blog / Pages / Content / Products / users / CMS / Livewire / Filament does
**not** belong here. Those packages integrate through contracts/resolvers/providers.

### 3.2 Does the current implementation follow this?

Mostly, with caveats:

- No fixtures from blog/pages/content — good. Zero coupling to other Basekit packages or Livewire/
  Filament.
- `ArticleSchema` is schema.org's *Article* type, a generic web trope, not "blog domain logic". It
  is acceptable, but its `type('BlogPosting')` escape hatch hints that domain types live elsewhere.
  Keep these five "core" schemas; let blog packages supply richer Article/BlogPosting variants.
- **Gap:** the package declares "centralized metadata" in its Composer description but ships *no*
  metadata model or rendering. It is currently a "schemas + robots + sitemap printer", not an SEO
  framework.

**Conclusion:** the responsibility boundary is right; the surface is incomplete.

---

## 4. SEO data model — `SeoData`

A central immutable value object is warranted. Every rendering concern (head, OG, Twitter,
alternates, structured data), every resolver, and every consumer integration should speak in terms
of this object.

### 4.1 Recommended design

`final readonly class SeoData` (PHP 8.3 `readonly`), created by named constructors/withers. The
builder/facade (`seo()`) is a separate *mutable* draft that resolves through the pipeline and
freezes into an immutable `SeoData`.

### 4.2 Fields

| Field | Type | Nullability | Default | Notes |
|---|---|---|---|---|
| `title` | `string` | nullable | `null` | plain text; suffixing (`title_suffix`) is a render/merge step, not stored |
| `description` | `string` | nullable | `null` | plain text |
| `canonicalUrl` | `Url` (or `string`) | nullable | `null` | normalized/absolute; see §10 |
| `robots` | `Robots` value (enum set + extras) | nullable | `null` | `noindex`/`nofollow`/`noarchive`/… + custom directives; distinct from `Support\Robots` (robots.txt builder) |
| `ogTitle` | `string` | nullable | `null` | falls back to `title` at render |
| `ogDescription` | `string` | nullable | `null` | falls back to `description` |
| `ogType` | `string` | nullable | `'website'` | `website` | `article` | `product` | … |
| `ogImage` | `string` | nullable | config default | absolute URL |
| `ogUrl` | `string` | nullable | canonical | absolute URL |
| `ogSiteName` | `string` | nullable | site default | |
| `twitterCard` | `string` | nullable | `'summary_large_image'` | |
| `twitterSite` | `string` | nullable | `null` | `@handle` |
| `twitterCreator` | `string` | nullable | `null` | `@handle` |
| `twitterTitle` / `twitterDescription` / `twitterImage` | `string` | nullable | fall back to og/title | |
| `locale` | `string` | nullable | config default | may differ from `app.locale` |
| `alternates` | `list<Alternate{hreflang, url}>` | always set | `[]` | `x-default` is a normal entry |
| `schemas` | `list<Schema|array>` | always set | `[]` | structured data; see §7 |

### 4.3 Semantics

- **Nullability = "not provided at this layer"** — allows merging lower layers; rendering skips
  nulls.
- **Defaults** come from config/package layer, applied during resolution (§5), not inside
  `SeoData`. The value object stays dumb.
- **Validation** is deliberately light at construction (it is a DTO): normalize/trim strings,
  validate URL scheme (`http`/`https`) and reject control characters in canonical/alternate URLs,
  enforce single-line robots directives. Strong validation belongs in resolvers/consumers.
- **Serialization**: `toArray()` (for caching, middleware, test assertions) plus `jsonSerialize()`
  for JSON-LD graph reuse. `__toString()` is *not* appropriate (ambiguous) — rendering goes through
  dedicated renderers/views.
- **Extensibility**:
  - `with(array $custom)` / pass-through attribute bag for keys the core doesn't model (e.g.
    `al:ios`, Facebook app IDs).
  - Wither methods `withTitle()`, `withCanonicalUrl()`, … returning `$this|static` are enough; no
    inheritance chain needed (final class + composition).
  - OpenGraph/Twitter details stay as flat nullable fields (not nested objects) to keep
    serialization trivial; switch to nested value objects only if the model grows substantially.

---

## 5. SEO resolution

### 5.1 Layered pipeline

Target precedence (lowest → highest):

1. **Package defaults** — `basekit-laravel-seo.defaults.*`.
2. **Application/site defaults** — published config the app edits (same keys, app-owned values).
3. **Route/page defaults** — opt-in: resolvable via a registered page descriptor
   (`seo()->page(...)` or a route-scoped default provider).
4. **Model/content-derived values** — consumer models/packages provide `SeoData` through a resolver.
5. **Explicit per-request overrides** — `seo()->title(...)->description(...)`.

Merge rule: non-null wins; lists concatenate (schemas, alternates); `robots` directives union.

### 5.2 Public surface

```php
seo()                              // SeoManager (container singleton, facade-backed)
seo()->title('Home')->description('...')   // draft/override layer
seo()->for($model|$content)        // resolve layer-4 values
seo()->render() / seo()->data()    // final immutable SeoData
```

### 5.3 The resolver contract

Keep it Eloquent-agnostic — subjects are `mixed`:

```php
interface SeoResolver
{
    public function supports(mixed $subject): bool;
    public function resolve(mixed $subject): ?SeoData;
}
```

- Consumers (or `basekit-laravel-blog`, etc.) bind their resolver to the container under a shared
  tag (`basekit-laravel-seo.resolvers`); the `SeoManager` collects tagged resolvers lazily.
- `seo()->for($model)` iterates resolvers, merges the first `supports()` match, then applies the
  explicit override layer.
- A small `EloquentResolver` convenience (trait or contract such as `IsSeoable`) is a **separate,
  optional** helper provided by an integration package — not a required dependency here (see §18).

Only introduce this single abstraction; anything more (resolver chains, middleware-driven
resolution) is speculative.

---

## 6. Rendering architecture

### 6.1 Primary mechanism

A Blade component:

```blade
<x-basekit-seo::head :data="$seoData ?? null" />
```

- Reads the current `seo()` state when called without props; accepts an explicit `SeoData`.
- Renders, from `SeoData`:
  - `<title>` (with optional `title_suffix` appended at render time)
  - `<meta name="description">`, `<meta name="robots">`, `<link rel="canonical">`
  - OpenGraph tags
  - Twitter/X card tags
  - `<link rel="alternate" hreflang="…">` + `x-default`
  - JSON-LD `<script>` blocks
- Each group is a small partial view under `resources/views/`, so consumers can publish and
  customize one group without forking the whole head.

### 6.2 Why a component (not a directive)

- Components are the idiomatic Laravel 11+/13 way; they compose (`@stack`/nested), accept props,
  are namespace-resolvable, and are testable via `component()->render()`.
- A directive (`@seoHead`) can be added later as a thin alias if ergonomics demand it — recommend
  **not** shipping both initially.

### 6.3 Safety

Every value passed through Blade `{{ }}` / attribute escaping; assets/URLs validated during
resolution (§10); JSON-LD hardening in §7.4/§15. The component is stateless and safe to render
multiple times.

---

## 7. Structured data architecture

### 7.1 Principle

Ship a small, well-formed core; never become a schema framework. Core value objects: **WebSite,
WebPage, Organization, Article, BreadcrumbList, Person** (Person and BreadcrumbList are missing
today and belong in the core). Additional schemas are supplied by consumers or future packages.

### 7.2 Form

- Keep class-based builders as today (they read well and are already public API). Move
  `BreadcrumbList` out of `WebPageSchema::breadcrumb()` into its own `BreadcrumbListSchema`.
- **Do not** build a generic "array-of-arbitrary-keys" framework; the `Schema` base already lets
  any package subclass and render.
- Provide the escape hatch that today prevents consumers from doing this: a way to render both
  `Schema` objects **and** plain arrays through the same head component.

### 7.3 Extension for consuming packages

Two complementary paths:

1. **Composition (recommended):** a blog package's resolver returns `SeoData` whose `schemas` list
   contains `ArticleSchema`/`BlogPostingSchema` instances. No registry needed — the `SeoData` is
   the integration point.
2. **Provider registry (optional, later):** `StructuredDataProvider { schemas(): iterable<Schema|array> }`
   tagged into the container, merged for the whole site (Organization/WebSite always present).
   Needed only once we want *site-wide* schema aggregation independent of a per-page resolver.

### 7.4 JSON-LD hardening

Today:

```php
json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
```

A value containing `</script>` (user-supplied title/description) terminates the script block and
breaks the page or enables injection. Because `JSON_UNESCAPED_SLASHES` keeps `/` literal, output
like `{"description":"</script><script>alert(1)</script>"}` is emitted verbatim.

Required change (P0): add `JSON_HEX_TAG` (encodes `<`/`>` → `\u003C`/`\u003E`), which neutralizes
`</script>` regardless of slashes, and keep `JSON_UNESCAPED_UNICODE`. Same hardening applies to any
`application/ld+json` block the package renders.

---

## 8. Sitemap architecture

### 8.1 Today

`Sitemap` renders an array of URL arrays into a single `<urlset>`. No providers, no routes, no
caching, no index, no alternates, no lastmod normalization. Input shape is an undocumented mixed
array contract.

### 8.2 Target

**Provider/registry system** so other packages contribute URLs:

```
SEO Sitemap
├── Pages provider      (basekit-laravel-pages)
├── Blog provider       (basekit-laravel-blog)
├── Product provider    (e-commerce package)
└── Application provider (consumer AppServiceProvider)
```

**Contracts:**

```php
interface SitemapProvider
{
    /** @return iterable<SitemapEntry> */
    public function urls(int $limit = null, int $offset = null): iterable;
}

final readonly class SitemapEntry
{
    public string $loc;                  // absolute URL
    public ?string $lastmod;             // ISO-8601
    public ?string $changefreq;          // 'always|hourly|daily|weekly|monthly|yearly|never'
    public ?string $priority;            // '0.0'..'1.0'
    /** @var list<array{hreflang: string, href: string}> */
    public array $alternates = [];       // xhtml:link
}
```

- `Sitemap` service aggregates tagged `SitemapProvider` bindings (container tag
  `basekit-laravel-seo.sitemap-providers`) and renders **both** a single urlset and a **sitemap
  index**.
- Lazy iteration (`iterable`/generators) so providers stream results; no Eloquent coupling —
  providers may be backed by anything.
- **Splitting**: configurable max URLs per sitemap (spec: ≤ 50 000) and sitemap-index references
  to `.../sitemap-1.xml` … `sitemap-N.xml`. Default: generated only when a provider-set exceeds the
  limit (no premature complexity).
- **Routes** (registered only when `enabled`): `/seo/sitemap.xml` (index when needed, else urlset)
  and `/seo/sitemap-{n}.xml`; names/paths configurable; each nameable via
  `basekit-laravel-seo.sitemap.route`.
- **Caching**: render cost is cheap; *aggregation cost is not* (DB queries). Cache the rendered
  sitemap (or the resolved entry list) keyed by provider set + fingerprint, TTL from config, using
  the app cache store — **off by default**, enabled per environment. Cache tags if the app uses them.
- **XML escaping**: use an XML-aware escaper or `XMLWriter`; `{{ }}` (HTML entity escaping) is
  close but not identical for XML text nodes — pin it with tests rather than assume (see §15).
- **Empty input**: render a valid empty `<urlset>` (tests assert well-formedness).
- **`lastmod`**: normalize to ISO-8601; accept `DateTimeInterface`/`CarbonInterface`/string.

---

## 9. robots.txt architecture

### 9.1 Today

`Robots` builder + config directives; no route, no auto-sitemap URL, single directive set for all
user agents, `Sitemap:` line separated by a blank line.

### 9.2 Target

- **Dynamic route** `/robots.txt` (configurable path; `null` disables the route entirely so an app
  can own its robots.txt), registered when `enabled`.
- Builder extensions:
  - multiple user-agent **groups** (`Robots` gains per-agent blocks rather than one shared list),
  - an explicit **custom directives** list (e.g. `Clean-param`, `Crawl-delay`) — validated against
    line-injection (`\r`/`\n` stripped),
  - automatic `Sitemap:` line from the canonical base URL when a sitemap route exists,
  - `toString()` + `response()` preserved for BC.
- **No hard-coded application rules.** All default rules come from config; providers/packages can
  contribute extra rules later (final `Robots` built by merging config + registered contributions).

---

## 10. Canonical URL security

### 10.1 Threat

Trusting `request()->url()` / `Host`/`X-Forwarded-Host` lets an attacker craft
`Host: evil.example` and generate canonical/hreflang/sitemap URLs pointing at their domain
(dilution attacks, phishing, poisoning of sitemap caches).

### 10.2 Recommended strategy

Canonical/alternate/sitemap/robots URLs are resolved from a trusted base, in order:

1. **Configured canonical base** — `basekit-laravel-seo.canonical.base_url`
   (e.g. `https://www.example.com`). Highest trust.
2. **`APP_URL` / `config('app.url')`** — fallback.
3. **Request-derived, only when both are absent and the host is verified:** scheme hardened to
   `https` (unless explicitly `http` configured), host from the request **only after** the
   `TrustHosts` middleware pattern is applied (app-level) — the package itself never blindly trusts
   the Host header.

Normalization of a generated canonical URL:

- scheme restricted to `http`/`https`; reject `javascript:`, `data:`, userinfo (`user@`), fragments;
- lowercase host, drop default ports, strip dot-segments (`/a/../b`);
- preserve a configurable path/query set; optionally strip tracking parameters (config list);
- strip trailing slash except for root;
- percent-encode/validate before emitting — or reject and fall back to the trusted base.

**Bundle this into a small `CanonicalUrl`/`Url` helper** used by canonical, alternates, OG `og:url`,
`sitemapEntry.loc`, and the robots `Sitemap:` line, so the trust rule lives in one place.

---

## 11. Internationalization

- `SeoData.locale` — from config default, `app.locale`, or explicit per-page value. No dependency
  on any localization package.
- **Alternates** are a first-class `SeoData` field: `seo()->alternate('de', $url)` /
  `seo()->alternate('x-default', $url)`, rendered as `<link rel="alternate" hreflang="…">`.
- Consumers register alternate URLs themselves (resolver or explicit builder); the package only
  renders and validates them (same URL trust rules as §10).
- **Never auto-generate** alternates — the mapping between locales and URLs is domain knowledge.

---

## 12. SEO storage

Three options considered:

| Option | Description | Fit |
|---|---|---|
| A. Consumer-owned columns | The model itself stores SEO fields; a resolver maps them → `SeoData` | Least coupled; package ships *no* migrations; fully portable |
| B. Central polymorphic model | A `seo` table + `seoable` morph target, managed by this package | Max convenience, max coupling; forces a schema on every consumer; migration + model + factory + admin surface to maintain |
| C. Hybrid | Core package = storage-free (Option A); an *optional companion* offers the central model | Best of both, at the cost of a second package |

**Recommendation: Option A for the core, and keep the door open for a future optional companion
(C).** This package must not ship migrations today. Rationale: (1) it is an ecosystem *foundation*
— consumers (blog/pages/e-commerce) already have their own persistence and typically want SEO
fields on their own tables; (2) a mandatory polymorphic model would make the "usable in ordinary
Laravel applications" goal heavier; (3) the resolver contract (§5) already gives Option A a clean
developer experience without a database.

If the ecosystem later shows strong demand for "edit SEO in one place", a
`basekit-laravel-seo-authoring` (or similar) companion can add the central model **without** a
breaking core change. This decision requires approval before any DB work (§19).

---

## 13. Dependencies

- **Missing declared runtime dependency:** `illuminate/view`. Source references
  `Illuminate\View\View` and the `view()` helper, but `illuminate/view` is absent from `require`
  (it happens to be present at runtime only because every Laravel app ships it). Add it to
  `require` — it is framework-native, keeps the third-party-free philosophy, and makes the
  dependency graph truthful.
- Everything else in `require` is used and justified.
- **Do not add**: `basekit-laravel-ui`, content/blocks packages, Livewire, Filament, spatie/
  array-compatible SEO libs, webmozart/assert, etc. A few lines of stdlib/Illuminate code cover
  validation needs (see §4.3). Re-evaluate only if a hard requirement appears (e.g. locale
  detection) — and even then prefer optional/suggested integration.

---

## 14. Caching and performance

Where caching pays:

- **Sitemap aggregation** — the only expensive path (DB-driven providers). Cache the rendered
  output/entry list; low TTL default; off by default; keyed by provider set. **Recommended P1.**
- **Resolved `SeoData` per subject** — avoids re-running resolvers across a request (e.g. several
  renders). Per-request memoization, not persistent cache.

Where caching is unnecessary (avoid premature work):

- Schema/metadata **building** — cheap short-lived objects; do **not** cache.
- Canonical/URL generation — trivial; call fresh each time.
- JSON-LD encoding — cheap for a handful of blocks.

Performance notes for providers: encourage `SELECT` with `yield`; avoid N+1; resolvers must be
cheap (defer DB hits to `for()` only when invoked).

---

## 15. Security

### 15.1 Findings in the current code

| # | Finding | Severity | Location |
|---|---|---|---|
| S1 | JSON-LD `render()` doesn't neutralize `</script>` (`JSON_UNESCAPED_SLASHES` w/o `JSON_HEX_TAG`) — user-supplied strings can break out of the script block | **Major** | `Schema::toJson()` |
| S2 | Sitemap uses `{{ }}` (HTML entity escaping) for XML text nodes — works in practice for `&<>\'"`, but is unverified; needs explicit XML escaping or `XMLWriter` + tests | Minor | `seo/sitemap.blade.php` |
| S3 | No canonical/URL trust handling exists (nothing to fix yet, but the *absence* is the risk once metadata rendering lands) | — | n/a |
| S4 | Robots paths are emitted verbatim — a `\n` in a config/path value would inject fake directives | Minor | `Robots::toString()` |

None of these are exploitable as-shipped (no user-facing metadata rendering exists yet), but they
must be fixed **as part of** the P0 rendering work, not after.

### 15.2 Target guarantees

- **Escaping:** all head tags via Blade `{{ }}`/attribute escaping; JSON-LD via `JSON_HEX_TAG` +
  `JSON_UNESCAPED_UNICODE`; sitemap via XML-aware escaper; robots via single-line sanitization.
- **URLs:** scheme whitelist, no userinfo/fragments, trusted base only (§10).
- **Host headers:** package never trusts `Host`/`X-Forwarded-Host` for generation.
- **User metadata:** treated as *text*, escaped at every output point; images `src` validated
  (`http`/`https`); lengths may be capped by config.
- **JSON-LD:** always a valid JSON object; no bare arrays; `@context` pinned.

### 15.3 Recommended security tests

See §16 — dedicated test group for each guarantee above (S1-style breakouts, host poisoning,
`javascript:` URLs, CRLF in robots, XML entity injection via `loc`).

---

## 16. Testing strategy

Build on the existing Pest + Testbench setup. Recommended suites:

| Area | Coverage |
|---|---|
| Metadata | title (+suffix), description, canonical, robots `<meta>` |
| Social | OpenGraph fields+fallbacks, Twitter card fields+fallbacks |
| Structured data | valid JSON, correct `@type`, custom schemas (subclass + plain array), graph of multiple schemas |
| Canonical | configured base URL; `app.url` fallback; query-string handling; HTTPS enforcement; malicious/missing Host headers; `javascript:`/userinfo rejection; trailing-slash/port normalization |
| Sitemap | single urlset; index; splitting; lastmod normalization; escaping (incl. `&`/`<` in loc); multiple providers; empty/one provider; streaming iterator; cache hit/miss |
| Robots | directive grouping, custom directives, line-injection rejection, auto-sitemap URL, disabled route |
| Localization | hreflang alternates, `x-default` |
| Extensibility | custom resolver (`supports`/`resolve`), custom schema, custom sitemap provider, tagged-container wiring |
| Security | §15.3 matrix |
| BC | current `Sitemap`/`Robots`/`Schema` API keeps working (regression suite) |
| Head component | renders with explicit `SeoData` and with implicit current state |

Unit tests for pure logic; feature tests (through Testbench HTTP + `get('/seo/sitemap.xml')`) for
routes/components. `tests/Feature/` currently exists but is empty — that is where route/component
tests belong.

---

## 17. Documentation

The package has **no public documentation** (no README, no docs site). Because this package is an
ecosystem dependency, documentation is part of its public API.

### 17.1 Proposed `docs/` (GitHub Pages, mirroring Basekit UI quality)

```
docs/
├── index.md                      # overview, positioning, "why this package"
├── installation.md               # composer, publish, provider
├── getting-started/
│   ├── quickstart.md             # `<x-basekit-laravel-seo::head/>` demo
│   ├── configuration.md          # config reference (published file)
│   └── customizing.md            # publishing views, overriding head partials
├── seo/
│   ├── seo-data.md               # the value object reference
│   ├── resolvers.md              # seo()->for($model), custom resolvers
│   └── overriding.md             # per-page fluent overrides
├── structured-data/
│   ├── core-schemas.md           # WebSite/WebPage/Organization/Article/BreadcrumbList/Person
│   ├── custom-schemas.md         # subclass Schema or use arrays
│   └── security.md               # JSON-LD escaping rules
├── sitemap/
│   ├── providers.md              # contract + how blog/pages/products integrate
│   ├── routes-and-index.md       # routes, splitting, lastmod
│   └── caching.md
├── robots.md                     # config, route, custom directives
├── internationalization.md       # locale, hreflang, x-default
├── integrations/                 # per-ecosystem-package recipes
│   ├── content.md
│   ├── pages.md
│   ├── blog.md
│   ├── blocks.md
│   └── grid.ecommerce.md / products.md
├── architecture/
│   ├── architecture.md           # this audit, condensed
│   └── extension-points.md       # tags, contracts, events
├── api/
│   ├── contracts.md
│   └── classes.md                # generated reference
└── security.md
```

### 17.2 Proposed README structure

1. One-line pitch + badges (packagist, licence, status)
2. Features list
3. Installation (`composer require basekit-laravel/basekit-laravel-seo` + publish)
4. Quickstart (`<x-basekit-laravel-seo::head/>`)
5. Core concepts (SeoData → resolution → render) with 30-second examples
6. Configuration summary (pointer to docs)
7. Ecosystem integration (how content/pages/blog/… contribute)
8. Security notes (canonical trust, JSON-LD, XML)
9. Testing + development commands
10. Changelog link + license

Do **not** write the final README/docs until the P0 architecture shape is approved (§19).

---

## 18. Ecosystem integration

Future ecosystem: `basekit-laravel-ui`, `-blocks`, `-content`, `-pages`, `-blog`, `-seo`.

Design so each optional partner integrates **without any** hard dependency from this package:

- **Content** — optional: exposes SEO metadata. A content model implements a resolver, or a content
  package provides a `SeoResolver` tagged binding mapping its fields → `SeoData`.
- **Pages** — page-specific SEO via the same resolver path (route/page defaults come from the pages
  package's own model).
- **Blog** — article-specific SEO: a blog resolver returns `SeoData` with `ArticleSchema`
  (subclass/`BlogPostingSchema` owned by the blog package), plus a `SitemapProvider` feeding posts.
- **Blocks** — can contribute structured data by including schemas in the `schemas` list it returns
  through *its* resolver; no separate contract needed. (Site-wide schema providers are P2+.)
- **E-commerce** — a product resolver + `SitemapProvider`, `og:type = product`, `ProductSchema` as a
  third-party schema class.

Dependency direction is always **one-way**: partner → this package. This package depends on nothing
outside Illuminate. The integration points are exactly three contracts/resolvers/providers:
`SeoResolver`, `SitemapProvider`, `StructuredDataProvider` (optional, §7.3).

---

## 19. Backwards compatibility

- **Keep** all current public symbols and their signatures stable for the P0 through P2 work:
  `Sitemap`, `Robots`, `Schema`, the four schema classes, all config keys, view namespace, publish
  tags, container binding name.
- `Schema::toJson()` flag change (`JSON_HEX_TAG`) is a deliberate, low-risk behaviour change
  (output still valid JSON, still `JSON_UNESCAPED_UNICODE`); document in the changelog — it is a
  security fix, not a compatibility break.
- New head rendering, routes, resolver contracts, and sitemap providers are *additive*.
- Only a future major (v2) may remove or rename existing API; per AGENTS.md, that requires a
  release workflow migration note.

---

## 20. Risks

| Risk | Mitigation |
|---|---|
| API scope creep (schema framework, admin UI) | Gate by roadmap P-levels; contracts before implementations |
| Mandatory DB model (Option B) | Keep core storage-free (Option A/C companion) |
| Host-header/canonical abuse after rendering lands | Canonical `Url` helper + tests bundled into P0 |
| `</script>` JSON-LD breakout shipped publicly | S1 is a P0 item, fixed before any user-facing strings |
| Ecosystem packages evolving Schema in incompatible ways | Keep `SeoData` additive/array-friendly; BC policy §19 |
| Over-eager caching hurting freshness | Caching off by default; short TTL; key invalidation |
| Too much abstraction (multiple contracts too early) | Only 2–3 contracts total; everything else composition |
| `illuminate/view` undeclared dep | P0 composer fix |
| Documentation debt while API still moving | Docs drafted per-P-level; avoid premature finalization |

---

## 21. In-scope / out-of-scope summary

### Should change

- Add `SeoData` immutable value object (P0).
- Add `seo()` manager/helper/facade + layered resolution incl. `SeoResolver` (P0).
- Add `<x-basekit::head>` rendering with per-group partials (P0).
- Harden JSON-LD escaping (`JSON_HEX_TAG`) (P0).
- Add canonical `Url` trust/normalization helper (P0).
- Add `SitemapProvider` registry, entries, routes, index/splitting, caching (P1).
- Add robots route + multi-group/custom directives (P1).
- Add alternates/hreflang rendering (P1).
- Add missing core schemas (Person, BreadcrumbList) (P1).
- Declare `illuminate/view` in `require` (P0).
- Tests for all of the above incl. security matrix (with each level).
- README + docs skeleton (P1), public docs site (P2).

### Should stay

- Minimal, third-party-free dependency philosophy.
- Eloquent-free core; storage-free core (no migrations).
- Existing public API (BC).
- Schema-builder class approach for structured data.
- Provider/registry design for ecosystem contributions.

### Should NOT be added (without approval)

- Persistence layer / central polymorphic SEO model in this package.
- Domain packages (blog/pages/content/…), Livewire, Filament, Basekit UI dependencies.
- A generic schema engine / sitewide schema registry beyond the optional provider.
- Auto-generated alternates/hreflang.
- Persistent caching of metadata/schema.
- Any of the new public contracts before approval of this audit.

### Decisions requiring approval

1. `SeoData` = the canonical model/API core (readonly value object + builder) — confirm.
2. Resolver discovery mechanism: container tag vs explicit registration vs event — choose one.
3. Head component as the *only* render surface initially (no directive) — confirm.
4. Sitemap routes registered **by the package** on `/seo/*` paths vs consumer-registered — confirm.
5. robots.txt route on by default vs opt-in — confirm.
6. Canonical base precedence (config → `app.url` → request-with-trust) — confirm.
7. Storage stays out of core (Option A + future companion) — confirm.
8. `StructuredDataProvider` deferred to P2 (composition-first for schemas) — confirm.

### Recommended implementation order

1. **P0 — Foundation:** `SeoData` → `Seo` manager/helper/resolver → head component + partials →
   JSON-LD/XML/robots escaping fixes → canonical `Url` helper → `illuminate/view` require →
   security test matrix → BC regression suite.
2. **P1 — Core functionality:** sitemap providers + routes + index/splitting + caching →
   robots route/custom directives → alternates/hreflang → missing core schemas → README + docs
   skeleton.
3. **P2 — Useful improvements:** sitewide `StructuredDataProvider`, sitemap cache management,
   public docs site, optional storage companion evaluation.
4. **P3 — Future/optional:** admin/UI surface for editing SEO, advanced caches, more schema
   primitives as ecosystem demand proves them.

For the prioritized backlog see `docs/FEATURE_ROADMAP.md`.