<template>
  <div class="space-y-4">
    <div v-for="call in calls" :key="call.id">
      <Call
        :call-id="call.id"
        :comments-count="call.comments_count"
        :call-date="call.date"
        :body="call.body"
        :user-id="call.user_id"
        :outcome-id="call.call_outcome_id"
        :authorizations="call.authorizations"
        :via-resource="viaResource"
      />
    </div>
  </div>
</template>
<script setup>
import Call from './CallListItem.vue'

defineProps({
  calls: Array,
  viaResource: { required: true, type: String },
})
</script>
