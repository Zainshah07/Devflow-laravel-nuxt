// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr:false,
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
    srcDir: 'app/',
    devServer: {
    host: '0.0.0.0',
    port: 3000
  },
  vite: {
    server: {
      watch: {
        usePolling: true,
        interval: 100
      }
    }
  },
  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],

 runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost/api',
    },
  },

  css: ['~/assets/css/main.css'],

  // This tells Nuxt explicitly that [id]/index.vue is NOT
  // a layout parent for [id]/graph.vue
  experimental: {
    scanPageMeta: true,
  },
})
