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
              v-t="'calls::call.manage_calls'"
            />
            <div
              class="mt-2 max-w-xl text-sm text-neutral-500 dark:text-neutral-200"
            >
              <p v-t="'calls::call.info'" />
            </div>
          </div>
          <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
            <IButton
              @click="showCreateForm = true"
              icon="Plus"
              size="sm"
              :text="$t('calls::call.add')"
            />
            <MakeCallButton
              v-if="$gate.userCan('use voip')"
              class="ml-2"
              :resource-name="resourceName"
              @call-requested="newCall"
            />
          </div>
        </div>
        <SearchInput
          class="mt-2"
          v-model="search"
          v-show="hasCalls || search"
          @input="performSearch($event, associateable)"
        />
      </div>
      <CreateCall
        v-if="showCreateForm"
        @cancel="showCreateForm = false"
        :shadow="false"
        :ring="false"
        :rounded="false"
        :via-resource="resourceName"
      />
    </div>

    <Calls :calls="calls" :via-resource="resourceName" />

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
import Calls from './CallList.vue'
import CreateCall from './CreateCall.vue'
import MakeCallButton from './CallMakeButton.vue'
import InfinityLoader from '~/Core/resources/js/components/InfinityLoader.vue'
import orderBy from 'lodash/orderBy'
import { useRecordTab } from '~/Core/resources/js/composables/useRecordTab'
import { useComments } from '~/Comments/resources/js/composables/useComments'
import { useRoute } from 'vue-router'
import { useVoip } from '~/Core/resources/js/composables/useVoip'

const route = useRoute()

const props = defineProps({
  resourceName: { required: true, type: String },
  scrollElement: { type: String },
})

const associateable = 'calls'
const infinityRef = ref(null)
const showCreateForm = ref(false)

const { voip } = useVoip()

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

const calls = computed(() =>
  orderBy(searchResults.value || record.value.calls, 'date', 'desc')
)

const hasCalls = computed(() => calls.value.length > 0)

async function newCall(phoneNumber) {
  showCreateForm.value = true
  await voip.makeCall(phoneNumber)
}

if (route.query.resourceId && route.query.section === associateable) {
  // Wait till the data is loaded for the first time and the
  // elements are added to the document so we can have a proper scroll
  watchOnce(dataLoadedFirstTime, () => {
    focusToAssociateableElement(
      associateable,
      route.query.resourceId,
      'call'
    ).then(() => {
      if (route.query.comment_id) {
        commentsAreVisible.value = true
      }
    })
  })
}
</script>
