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
        v-t="'webforms::form.sections.message.message'"
      />
    </template>

    <template #actions>
      <div class="inline-flex space-x-2">
        <IButtonIcon
          icon="PencilAlt"
          class="block md:hidden md:group-hover:block"
          icon-class="h-4 w-4"
          v-show="!editing"
          @click="setEditingMode"
        />
        <IButtonIcon
          icon="Trash"
          class="block md:hidden md:group-hover:block"
          icon-class="h-4 w-4"
          @click="requestSectionRemove"
        />
      </div>
    </template>

    <div
      v-show="!editing"
      v-html="section.message"
      class="text-sm text-neutral-600 dark:text-neutral-200"
    />

    <div v-if="editing">
      <IFormGroup :label="$t('webforms::form.sections.message.message')">
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

const emit = defineEmits([
  'update-section-requested',
  'remove-section-requested',
])

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
})

const editing = ref(false)
const message = ref(null)

function requestSectionSave() {
  emit('update-section-requested', {
    message: message.value,
  })

  editing.value = false
}

function requestSectionRemove() {
  emit('remove-section-requested')
}

function setEditingMode() {
  message.value = props.section.message
  editing.value = true
}
</script>
