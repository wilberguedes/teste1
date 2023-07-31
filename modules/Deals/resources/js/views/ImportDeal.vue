<template>
  <ILayout>
    <import
      resource-name="deals"
      :request-query-string="{ pipeline_id: pipeline.id }"
    >
      <template #header>
        <ICardHeading class="flex items-center">
          <i18n-t scope="global" keypath="deals::deal.import_in">
            <template #pipeline>
              <div class="ml-3 sm:w-1/5">
                <ICustomSelect
                  :clearable="false"
                  label="name"
                  v-model="pipeline"
                  :options="pipelines"
                />
              </div>
            </template>
          </i18n-t>
        </ICardHeading>
      </template>
    </import>
  </ILayout>
</template>
<script setup>
import { shallowRef } from 'vue'
import Import from '~/Core/resources/js/components/Import'
import { usePipelines } from '../composables/usePipelines'

const { orderedPipelines: pipelines } = usePipelines()
const pipeline = shallowRef(pipelines.value[0])
</script>
