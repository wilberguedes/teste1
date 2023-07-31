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

import SettingsWebForms from './components/SettingsWebForms.vue'
import WebFormPublicView from './views/WebFormPublicView.vue'
import RecordTabTimelineWebFormSubmission from './components/RecordTabTimelineWebFormSubmission.vue'
import CreateWebForm from './views/CreateWebForm.vue'
import EditWebForm from './views/EditWebForm.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (Vue, router) {
    Vue.component('WebFormPublicView', WebFormPublicView)
    Vue.component(
      'WebFormSubmissionChangelog',
      RecordTabTimelineWebFormSubmission
    )

    router.addRoute('settings', {
      path: 'forms/:id/edit',
      name: 'web-form-edit',
      component: EditWebForm,
    })

    router.addRoute('settings', {
      path: 'forms',
      name: 'web-forms-index',
      component: SettingsWebForms,
      meta: {
        title: i18n.t('webforms::form.forms'),
      },
      children: [
        {
          path: 'create',
          name: 'web-form-create',
          component: CreateWebForm,
        },
      ],
    })
  })
}
