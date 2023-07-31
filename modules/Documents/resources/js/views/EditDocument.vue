<template>
  <Teleport to="body" :disabled="documentNotFound">
    <FormLayout
      v-model:active-section="section"
      :total-products="
        componentReady ? form.billable.products.length : undefined
      "
      :total-signers="
        componentReady && form.requires_signature
          ? form.signers.length
          : undefined
      "
      :remaining-signers="
        componentReady
          ? form.signers.filter(signer => !Boolean(signer.signed_at)).length
          : undefined
      "
      @exit-requested="exit"
    >
      <template #actions>
        <div class="flex items-center space-x-2">
          <IButtonCopy
            v-if="componentReady && document.authorizations.view"
            :text="document.public_url"
            :success-message="$t('documents::document.url_copied')"
            v-i-tooltip.bottom="$t('documents::document.copy_url')"
            class="mr-1 shrink-0"
          />

          <IDropdown
            v-if="componentReady && document.authorizations.view"
            size="sm"
            variant="white"
            no-caret
            placement="bottom-end"
          >
            <template #toggle-content>
              <Icon icon="DocumentDownload" class="h-4 w-4" />
            </template>
            <IDropdownItem
              :href="document.public_url + '/pdf'"
              target="_blank"
              rel="noopener noreferrer"
              :text="$t('documents::document.view_pdf')"
            />
            <IDropdownItem
              :href="document.public_url + '/pdf?output=download'"
              :text="$t('documents::document.download_pdf')"
            />
          </IDropdown>
          <span
            class="inline-block"
            v-i-tooltip.bottom="
              document.authorizations && !document.authorizations.update
                ? $t('core::app.action_not_authorized')
                : ''
            "
          >
            <IDropdownButtonGroup
              v-if="componentReady"
              size="sm"
              placement="bottom-end"
              :disabled="
                form.busy ||
                associationsBeingSynced ||
                !document.authorizations.update
              "
              :loading="form.busy || associationsBeingSynced"
              :text="$t('core::app.save')"
              @click="save"
            >
              <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                <div>
                  <IDropdownItem
                    @click="saveAndExit"
                    :text="$t('core::app.save_and_exit')"
                  />

                  <IDropdownItem
                    v-if="
                      document.status !== 'accepted' &&
                      document.status !== 'lost'
                    "
                    @click="markAsLost"
                    :text="$t('documents::document.actions.mark_as_lost')"
                  />

                  <IDropdownItem
                    v-if="document.status !== 'accepted'"
                    @click="markAsAccepted"
                    :text="$t('documents::document.actions.mark_as_accepted')"
                  />

                  <IDropdownItem
                    v-if="document.status === 'lost'"
                    @click="reactivate"
                    :text="$t('documents::document.actions.reactivate')"
                  />

                  <IDropdownItem
                    v-if="
                      document.status === 'accepted' &&
                      document.marked_accepted_by
                    "
                    @click="reactivate"
                    :text="$t('documents::document.actions.undo_acceptance')"
                  />
                  <IDropdownItem @click="clone" :text="$t('core::app.clone')" />
                </div>

                <IDropdownItem
                  v-if="document.authorizations.delete"
                  @click="destroy"
                  :text="$t('core::app.delete')"
                />
              </div>
            </IDropdownButtonGroup>
          </span>
        </div>
      </template>

      <IOverlay :show="!componentReady">
        <div class="container mx-auto" v-if="componentReady">
          <div
            v-if="componentReady && $gate.denies('view', document)"
            class="mx-auto mb-6 max-w-6xl"
          >
            <IAlert variant="warning">
              {{ $t('core::role.view_non_authorized_after_record_create') }}
            </IAlert>
          </div>

          <SectionDetails
            :form="form"
            :document="document"
            :visible="section == 'details'"
          >
            <template #actions>
              <div class="inline-block">
                <AssociationsPopover
                  @change="syncDocumentAssociations"
                  width-class="w-80"
                  :disabled="associationsBeingSynced"
                  :associateables="document.associations"
                  :initial-associateables="viaResource ? record : undefined"
                  :modelValue="document.associations"
                  :primary-resource-name="viaResource"
                >
                  <template
                    #after-record="{ record, isSelected, isSearching, title }"
                  >
                    <span
                      v-if="
                        record.is_primary_associated &&
                        isSelected &&
                        !isSearching
                      "
                      class="ml-1.5 self-start"
                      v-i-tooltip.top="
                        $t(
                          'documents::document.will_use_placeholders_from_record',
                          { resourceName: title }
                        )
                      "
                    >
                      <Icon
                        icon="CodeBracket"
                        class="h-5 w-5 text-neutral-500 dark:text-neutral-400"
                      />
                    </span>
                  </template>
                </AssociationsPopover>
              </div>
            </template>
          </SectionDetails>
          <SectionProducts
            :form="form"
            :visible="section == 'products'"
            :document="document"
          />
          <SectionContent
            ref="sectionContentRef"
            :form="form"
            :document="document"
            :visible="section == 'content'"
            :is-ready="componentReady"
          />
          <SectionSignature
            :form="form"
            :document="document"
            :visible="section == 'signature'"
          />
          <SectionSend
            @send-requested="send"
            @save-requested="save"
            :sending="sending"
            :form="form"
            :document="document"
            :visible="section == 'send'"
          />
        </div>
      </IOverlay>
    </FormLayout>
  </Teleport>
