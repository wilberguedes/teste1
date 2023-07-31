<template>
  <Teleport to="body">
    <FormLayout
      v-model:active-section="selectedSection"
      :total-products="form.billable.products.length"
      :total-signers="form.requires_signature ? form.signers.length : undefined"
      @exit-requested="exit"
    >
      <template #actions>
        <IDropdownButtonGroup
          size="sm"
          placement="bottom-end"
          :disabled="form.busy"
          :loading="form.busy"
          :text="$t('core::app.save')"
          @click="save"
        >
          <IDropdownItem
            @click="saveAndExit"
            :text="$t('core::app.save_and_exit')"
          />
        </IDropdownButtonGroup>
      </template>

      <div v-if="componentReady">
        <SectionDetails :form="form" :visible="selectedSection == 'details'">
          <template #actions>
            <div class="inline-block">
              <AssociationsPopover
                width-class="w-80"
                v-model="form.associations"
                :primary-resource-name="viaResource"
                :primary-record="viaResource ? viaResourceRecord : undefined"
                :primary-record-disabled="true"
                :initial-associateables="
                  viaResource ? viaResourceRecord : undefined
                "
                :associateables="associateables"
              >
                <template
                  #after-record="{
                    record,
                    selectedRecords,
                    isSearching,
                    title,
                  }"
                >
                  <span
                    v-if="selectedRecords[0] === record.id && !isSearching"
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
          <template #top>
            <IFormGroup
              v-if="showDealSelector"
              class="mb-6 mt-3 rounded-md border border-neutral-300 p-5 dark:border-neutral-700"
              :description="$t('documents::document.deal_description')"
            >
              <ICustomSelect
                ref="createDealSelectRef"
                :placeholder="$t('deals::deal.choose_or_create')"
                label="name"
                input-id="deal_id"
                :options="deals"
                :filterable="false"
                :modelValue="selectedDeal"
                @update:modelValue="handleSelectedDealChanged"
                @search="onDealsSearch"
                @option:selected="handleDealSelected"
              >
                <template #no-options="{ searching, text }">
                  <span v-show="searching" v-text="text"></span>
                  <span
                    v-show="!searching"
                    v-t="'core::app.type_to_search'"
                  ></span>
                </template>

                <template #footer>
                  <div
                    class="border-t border-neutral-200 bg-neutral-50 px-3 py-2"
                  >
                    <a
                      href="#"
                      @click.prevent="
                        ;(dealIsBeingCreated = true),
                          $refs.createDealSelectRef.hide()
                      "
                      class="link text-sm"
                      v-t="'deals::deal.create'"
                    />
                  </div>
                </template>
              </ICustomSelect>
            </IFormGroup>
          </template>
        </SectionDetails>
        <SectionProducts
          :form="form"
          :visible="selectedSection == 'products'"
        />
        <SectionContent
          :form="form"
          ref="sectionContentRef"
          :visible="selectedSection == 'content'"
        />
        <SectionSignature
          :form="form"
          :visible="selectedSection == 'signature'"
        />
        <SectionSend
          @send-requested="send"
          @save-requested="save"
          :sending="sending"
          :form="form"
          :visible="selectedSection == 'send'"
        />
      </div>
    </FormLayout>

    <CreateDealModal
      v-model:visible="dealIsBeingCreated"
      @created="dealCreatedHandler"
    />
  </Teleport>
</template>
<script setup>
import { ref, computed, provide, nextTick, onMounted } from 'vue'
import SectionContent from '../components/DocumentFormContent.vue'
import SectionDetails from '../components/DocumentFormDetails.vue'
import SectionProducts from '../components/DocumentFormProducts.vue'
import SectionSend from '../components/DocumentFormSend.vue'
import FormLayout from './DocumentFormLayout.vue'
import SectionSignature from '../components/DocumentFormSignature.vue'
import AssociationsPopover from '~/Core/resources/js/components/AssociationsPopover.vue'
import debounce from 'lodash/debounce'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import omit from 'lodash/omit'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useBrands } from '~/Brands/resources/js/composables/useBrands'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useDocumentTypes } from '../composables/useDocumentTypes'

