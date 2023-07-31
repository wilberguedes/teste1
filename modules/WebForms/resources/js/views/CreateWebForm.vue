<template>
  <IModal
    size="sm"
    @hidden="$router.back"
    @shown="() => $refs.inputTitleRef.focus()"
    :ok-title="$t('core::app.create')"
    :ok-disabled="form.busy"
    :visible="true"
    static-backdrop
    :title="$t('webforms::form.create')"
    form
    @submit="create"
    @keydown="form.onKeydown($event)"
  >
    <IFormGroup
      label-for="title"
      :description="$t('webforms::form.title_visibility_info')"
      :label="$t('webforms::form.title')"
      required
    >
      <IFormInput ref="inputTitleRef" v-model="form.title" id="title" />
      <IFormError v-text="form.getError('title')" />
    </IFormGroup>
    <div class="mb-2">
      <h5
        class="mb-3 font-medium text-neutral-700 dark:text-neutral-300"
        v-t="'webforms::form.style.style'"
      />
      <IFormGroup :label="$t('webforms::form.style.primary_color')">
        <IColorSwatches
          :allow-remove="false"
          v-model="form.styles.primary_color"
          :swatches="swatches"
        />
        <IFormError v-text="form.getError('styles.primary_color')" />
      </IFormGroup>
      <IFormGroup :label="$t('webforms::form.style.background_color')">
        <IColorSwatches
          :allow-remove="false"
          v-model="form.styles.background_color"
          :swatches="swatches"
        />
        <IFormError v-text="form.getError('styles.background_color')" />
      </IFormGroup>
    </div>
  </IModal>
</template>
<script setup>
import { useRouter } from 'vue-router'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useWebForms } from '../composables/useWebForms'

const router = useRouter()
const { addWebForm } = useWebForms()

const swatches = Innoclapps.config('favourite_colors')

const { form } = useForm({
  title: null,
  styles: {
    primary_color: '#4f46e5',
    background_color: '#F3F4F6',
  },
})

function create() {
  form.post('/forms').then(data => {
    addWebForm(data)

    router.push({
      name: 'web-form-edit',
      params: {
        id: data.id,
      },
    })
  })
}
</script>
