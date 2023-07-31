<template>
  <ITabPanel>
    <div
      class="-mt-[20px] mb-3 rounded-b-md border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 sm:mb-7"
    >
      <div class="px-4 py-5 sm:p-6">
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <h3
              class="text-base/6 font-medium text-neutral-900 dark:text-white"
              v-t="'billable::product.products'"
            />
            <div
              class="mt-2 max-w-xl text-sm text-neutral-500 dark:text-neutral-200"
            >
              <p v-t="'billable::product.deal_info'" />
            </div>
          </div>
          <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
            <IButton
              @click="manageProducts = true"
              :disabled="!record.authorizations.update"
              size="sm"
              :text="$t('billable::product.manage')"
            />
          </div>
        </div>
      </div>
    </div>

    <div v-show="hasProducts">
      <h4
        class="mb-3 text-base font-medium text-neutral-700 dark:text-neutral-200"
        v-t="'billable::product.related_products'"
      />
      <ProductsTable :billable="record.billable" />
    </div>

    <FormTableModal
      @hidden="manageProducts = false"
      :billable="record.billable"
      :resource-name="resourceName"
      :resource-id="record.id"
      @saved="handleBillableModelSavedEvent"
      :visible="manageProducts"
    />
  </ITabPanel>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import FormTableModal from './Billable/FormProductsModal.vue'
import ProductsTable from './Billable/ProductsTable.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
})

const { record, setRecord } = useRecordStore()

const totalProducts = computed(() =>
  !record.value || !record.value.billable
    ? 0
    : record.value.billable.products.length
)

const hasProducts = computed(() => totalProducts.value > 0)

const manageProducts = ref(false)

function showProductsDialog() {
  manageProducts.value = true
}

function handleBillableModelSavedEvent(billable) {
  setRecord({ billable })
}

defineExpose({ showProductsDialog })
</script>
