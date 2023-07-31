<template>
  <MediaCard
    :resource-name="resourceName"
    :resource-id="deal.id"
    :media="deal.media"
    :authorize-delete="deal.authorizations.update"
    @deleted="removeResourceRecordMedia"
    @uploaded="addResourceRecordMedia"
  />
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import MediaCard from '~/Core/resources/js/components/Media/ResourceRecordMediaCard.vue'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const props = defineProps({
  deal: { required: true, type: Object },
})

const resourceName = Innoclapps.config('resources.deals.name')

const { removeResourceRecordMedia, addResourceRecordMedia } = useRecordStore()
</script>
