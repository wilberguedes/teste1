<template>
  <a
    href="#"
    @click.prevent="$emit('click')"
    class="group flex w-full items-center"
  >
    <span class="flex items-center py-2 px-4 text-sm font-medium">
      <span
        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
        :class="[
          {
            'bg-success-500 group-hover:bg-success-700 dark:bg-success-400 dark:group-hover:bg-success-600':
              (dealStatus === 'won' && dealStageIsCurrentOrBehindStage) ||
              dealStatus === 'open',

            'border border-success-500 bg-transparent group-hover:border-success-700 dark:border-success-400 dark:group-hover:border-success-600':
              dealStatus === 'won' && currentStageIsAfterDealStage,

            'bg-danger-500 group-hover:bg-danger-700 dark:bg-danger-400 dark:group-hover:bg-danger-600':
              dealStatus === 'lost' && dealStageIsCurrentOrBehindStage,

            'border border-danger-500 bg-transparent group-hover:border-danger-700 dark:border-danger-400 dark:group-hover:border-danger-600':
              dealStatus === 'lost' && currentStageIsAfterDealStage,
          },
        ]"
      >
        <ISpinner v-if="requestInProgress" class="h-6 w-6 text-current" />

        <Icon
          v-else-if="dealStatus === 'open' || dealStatus === 'won'"
          icon="Check"
          :class="[
            'h-6 w-6',
            dealStatus === 'open'
              ? 'text-white'
              : {
                  'text-white': dealStageIsCurrentOrBehindStage,
                  'text-success-500': currentStageIsAfterDealStage,
                },
          ]"
        />
        <!-- lost -->
        <Icon
          v-else
          icon="X"
          :class="[
            'h-6 w-6',
            {
              'text-white': dealStageIsCurrentOrBehindStage,
              'text-danger-500': currentStageIsAfterDealStage,
            },
          ]"
        />
      </span>
      <span
        class="ml-4 text-sm font-medium text-neutral-900 dark:text-neutral-200"
      >
        <slot></slot>
      </span>
    </span>
  </a>
</template>
<script setup>
import { computed } from 'vue'

const emit = defineEmits(['click'])

const props = defineProps({
  dealStatus: { type: String, required: true },
  requestInProgress: Boolean,
  stageIndex: { required: true, type: Number },
  dealStageIndex: { required: true, type: Number },
})

const currentStageIsAfterDealStage = computed(
  () => props.stageIndex > props.dealStageIndex
)

const dealStageIsCurrentOrBehindStage = computed(
  () => props.stageIndex <= props.dealStageIndex
)
</script>
