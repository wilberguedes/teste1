<template>
  <li
    class="relative overflow-hidden truncate bg-neutral-50 dark:bg-neutral-800 lg:flex lg:flex-1"
  >
    <DealMiniPipelineStageComplete
      v-if="
        dealStageIsBeforeCurrentStage ||
        dealStatus === 'lost' ||
        dealStatus === 'won'
      "
      :request-in-progress="requestInProgress"
      v-i-tooltip="tooltipLabel"
      :deal-status="dealStatus"
      :deal-stage-index="dealStageIndex"
      :stage-index="index"
      @click="updateStage"
    >
      {{ stageName }}
    </DealMiniPipelineStageComplete>

    <DealMiniPipelineStageCurrent
      v-else-if="dealStageIsCurrentStage"
      :request-in-progress="requestInProgress"
      @click="updateStage"
      v-i-tooltip="tooltipLabel"
    >
      {{ stageName }}
    </DealMiniPipelineStageCurrent>

    <DealMiniPipelineStageFuture
      v-else
      :request-in-progress="requestInProgress"
      @click="updateStage"
      v-i-tooltip="tooltipLabel"
    >
      {{ stageName }}
    </DealMiniPipelineStageFuture>

    <template v-if="index !== totalStages - 1">
      <!-- Arrow separator for lg screens and up -->
      <div
        class="absolute right-0 top-0 hidden h-full w-5 bg-neutral-50 dark:bg-neutral-800 lg:block"
        aria-hidden="true"
      >
        <svg
          class="h-full w-full text-neutral-300 dark:text-neutral-600"
          viewBox="0 0 22 80"
          fill="none"
          preserveAspectRatio="none"
        >
          <path
            d="M0 -2L20 40L0 82"
            vector-effect="non-scaling-stroke"
            stroke="currentcolor"
            stroke-linejoin="round"
          />
        </svg>
      </div>
    </template>
  </li>
</template>
<script setup>
import { ref, computed } from 'vue'
import DealMiniPipelineStageCurrent from './DealMiniPipelineStageCurrent.vue'
import DealMiniPipelineStageComplete from './DealMiniPipelineStageComplete.vue'
import DealMiniPipelineStageFuture from './DealMiniPipelineStageFuture.vue'
import { useI18n } from 'vue-i18n'

const emit = defineEmits(['stage-updated'])

const props = defineProps({
  dealStatus: { type: String, required: true },
  stageId: { type: Number, required: true },
  timeInStages: { type: Object, required: true },
  stageName: { type: String, required: true },
  totalStages: { type: Number, required: true },
  dealId: { type: Number, required: true },
  dealStageId: { type: Number, required: true },
  dealStageIndex: { type: Number, required: true },
  index: { required: true, type: Number },
})

const { t } = useI18n()

const requestInProgress = ref(false)

/**
 * Get the current stage index
 */
const currentStageIndex = computed(() => props.index)

/**
 * Indicates whether the deal stage is before the current stage
 */
const dealStageIsBeforeCurrentStage = computed(
  () => props.dealStageIndex > currentStageIndex.value
)

/**
 * Indicates whether the deal stage is the current stage
 */
const dealStageIsCurrentStage = computed(
  () => props.dealStageIndex === currentStageIndex.value
)

/**
 * Indicates whether the deal has been in the current stage
 */
const beenInStage = computed(
  () => props.timeInStages[props.stageId] != undefined
)

/**
 * Get the moment instance duration
 */
const duration = computed(() =>
  moment.duration({
    seconds: beenInStage.value ? props.timeInStages[props.stageId] : 0,
  })
)

/**
 * Get the stage tooltip label
 */
const tooltipLabel = computed(() => {
  let tooltip = ''

  // When there are more then 6 stages they may be truncated
  // for this reason, show the stage name in the tooltip
  if (props.totalStages > 6) {
    tooltip += props.stageName + ' - '
  }

  if (!beenInStage.value) {
    tooltip += t('deals::deal.hasnt_been_in_stage')
  } else {
    tooltip += t('deals::deal.been_in_stage_time', {
      time: duration.value.humanize(),
    })
  }

  return tooltip
})

function updateStage() {
  if (props.stageId === props.dealStageId || props.dealStatus !== 'open') {
    return
  }

  requestInProgress.value = true
  Innoclapps.request()
    .put(`/deals/${props.dealId}`, {
      stage_id: props.stageId,
    })
    .then(({ data }) => {
      emit('stage-updated', data)
      Innoclapps.$emit('deals-record-updated', data)
    })
    .finally(() => (requestInProgress.value = false))
}
</script>