</template>
<script setup>
import { ref, computed, nextTick, provide } from 'vue'
import SectionContent from '../components/DocumentFormContent.vue'
import SectionDetails from '../components/DocumentFormDetails.vue'
import SectionProducts from '../components/DocumentFormProducts.vue'
import SectionSend from '../components/DocumentFormSend.vue'
import SectionSignature from '../components/DocumentFormSignature.vue'
import AssociationsPopover from '~/Core/resources/js/components/AssociationsPopover.vue'
import FormLayout from './DocumentFormLayout.vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useBrands } from '~/Brands/resources/js/composables/useBrands'
import { useForm } from '~/Core/resources/js/composables/useForm'
import merge from 'lodash/merge'
import { useResource } from '~/Core/resources/js/composables/useResource'

const emit = defineEmits([
  'changed',
  'updated',
  'deleted',
  'cloned',
  'lost',
  'reactivated',
  'accept',
  'sent',
  'associations-updated',
])

const props = defineProps({
  id: Number,
  viaResource: String,
  section: String,
  exitUsing: Function,
})

const { t } = useI18n()

const { syncAssociations, associationsBeingSynced } = useResource('documents')

const router = useRouter()
const route = useRoute()

const { setPageTitle } = useApp()

const {
  record,
  addResourceRecordHasManyRelationship,
  incrementResourceRecordCount,
} = useRecordStore()

const sectionContentRef = ref(null)

const sending = ref(false)
const documentFetched = ref(false)
const { orderedBrands: brands, brandsAreBeingFetched } = useBrands()
const section = ref(props.section || route.query.section || 'details')
const document = ref({})
const documentNotFound = ref(false)

const { form } = useForm()

const componentReady = computed(
  () => documentFetched.value && !brandsAreBeingFetched.value
)

provide('brands', brands)
provide('document', document)

function syncDocumentAssociations(data) {
  syncAssociations(document.value.id, data).then(data => {
    // Update the document associations to reflect the "is_primary_associated" attribute.
    document.value.associations = merge(
      document.value.associations,
      data.associations
    )

    emit('associations-updated', data)
    emit('changed', data)
  })
}

function prepareComponent(id) {
  ;(router.document
    ? new Promise(resolve => resolve({ data: router.document }))
    : Innoclapps.request().get(`/documents/${id}`)
  )
    .then(({ data }) => {
      form.set({ send: false })

      prepareDocument(data)

      documentFetched.value = true
      router.document && delete router.document
    })
    .catch(error => {
      if (error.response.status === 404) {
        documentNotFound.value = true
      }
    })
}

/**
 * Prepare the document for edit
 */
