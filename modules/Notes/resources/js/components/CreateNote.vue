<template>
  <ICard tag="form" @submit.prevent="create" method="POST">
    <Editor
      :placeholder="$t('notes::note.write')"
      v-model="form.body"
      :with-mention="true"
      @init="() => $refs.editorRef.focus()"
      @input="form.errors.clear('body')"
      ref="editorRef"
    />
    <IFormError v-text="form.getError('body')" />
    <template #footer>
      <div class="flex flex-col sm:flex-row sm:items-center">
        <div class="grow">
          <CreateFollowUpTask :form="form" />
        </div>
        <div class="mt-2 space-y-2 sm:mt-0 sm:space-x-2 sm:space-y-0">
          <IButton
            class="w-full sm:w-auto"
            variant="white"
            size="sm"
            :text="$t('core::app.cancel')"
            @click="$emit('cancel')"
          />
          <IButton
            class="w-full sm:w-auto"
            @click="create"
            size="sm"
            :text="$t('notes::note.add')"
            :disabled="form.busy"
          />
        </div>
      </div>
    </template>
  </ICard>
</template>
<script setup>
import CreateFollowUpTask from '~/Activities/resources/js/components/CreateFollowUpTask.vue'
import { useI18n } from 'vue-i18n'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['cancel'])

const props = defineProps({
  viaResource: { required: true, type: String },
})

const { t } = useI18n()

const {
  addResourceRecordHasManyRelationship,
  record,
  incrementResourceRecordCount,
} = useRecordStore()

const { form } = useForm(
  {
    body: '',
    with_task: false,
    task_date: null,
  },
  {
    resetOnSuccess: true,
  }
)

function handleNoteCreated(note) {
  if (note.createdActivity) {
    addResourceRecordHasManyRelationship(note.createdActivity, 'activities')
    incrementResourceRecordCount('incomplete_activities_for_user_count')
    delete note.createdActivity
  }

  addResourceRecordHasManyRelationship(note, 'notes')
  incrementResourceRecordCount('notes_count')
  Innoclapps.success(t('notes::note.created'))
}

function create() {
  form
    .set(props.viaResource, [record.value.id])
    .withQueryString({
      via_resource: props.viaResource,
      via_resource_id: record.value.id,
    })
    .post('/notes')
    .then(handleNoteCreated)
}
</script>
