<template>
  <div class="mx-auto max-w-5xl" v-show="visible">
    <h3
      class="mb-6 text-base font-medium text-neutral-800 dark:text-neutral-100"
      v-t="'documents::document.document_products'"
    />

    <div
      :class="{
        'pointer-events-none opacity-70': document.status === 'accepted',
      }"
    >
      <FormTaxTypes
        v-model="form.billable.tax_type"
        class="mb-4 flex flex-col space-y-1 sm:flex-row sm:space-x-2 sm:space-y-0"
      />

      <FormTableProducts
        v-model:products="form.billable.products"
        v-model:removedProducts="form.billable.removed_products"
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
  </div>
</template>
<script setup>
import propsDefinition from './formSectionProps'
import FormTableProducts from '~/Billable/resources/js/components/Billable/FormTableProducts.vue'
import FormTaxTypes from '~/Billable/resources/js/components/Billable/FormTaxTypes.vue'

const props = defineProps(propsDefinition)

function handleProductRemovedEvent(e) {
  // Clear errors in case there was error previously for the index
  // If we don't clear the errors the product that is below will be
  // shown as error after the given index is deleted
  // e.q. add 2 products, cause error on first, delete first
  if (props.form.errors.has('billable.products.' + e.index + '.name')) {
    props.form.errors.clear('billable.products.' + e.index + '.name')
  }
}
</script>
