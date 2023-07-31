<template>
  <ICard>
    <Editor
      v-model="form.body"
      @input="form.errors.clear('body')"
      :with-mention="true"
    />

    <IFormError v-text="form.getError('body')" />

    <template #footer>
      <div class="flex justify-end space-x-2">
        <IButton
          variant="white"
          size="sm"
          :text="$t('core::app.cancel')"
          @click="$emit('cancelled')"
        />

        <IButton
          variant="primary"
          size="sm"
          @click="update"
          :text="$t('core::app.save')"
          :disabled="form.busy"
        />
      </div>
    </template>
  </ICard>
</template>
<script setup>
import { useI18n } from 'vue-i18n'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['updated', 'cancelled'])

const props = defineProps({
  viaResource: { required: true, type: String },
  noteId: { required: true, type: Number },
  body: { required: true, type: String },
})

const { t } = useI18n()

const { record, updateResourceRecordHasManyRelationship } = useRecordStore()

const { form } = useForm({
  body: props.body,
})

function update() {
  form
    .withQueryString({
      via_resource: props.viaResource,
      via_resource_id: record.value.id,
    })
    .put(`/notes/${props.noteId}`)
    .then(updatedNote => {
      updateResourceRecordHasManyRelationship(updatedNote, 'notes')

      emit('updated', updatedNote)

      Innoclapps.success(t('notes::note.updated'))
    })
}
</script>
