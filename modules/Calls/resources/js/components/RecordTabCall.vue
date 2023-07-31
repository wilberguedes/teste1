<template>
  <ITab
    :badge="record.calls_count"
    badge-variant="neutral"
    :title="$t('calls::call.calls')"
    icon="Phone"
  />
</template>
<script setup>
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const { record } = useRecordStore()
</script>
