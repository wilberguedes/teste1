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

export const useDocumentTypes = createGlobalState(() => {
  const { setLoading, isLoading: typesAreBeingFetched } = useLoader()

  const documentTypes = ref([])

  const typesByName = computed(() => orderBy(documentTypes.value, 'name'))

  function findTypeById(id) {
    return typesByName.value.find(t => t.id == id)
  }

  function setDocumentTypes(types) {
    documentTypes.value = types
  }

  function fetchDocumentTypes() {
    setLoading(true)

    Innoclapps.request()
      .get('/document-types', {
        params: {
          per_page: 100,
        },
      })
      .then(({ data }) => (documentTypes.value = data.data))
      .finally(() => setLoading(false))
  }

  return {
    documentTypes,
    typesByName,
    typesAreBeingFetched,

    findTypeById,
    setDocumentTypes,
    fetchDocumentTypes,
  }
})
