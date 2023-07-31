<template>
  <EditDocument
    v-if="documentBeingEdited"
    :id="documentId"
    :section="editSection"
    :via-resource="viaResource"
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

  <ICard v-bind="$attrs" :class="'document-' + documentId">
    <div class="flex items-center">
      <a
        class="text-base/6 font-medium text-neutral-900 dark:text-white"
        @click.prevent="editDocument()"
        :href="path"
        v-text="displayName"
      />
      <TextBackground
        :color="type.swatch_color"
        class="ml-3 inline-flex items-center justify-center rounded-full px-2.5 text-sm/5 font-normal dark:!text-white"
      >
        <Icon :icon="type.icon" class="mr-1.5 h-4 w-4 text-current" />
        {{ type.name }}
      </TextBackground>
    </div>

    <AssociationsPopover
      @change="
        syncAssociations(documentId, $event).then(data =>
          updateResourceRecordHasManyRelationship(data, 'documents')
        )
      "
      :modelValue="associations"
      :associateables="associations"
      :initial-associateables="record"
      :disabled="associationsBeingSynced"
      :primary-resource-name="viaResource"
    />

    <div class="mt-5">
      <div
        class="rounded-md bg-neutral-50 px-6 py-5 dark:bg-neutral-800 sm:flex sm:items-start sm:justify-between"
      >
        <div class="sm:flex sm:items-start">
          <TextBackground
            :color="statuses[status].color"
            class="inline-flex w-auto items-center justify-center self-start rounded-full px-2.5 text-sm/5 font-normal dark:!text-white sm:shrink-0"
          >
            {{ $t('documents::document.status.' + status) }}
          </TextBackground>

          <div class="mt-3 sm:ml-4 sm:mt-0">
            <div
              class="text-sm font-medium text-neutral-900 dark:text-neutral-100"
              v-text="formatMoney(amount)"
            />

            <div
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-200 sm:flex sm:items-center"
              v-text="
                $t('documents::document.sent_at', {
                  date: lastDateSent ? localizedDateTime(lastDateSent) : 'N/A',
                })
              "
            />

            <div
              v-if="acceptedAt"
              class="mt-1 text-sm text-neutral-600 sm:flex sm:items-center"
            >
              {{ $t('documents::document.accepted_at') }}:
              <span class="ml-1" v-text="localizedDateTime(acceptedAt)" />
            </div>
          </div>
        </div>
        <div class="mt-4 sm:ml-6 sm:mt-0 sm:shrink-0">
          <div class="flex items-center space-x-2">
            <IButton
              v-show="authorizations.view"
              size="sm"
              variant="white"
              :text="$t('core::app.edit')"
              @click="editDocument()"
            />

            <IButton
              v-if="status === 'draft'"
              v-show="authorizations.view"
              size="sm"
              variant="white"
              :text="$t('documents::document.send.send')"
              @click="editDocument('send')"
            />
            <IMinimalDropdown>
              <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                <IDropdownItem
                  :href="publicUrl"
                  :text="$t('documents::document.view')"
                />
                <div>
                  <IDropdownItem
                    :href="publicUrl + '/pdf'"
                    target="_blank"
                    rel="noopener noreferrer"
                    :text="$t('documents::document.view_pdf')"
                  />
                  <IDropdownItem
                    :href="publicUrl + '/pdf?output=download'"
                    :text="$t('documents::document.download_pdf')"
                  />
                </div>
                <div>
                  <IDropdownItem
                    v-if="authorizations.update"
                    @click="editDocument()"
                    :text="$t('core::app.edit')"
                  />
                  <IDropdownItem
                    v-if="authorizations.delete"
                    @click="destroy(documentId)"
                    :text="$t('core::app.delete')"
                  />
                </div>
              </div>
            </IMinimalDropdown>
          </div>
        </div>
      </div>
    </div>
  </ICard>
</template>
<script setup>
import { ref, computed } from 'vue'
import TextBackground from '~/Core/resources/js/components/TextBackground.vue'
import AssociationsPopover from '~/Core/resources/js/components/AssociationsPopover.vue'
import EditDocument from '../views/EditDocument.vue'
import cloneDeep from 'lodash/cloneDeep'
import { useI18n } from 'vue-i18n'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useDocumentTypes } from '../composables/useDocumentTypes'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'
import { useResource } from '~/Core/resources/js/composables/useResource'

const props = defineProps({
  documentId: { type: Number, required: true },
  typeId: { type: Number, required: true },
  status: { type: String, required: true },
  displayName: { type: String, required: true },
  path: { type: String, required: true },
  publicUrl: { type: String, required: true },
  acceptedAt: String,
  lastDateSent: String,
  amount: { required: true },
  authorizations: { type: Object, required: true },
  associations: { type: Object, required: true },
  viaResource: { type: String, required: true },
})

const { t } = useI18n()

const { syncAssociations, associationsBeingSynced } = useResource('documents')

const { formatMoney } = useAccounting()
const { localizedDateTime } = useDates()

const {
  record,
  decrementResourceRecordCount,
  removeResourceRecordHasManyRelationship,
  updateResourceRecordHasManyRelationship,
} = useRecordStore()

const { findTypeById } = useDocumentTypes()

const documentBeingEdited = ref(false)
const editSection = ref(null)

const type = computed(() => findTypeById(props.typeId))

const statuses = Innoclapps.config('documents.statuses')

function handleDocumentChanged(doc) {
  updateResourceRecordHasManyRelationship(
    cloneDeep(doc), // use clean object to avoid mutation errors
    'documents'
  )
}

function editDocument(section = null) {
  editSection.value = section || 'details'
  documentBeingEdited.value = true
}

function refreshRecordView() {
  Innoclapps.$emit('refresh-details-view')
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/documents/${id}`)

  if (props.status === 'draft') {
    decrementResourceRecordCount('draft_documents_for_user_count')
  }

  removeResourceRecordHasManyRelationship(id, 'documents')
  decrementResourceRecordCount('documents_count')
  decrementResourceRecordCount('documents_for_user_count')

  Innoclapps.success(t('documents::document.deleted'))
}
</script>
