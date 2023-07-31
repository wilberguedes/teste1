<template>
  <div class="flex items-center">
    <div v-if="authorizedToUpdate">
      <IPopover
        v-if="status !== 'lost'"
        ref="popoverRef"
        class="w-72"
        @show="handleStagePopoverShowEvent"
      >
        <button
          type="button"
          class="flex flex-wrap items-center justify-center focus:outline-none md:flex-nowrap md:justify-start"
        >
          <span>{{ dealPipeline.name }}</span>
          <Icon icon="ChevronRight" class="h-4 w-4" /> {{ dealStage.name }}
          <Icon icon="ChevronDown" class="ml-1.5 hidden h-4 w-4 md:block" />
        </button>

        <template #popper>
          <div class="px-5 py-4">
            <ICustomSelect
              :options="pipelines"
              v-model="selectPipeline"
              @option:selected="handlePipelineChangedEvent"
              :clearable="false"
              class="mb-2"
              label="name"
            />
            <ICustomSelect
              :options="selectPipeline ? selectPipeline.stages : []"
              :clearable="false"
              v-model="selectPipelineStage"
              label="name"
            />
            <div
              class="-mx-5 -mb-4 mt-4 flex justify-end space-x-1 bg-neutral-100 px-6 py-3 dark:bg-neutral-900"
            >
              <IButton
                size="sm"
                variant="white"
                :disabled="requestInProgress"
                :text="$t('core::app.cancel')"
                @click="() => $refs.popoverRef.hide()"
              />
              <IButton
                size="sm"
                variant="primary"
                :text="$t('core::app.save')"
                :loading="requestInProgress"
                :disabled="requestInProgress || !selectPipelineStage"
                @click="saveStageChange"
              />
            </div>
          </div>
        </template>
      </IPopover>
      <div
        v-else
        class="flex items-center text-neutral-800 dark:text-neutral-200"
      >
        {{ dealPipeline.name }} <Icon icon="ChevronRight" class="h-4 w-4" />
        {{ dealStage.name }}
      </div>
    </div>
    <div
      v-else
      class="flex items-center text-neutral-800 dark:text-neutral-200"
    >
      {{ dealPipeline.name }} <Icon icon="ChevronRight" class="h-4 w-4" />
      {{ dealStage.name }}
    </div>
    <slot></slot>
  </div>
</template>
<script setup>
import { ref, shallowRef, computed } from 'vue'
import { usePipelines } from '../composables/usePipelines'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const props = defineProps({
  dealId: { required: true, type: Number },
  pipelineId: { required: true, type: Number },
  stageId: { required: true, type: Number },
  status: { required: true, type: String },
  authorizedToUpdate: { required: true, type: Boolean },
  isFloating: { type: Boolean, default: false },
})

const { ensureRecordIsUpdated } = useRecordStore()

const {
  orderedPipelines: pipelines,
  findPipelineById,
  findPipelineStageById,
} = usePipelines()

const dealPipeline = computed(() => findPipelineById(props.pipelineId))

const dealStage = computed(() =>
  findPipelineStageById(props.pipelineId, props.stageId)
)

const popoverRef = ref(null)
const selectPipeline = shallowRef(null)
const selectPipelineStage = shallowRef(null)
const requestInProgress = ref(false)

function saveStageChange() {
  requestInProgress.value = true
  Innoclapps.request()
    .put(`/deals/${props.dealId}`, {
      pipeline_id: selectPipeline.value.id,
      stage_id: selectPipelineStage.value.id,
    })
    .then(({ data }) => {
      ensureRecordIsUpdated(data, 'deals', props.isFloating)
      Innoclapps.$emit('deals-record-updated', data)
    })
    .finally(() => {
      popoverRef.value.hide()
      requestInProgress.value = false
    })
}

function handleStagePopoverShowEvent() {
  selectPipeline.value = dealPipeline.value
  selectPipelineStage.value = dealStage.value
}

function handlePipelineChangedEvent(value) {
  if (value.id != props.pipelineId) {
    // Use the first stage selected from the new pipeline
    selectPipelineStage.value = value.stages[0] || null
  } else if (value.id === props.pipelineId) {
    // revent back to the original stage after the user select new stage
    // and goes back to the original without saving
    selectPipelineStage.value = dealStage.value
  }
}
</script>
