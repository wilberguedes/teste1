<template>
  <div>
    <IFormRadio
      v-for="taxType in formattedTaxTypes"
      :key="taxType.value"
      :id="key + '-' + taxType.value"
      :value="taxType.value"
      :modelValue="modelValue"
      name="tax_type"
      @change="$emit('update:modelValue', $event)"
      :label="taxType.text"
    />
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { randomString } from '@/utils'
import { useI18n } from 'vue-i18n'

defineEmits(['update:modelValue'])

defineProps(['modelValue'])

const { t } = useI18n()
const taxTypes = Innoclapps.config('taxes.types')

// In case included in modal, make sure unique ID is given so the label click works properly
const key = randomString()

const formattedTaxTypes = computed(() => {
  return taxTypes.map(type => ({
    value: type,
    text: t('billable::billable.tax_types.' + type),
  }))
})
</script>
