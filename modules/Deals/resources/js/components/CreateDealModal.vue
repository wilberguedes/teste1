<template>
  <ISlideover
    @shown="handleModalShownEvent"
    @hidden="handleModalHiddenEvent"
    @submit="createUsing ? createUsing(create) : create()"
    @update:visible="$emit('update:visible', $event)"
    :visible="visible"
    :title="title || $t('deals::deal.create')"
    :ok-title="$t('core::app.create')"
    :ok-disabled="form.busy"
    :cancel-title="$t('core::app.cancel')"
    id="createDealModal"
    :initial-focus="modalCloseElement"
    static-backdrop
    form
    :size="withProducts ? 'xxl' : 'md'"
  >
    <FieldsPlaceholder v-if="fields.isEmpty()" />

    <slot name="top" :isReady="fields.isNotEmpty()"></slot>

    <div v-show="fieldsVisible">
      <div v-if="withProducts" class="border-b border-neutral-200 pb-8">
        <h3
          class="mb-5 inline-flex items-center text-base font-medium text-neutral-800"
        >
          <span v-t="'billable::product.products'" />

          <a
            v-show="withProducts"
            href="#"
            class="link ml-2 mt-0.5 text-sm"
            @click.prevent="hideProductsSection"
            v-t="'deals::deal.dont_add_products'"
          />
        </h3>

        <FormTaxTypes
          v-model="form.billable.tax_type"
          class="mb-4 flex flex-col space-y-1 sm:flex-row sm:space-x-2 sm:space-y-0"
        />

        <FormTableProducts
          v-model:products="form.billable.products"
          :tax-type="form.billable.tax_type"
          @productSelected="
            form.errors.clear('billable.products.' + $event.index + '.name')
          "
          @productRemoved="handleProductRemovedEvent"
        >
          <template #after-product-select="{ index }">
            <IFormError
              v-text="form.getError('billable.products.' + index + '.name')"
            />
          </template>
        </FormTableProducts>
      </div>

      <FieldsGenerator
        focus-first
        :form-id="form.formId"
        :fields="fields"
        view="create"
        :is-floating="true"
      >
        <template #after-contacts-field>
          <span class="-mt-2 block text-right">
            <a
              href="#"
              @click.prevent="contactBeingCreated = true"
              class="link text-sm"
            >
              + {{ $t('contacts::contact.create') }}
            </a>
          </span>
        </template>

        <template #after-companies-field>
          <span class="-mt-2 block text-right">
            <a
              href="#"
              @click.prevent="companyBeingCreated = true"
              class="link text-sm"
            >
              + {{ $t('contacts::company.create') }}
            </a>
          </span>
        </template>

        <template #after-amount-field>
          <span class="-mt-2 block text-right">
            <a
              v-show="!withProducts"
              href="#"
              class="link text-sm"
              @click.prevent="showProductsSection"
              v-t="'deals::deal.add_products'"
            />
            <a
              v-show="withProducts"
              href="#"
              class="link text-sm"
              @click.prevent="hideProductsSection"
              v-t="'deals::deal.dont_add_products'"
            />
          </span>
        </template>
      </FieldsGenerator>
    </div>

    <template #modal-ok v-if="withExtendedSubmitButtons">
      <IDropdownButtonGroup
        type="submit"
        placement="top-end"
        :disabled="form.busy"
        :loading="form.busy"
        :text="$t('core::app.create')"
      >
        <IDropdownItem
          @click="createAndAddAnother"
          :text="$t('core::app.create_and_add_another')"
        />
        <IDropdownItem
          v-if="goToList"
          @click="createAndGoToList"
          :text="$t('core::app.create_and_go_to_list')"
        />
      </IDropdownButtonGroup>
    </template>

    <CreateContactModal
      v-model:visible="contactBeingCreated"
      :overlay="false"
      @created="
        fields.mergeValue('contacts', $event.contact),
          (contactBeingCreated = false)
      "
    />

    <CreateCompanyModal
      v-model:visible="companyBeingCreated"
      :overlay="false"
      @created="
        fields.mergeValue('companies', $event.company),
          (companyBeingCreated = false)
      "
    />
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { whenever } from '@vueuse/core'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import FormTableProducts from '~/Billable/resources/js/components/Billable/FormTableProducts.vue'
import FormTaxTypes from '~/Billable/resources/js/components/Billable/FormTaxTypes.vue'
import find from 'lodash/find'
import castArray from 'lodash/castArray'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'
import { computedWithControl } from '@vueuse/shared'

