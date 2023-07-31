<template>
  <div>
    <ICard
      no-body
      :header="$t('documents::document.documents')"
      class="mb-3"
      :overlay="!componentReady"
    >
      <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
        <li class="px-4 py-4 sm:px-6">
          <IFormGroup
            :label="$t('documents::document.type.default_type')"
            class="mb-0"
            label-for="default_document_type"
          >
            <ICustomSelect
              input-id="default_document_type"
              v-model="defaultType"
              class="xl:w-1/3"
              :clearable="false"
              @option:selected="handleDocumentTypeInputEvent"
              label="name"
              :options="documentTypes"
            >
            </ICustomSelect>
          </IFormGroup>
        </li>
      </ul>
    </ICard>
    <DocumentTypeIndex></DocumentTypeIndex>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { watchOnce } from '@vueuse/core'
import DocumentTypeIndex from '../views/DocumentTypeIndex.vue'
import { useSettings } from '~/Core/resources/js/views/Settings/useSettings'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useDocumentTypes } from '../composables/useDocumentTypes'
const { resetStoreState } = useApp()
const { form, submit, isReady: componentReady } = useSettings()

const defaultType = ref(null)

const { typesByName: documentTypes } = useDocumentTypes()

function handleDocumentTypeInputEvent(e) {
  form.default_document_type = e.id
  submit(resetStoreState)
}

watchOnce(componentReady, function (newVal, oldVal) {
  defaultType.value = documentTypes.value.find(
    type => type.id == form.default_document_type
  )
})
</script>
