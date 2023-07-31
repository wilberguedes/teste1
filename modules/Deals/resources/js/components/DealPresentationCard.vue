<template>
  <PresentationChart :card="card" :request-query-string="requestQueryString">
    <template #actions>
      <DropdownSelectInput
        :items="pipelines"
        toggle-class="md:mr-3"
        label-key="name"
        value-key="id"
        placement="bottom-end"
        v-model="pipeline"
      />
    </template>
  </PresentationChart>
</template>
<script setup>
import { shallowRef, computed } from 'vue'
import { usePipelines } from '../composables/usePipelines'

const props = defineProps({
  card: Object,
})

const { orderedPipelines: pipelines, findPipelineById } = usePipelines()
const pipeline = shallowRef(findPipelineById(props.card.pipeline_id)) // active selected pipeline

const requestQueryString = computed(() => ({
  pipeline_id: pipeline.value.id,
}))
</script>
