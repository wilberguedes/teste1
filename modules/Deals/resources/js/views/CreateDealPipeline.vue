<template>
  <IModal
    size="sm"
    @hidden="$router.back"
    form
    @keydown="form.onKeydown($event)"
    @submit="create"
    @shown="() => $refs.inputNameRef.focus()"
    :visible="true"
    :ok-title="$t('core::app.create')"
    :ok-disabled="form.busy"
    :title="$t('deals::deal.pipeline.create')"
  >
    <IFormGroup
      label-for="name"
      :label="$t('deals::deal.pipeline.name')"
      required
    >
      <IFormInput
        v-model="form.name"
        id="name"
        ref="inputNameRef"
        name="name"
        type="text"
      />
      <IFormError v-text="form.getError('name')" />
    </IFormGroup>
  </IModal>
</template>
<script setup>
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useRouter } from 'vue-router'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { usePipelines } from '../composables/usePipelines'

const router = useRouter()
const { addPipeline } = usePipelines()

const { resetStoreState } = useApp()

const { form } = useForm({
  name: null,
})

function create() {
  form.post('/pipelines').then(pipeline => {
    addPipeline(pipeline)
    resetStoreState()
    router.push('/settings/deals/pipelines/' + pipeline.id + '/edit')
  })
}
</script>
