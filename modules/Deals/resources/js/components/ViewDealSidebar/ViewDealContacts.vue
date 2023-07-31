<template>
  <DealContactCard
    :deal-id="deal.id"
    :contacts="deal.contacts"
    :authorize-dissociate="deal.authorizations.update"
    :show-create-button="deal.authorizations.update"
    @create-requested="
      $router.push({ name: 'createContactViaDeal', params: { id: deal.id } })
    "
    @dissociated="$emit('refetch')"
  />
</template>
<script setup>
import DealContactCard from '../Cards/DealContactCard.vue'

defineEmits(['refetch'])

defineProps({
  deal: { required: true, type: Object },
})
</script>
