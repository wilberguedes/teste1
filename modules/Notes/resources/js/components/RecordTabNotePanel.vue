<template>
  <ITabPanel @activated.once="loadData" :lazy="!dataLoadedFirstTime">
    <div
      class="-mt-[20px] mb-3 overflow-hidden rounded-b-md border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 sm:mb-7"
    >
      <div class="px-4 py-5 sm:p-6" v-show="!showCreateForm">
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <h3
              class="text-base/6 font-medium text-neutral-900 dark:text-white"
              v-t="'notes::note.manage_notes'"
            />
            <div
              class="mt-2 max-w-xl text-sm text-neutral-500 dark:text-neutral-200"
            >
              <p v-t="'notes::note.info'" />
            </div>
          </div>
          <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
            <IButton
              @click="showCreateForm = true"
              size="sm"
              icon="Plus"
              :text="$t('notes::note.add')"
            />
          </div>
        </div>

        <SearchInput
          class="mt-2"
          v-show="hasNotes || search"
          v-model="search"
          @input="performSearch($event, associateable)"
        />
      </div>
      <CreateNote
        v-if="showCreateForm"
        @cancel="showCreateForm = false"
        :shadow="false"
        :ring="false"
        :rounded="false"
        :via-resource="resourceName"
      />
    </div>

    <Notes :notes="notes" :via-resource="resourceName" />

    <div
      class="mt-6 text-center text-neutral-800 dark:text-neutral-200"
      v-show="isPerformingSearch && !hasSearchResults"
      v-t="'core::app.no_search_results'"
    />

    <InfinityLoader
      @handle="infiniteHandler($event, associateable)"
      :scroll-element="scrollElement"
      ref="infinityRef"
    />
  </ITabPanel>
</template>
<script setup>
import { ref, computed } from 'vue'
import { watchOnce } from '@vueuse/core'
import Notes from './NoteList.vue'
import CreateNote from './CreateNote.vue'
import InfinityLoader from '~/Core/resources/js/components/InfinityLoader.vue'
import orderBy from 'lodash/orderBy'
import { useComments } from '~/Comments/resources/js/composables/useComments'
import { useRecordTab } from '~/Core/resources/js/composables/useRecordTab'
import { useRoute } from 'vue-router'

const route = useRoute()

const props = defineProps({
  resourceName: { required: true, type: String },
  scrollElement: { type: String },
})

const associateable = 'notes'
const infinityRef = ref(null)
const showCreateForm = ref(false)

const { commentsAreVisible } = useComments(
  route.query.resourceId,
  associateable
)

const {
  dataLoadedFirstTime,
  focusToAssociateableElement,
  searchResults,
  record,
  isPerformingSearch,
  hasSearchResults,
  performSearch,
  search,
  loadData,
  infiniteHandler,
} = useRecordTab({
  resourceName: props.resourceName,
  infinityRef,
  scrollElement: props.scrollElement,
})

const notes = computed(() =>
  orderBy(searchResults.value || record.value.notes, 'created_at', 'desc')
)

const hasNotes = computed(() => notes.value.length > 0)

if (route.query.resourceId && route.query.section === associateable) {
  // Wait till the data is loaded for the first time and the
  // elements are added to the document so we can have a proper scroll
  watchOnce(dataLoadedFirstTime, () => {
    focusToAssociateableElement(
      associateable,
      route.query.resourceId,
      'note'
    ).then(() => {
      if (route.query.comment_id) {
        commentsAreVisible.value = true
      }
    })
  })
}
</script>
