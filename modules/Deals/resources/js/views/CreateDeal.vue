<template>
  <CreateDealModal
    :title="modalTitle"
    :ok-title="
      hasSelectedExistingDeal
        ? $t('core::app.associate')
        : $t('core::app.create')
    "
    :fields-visible="!hasSelectedExistingDeal"
    :with-extended-submit-buttons="!hasSelectedExistingDeal"
    :create-using="
      createFunc => (hasSelectedExistingDeal ? associate() : createFunc())
    "
    @ready="handleReady"
  >
    <template #top="{ isReady }">
      <div
        v-if="viaResource"
        v-show="isReady"
        class="mb-4 rounded-md border border-success-400 px-4 py-3"
      >
        <FieldsGenerator
          :form-id="associateForm.formId"
          :fields="associateField"
          view="create"
        />
      </div>
    </template>
  </CreateDealModal>
</template>

<script setup>
import { computed } from 'vue'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useI18n } from 'vue-i18n'
import { useApp } from '~/Core/resources/js/composables/useApp'

const emit = defineEmits(['associated'])

const props = defineProps({
  viaResource: String,
})

const { t } = useI18n()
const { setPageTitle } = useApp()

const { fields: associateField } = useResourceFields([
  {
    asyncUrl: '/deals/search',
    attribute: 'id',
    component: 'SelectField',
    helpText: t('deals::deal.associate_field_info'),
    helpTextDisplay: 'text',
    label: t('deals::deal.deal'),
    labelKey: 'name',
    valueKey: 'id',
    lazyLoad: { url: '/deals', params: { order: 'created_at|desc' } },
  },
])

const { form: associateForm } = useFieldsForm(associateField, {
  id: null,
})

const { record } = useRecordStore()

const hasSelectedExistingDeal = computed(
  () => !!associateField.value.find('id').currentValue
)

const modalTitle = computed(() => {
  if (!props.viaResource) {
    return t('deals::deal.create')
  }

  if (!hasSelectedExistingDeal.value) {
    return t('deals::deal.create_with', {
      name: record.value.display_name,
    })
  }

  return t('deals::deal.associate_with', {
    name: record.value.display_name,
  })
})

function associate() {
  associateForm.hydrate()

  Innoclapps.request()
    .put(`associations/${props.viaResource}/${record.value.id}`, {
      deals: [associateForm.id],
    })
    .then(() => {
      emit('associated', associateForm.id)

      Innoclapps.success(t('core::resource.associated'))
    })
}

function handleReady(fields) {
  setPageTitle(modalTitle)

  if (props.viaResource) {
    fields.value.updateValue(props.viaResource, [record.value])
  }
}
</script>
