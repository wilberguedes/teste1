<template>
  <ICard
    tag="form"
    @submit.prevent="create"
    method="POST"
    :overlay="fields.isEmpty()"
  >
    <FieldsGenerator
      :form-id="form.formId"
      view="create"
      :via-resource="viaResource"
      :via-resource-id="record.id"
      :fields="fields"
    />
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
            size="sm"
            :disabled="form.busy"
            :text="$t('calls::call.add')"
            @click="create"
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
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'

const emit = defineEmits(['cancel'])

const props = defineProps({
  viaResource: { required: true, type: String },
})

const { t } = useI18n()

const { fields, getCreateFields } = useResourceFields()

const { form } = useFieldsForm(
  fields,
  {
    with_task: false,
    task_date: null,
  },
  {
    resetOnSuccess: true,
  }
)

const {
  record,
  addResourceRecordHasManyRelationship,
  incrementResourceRecordCount,
} = useRecordStore()

function handleCallCreated(call) {
  if (call.createdActivity) {
    addResourceRecordHasManyRelationship(call.createdActivity, 'activities')
    incrementResourceRecordCount('incomplete_activities_for_user_count')

    delete call.createdActivity
  }

  addResourceRecordHasManyRelationship(call, 'calls')
  incrementResourceRecordCount('calls_count')

  Innoclapps.success(t('calls::call.created'))
}

function create() {
  form
    .set(props.viaResource, [record.value.id])
    .withQueryString({
      via_resource: props.viaResource,
      via_resource_id: record.value.id,
    })
    .hydrate()
    .post('/calls')
    .then(handleCallCreated)
}

function prepareComponent() {
  getCreateFields(Innoclapps.config('fields.groups.calls'), {
    viaResource: props.viaResource,
    viaResourceId: record.value.id,
  }).then(createFields => fields.value.set(createFields))
}

prepareComponent()
</script>
