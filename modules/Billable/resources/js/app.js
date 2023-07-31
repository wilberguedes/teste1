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

import SettingsProducts from './components/SettingsProducts.vue'

import ProductsTab from './components/RecordTabProduct.vue'
import ProductsTabPanel from './components/RecordTabProductPanel.vue'

import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (Vue, router) {
    Vue.component('ProductsTab', ProductsTab)
    Vue.component('ProductsTabPanel', ProductsTabPanel)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'products',
      component: SettingsProducts,
      name: 'settings-products',
      meta: { title: i18n.t('billable::product.products') },
    })
  })
}
