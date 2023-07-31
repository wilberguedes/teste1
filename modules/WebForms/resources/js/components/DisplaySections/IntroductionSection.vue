<template>
  <div class="mb-3" v-if="showIntroductionSection">
    <h4
      class="text-lg font-medium text-neutral-800 dark:text-neutral-100"
      v-text="section.title"
    />
    <div
      class="wysiwyg-text text-sm text-neutral-600 dark:text-neutral-300"
      v-html="section.message"
    />
  </div>
</template>
<script setup>
import { computed } from 'vue'
import propsDefinition from './props'

const props = defineProps(propsDefinition)

const showIntroductionSection = computed(
  () => props.section.title || props.section.message
)
</script>
