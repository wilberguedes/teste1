<template>
  <ITab
    :badge="badge"
    :badge-variant="badgeVariant"
    :title="$t('documents::document.documents')"
    icon="DocumentText"
  />
</template>
<script setup>
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { computed } from 'vue'

const { record } = useRecordStore()

const badge = computed(() =>
  record.value.draft_documents_for_user_count > 0
    ? record.value.draft_documents_for_user_count
    : record.value.documents_for_user_count
)

const badgeVariant = computed(() => {
  return (record.value.documents || []).filter(
    document => document.status === 'draft'
  ).length > 0
    ? 'danger'
    : 'neutral'
})
</script>
