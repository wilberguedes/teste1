<template>
  <ICard class="border border-primary-400">
    <template #header>
      <p
        class="font-semibold text-neutral-800 dark:text-neutral-200"
        v-t="'webforms::form.sections.new'"
      />
    </template>
    <template #actions>
      <IButtonIcon
        icon="X"
        icon-class="h-4 w-4"
        @click="requestSectionRemove"
      />
    </template>
    <IFormGroup
      :label="$t('webforms::form.sections.type')"
      label-for="section_type"
    >
      <ICustomSelect
        label="label"
        field-id="section_type"
        :options="sectionTypes"
        :clearable="false"
        :reduce="type => type.id"
        @option:selected="
          $event.id === 'file' ? (fieldLabel = 'Attachment') : null,
            $event.id !== 'field' ? (field = null) : ''
        "
        v-model="sectionType"
      />
    </IFormGroup>
    <IFormGroup
      v-if="sectionType === 'message'"
      :label="$t('webforms::form.sections.message.message')"
    >
      <Editor :with-image="false" v-model="message" />
    </IFormGroup>
    <div v-else>
      <IFormGroup
        :label="$t('webforms::form.sections.field.resourceName')"
        label-for="resourceName"
      >
        <ICustomSelect
          label="label"
          field-id="resourceName"
          :clearable="false"
          :options="availableResources"
          @option:selected="field = null"
          :reduce="resource => resource.id"
          v-model="resourceName"
        />
      </IFormGroup>
      <IFormGroup
        v-if="sectionType === 'field'"
        :label="$t('core::fields.field')"
        label-for="field"
      >
        <ICustomSelect
          label="label"
          field-id="field"
          :clearable="false"
          :selectable="field => field.disabled"
          :options="availableFields"
          @option:selected="handleFieldChanged"
          v-model="field"
        />
      </IFormGroup>
      <IFormGroup
        v-show="field !== null || sectionType === 'file'"
        :label="$t('core::fields.label')"
      >
        <Editor
          :with-image="false"
          default-tag="div"
          toolbar="bold italic underline link removeformat"
          v-model="fieldLabel"
        />
      </IFormGroup>
      <IFormGroup>
        <IFormCheckbox
          id="new-is-required"
          name="new-is-required"
          v-model:checked="isRequired"
          :disabled="fieldMustBeRequired"
          v-show="field !== null || sectionType === 'file'"
          :label="$t('core::fields.is_required')"
        />

        <IFormCheckbox
          id="new-file-multiple"
          name="new-file-multiple"
          v-model:checked="fileAcceptMultiple"
          v-show="sectionType === 'file'"
          :label="$t('webforms::form.sections.file.multiple')"
        />
      </IFormGroup>
    </div>

    <div class="space-x-2 text-right">
      <IButton
        size="sm"
        @click="requestSectionRemove"
        variant="white"
        :text="$t('core::app.cancel')"
      />
      <IButton
        size="sm"
        @click="requestNewSection"
        :disabled="saveIsDisabled"
        :text="$t('core::app.save')"
        variant="secondary"
      />
    </div>
  </ICard>
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref, toRef, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFieldSection } from './useSectionField'

const emit = defineEmits(['create-section-requested', 'remove-section-requested'])

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
  companiesFields: { required: true },
  contactsFields: { required: true },
  dealsFields: { required: true },
  availableResources: { required: true },
})

const { t } = useI18n()

const sectionType = ref('field')
const resourceName = ref('contacts')
const fileAcceptMultiple = ref(false)
const message = ref(null)

const currentResourceFields = computed(
  () => props[resourceName.value + 'Fields']
)

const {
  field,
  availableFields,
  handleFieldChanged,
  fieldLabel,
  isRequired,
  fieldMustBeRequired,
  generateRequestAttribute,
} = useFieldSection(
  resourceName,
  currentResourceFields,
  toRef(props.form, 'sections')
)

const sectionTypes = [
  {
    id: 'field',
    label: t('webforms::form.sections.types.input_field'),
  },
  {
    id: 'message',
    label: t('webforms::form.sections.types.message'),
  },
  {
    id: 'file',
    label: t('webforms::form.sections.types.file'),
  },
]

const saveIsDisabled = computed(() => {
  if (sectionType.value === 'field') {
    return (
      fieldLabel.value === null || fieldLabel.value == '' || field.value == null
    )
  } else if (sectionType.value === 'message') {
    return message.value === null || message.value == ''
  } else if (sectionType.value === 'file') {
    return fieldLabel.value === null || fieldLabel.value == ''
  }
})

function requestNewMessageSection() {
  emit('create-section-requested', {
    type: 'message-section',
    message: message.value,
  })
}

function requestNewFieldSection() {
  emit('create-section-requested', {
    type: 'field-section',
    isRequired: isRequired.value,
    label: fieldLabel.value,
    resourceName: resourceName.value,
    attribute: field.value.attribute,
    requestAttribute: generateRequestAttribute(),
  })
}

function requestNewFileSection() {
  emit('create-section-requested', {
    type: 'file-section',
    isRequired: isRequired.value,
    label: fieldLabel.value,
    resourceName: resourceName.value,
    multiple: fileAcceptMultiple.value,
    requestAttribute: generateRequestAttribute(),
  })
}

function requestNewSection() {
  if (sectionType.value === 'message') {
    requestNewMessageSection()
  } else if (sectionType.value === 'file') {
    requestNewFileSection()
  } else {
    requestNewFieldSection()
  }
}

function requestSectionRemove() {
  emit('remove-section-requested')
}
</script>
