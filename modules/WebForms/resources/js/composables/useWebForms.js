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

export const useWebForms = createGlobalState(() => {
  const { setLoading, isLoading: formsAreBeingFetched } = useLoader()

  const webForms = ref([])

  const webFormsOrderedByNameAndStatus = computed(() =>
    orderBy(webForms.value, ['status', 'title'], ['asc', 'asc'])
  )

  // Only excuted once
  fetchWebForms()

  function idx(id) {
    return webForms.value.findIndex(form => form.id == id)
  }

  function removeWebForm(id) {
    webForms.value.splice(idx(id), 1)
  }

  function addWebForm(form) {
    webForms.value.push(form)
  }

  function setWebForm(id, form) {
    webForms.value[idx(id)] = form
  }

  async function fetchWebForm(id, options = {}) {
    const { data } = await Innoclapps.request().get(`/forms/${id}`, options)
    return data
  }

  async function deleteWebForm(id) {
    await Innoclapps.request().delete(`/forms/${id}`)
    removeWebForm(id)
  }

  function fetchWebForms() {
    setLoading(true)

    Innoclapps.request()
      .get('/forms')
      .then(({ data }) => (webForms.value = data))
      .finally(() => setLoading(false))
  }

  return {
    webForms,
    webFormsOrderedByNameAndStatus,
    formsAreBeingFetched,

    addWebForm,
    removeWebForm,
    setWebForm,

    fetchWebForms,
    fetchWebForm,
    deleteWebForm,
  }
})
