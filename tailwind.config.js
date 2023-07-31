/** @type {import('tailwindcss').Config} */

import colors from 'tailwindcss/colors'
import defaultTheme from 'tailwindcss/defaultTheme'
import { generateColorVariant } from './resources/js/tailwindcss/utils'

import forms from '@tailwindcss/forms'
import aspectRatio from '@tailwindcss/aspect-ratio'
import typoegraphy from '@tailwindcss/typography'
import scrollbar from 'tailwind-scrollbar'
import tailwindAll from './resources/js/tailwindcss/plugins/all'
import tailwindTinyMCE from './resources/js/tailwindcss/plugins/tinymce'
import tailwindChartist from './resources/js/tailwindcss/plugins/chartist'
import tailwindMail from './resources/js/tailwindcss/plugins/mail'

export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './modules/**/resources/js/**/*.vue',
    './modules/**/resources/**/*.blade.php',
    './public/assets/contentbuilder/contentbuilder/plugins/*.js',
  ],

  safelist: [
    // Highlights
    'bg-warning-500',
    'bg-info-500',
    // Fields cols width
    'col-span-12',
    'sm:col-span-6',
    // Cards cols width
    'w-full',
    'lg:w-1/2',
    // Coolean column centering
    'text-center',
    // Webhook workflow action attributes
    '!pl-16',
    // TinyMCE
    'tox-tinymce',
    'tox',
    // Chartist
    'chartist-tooltip',
    'ct-label',
    // https://tailwindcss.com/docs/content-configuration#safelisting-classes
    {
      pattern: /^chart-.*/,
    },
    {
      pattern: /^ct-.*/,
    },
  ],

  darkMode: 'class', // or 'media' or 'class'

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
        signature: ['Dancing Script', 'cursive'],
      },
      height: {
        navbar: 'var(--navbar-height)',
      },
    },

    colors: {
      transparent: 'transparent',
      current: 'currentColor',

      black: colors.black,
      white: colors.white,

      neutral: generateColorVariant('neutral'),
      danger: generateColorVariant('danger'),
      warning: generateColorVariant('warning'),
      success: generateColorVariant('success'),
      info: generateColorVariant('info'),
      primary: generateColorVariant('primary'),
    },
  },
  plugins: [
    forms,
    aspectRatio,
    typoegraphy,
    scrollbar({ nocompatible: true }),
    tailwindAll,
    tailwindTinyMCE,
    tailwindChartist,
    tailwindMail,
  ],
}
