<template>
  <form @keydown="form.onKeydown($event)" @submit.prevent="">
    <IFormGroup
      label-for="name"
      :label="$t('mailclient::mail.templates.name')"
      required
    >
      <IFormInput v-model="form.name" />
      <IFormError v-text="form.getError('name')" />
    </IFormGroup>
    <IFormGroup
      label-for="subject"
      :label="$t('mailclient::mail.templates.subject')"
      required
    >
      <IFormInput
        v-model="form.subject"
        :class="{
          'border-dashed !border-neutral-400': subjectDragover,
        }"
        @dragover="subjectDragover = true"
        @dragleave="subjectDragover = false"
        @drop="subjectDragover = false"
      />
      <IFormError v-text="form.getError('subject')" />
    </IFormGroup>
    <IFormGroup
      label-for="body"
      :label="$t('mailclient::mail.templates.body')"
      required
    >
      <MailEditor
        v-model="form.body"
        :placeholders="placeholders"
        :placeholders-disabled="true"
      />
      <IFormError v-text="form.getError('body')" />
    </IFormGroup>
    <IFormGroup>
      <IFormCheckbox
        id="is_shared"
        name="is_shared"
        v-model:checked="form.is_shared"
        :label="$t('mailclient::mail.templates.is_shared')"
      />
      <IFormError v-text="form.getError('is_shared')" />
    </IFormGroup>
    <slot name="bottom"></slot>
  </form>
</template>
<script setup>
import { ref, computed } from 'vue'
import MailEditor from '../../components/MailEditor'
import { useStore } from 'vuex'

defineProps({
  form: { type: Object, default: () => ({}) },
})

const subjectDragover = ref(false)

const store = useStore()

const placeholders = computed(() => store.state.fields.placeholders)

store.dispatch('fields/fetchPlaceholders')
</script>
