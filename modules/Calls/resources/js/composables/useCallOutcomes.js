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
import { ref, computed } from 'vue'
import orderBy from 'lodash/orderBy'
import { createGlobalState } from '@vueuse/core'
import { useLoader } from '~/Core/resources/js/composables/useLoader'

export const useCallOutcomes = createGlobalState(() => {
  const { setLoading, isLoading: outcomesAreBeingFetched } = useLoader()

  const callOutcomes = ref([])

  const outcomesByName = computed(() => orderBy(callOutcomes.value, 'name'))

  function setCallOutcomes(outcomes) {
    callOutcomes.value = outcomes
  }

  function fetchCallOutcomes() {
    setLoading(true)

    Innoclapps.request()
      .get('/call-outcomes', {
        params: {
          per_page: 100,
        },
      })
      .then(({ data }) => (callOutcomes.value = data.data))
      .finally(() => setLoading(false))
  }

  return {
    callOutcomes,
    outcomesByName,
    outcomesAreBeingFetched,

    setCallOutcomes,
    fetchCallOutcomes,
  }
})
