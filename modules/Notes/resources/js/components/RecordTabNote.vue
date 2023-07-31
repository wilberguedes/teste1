<template>
  <ITab
    :badge="record.notes_count"
    badge-variant="neutral"
    :title="$t('notes::note.notes')"
    icon="PencilAlt"
  />
</template>
<script setup>
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const { record } = useRecordStore()
</script>
