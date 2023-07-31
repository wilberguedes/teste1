<template>
  <ITabPanel @activated.once="loadData" :lazy="!dataLoadedFirstTime">
    <div
      class="-mt-[20px] mb-3 rounded-b-md border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 sm:mb-7"
    >
      <div class="px-4 py-5 sm:p-6">
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <h3
              class="text-base/6 font-medium text-neutral-900 dark:text-white"
              v-t="'documents::document.manage_documents'"
            />
            <div
              class="mt-2 max-w-xl text-sm text-neutral-500 dark:text-neutral-200"
            >
              <p v-t="'documents::document.info'" />
            </div>
          </div>
          <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
            <IButton
              @click="documentBeingCreated = true"
              size="sm"
              :text="$t('documents::document.create')"
              icon="Plus"
            />
          </div>
        </div>
        <SearchInput
          class="mt-2"
          v-model="search"
          v-show="hasDocuments || search"
          @input="performSearch($event, associateable)"
        />
      </div>
    </div>

    <CreateDocument
      v-if="documentBeingCreated"
      :via-resource="resourceName"
      :exit-using="() => (documentBeingCreated = null)"
      :edit-redirect-handler="handleRedirectOnEditWhenCreating"
    />

    <EditDocument
      v-if="documentBeingEdited"
      :via-resource="resourceName"
      :id="documentBeingEdited"
      :exit-using="() => (documentBeingEdited = false)"
      @reactivated="refreshRecordView"
      @sent="refreshRecordView"
      @lost="refreshRecordView"
      @accept="refreshRecordView"
      @changed="handleDocumentChanged"
      @deleted="
        removeResourceRecordHasManyRelationship($event.id, 'documents'),
          refreshRecordView()
      "
    />

    <Document
      v-for="document in documents"
      :key="document.id"
      :document-id="document.id"
      :type-id="document.document_type_id"
      :status="document.status"
      :display-name="document.display_name"
      :path="document.path"
      :public-url="document.public_url"
      :accepted-at="document.accepted_at"
      :last-date-sent="document.last_date_sent"
      :amount="document.amount"
      :authorizations="document.authorizations"
      :associations="document.associations"
      :via-resource="resourceName"
      class="mb-3"
    />

    <div
      v-show="isPerformingSearch && !hasSearchResults"
      class="mt-6 text-center text-neutral-800 dark:text-neutral-200"
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
import orderBy from 'lodash/orderBy'
import cloneDeep from 'lodash/cloneDeep'
import InfinityLoader from '~/Core/resources/js/components/InfinityLoader.vue'
import Document from './RelatedDocumentListItem.vue'
import { useRecordTab } from '~/Core/resources/js/composables/useRecordTab'
import CreateDocument from '../views/CreateDocument.vue'
import EditDocument from '../views/EditDocument.vue'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const props = defineProps({
  resourceName: { required: true, type: String },
  scrollElement: { type: String },
})

const associateable = 'documents'

const infinityRef = ref(null)
const documentBeingCreated = ref(false)
const documentBeingEdited = ref(null)

const {
  updateResourceRecordHasManyRelationship,
  removeResourceRecordHasManyRelationship,
} = useRecordStore()

const {
  dataLoadedFirstTime,
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

const iterable = computed(() =>
  isPerformingSearch.value
    ? searchResults.value || []
    : record.value.documents || []
)

const lost = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'lost'),
    document => new Date(document.created_at),
    'asc'
  )
)

const accepted = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'accepted'),
    document => new Date(document.accepted_at),
    'asc'
  )
)

const draft = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'draft'),
    document => new Date(document.created_at),
    'asc'
  )
)

const sent = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'sent'),
    document => new Date(document.last_date_sent),
    'desc'
  )
)

const documents = computed(() => [
  ...draft.value,
  ...sent.value,
  ...lost.value,
  ...accepted.value,
])

const hasDocuments = computed(() => documents.value.length > 0)

function handleDocumentChanged(doc) {
  updateResourceRecordHasManyRelationship(
    cloneDeep(doc), // use clean object to avoid mutation errors
    'documents'
  )
}

function handleRedirectOnEditWhenCreating(document) {
  documentBeingEdited.value = document.id
  documentBeingCreated.value = false
}

function refreshRecordView() {
  Innoclapps.$emit('refresh-details-view')
}
</script>
