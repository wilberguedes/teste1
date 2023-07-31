<template>
  <timeline-entry
    :resource-name="resourceName"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timelineable-key="log.timeline_key"
    icon="Bars3BottomLeft"
    :heading="$t('webforms::form.submission')"
    heading-class="font-medium"
  >
    <ICard class="mt-2" v-once>
      <div class="space-y-2">
        <div v-for="(property, index) in log.properties" :key="index">
          <div
            class="flex justify-start space-x-1 text-sm font-semibold text-neutral-800 dark:text-neutral-200"
          >
            <span>{{ resources[property.resourceName].singularLabel }} /</span>
            <span class="font-medium" v-html="property.label" />
          </div>

          <div class="text-sm text-neutral-600 dark:text-neutral-400">
            <span v-if="property.value === null" v-text="'/'" />
            <span v-else v-text="maybeFormatDateValue(property.value) || property.value" />
          </div>
        </div>
      </div>
    </ICard>
  </timeline-entry>
</template>
<script setup>
import TimelineEntry from '~/Core/resources/js/views/Timeline/RecordTabTimelineTemplate.vue'
import propsDefinition from '~/Core/resources/js/views/Timeline/props'
import { useDates } from '~/Core/resources/js/composables/useDates'

defineProps(propsDefinition)

const { maybeFormatDateValue } = useDates()
const resources = Innoclapps.config('resources')
</script>
