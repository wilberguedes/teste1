/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */
import { ref, computed } from 'vue'
import orderBy from 'lodash/orderBy'
import { createGlobalState } from '@vueuse/core'
import { useLoader } from '~/Core/resources/js/composables/useLoader'

export const usePipelines = createGlobalState(() => {
  const { setLoading, isLoading: pipelinesAreBeingFetched } = useLoader()

  const pipelines = ref([])

  const orderedPipelines = computed(() =>
    orderBy(
      pipelines.value,
      ['user_display_order', 'is_primary', 'name'],
      ['asc', 'desc', 'asc']
    )
  )

  function idx(id) {
    return pipelines.value.findIndex(pipeline => pipeline.id == id)
  }

  function setPipelines(value) {
    pipelines.value = value
  }

  function findPipelineById(id) {
    return pipelines.value[idx(id)]
  }

  function findPipelineStageById(pipelineId, id) {
    const pipeline = findPipelineById(pipelineId)

    return pipeline?.stages.filter(stage => Number(id) === Number(stage.id))[0]
  }

  function removePipeline(id) {
    pipelines.value.splice(idx(id), 1)
  }

  function addPipeline(pipeline) {
    pipelines.value.push(pipeline)
  }

  function setPipeline(id, pipeline) {
    pipelines.value[idx(id)] = pipeline
  }

  async function fetchPipeline(id, options = {}) {
    const { data } = await Innoclapps.request().get(`/pipelines/${id}`, options)
    return data
  }

  async function deletePipeline(id) {
    await Innoclapps.request().delete(`/pipelines/${id}`)
    removePipeline(id)
  }

  function fetchPipelines() {
    setLoading(true)

    Innoclapps.request()
      .get('/pipelines')
      .then(({ data }) => (pipelines.value = data))
      .finally(() => setLoading(false))
  }

  return {
    pipelines,
    orderedPipelines,
    pipelinesAreBeingFetched,

    addPipeline,
    removePipeline,
    setPipelines,
    setPipeline,
    findPipelineById,
    findPipelineStageById,

    fetchPipelines,
    fetchPipeline,
    deletePipeline,
  }
})
