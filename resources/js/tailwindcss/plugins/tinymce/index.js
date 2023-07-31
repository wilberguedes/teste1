/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */
import plugin from 'tailwindcss/plugin'

export default plugin(function ({ addComponents }) {
  addComponents({
    // Typography
    '.wysiwyg-text': {
      '@apply prose prose-sm prose-neutral max-w-none prose-headings:my-3 prose-headings:font-semibold dark:prose-invert prose-a:text-primary-600 hover:prose-a:text-primary-900 first:prose-p:mt-0 last:prose-p:mb-0':
        {},
    },

    // Styling

    '.tox .tox-toolbar__group': {
      '@apply !pt-0 !pb-0 !pr-1 !pl-1': {},
    },

    '.tox:not(.tox-tinymce-inline) .tox-editor-header': {
      '@apply !p-0 !shadow-none': {},
      borderBottom: '1px solid rgba(var(--color-neutral-300), 1) !important',
    },

    'html.dark .tox:not(.tox-tinymce-inline) .tox-editor-header': {
      borderBottom: '1px solid rgba(var(--color-neutral-500), 1) !important',
    },

    '.tox-tinymce': {
      '@apply !rounded-md !border !border-neutral-300 dark:!border-neutral-500':
        {},
    },

    '.tox': {
      // Lists
      '.tox-collection--list': {
        '.tox-collection__item--active': {
          '@apply !bg-primary-50': {},
        },
      },

      // Toolbar buttons
      '.tox-tbtn--select': {
        margin: '5px 1px 5px 0 !important',
      },

      '.tox-tbtn--bespoke': {
        '@apply !text-primary-700 !bg-primary-50': {},
        svg: {
          fill: 'rgba(var(--color-primary-700)) !important',
        },
      },

      '.tox-tbtn:hover': {
        '@apply !text-primary-700 !bg-primary-100': {},
        svg: {
          fill: 'rgba(var(--color-primary-700)) !important',
        },
      },

      '.tox-tbtn--enabled, .tox-tbtn:focus': {
        '@apply !text-primary-700 !bg-primary-100': {},
        svg: {
          fill: 'rgba(var(--color-primary-700)) !important',
        },
        '&:hover': {
          '@apply !bg-primary-200': {},
        },
      },

      '.tox-split-button': {
        '&:hover,&:focus,&:active,.tox-tbtn:hover,.tox-tbtn:focus,.tox-tbtn:active':
          {
            boxShadow: 'none !important;',
            '@apply !text-primary-700 !bg-primary-100': {},
            svg: {
              fill: 'rgba(var(--color-primary-700)) !important',
            },
          },
      },

      // Regular buttons
      '.tox-button--secondary': {
        '@apply !border !border-neutral-300 !shadow-sm !bg-white !text-neutral-700 hover:!bg-neutral-50 dark:!border-neutral-500 dark:!bg-neutral-700 dark:!text-white dark:hover:!bg-neutral-600 !font-medium !rounded-md !px-4 !py-2 !text-sm focus:!ring-2 focus:!ring-offset-2 disabled:!pointer-events-none disabled:!opacity-60':
          {},
      },

      // Buttons
      '.tox-button--naked': {
        '@apply focus:!bg-neutral-100 hover:!bg-neutral-100 !px-2.5 !py-1.5 disabled:!pointer-events-none':
          {},
      },

      '.tox-dialog__header .tox-button': {
        '@apply !rounded-md !bg-white !text-neutral-400 hover:!text-neutral-500 focus:!outline-none focus:!ring-2 focus:!ring-primary-500 focus:!ring-offset-2 dark:!bg-neutral-800 !p-0 disabled:!pointer-events-none':
          {},
      },

      '.tox-button:not(.tox-button--naked, .tox-button--secondary)': {
        '@apply !border !border-transparent !shadow-sm !bg-primary-600 !text-white hover:!bg-primary-700 focus:!ring-primary-500 !font-medium !rounded-md !px-4 !py-2 !text-sm focus:!ring-2 focus:!ring-offset-2 disabled:!pointer-events-none disabled:!opacity-60':
          {},
      },

      // Dialog
      '.tox-dialog': {
        '@apply !rounded-lg dark:!bg-neutral-900': {},
      },

      '.tox-dialog__body-nav-item:focus': {
        backgroundColor: 'rgba(var(--color-primary-50),.5) !important;',
      },

      '.tox-dialog__body-nav-item--active': {
        '@apply !text-primary-600 !border-b-2 !border-primary-600 dark:!text-primary-400':
          {},
      },

      '.tox-dialog__header': {
        '@apply !p-5 dark:!bg-neutral-900': {},
      },

      '.tox-dialog__footer': {
        '@apply dark:!bg-neutral-900': {},
      },

      '.tox-dialog__title': {
        '@apply !text-lg !font-medium !leading-6 !text-neutral-700 dark:!text-white':
          {},
      },

      '.tox-dialog-wrap__backdrop': {
        '@apply  !bg-neutral-500/75 dark:!bg-neutral-700/90': {},
      },

      // Forms
      '.tox-form__group': {
        '@apply !mb-3': {},
      },

      '.tox-label': {
        '@apply !block !text-sm !font-medium !text-neutral-700 dark:!text-neutral-100 !mb-1':
          {},
      },

      '.tox-listboxfield .tox-listbox--select, .tox-textarea, .tox-textfield, .tox-toolbar-textfield':
        {
          '@apply !shadow-sm focus:!border-primary-500 focus:!ring-primary-500 disabled:!bg-neutral-200 !text-sm !px-2.5 !py-2 !min-h-0 !border !border-neutral-300 dark:!border-neutral-500 !rounded-md':
            {},
        },
    },

    ".tox:not([dir='rtl']) .tox-toolbar__group:not(:last-of-type)": {
      '@apply !border-r !border-neutral-300 dark:!border-neutral-500': {},
    },
  })
})
