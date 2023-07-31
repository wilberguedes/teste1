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
import { ref, unref, computed } from 'vue'
import Fields from '~/Core/resources/js/components/Fields/Fields'
import { useStore } from 'vuex'

export function useResourceFields(list = []) {
  const store = useStore()

  const fields = ref(new Fields(list))

  const totalCollapsableFields = computed(
    () => fields.value.all().filter(field => field.collapsed).length
  )

  const hasCollapsableFields = computed(() => totalCollapsableFields.value > 0)

  function getCreateFields(group, params = {}) {
    return store.dispatch('fields/getForResource', {
      resourceName: unref(group),
      view: Innoclapps.config('fields.views.create'),
      ...params,
    })
  }

  function getDetailFields(group, id, params = {}) {
    return store.dispatch('fields/getForResource', {
      resourceName: unref(group),
      resourceId: id,
      view: Innoclapps.config('fields.views.detail'),
      ...params,
    })
  }

  function getUpdateFields(group, id, params = {}) {
    return store.dispatch('fields/getForResource', {
      resourceName: unref(group),
      resourceId: id,
      view: Innoclapps.config('fields.views.update'),
      ...params,
    })
  }

  return {
    fields,
    hasCollapsableFields,
    totalCollapsableFields,

    getCreateFields,
    getUpdateFields,
    getDetailFields,
  }
}
