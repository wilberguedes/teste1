<template>
  <div class="space-y-4">
    <div v-for="note in notes" :key="note.id">
      <Note
        :note-id="note.id"
        :comments-count="note.comments_count"
        :created-at="note.created_at"
        :body="note.body"
        :user-id="note.user_id"
        :authorizations="note.authorizations"
        :via-resource="viaResource"
      />
    </div>
  </div>
</template>
<script setup>
import Note from './NoteListItem.vue'

defineProps({
  notes: Array,
  viaResource: { type: String, required: true },
})
</script>
