<template>
  <span
    class="inline-block"
    v-i-tooltip="isDisabled ? callDropdownTooltip : null"
  >
    <IDropdown
      v-if="hasMorePhoneNumbers"
      :disabled="isDisabled"
      size="sm"
      icon="Phone"
      variant="secondary"
      :text="$t('calls::call.make')"
    >
      <IDropdownItem
        v-for="(phoneNumber, index) in phoneNumbers"
        :key="phoneNumber.phoneNumber + phoneNumber.type + index"
        @click="requestNewCall(phoneNumber.phoneNumber)"
        :icon="phoneNumber.type == 'mobile' ? 'DeviceMobile' : 'Phone'"
      >
        {{ phoneNumber.phoneNumber }} ({{ phoneNumber.resourceDisplayName }})
      </IDropdownItem>
    </IDropdown>
    <IButton
      v-else
      size="sm"
      icon="Phone"
      variant="secondary"
      :disabled="!hasPhoneNumbers || isDisabled"
      :text="$t('calls::call.make')"
      @click="requestNewCall(onlyPhoneNumbers[0])"
    />
  </span>
</template>
<script setup>
import { computed } from 'vue'
import castArray from 'lodash/castArray'
import { useI18n } from 'vue-i18n'
import { useGate } from '~/Core/resources/js/composables/useGate'
import { useVoip } from '~/Core/resources/js/composables/useVoip'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const emit = defineEmits(['call-requested'])

const props = defineProps({
  resourceName: { required: true, type: String },
})

const { t } = useI18n()
const { gate } = useGate()
const { hasVoIPClient } = useVoip()

const { record } = useRecordStore()

const callDropdownTooltip = computed(() => {
  if (!hasVoIPClient) {
    return t('core::app.integration_not_configured')
  } else if (gate.userCant('use voip')) {
    return t('calls::call.no_voip_permissions')
  }

  return ''
})

const isDisabled = computed(() => gate.userCant('use voip') || !hasVoIPClient)

const phoneNumbers = computed(() => {
  let numbers = []

  numbers.push(...getPhoneNumbersFromResource(record.value))

  switch (props.resourceName) {
    case 'contacts':
      numbers.push(...getPhoneNumbersFromResource(record.value.companies || []))
      break
    case 'companies':
      numbers.push(...getPhoneNumbersFromResource(record.value.contacts || []))
      break
    case 'deals':
      numbers.push(...getPhoneNumbersFromResource(record.value.companies || []))
      numbers.push(...getPhoneNumbersFromResource(record.value.contacts || []))
      break
  }

  return numbers
})

const onlyPhoneNumbers = computed(() =>
  phoneNumbers.value.map(phone => phone.phoneNumber)
)

const totalPhoneNumbers = computed(() => onlyPhoneNumbers.value.length)

const hasPhoneNumbers = computed(() => totalPhoneNumbers.value > 0)

const hasMorePhoneNumbers = computed(() => totalPhoneNumbers.value > 1)

function requestNewCall(phoneNumber) {
  emit('call-requested', phoneNumber)
}

function getPhoneNumbersFromResource(resource) {
  let numbers = []
  castArray(resource).forEach(resource => {
    numbers = numbers.concat(
      ...(resource.phones || []).map(phone => {
        return {
          type: phone.type,
          phoneNumber: phone.number,
          resourceDisplayName: resource.display_name,
        }
      })
    )
  })

  return numbers
}
</script>
