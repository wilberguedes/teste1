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
import { ref, unref, watchEffect } from 'vue'

export function useResource(resourceName) {
  const singularName = ref(null)
  const singularLabel = ref(null)
  const associationsBeingSynced = ref(false)

  async function syncAssociations(id, associations) {
    associationsBeingSynced.value = true

    let { data } = await Innoclapps.request()
      .post(`associations/${resourceName}/${id}`, associations)
      .finally(() => (associationsBeingSynced.value = false))

    return data
  }

  function getSingularName(name) {
    return Innoclapps.config(`resources.${unref(name || resourceName)}`)
      .singularName
  }

  function getSingularLabel(name) {
    return Innoclapps.config(`resources.${unref(name || resourceName)}`)
      .singularLabel
  }

  watchEffect(() => {
    if (unref(resourceName)) {
      singularName.value = getSingularName()
    } else {
      singularName.value = null
    }
  })

  watchEffect(() => {
    if (unref(resourceName)) {
      singularLabel.value = getSingularLabel()
    } else {
      singularLabel.value = null
    }
  })

  return {
    singularName,
    singularLabel,
    getSingularName,
    getSingularLabel,
    syncAssociations,
    associationsBeingSynced,
  }
}
