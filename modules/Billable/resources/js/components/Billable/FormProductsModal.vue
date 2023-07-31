<template>
  <IModal
    :visible="visible"
    size="xxl"
    @ok="save"
    id="productsModal"
    static-backdrop
    :cancel-title="$t('core::app.cancel')"
    :ok-title="$t('core::app.save')"
    :ok-disabled="form.busy"
    @hidden="$emit('hidden')"
    @show="handleModalShowEvent"
    @shown="handleModalShownEvent"
    :title="$t('billable::product.add_to_deal')"
  >
    <FormTaxTypes
      v-model="form.tax_type"
      class="mb-4 flex flex-col space-y-1 sm:flex-row sm:space-x-2 sm:space-y-0"
    />

    <FormTableProducts
      ref="productsRef"
      v-model:products="form.products"
      v-model:removedProducts="form.removed_products"
      :tax-type="form.tax_type"
      @productSelected="form.errors.clear('products.' + $event.index + '.name')"
      @productRemoved="handleProductRemovedEvent"
    >
      <template #after-product-select="{ index }">
        <IFormError v-text="form.getError('products.' + index + '.name')" />
      </template>
    </FormTableProducts>
  </IModal>
</template>
<script setup>
import { ref, nextTick } from 'vue'
import FormTableProducts from './FormTableProducts.vue'
import FormTaxTypes from './FormTaxTypes.vue'
import cloneDeep from 'lodash/cloneDeep'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['saved', 'hidden'])

const props = defineProps({
  billable: { type: Object },
  visible: { default: false, type: Boolean },
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: Number },
})

const { form } = useForm({
  products: [],
  removed_products: [],
})

const productsRef = ref(null)

function handleProductRemovedEvent(e) {
  // Clear errors in case there was error previously for the index
  // If we don't clear the errors the product that is below will be
  // shown as error after the given index is deleted
  // e.q. add 2 products, cause error on first, delete first
  if (form.errors.has('products.' + e.index + '.name')) {
    form.errors.clear('products.' + e.index + '.name')
  }
}

function save() {
  form
    .post(`${props.resourceName}/${props.resourceId}/billable`)
    .then(billable => {
      emit('saved', billable)
      Innoclapps.modal().hide('productsModal')
    })
}

function handleModalShowEvent() {
  if (props.billable) {
    form.set('tax_type', props.billable.tax_type)
    form.set('products', cloneDeep(props.billable.products))
  } else {
    form.set('tax_type', 'exclusive')
    form.set('products', [])
  }
}

function handleModalShownEvent() {
  if (form.products.length === 0) {
    nextTick(productsRef.value.insertNewLine)
  }
}
</script>
