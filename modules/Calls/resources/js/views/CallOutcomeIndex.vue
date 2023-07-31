<template>
  <RelatedFieldResource
    resource-name="call-outcomes"
    @created="fetchCallOutcomes"
    @updated="fetchCallOutcomes"
    @deleted="fetchCallOutcomes"
    :with-cancel="false"
  >
    <template #header>
      <ICardHeading>{{ $t('calls::call.outcome.outcomes') }}</ICardHeading>
    </template>
  </RelatedFieldResource>
</template>
<script setup>
import RelatedFieldResource from '~/Core/resources/js/components/SimpleResourceCRUD.vue'
import { useCallOutcomes } from '../composables/useCallOutcomes'

const { fetchCallOutcomes } = useCallOutcomes()
</script>
