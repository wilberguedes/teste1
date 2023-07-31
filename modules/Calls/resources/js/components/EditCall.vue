<template>
  <ICard :overlay="fields.isEmpty()">
    <FieldsGenerator
      :form-id="form.formId"
      :via-resource="viaResource"
      :via-resource-id="record.id"
      view="update"
      :fields="fields"
    />
    <template #footer>
      <div class="flex justify-end space-x-2">
        <IButton
          variant="white"
          size="sm"
          @click="$emit('cancelled')"
          :text="$t('core::app.cancel')"
        />
        <IButton
          variant="primary"
          size="sm"
          @click="update"
          :disabled="form.busy"
          :text="$t('core::app.save')"
        />
      </div>
    </template>
  </ICard>
</template>
<script setup>
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'
import { useI18n } from 'vue-i18n'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const emit = defineEmits(['updated', 'cancelled'])

const props = defineProps({
  viaResource: { required: true, type: String },
  callId: { required: true, type: Number },
})

const { t } = useI18n()

const { record, updateResourceRecordHasManyRelationship } = useRecordStore()

const { fields, getUpdateFields } = useResourceFields()

const { form } = useFieldsForm(fields)

function update() {
  form
    .withQueryString({
      via_resource: props.viaResource,
      via_resource_id: record.value.id,
    })
    .hydrate()
    .put(`/calls/${props.callId}`)
    .then(handleCallUpdated)
}

function handleCallUpdated(updatedCall) {
  updateResourceRecordHasManyRelationship(updatedCall, 'calls')

  emit('updated', updatedCall)

  Innoclapps.success(t('calls::call.updated'))
}

function prepareComponent() {
  Innoclapps.request()
    .get(`/calls/${props.callId}`)
    .then(({ data }) => {
      getUpdateFields(Innoclapps.config('fields.groups.calls'), props.callId, {
        viaResource: props.viaResource,
        viaResourceId: record.value.id,
      }).then(updateFields => {
        fields.value.set(updateFields).populate(data)
      })
    })
}

prepareComponent()
</script>