const emit = defineEmits(['created', 'sent'])

const props = defineProps({
  viaResource: String,
  editRedirectHandler: Function,
  exitUsing: Function,
})

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { currentUser, setPageTitle } = useApp()

const sectionContentRef = ref(null)

const sending = ref(false)
const { orderedBrands: brands, brandsAreBeingFetched } = useBrands()
const selectedSection = ref(route.query.section || 'details')
const associateables = ref({})
const dealIsBeingCreated = ref(false)
const selectedDeal = ref(null)
const showDealSelector = ref(true)
const deals = ref([])

provide('brands', brands)

const componentReady = computed(() => !brandsAreBeingFetched.value)

const { documentTypes } = useDocumentTypes()

const { form } = useForm({
  title: null,
  brand_id: null,
  user_id: null,
  content: null,
  view_type: 'nav-top',
  locale: currentUser.value.locale,

  // In case default type not visible to current user
  document_type_id: documentTypes.value.find(
    type => type.id == Innoclapps.config('documents.default_document_type')
  )?.id,

  requires_signature: true,
  signers: [],
  recipients: [],

  pdf: {
    padding: '15px',
  },

  billable: {
    tax_type: Innoclapps.config('options.tax_type'),
    products: [],
  },

  send: false,
  send_mail_account_id: null,
  send_mail_body: null,
  send_mail_subject: null,

  associations: {},
})

const {
  record: viaResourceRecord,
  incrementResourceRecordCount,
  addResourceRecordHasManyRelationship,
} = useRecordStore()

/**
 * Exit handler
 */
function exit() {
  if (props.exitUsing) {
    props.exitUsing()
    return
  }
  router.back()
}

/**
 * Redirect the doc to edit route
 */
function performEditRedirect(document, query = {}) {
  router.document = document

  if (props.editRedirectHandler) {
    props.editRedirectHandler(document, query)
    return
  }

  // Use replace so the exit link works well and returns to the previous location
  router.replace({
    name: 'edit-document',
    params: { id: document.id },
    query: query,
  })
}

/**
 * Send the document
 */
function send() {
  sending.value = true

  makeSaveRequest()
    .then(document => {
      form.send = true

      if (!document.authorizations.view) {
        performEditRedirect(document, { section: 'send' })

        Innoclapps.error(
          'Document not sent, your account not authorized to perform this action.'
        )

        return
      }

      // Update the form billable and signers to reflect the newly created id's
      form.billable = document.billable
      form.signers = document.signers

      form.put(`/documents/${document.id}`).then(document => {
        Innoclapps.success(t('documents::document.sent'))
        emit('sent', document)
        performEditRedirect(document, { section: 'send' })
      })
    })
    .catch(() => (sending.value = false))
}

/**
 * Save the document and exit
 */
function saveAndExit() {
  makeSaveRequest().then(exit)
}

/**
 * Save the document
 */
async function save() {
  let document = await makeSaveRequest()

  performEditRedirect(document, { section: selectedSection.value })

  return document
}

/**
 * Make save request
 */
async function makeSaveRequest() {
  form.busy = true
  await sectionContentRef.value.builderRef.saveBase64Images()

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let document = await form.post('/documents').catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(
        t('core::app.form_validation_failed_with_sections'),
        3000
      )
    }
    return Promise.reject(e)
  })

  if (props.viaResource && document.authorizations.view) {
    addResourceRecordHasManyRelationship(document, 'documents')
    incrementResourceRecordCount([
      'documents_count',
      'documents_for_user_count',
      'draft_documents_for_user_count',
    ])
  }

  emit('created', document)

  return document
}

/**
 * Handle the deal created event
 */
function dealCreatedHandler({ deal }) {
  selectedDeal.value = deal
  deals.value.push(deal)
  handleDealSelected(deal)
  dealIsBeingCreated.value = false
}

