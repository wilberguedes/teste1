<template>
  <ICard
    class="group"
    :class="{
      'border border-primary-400': editing,
      'border border-transparent transition duration-75 hover:border-primary-400 dark:border dark:border-neutral-700':
        !editing,
    }"
  >
    <template #header>
      <p
        class="font-semibold text-neutral-800 dark:text-neutral-200"
        v-t="'webforms::form.sections.introduction.introduction'"
      />
    </template>
    <template #actions>
      <IButtonIcon
        icon="PencilAlt"
        v-show="!editing"
        class="block md:hidden md:group-hover:block"
        icon-class="h-4 w-4"
        @click="setEditingMode"
      />
    </template>
    <div v-show="!editing">
      <h4
        class="text-lg font-medium text-neutral-800 dark:text-neutral-100"
        v-text="section.title"
      />
      <p
        class="text-sm text-neutral-600 dark:text-neutral-300"
        v-html="section.message"
      />
    </div>
    <div v-if="editing">
      <IFormGroup
        :label="$t('webforms::form.sections.introduction.title')"
        label-for="title"
      >
        <IFormInput id="title" v-model="title" />
      </IFormGroup>
      <IFormGroup :label="$t('webforms::form.sections.introduction.message')">
        <Editor :with-image="false" v-model="message" />
      </IFormGroup>
      <div class="space-x-2 text-right">
        <IButton
          size="sm"
          @click="editing = false"
          variant="white"
          :text="$t('core::app.cancel')"
        />
        <IButton
          size="sm"
          @click="requestSectionSave"
          variant="secondary"
          :text="$t('core::app.save')"
        />
      </div>
    </div>
  </ICard>
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref } from 'vue'

const emit = defineEmits(['update-section-requested'])

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
})

const editing = ref(false)
const title = ref(null)
const message = ref(null)

function requestSectionSave() {
  emit('update-section-requested', {
    title: title.value,
    message: message.value,
  })

  editing.value = false
}

function setEditingMode() {
  title.value = props.section.title
  message.value = props.section.message
  editing.value = true
}
</script>
