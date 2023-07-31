<template>
  <FieldFormGroup
    :field="field"
    :label="label"
    :form-id="formId"
    :field-id="fieldId"
  >
    <MainLostReasonField v-model="value" :attribute="field.attribute" />
  </FieldFormGroup>
</template>
<script setup>
import { ref, toRef, onMounted } from 'vue'
import FieldFormGroup from '~/Core/resources/js/components/Fields/FieldFormGroup.vue'
import MainLostReasonField from './DealLostReasonField.vue'

import propsDefinition from '~/Core/resources/js/components/Fields/props'
import { useField } from '~/Core/resources/js/components/Fields/useField'

const props = defineProps(propsDefinition)

const value = ref('')

const { field, label, fieldId, initialize } = useField(
  value,
  toRef(props, 'field'),
  props.formId,
  toRef(props, 'isFloating')
)

onMounted(initialize)
</script>