/**
 * Remove associations
 */
function removeAssociation(id, resourceName) {
  let associateablesIndex = findIndex(associateables.value[resourceName], [
    'id',
    id,
  ])

  let modelIndex = form.associations[resourceName].findIndex(
    associatedId => associatedId === id
  )

  if (associateablesIndex !== -1) {
    associateables.value[resourceName].splice(associateablesIndex, 1)
  }

  if (modelIndex !== -1) {
    form.associations[resourceName].splice(modelIndex, 1)
  }
}

/** Add custom association to the form */
function addAssociation(record, resourceName) {
  if (!form.associations.hasOwnProperty(resourceName)) {
    form.associations[resourceName] = []
  }

  if (!associateables.value.hasOwnProperty(resourceName)) {
    associateables.value[resourceName] = []
  }

  if (!find(form.associations[resourceName], ['id', record.id])) {
    form.associations[resourceName].push(record.id)
    associateables.value[resourceName].push(record)
  }
}

/**
 * Handle the deal selected event
 */
function handleDealSelected(deal) {
  if (deal) {
    addAssociation({ id: deal.id, display_name: deal.display_name }, 'deals')

    setDataFromDeal(deal)
  }
}

function handleSelectedDealChanged(value) {
  if (!value && selectedDeal.value) {
    removeAssociation(selectedDeal.value.id, 'deals')
  }
  selectedDeal.value = value
}

/** Set the data from the given deal */
function setDataFromDeal(deal) {
  if (!form.title && form.document_type_id) {
    form.title = `${deal.display_name} ${
      find(documentTypes.value, ['id', form.document_type_id]).name
    }`
  }

  if (deal.billable) {
    form.billable.products = deal.billable.products.map(product =>
      omit(product, ['id'])
    )
    form.billable.tax_type = deal.billable.tax_type
  }

  if (form.requires_signature) {
    ;(deal.contacts || [])
      .filter(contact => Boolean(contact.email))
      .forEach(contact =>
        form.signers.unshift({
          name: contact.display_name,
          email: contact.email,
          send_email: true,
        })
      )
  }
}

/**
 * Set data when creating document via resource
 */
function setDataWhenViaResource() {
  addAssociation(
    {
      id: viaResourceRecord.value.id,
      display_name: viaResourceRecord.value.display_name,
    },
    props.viaResource
  )

  if (props.viaResource === 'deals') {
    setDataFromDeal(viaResourceRecord.value)
    showDealSelector.value = false
  }

  if (props.viaResource === 'contacts' && viaResourceRecord.value.email) {
    form.signers.unshift({
      name: viaResourceRecord.value.display_name,
      email: viaResourceRecord.value.email,
      send_email: true,
    })
  }

  if (props.viaResource === 'companies') {
    ;(viaResourceRecord.value.contacts || [])
      .filter(contact => Boolean(contact.email))
      .forEach(contact => {
        form.signers.unshift({
          name: contact.display_name,
          email: contact.email,
          send_email: true,
        })
      })
  }
}

/**
 * Perform async deals search
 *
 * @param  {String} q
 * @param  {Function} loading
 *
 * @return {Void}
 */
const onDealsSearch = debounce(function (q, loading) {
  if (q == '') {
    deals.value = []
    return
  }

  searchDeals(q, loading)
}, 400)

/**
 * Perform async deals search
 *
 * @param  {String} q
 * @param  {Function} loading
 *
 * @return {Void}
 */
async function searchDeals(q, loading) {
  loading(true)

  let { data } = await Innoclapps.request().get('/deals/search', {
    params: {
      q: q,
      with: 'billable.products',
    },
  })

  deals.value = data
  loading(false)
}

/**
 * Prepare the component
 */
function prepareComponent() {
  if (props.viaResource) {
    setDataWhenViaResource()
  }

  form.set('user_id', currentUser.value.id)
}

onMounted(() => {
  setPageTitle(t('documents::document.create'))
})

prepareComponent()
</script>
