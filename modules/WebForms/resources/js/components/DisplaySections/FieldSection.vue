<template>
  <component
    :field="section.field"
    :form-id="form.formId"
    view="internal"
    :is="section.field.component"
  />
</template>
<script setup>
import propsDefinition from './props'

defineProps(propsDefinition)
</script>
