<template>
  <div
    v-html="section.message"
    class="wysiwyg-text mb-3 text-sm text-neutral-600 dark:text-neutral-300"
  />
</template>
<script setup>
import propsDefinition from './props'

defineProps(propsDefinition)
</script>
