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
import find from 'lodash/find'
import map from 'lodash/map'
import orderBy from 'lodash/orderBy'
import { randomString } from '@/utils'

export function useFieldSection(
  resourceName,
  resourceFields,
  selectedSections
) {
  const field = ref(null)
  const fieldLabel = ref(null)
  const isRequired = ref(false)

  const fieldMustBeRequired = computed(
    () => field.value && field.value.isRequired && field.value.primary
  )

  const availableFields = computed(() =>
    orderBy(
      map(unref(resourceFields), f => {
        f.disabled = isFieldAlreadySelected(f)

        return f
      }),
      ['disabled', 'order'],
      ['desc', 'asc']
    )
  )

  function generateRequestAttribute() {
    return randomString(25)
  }

  function handleFieldChanged(f) {
    if (f) {
      fieldLabel.value = f.label
      isRequired.value = f.isRequired
    } else {
      fieldLabel.value = ''
      isRequired.value = false
    }
  }

  function isFieldAlreadySelected(f) {
    return !find(unref(selectedSections), {
      attribute: f.attribute,
      resourceName: unref(resourceName),
    })
  }

  return {
    field,
    fieldLabel,
    isRequired,

    fieldMustBeRequired,
    availableFields,
    generateRequestAttribute,
    handleFieldChanged,
    isFieldAlreadySelected,
  }
}
