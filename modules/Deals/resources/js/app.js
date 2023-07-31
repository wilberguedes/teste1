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

import DealPresentationCard from './components/DealPresentationCard.vue'
import DealPreview from './components/PreviewDeal.vue'
import LostReasonField from './components/LostReasonField.vue'
import PipelineStageField from './components/PipelineStageField.vue'

import SettingsDeals from './components/SettingsDeals.vue'
import CreateDealPipeline from './views/CreateDealPipeline.vue'
import EditDealPipeline from './views/EditDealPipeline.vue'

import CreateDealModal from './components/CreateDealModal.vue'

import { usePipelines } from './composables/usePipelines'
import { useLostReasons } from './composables/useLostReasons'

const { setPipelines } = usePipelines()
const { setLostReasons } = useLostReasons()

import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting((Vue, router) => {
    Vue.component('DealPresentationCard', DealPresentationCard)
    Vue.component('DealPreview', DealPreview)
    Vue.component('LostReasonField', LostReasonField)
    Vue.component('PipelineStageField', PipelineStageField)
    Vue.component('CreateDealModal', CreateDealModal)

    setPipelines(Innoclapps.config('deals.pipelines') || [])
    setLostReasons(Innoclapps.config('deals.lost_reasons') || [])

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'deals',
      name: 'deals-settings-index',
      component: SettingsDeals,
      meta: {
        title: i18n.t('deals::deal.deals'),
      },
      children: [
        {
          path: 'pipelines/create',
          name: 'create-pipeline',
          component: CreateDealPipeline,
          meta: { title: i18n.t('deals::deal.pipeline.create') },
        },
      ],
    })
    router.addRoute('settings', {
      path: 'deals/pipelines/:id/edit',
      name: 'edit-pipeline',
      component: EditDealPipeline,
      meta: { title: i18n.t('deals::deal.pipeline.edit') },
    })
  })
}
