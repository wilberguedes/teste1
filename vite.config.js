import { defineConfig, splitVendorChunkPlugin } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
import Unfonts from 'unplugin-fonts/vite'

export default defineConfig({
  resolve: {
    alias: {
      '@': '/resources/js',
      '~': '/modules',
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },
  server: {
    hmr: {
      host: 'localhost',
    },
  },
  plugins: [
    splitVendorChunkPlugin(),
    laravel(['resources/js/app.js', 'resources/css/contentbuilder/theme.css']),
    Unfonts({
      custom: {
        families: [
          {
            name: 'Dancing Script',
            local: 'Dancing Script',
            src: './public/fonts/DancingScript-Regular.ttf',
          },
        ],
      },
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    VueI18nPlugin({
      compositionOnly: true,
      runtimeOnly: false,
      globalSFCScope: true,
    }),
  ],
})