const emit = defineEmits([
  'created',
  'modal-shown',
  'modal-hidden',
  'update:visible',
  'ready',
])

const props = defineProps({
  visible: { type: Boolean, default: true },
  goToList: { type: Boolean, default: true },
  redirectToView: { type: Boolean, default: false },
  createUsing: Function,
  withExtendedSubmitButtons: { type: Boolean, default: false },
  fieldsVisible: { type: Boolean, default: true },
  title: String,

  disabledFields: [Array, String],
  hiddenFields: [Array, String],

  associations: Object,
  // Must be passed if stageId is provided
  pipeline: Object,
  stageId: Number,
  name: String,
  contacts: Array,
  companies: Array,
})

const { t } = useI18n()
const router = useRouter()

const contactBeingCreated = ref(false)
const companyBeingCreated = ref(false)

const withProducts = ref(false)

const { fields, getCreateFields } = useResourceFields()

const { form } = useFieldsForm(fields, {
  billable: {
    tax_type: Innoclapps.config('options.tax_type'),
    products: [],
  },
})

// Provide initial focus element as the modal can be nested and it's not
// finding an element for some reason when the second modal is closed
// showing error "There are no focusable elements inside the <FocusTrap />"
const modalCloseElement = computedWithControl(
  () => null,
  () => document.querySelector('#modalClose-createDealModal')
)

whenever(() => props.visible, prepareComponent, { immediate: true })

function createdHandler(data) {
  data.indexRoute = { name: 'deal-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return

  if (props.redirectToView) {
    let deal = data.deal
    router.deal = deal

    router.push({
      name: 'view-deal',
      params: {
        id: deal.id,
      },
    })
  }
}

function handleModalShownEvent() {
  emit('modal-shown')
  modalCloseElement.trigger()
}

function handleModalHiddenEvent() {
  emit('modal-hidden')

  withProducts.value = false
  resetBillable()

  fields.value.set([])
}

/** Reset the form billable */
function resetBillable() {
  form.billable.products = []
  form.billable.tax_type = Innoclapps.config('options.tax_type')
}

function showProductsSection() {
  withProducts.value = true
  fields.value.update('amount', { readonly: true })
}

function hideProductsSection() {
  withProducts.value = false
  fields.value.update('amount', { readonly: false })
}

function handleProductRemovedEvent(e) {
  // Clear errors in case there was error previously for the index
  // If we don't clear the errors the product that is below will be
  // shown as error after the given index is deleted
  // e.q. add 2 products, cause error on first, delete first
  if (form.errors.has('billable.products.' + e.index + '.name')) {
    form.errors.clear('billable.products.' + e.index + '.name')
  }
}

function create() {
  request().then(createdHandler)
}

function createAndAddAnother() {
  request('create-another').then(data => {
    form.reset()
    createdHandler(data)
  })
}

function createAndGoToList() {
  request('go-to-list').then(createdHandler)
}

async function request(actionType = null) {
  if (!withProducts.value) {
    resetBillable()
  }

  if (props.associations) {
    form.fill(props.associations)
  }

  let deal = await form
    .hydrate()
    .post('/deals')
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(t('core::app.form_validation_failed'), 3000)
      }
      return Promise.reject(e)
    })

  let payload = {
    deal: deal,
    isRegularAction: actionType === null,
    action: actionType,
  }

  emit('created', payload)

  Innoclapps.success(t('core::resource.created'))

  return payload
}

async function prepareComponent() {
  let createFields = await getCreateFields(
    Innoclapps.config('fields.groups.deals')
  )

  fields.value.set(createFields)

  // From props, same attribute name and prop name
  ;['contacts', 'companies', 'name'].forEach(attribute => {
    if (props[attribute]) {
      fields.value.updateValue(attribute, props[attribute])
    }
  })

  if (props.pipeline) {
    fields.value.updateValue('pipeline_id', props.pipeline)
  }

  if (props.stageId) {
    // Sets to read only as if the user change the e.q. stage
    // manually will have unexpected UI confusions
    fields.value.updateValue(
      'stage_id',
      props.stageId
        ? find(props.pipeline.stages, stage => stage.id === props.stageId)
        : null
    )
  }

  if (props.disabledFields) {
    castArray(props.disabledFields).forEach(attribute =>
      fields.value.update(attribute, { readonly: true })
    )
  }

  if (props.hiddenFields) {
    castArray(props.hiddenFields).forEach(attribute =>
      fields.value.update(attribute, { displayNone: true })
    )
  }

  emit('ready', fields)
}
</script>
