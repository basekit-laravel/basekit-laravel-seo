import { defineConfig } from 'vitepress'

const repo = 'https://github.com/basekit-laravel/basekit-laravel-seo'

export default defineConfig({
  lang: 'en-US',
  title: 'Basekit Laravel SEO',
  description:
    'Reusable, optional SEO feature package for Basekit-powered Laravel websites: centralized metadata, structured data (JSON-LD), robots directives and XML sitemaps.',

  base: '/basekit-laravel-seo/',
  cleanUrls: true,
  lastUpdated: true,

  // Maintainer-only planning documents; they stay in the repository but are
  // not part of the published user documentation.
  srcExclude: ['development/architecture.md', 'development/feature-roadmap.md'],

  head: [['meta', { name: 'theme-color', content: '#7c3aed' }]],

  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/getting-started' },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'Page metadata', link: '/guide/page-metadata' },
          { text: 'Structured data (JSON-LD)', link: '/guide/structured-data' },
          { text: 'Resolvers', link: '/guide/resolvers' },
          { text: 'XML sitemap', link: '/guide/sitemap' },
          { text: 'robots.txt', link: '/guide/robots' },
          { text: 'Configuration', link: '/guide/configuration' },
        ],
      },
    ],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: repo }],
    editLink: {
      pattern: `${repo}/edit/master/docs/:path`,
      text: 'Edit this page on GitHub',
    },
    outline: { level: [2, 3] },
  },
})