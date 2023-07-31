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

import SettingsTranslator from './components/SettingsTranslator.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (Vue, router) {
    router.addRoute('settings', {
      path: '/settings/translator',
      component: SettingsTranslator,
      meta: { title: i18n.t('translator::translator.translator') },
    })
  })
}
