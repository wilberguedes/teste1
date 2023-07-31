<template>
  <TimelineEntry
    :resource-name="resourceName"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned || false"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timelineable-key="log.timeline_key"
    icon="PencilAlt"
    :heading="$t('notes::note.timeline.heading')"
    heading-class="font-medium"
  >
    <div class="mt-3">
      <Note
        :note-id="log.id"
        :comments-count="log.comments_count"
        :created-at="log.created_at"
        :body="log.body"
        :user-id="log.user_id"
        :authorizations="log.authorizations"
        :via-resource="resourceName"
        collapsable
      />
    </div>
  </TimelineEntry>
</template>
<script setup>
import Note from './NoteListItem.vue'
import TimelineEntry from '~/Core/resources/js/views/Timeline/RecordTabTimelineTemplate.vue'
import propsDefinition from '~/Core/resources/js/views/Timeline/props'
defineProps(propsDefinition)
</script>
