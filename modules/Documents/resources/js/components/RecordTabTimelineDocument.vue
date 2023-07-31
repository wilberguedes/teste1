<template>
  <TimelineEntry
    :resource-name="resourceName"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned || false"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timelineable-key="log.timeline_key"
    :icon="log.type.icon"
    :heading="$t('documents::document.timeline.heading')"
    heading-class="font-medium"
  >
    <Document
      class="mt-3"
      :document-id="log.id"
      :type-id="log.document_type_id"
      :status="log.status"
      :display-name="log.display_name"
      :path="log.path"
      :public-url="log.public_url"
      :accepted-at="log.accepted_at"
      :last-date-sent="log.last_date_sent"
      :amount="log.amount"
      :authorizations="log.authorizations"
      :associations="log.associations"
      :via-resource="resourceName"
    />
  </TimelineEntry>
</template>
<script setup>
import Document from './RelatedDocumentListItem.vue'
import TimelineEntry from '~/Core/resources/js/views/Timeline/RecordTabTimelineTemplate.vue'
import propsDefinition from '~/Core/resources/js/views/Timeline/props'
defineProps(propsDefinition)
</script>
