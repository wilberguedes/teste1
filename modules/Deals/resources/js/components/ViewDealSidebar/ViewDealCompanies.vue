<template>
  <DealCompanyCard
    :deal-id="deal.id"
    :companies="deal.companies"
    :authorize-dissociate="deal.authorizations.update"
    :show-create-button="deal.authorizations.update"
    @create-requested="
      $router.push({ name: 'createCompanyViaDeal', params: { id: deal.id } })
    "
    @dissociated="$emit('refetch')"
  />
</template>
<script setup>
import DealCompanyCard from '../Cards/DealCompanyCard.vue'

defineEmits(['refetch'])

defineProps({
  deal: { required: true, type: Object },
})
</script>
