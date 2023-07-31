<template>
  <RelatedFieldResource
    resource-name="document-types"
    @created="fetchDocumentTypes"
    @updated="fetchDocumentTypes"
    @deleted="fetchDocumentTypes"
    :with-cancel="false"
  >
    <template #header>
      <ICardHeading>{{ $t('documents::document.type.types') }}</ICardHeading>
    </template>
  </RelatedFieldResource>
</template>
<script setup>
import RelatedFieldResource from '~/Core/resources/js/components/SimpleResourceCRUD.vue'
import { useDocumentTypes } from '../composables/useDocumentTypes'

const { fetchDocumentTypes } = useDocumentTypes()
</script>