function prepareDocument(documentObject) {
  document.value = documentObject

  setPageTitle(documentObject.title)

  form.set({
    title: documentObject.title,
    view_type: documentObject.view_type,
    locale: documentObject.locale,
    user_id: documentObject.user_id,
    brand_id: documentObject.brand_id,
    document_type_id: documentObject.document_type_id,
    content: documentObject.content,
    requires_signature: documentObject.requires_signature,

    signers: documentObject.signers,

    recipients: documentObject.recipients,

    pdf: documentObject.pdf,

    billable: {
      tax_type: documentObject.billable.tax_type,
      products: documentObject.billable.products,
      removed_products: [],
    },

    send_at: documentObject.send_at,

    send_mail_account_id: documentObject.send_mail_account_id,
    send_mail_body: documentObject.send_mail_body,
    send_mail_subject: documentObject.send_mail_subject,
  })
}

/**
 * Mark the document as lost
 */
async function markAsLost() {
  await Innoclapps.dialog().confirm({
    message: t('documents::document.actions.mark_as_lost_message'),
    title: false,
    icon: 'QuestionMarkCircle',
    iconWrapperColorClass: 'bg-info-100',
    iconColorClass: 'text-info-400',
    html: true,
    confirmText: t('core::app.confirm'),
    confirmVariant: 'secondary',
  })

  Innoclapps.request()
    .post(`/documents/${document.value.id}/lost`)
    .then(({ data }) => {
      emit('lost', data)
      emit('changed', data)

      Innoclapps.success(t('documents::document.marked_as_lost'))

      exit()
    })
}

/**
 * Mark the document as accepted
 */
function markAsAccepted() {
  Innoclapps.request()
    .post(`/documents/${document.value.id}/accept`)
    .then(({ data }) => {
      emit('accept', data)
      emit('changed', data)

      Innoclapps.success(t('documents::document.marked_as_accepted'))

      prepareDocument(data)
    })
}

/**
 * Reactivate the document
 */
async function reactivate() {
  await Innoclapps.dialog().confirm()

  Innoclapps.request()
    .post(`/documents/${document.value.id}/draft`)
    .then(({ data }) => {
      Innoclapps.success(t('documents::document.reactivated'))
      emit('reactivated', data)
      emit('changed', data)
      prepareDocument(data)
    })
}

/**
 * Send the document
 */
function send() {
  sending.value = true

  save()
    .then(() => {
      form.send = true

      save()
        .then(doc => {
          Innoclapps.success(t('documents::document.sent'))
        })
        .finally(doc => {
          form.send = false
          sending.value = false
          emit('sent', doc)
        })
    })
    .catch(error => {
      console.error(error)
      sending.value = false
    })
}

/**
 * Save the document
 */
async function save() {
  form.busy = true
  await sectionContentRef.value.builderRef.saveBase64Images()

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let updatedDocument = await form
    .put(`/documents/${document.value.id}`)
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(
          t('core::app.form_validation_failed_with_sections'),
          3000
        )
      }
      return Promise.reject(e)
    })

  prepareDocument(updatedDocument)

  emit('updated', updatedDocument)
  emit('changed', updatedDocument)

  return updatedDocument
}

/**
 * Save the document and exit
 */
function saveAndExit() {
  save().then(exit)
}

/**
 * Clone the document being edited
 */
function clone() {
  Innoclapps.request()
    .post(`/documents/${document.value.id}/clone`)
    .then(({ data }) => {
      emit('cloned', data)

      if (props.viaResource) {
        router.push({ name: route.name, params: { documentId: data.id } })

        if (props.viaResource && data.authorizations.view) {
          addResourceRecordHasManyRelationship(data, 'documents')

          incrementResourceRecordCount([
            'documents_count',
            'documents_for_user_count',
            'draft_documents_for_user_count',
          ])
        }
      } else {
        router.push({ name: route.name, params: { id: data.id } })
      }

      prepareDocument(data)
    })
}

/**
 * Remove document from storage
 */
async function destroy() {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/documents/${document.value.id}`)

  emit('deleted', document.value)

  Innoclapps.success(t('documents::document.deleted'))

  exit()
}

/**
 * Exit the document edit
 */
function exit() {
  if (props.exitUsing) {
    props.exitUsing()
    return
  }

  router.back()
}

prepareComponent(props.id || route.params.id)
</script>
