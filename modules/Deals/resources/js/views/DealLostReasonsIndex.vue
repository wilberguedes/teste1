<template>
  <RelatedFieldResource
    resource-name="lost-reasons"
    @created="fetchLostReasons"
    @updated="fetchLostReasons"
    @deleted="fetchLostReasons"
    :with-cancel="false"
  >
    <template #header>
      <ICardHeading>
        {{ $t('deals::deal.lost_reasons.lost_reasons') }}
      </ICardHeading>
    </template>
  </RelatedFieldResource>
</template>
<script setup>
import RelatedFieldResource from '~/Core/resources/js/components/SimpleResourceCRUD.vue'
import { useLostReasons } from '../composables/useLostReasons'

const { fetchLostReasons } = useLostReasons()
</script>
