<template>
  <ITab
    :badge="totalProducts"
    badge-variant="neutral"
    :title="$t('billable::product.products')"
    icon="Banknotes"
  />
</template>
<script setup>
import { computed } from 'vue'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const { record } = useRecordStore()

const totalProducts = computed(() =>
  !record.value || !record.value.billable
    ? 0
    : record.value.billable.products.length
)
</script>
