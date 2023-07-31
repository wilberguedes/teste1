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
import i18n from '~/Core/resources/js/i18n'

import { useStorage } from '@vueuse/core'

import ImportDeal from './views/ImportDeal.vue'
import DealIndex from './views/DealIndex.vue'
import CreateDeal from './views/CreateDeal.vue'
import ViewDeal from './views/ViewDeal.vue'
import DealBoard from './views/DealBoard.vue'

import CreateCompany from '~/Contacts/resources/js/views/Companies/CreateCompany.vue'
import CreateContact from '~/Contacts/resources/js/views/Contacts/CreateContact.vue'

const isBoardDefaultView = useStorage('deals-board-view-default', false)

export default [
  {
    path: '/deals',
    name: 'deal-index',
    component: DealIndex,
    meta: {
      title: i18n.t('deals::deal.deals'),
      subRoutes: ['create-deal'],
      boardRoute: 'deal-board',
      initialize: false,
    },
    beforeEnter: async (to, from) => {
      // Check if the deals board is active
      if (
        isBoardDefaultView.value &&
        from.name != to.meta.boardRoute &&
        to.meta.subRoutes.indexOf(to.name) === -1
      ) {
        return { name: to.meta.boardRoute, query: to.query }
      }

      to.meta.initialize = to.name === 'deal-index'

      if (to.meta.subRoutes.indexOf(to.name) === -1) {
        isBoardDefaultView.value = false
      }
    },
    children: [
      {
        path: 'create',
        name: 'create-deal',
        components: {
          create: CreateDeal,
        },
        meta: { title: i18n.t('deals::deal.create') },
      },
    ],
  },
  {
    path: '/import/deals',
    name: 'import-deal',
    component: ImportDeal,
    meta: { title: i18n.t('deals::deal.import') },
  },
  {
    path: '/deals/board',
    name: 'deal-board',
    component: DealBoard,
    meta: {
      title: i18n.t('deals::deal.deals'),
    },
    beforeEnter: () => {
      isBoardDefaultView.value = true
    },
  },
  {
    path: '/deals/:id',
    name: 'view-deal',
    component: ViewDeal,
    props: {
      resourceName: 'deals',
    },
    children: [
      {
        path: 'contacts/create',
        component: CreateContact,
        name: 'createContactViaDeal',
      },
      {
        path: 'companies/create',
        component: CreateCompany,
        name: 'createCompanyViaDeal',
      },
    ].map(route => Object.assign(route, { meta: { scrollToTop: false } })),
  },
]
