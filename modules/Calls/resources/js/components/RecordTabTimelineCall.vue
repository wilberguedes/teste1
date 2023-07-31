<template>
  <TimelineEntry
    :resource-name="resourceName"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned || false"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timelineable-key="log.timeline_key"
    icon="Phone"
    :heading="$t('calls::call.timeline.heading')"
    heading-class="font-medium"
  >
    <div class="mt-3">
      <Call
        :call-id="log.id"
        :comments-count="log.comments_count"
        :call-date="log.date"
        :body="log.body"
        :user-id="log.user_id"
        :outcome-id="log.call_outcome_id"
        :authorizations="log.authorizations"
        :via-resource="resourceName"
        collapsable
      />
    </div>
  </TimelineEntry>
</template>
<script setup>
import Call from './CallListItem.vue'
import TimelineEntry from '~/Core/resources/js/views/Timeline/RecordTabTimelineTemplate.vue'
import propsDefinition from '~/Core/resources/js/views/Timeline/props'
defineProps(propsDefinition)
</script>
