<template>
  <div class="flex w-full items-center overflow-x-auto">
    <a
      v-show="value && !isDisabled"
      href="#"
      @click.prevent="value = null"
      class="link mr-3 border-r-2 border-neutral-200 pr-3 dark:border-neutral-600"
      v-t="'core::app.all'"
    />
    <div
      v-for="status in statuses"
      :key="status.name"
      :class="[
        'mr-2 rounded-md',
        status.name === modelValue ? 'bg-neutral-800/10' : '',
      ]"
    >
      <TextBackground
        :color="status.color"
        :bg-opacity="0.09"
        @click="!isDisabled ? (value = status.name) : undefined"
        v-i-tooltip="
          statusRuleIsApplied
            ? $t('documents::document.filters.status_disabled')
            : ''
        "
        :class="isDisabled ? 'opacity-70' : 'cursor-pointer'"
        class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm/5 font-normal dark:!text-white"
      >
        <Icon :icon="status.icon" class="mr-1.5 h-4 w-4 text-current" />
        {{ $t('documents::document.status.' + status.name) }}
      </TextBackground>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import TextBackground from '~/Core/resources/js/components/TextBackground.vue'
import { useQueryBuilder } from '~/Core/resources/js/components/QueryBuilder/useQueryBuilder'

const emit = defineEmits(['update:modelValue'])
const props = defineProps(['modelValue'])

const statuses = Innoclapps.config('documents.statuses')
const value = ref(props.modelValue)

const { hasBuilderRules, rulesAreValid, findRule } =
  useQueryBuilder('documents')

const statusRuleIsApplied = computed(() => Boolean(findRule('status')))

const isDisabled = computed(
  () => statusRuleIsApplied.value || !rulesAreValid.value
)

watch(value, newVal => {
  emit('update:modelValue', newVal)
})

// Remove selected type when the builder has rules and they are valid
// to prevent errors in the filters
watch(hasBuilderRules, newVal => {
  if (newVal && rulesAreValid.value) {
    value.value = undefined
  }
})

// The same for when rules become valid, when valid and has builder rules remove selected type
watch(rulesAreValid, newVal => {
  if (hasBuilderRules.value && newVal) {
    value.value = undefined
  }
})
</script>
