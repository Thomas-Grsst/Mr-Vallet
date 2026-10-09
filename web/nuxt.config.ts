export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  app: {
    head: {
      title: 'Vallet Location — Réservations',
      htmlAttrs: { lang: 'fr' },
    },
  },
  runtimeConfig: {
    public: {
      today: '2026-10-12',
    },
  },
  routeRules: {
    '/api/**': { proxy: `${process.env.NUXT_API_BASE ?? 'http://localhost:8000'}/api/**` },
  },
})
