<template>
  <ICard
    no-body
    :header="$t('deals::deal.pipeline.edit')"
    :overlay="!componentReady"
  >
    <template #actions>
      <IButtonMinimal
        variant="info"
        @click="$router.back"
        :text="$t('core::app.go_back')"
      />
    </template>
    <form @keydown="form.onKeydown($event)" @submit.prevent="update">
      <ICardBody>
        <IFormGroup
          label-for="name"
          :label="$t('deals::deal.pipeline.name')"
          required
        >
          <IFormInput v-model="form.name" id="name" name="name" type="text" />
          <IFormError v-text="form.getError('name')" />
        </IFormGroup>

        <IFormGroup class="mt-4">
          <VisibilityGroupSelector
            :disabled="pipeline.is_primary"
            v-model:type="form.visibility_group.type"
            v-model:dependsOn="form.visibility_group.depends_on"
          />
        </IFormGroup>

        <IAlert
          variant="info"
          :show="componentReady && pipeline.is_primary"
          class="mt-4"
        >
          {{ $t('deals::deal.pipeline.visibility_group.primary_restrictions') }}
        </IAlert>
      </ICardBody>
      <ITable>
        <thead>
          <tr>
            <th class="text-left" v-t="'deals::deal.stage.name'"></th>
            <th
              class="text-left"
              v-t="'deals::deal.stage.win_probability'"
            ></th>
          </tr>
        </thead>
        <draggable
          v-model="form.stages"
          tag="tbody"
          :item-key="(item, index) => index"
          v-bind="draggableOptions"
          handle=".draggable-handle"
        >
          <template #item="{ element, index }">
            <tr>
              <td class="w-full sm:w-auto">
                <div class="flex rounded-md shadow-sm">
                  <div
                    class="relative flex grow items-stretch focus-within:z-10"
                  >
                    <div
                      class="absolute inset-y-0 left-0 flex items-center pl-3"
                    >
                      <IButtonIcon
                        icon="Selector"
                        class="draggable-handle cursor-move"
                      />
                    </div>
                    <div
                      v-if="element.id"
                      class="absolute inset-y-0 left-11 hidden w-14 border-l border-r border-neutral-300 px-2 dark:border-neutral-500 sm:flex sm:items-center sm:justify-center"
                    >
                      ID: {{ element.id }}
                    </div>
                    <IFormInput
                      ref="stageNameInputRef"
                      class="rounded-l-md"
                      :rounded="false"
                      :class="[element.id ? 'pl-10 sm:pl-[6.7rem]' : 'pl-10']"
                      @keydown.enter="newStage"
                      v-model="form.stages[index].name"
                    />
                  </div>
                  <IButton
                    variant="white"
                    :text="$t('core::app.delete')"
                    :rounded="false"
                    @click="deleteStage(index)"
                    class="relative -ml-px rounded-r-md"
                  />
                </div>
                <IFormError
                  v-text="form.getError('stages.' + index + '.name')"
                />
              </td>
              <td>
                <div class="mt-sm-0 mt-4 flex items-center">
                  <div class="mr-4 grow">
                    <input
                      v-model="form.stages[index].win_probability"
                      type="range"
                      class="h-2 w-full appearance-none rounded-md border border-neutral-200 bg-neutral-200 dark:border-neutral-500 dark:bg-neutral-700"
                      :min="1"
                      :max="100"
                    />
                  </div>
                  <div>
                    {{ form.stages[index].win_probability }}
                  </div>
                </div>
                <IFormError
                  v-text="form.getError('stages.' + index + '.win_probability')"
                />
              </td>
            </tr>
          </template>
        </draggable>

        <tfoot>
          <tr>
            <td colspan="2" class="px-7 py-3">
              <IButtonMinimal
                variant="primary"
                :text="$t('deals::deal.stage.add')"
                @click="newStage"
              />
            </td>
          </tr>
        </tfoot>
      </ITable>
    </form>
    <template #footer>
      <div class="flex justify-end">
        <IButton
          type="button"
          @click="update"
          :disabled="form.busy"
          :text="$t('core::app.save')"
        />
      </div>
    </template>
  </ICard>
</template>
<script setup>
import { ref, nextTick } from 'vue'
import VisibilityGroupSelector from '~/Core/resources/js/components/VisibilityGroupSelector.vue'
import draggable from 'vuedraggable'
import map from 'lodash/map'
import { useDraggable } from '~/Core/resources/js/composables/useDraggable'
import { useRoute } from 'vue-router'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useI18n } from 'vue-i18n'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { usePipelines } from '../composables/usePipelines'

const { t } = useI18n()
const route = useRoute()
const { draggableOptions } = useDraggable()
const { resetStoreState } = useApp()
const { setPipeline, fetchPipeline } = usePipelines()

const stageNameInputRef = ref(null)

const pipeline = ref({})
const componentReady = ref(false)

const { form } = useForm({
  name: null,
  stages: [],
  visibility_group: {
    type: 'all',
    depends_on: [],
  },
})

async function update() {
  form.stages = map(form.stages, (stage, index) => {
    stage.display_order = index
    return stage
  })

  const pipeline = await form.put(`/pipelines/${route.params.id}`, {
    params: {
      with: 'visibilityGroup.users;visibilityGroup.teams',
    },
  })

  setPipeline(pipeline.id, pipeline)
  resetStoreState()
  // Update the stages in case new stages are created so we can have the ID's
  form.stages = pipeline.stages

  Innoclapps.success(t('deals::deal.pipeline.updated'))
}

function newStage() {
  form.stages.push({
    name: '',
    win_probability: 100,
  })

  nextTick(() => {
    stageNameInputRef.value.focus()
  })
}

function removeStageFromForm(index) {
  form.stages.splice(index, 1)
}

async function deleteStage(index) {
  let stageId = form.stages[index].id

  // Form not yet saved, e.q. user added new stage then want to
  // delete before saving the form
  if (!stageId) {
    removeStageFromForm(index)
    return
  }

  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/pipeline-stages/${stageId}`)

  resetStoreState()
  removeStageFromForm(index)
}

async function prepareComponent() {
  try {
    const response = await fetchPipeline(route.params.id, {
      params: {
        with: 'visibilityGroup.users;visibilityGroup.teams',
      },
    })

    pipeline.value = response

    form.fill('name', response.name)
    form.fill('stages', response.stages)

    if (response.visibility_group) {
      form.fill('visibility_group', response.visibility_group)
    }

    if (form.stages.length === 0) {
      newStage()
    }
  } finally {
    componentReady.value = true
  }
}

prepareComponent()
</script>
