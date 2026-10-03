# Changelog

All notable user-visible changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0](https://github.com/basekit-laravel/basekit-laravel-seo/compare/v1.0.1...v1.1.0) (2026-10-03)


### Features

* accept schema objects as breadcrumb items ([5ad2157](https://github.com/basekit-laravel/basekit-laravel-seo/commit/5ad2157ce57613ea23ba4333cb5d256d2bed77c3))
* make sitemap route middleware configurable ([fe71e55](https://github.com/basekit-laravel/basekit-laravel-seo/commit/fe71e556f52e3af71d350f7fa5db85f769fd7b61))
* rebuild sitemaps once and add cache warm/clear commands ([64d4fba](https://github.com/basekit-laravel/basekit-laravel-seo/commit/64d4fba1df13b4767714f09bdf4ef87843d086e5))
* send HTTP cache headers from sitemap and robots routes ([3941cab](https://github.com/basekit-laravel/basekit-laravel-seo/commit/3941cab3dbf564ba6799349a7e4d56b6ddc76ac1))


### Bug Fixes

* escape sitemap, meta and JSON-LD output safely ([369f4ac](https://github.com/basekit-laravel/basekit-laravel-seo/commit/369f4ac9a73a77b06c63329b5fa0008307eb3ac4))
* remove packagist workflow ([4c5d95d](https://github.com/basekit-laravel/basekit-laravel-seo/commit/4c5d95dd44eb05e9de213baa082220a8747b8e54))


### Performance Improvements

* memoise resolved SEO metadata per request ([f8416aa](https://github.com/basekit-laravel/basekit-laravel-seo/commit/f8416aa2df7e55cafa0c43d1f034576ba279087a))
* stream sitemap entries and keep chunking stateless ([6714661](https://github.com/basekit-laravel/basekit-laravel-seo/commit/6714661dc38299faac28c02a913deaf481fa9e4a))


### Miscellaneous Chores

* tidy test config and dependencies ([b8e501e](https://github.com/basekit-laravel/basekit-laravel-seo/commit/b8e501e25bf14deea9700370e1e8ed75a9f9251f))

## [1.0.1](https://github.com/basekit-laravel/basekit-laravel-seo/compare/v1.0.0...v1.0.1) (2026-09-23)


### Miscellaneous Chores

* add rector and apply automated code quality refactors ([0647abc](https://github.com/basekit-laravel/basekit-laravel-seo/commit/0647abc40026cb61c73cfe3e398d47a3c4d05c9d))

## 1.0.0 (2026-09-22)


### Features

* add SEO core foundation for centralized metadata ([cf879e5](https://github.com/basekit-laravel/basekit-laravel-seo/commit/cf879e5424440dbbf7280abd3604c1a5df542268))
* add SEO head rendering layer ([37cba3c](https://github.com/basekit-laravel/basekit-laravel-seo/commit/37cba3c5756fca2ed7ff5679371f2b371e937060))
* add sitemap splitting, index and caching ([2e18c07](https://github.com/basekit-laravel/basekit-laravel-seo/commit/2e18c07540569e04dab877946894142f7b07c966))
* add XML sitemap and robots.txt routes ([17168e7](https://github.com/basekit-laravel/basekit-laravel-seo/commit/17168e7524a489f920d4d8c2969a5939e1e7719a))
