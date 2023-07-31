<template>
  <div class="table-responsive">
    <div
      class="border border-neutral-200 dark:border-neutral-800 sm:rounded-md"
    >
      <!-- https://github.com/SortableJS/Vue.Draggable/issues/160 -->
      <draggable
        v-model="localProducts"
        tag="table"
        class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700"
        :item-key="item => item.draggable_key"
        handle=".product-draggable-handle"
        v-bind="draggableOptions"
        @end="updateProductsOrder"
        @start="productIdxNoteBeingAdded = null"
      >
        <template #header>
          <thead class="bg-neutral-50 dark:bg-neutral-800">
            <tr>
              <th
                class="bg-neutral-50 py-3 pl-4 pr-2 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'billable::product.table_heading'"
              />
              <th
                class="bg-neutral-50 p-2 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'billable::product.qty'"
              />
              <th
                class="bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'billable::product.unit_price'"
              />
              <th
                class="bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-show="hasTax"
                v-t="'billable::product.tax'"
              />
              <th
                class="bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'billable::product.discount'"
              />
              <th
                class="bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'billable::product.amount'"
              />
              <th />
            </tr>
          </thead>
          <tbody v-show="!localProducts.length">
            <tr>
              <td
                :colspan="hasTax ? 6 : 5"
                class="bg-white p-3 text-center text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
                v-t="'billable::product.resource_has_no_products'"
              />
            </tr>
          </tbody>
        </template>
        <template #item="{ element, index }">
          <tbody class="bg-white dark:bg-neutral-800">
            <FormTableProduct
              :tax-type="taxType"
              :index="index"
              v-model="localProducts[index]"
              @product-selected="$emit('productSelected', $event)"
            >
              <template #after-product-select="slotProps">
                <slot name="after-product-select" v-bind="slotProps" />
              </template>
              <td
                class="bg-white p-2 text-right align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <div class="flex items-center justify-between space-x-2">
                  <div class="mt-2">
                    <IMinimalDropdown type="horizontal">
                      <IDropdownItem
                        v-show="
                          (productIdxNoteBeingAdded === null ||
                            productIdxNoteBeingAdded !== index) &&
                          !localProducts[index].note
                        "
                        @click="addNote(index)"
                        :text="$t('core::app.add_note')"
                      />
                      <IDropdownItem
                        @click="removeProduct(index)"
                        :text="$t('core::app.remove')"
                      />
                    </IMinimalDropdown>
                  </div>
                  <!-- Disabled for now, as there are 2 <tr> when not exists and cannot be sorted (multiple elements) -->
                  <IButtonIcon
                    icon="Selector"
                    class="product-draggable-handle mt-2 cursor-move"
                  />
                </div>
              </td>
            </FormTableProduct>
            <tr
              v-if="
                productIdxNoteBeingAdded === index ||
                element.note ||
                localProducts[index].note
              "
            >
              <td :colspan="hasTax ? 7 : 6" class="px-1">
                <div
                  class="relative z-auto -mt-2 mb-1 rounded-sm bg-white p-2 dark:bg-neutral-800"
                >
                  <IFormLabel :for="'product-note-' + index" class="mb-1">
                    {{ $t('core::app.note_is_private') }}
                  </IFormLabel>
                  <IFormTextarea
                    rows="2"
                    ref="productNoteRef"
                    class="rounded border border-warning-300 bg-warning-100 focus:border-warning-400 focus:ring-warning-400 dark:bg-warning-200 dark:text-neutral-800"
                    :bordered="false"
                    :rounded="false"
                    v-model="localProducts[index].note"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </template>
      </draggable>
    </div>
  </div>
  <a
    class="link mt-3 inline-block text-sm font-medium"
    href="#"
    @click.prevent="insertNewLine"
  >
    + {{ $t('core::app.insert_new_line') }}
  </a>
  <div>
    <TotalSection
      :tax-type="taxType"
      :total="total"
      :total-discount="totalDiscount"
      :subtotal="subtotal"
      :taxes="taxes"
    />
  </div>
</template>
<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import FormTableProduct from './FormTableProductRow.vue'
import TotalSection from './TotalSection.vue'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'
import unionBy from 'lodash/unionBy'
import filter from 'lodash/filter'
import sortBy from 'lodash/sortBy'
import { useProducts } from '../../composables/useProducts'

import {
  totalProductAmountWithDiscount,
  totalProductDiscountAmount,
  totalTaxInAmount,
  blankProduct,
} from './utils'

import { randomString } from '@/utils'

import draggable from 'vuedraggable'
import { useDraggable } from '~/Core/resources/js/composables/useDraggable'

const emit = defineEmits([
  'update:products',
  'update:removedProducts',
  'productSelected',
  'productRemoved',
])

const props = defineProps({
  products: {
    type: Array,
    default() {
      return []
    },
  },
  removedProducts: {
    type: Array,
    default() {
      return []
    },
  },
  taxType: { required: true, type: String },
})

const { toFixed } = useAccounting()
const { draggableOptions } = useDraggable()

const {
  limitedNumberOfActiveProductsRetrieved,
  limitedNumberOfActiveProducts,
} = useProducts()

const productNoteRef = ref(null)
const precision = Innoclapps.config('currency.precision')
const productIdxNoteBeingAdded = ref(null)
const localProducts = ref([])

// Reset each time the component is unmounted
onUnmounted(() => {
  limitedNumberOfActiveProductsRetrieved.value = false
  limitedNumberOfActiveProducts.value = []
})

watch(
  localProducts,
  newVal => {
    emit('update:products', newVal)

    ensureCurrentProductsHasDraggableKey()
  },
  { deep: true }
)

watch(
  () => props.products,
  newVal => {
    localProducts.value = newVal
  },
  { immediate: true }
)

const hasTax = computed(() => props.taxType !== 'no_tax')

const isTaxInclusive = computed(() => props.taxType === 'inclusive')

const total = computed(() => {
  let total =
    parseFloat(subtotal.value) +
    parseFloat(!isTaxInclusive.value ? totalTax.value : 0)

  return parseFloat(toFixed(total, precision))
})

const totalDiscount = computed(() => {
  return parseFloat(
    toFixed(
      localProducts.value.reduce((total, product) => {
        return total + totalProductDiscountAmount(product, isTaxInclusive.value)
      }, 0),
      precision
    )
  )
})

const subtotal = computed(() => {
  return parseFloat(
    toFixed(
      localProducts.value.reduce((total, product) => {
        return (
          total + totalProductAmountWithDiscount(product, isTaxInclusive.value)
        )
      }, 0),
      precision
    )
  )
})
/**
 * Get the unique applied taxes
 */
const taxes = computed(() => {
  if (!hasTax.value) {
    return []
  }

  return sortBy(
    unionBy(localProducts.value, product => {
      // Track uniqueness by tax label and tax rate
      return product.tax_label + product.tax_rate
    }),
    'tax_rate'
  )
    .filter(tax => tax.tax_rate > 0)
    .reduce((groups, tax) => {
      let group = {
        key: tax.tax_label + tax.tax_rate,
        rate: tax.tax_rate,
        label: tax.tax_label,
        // We will get all products that are using the current tax in the loop
        raw_total: filter(localProducts.value, {
          tax_label: tax.tax_label,
          tax_rate: tax.tax_rate,
        })
          // Calculate the total tax based on the product
          .reduce((total, product) => {
            total += totalTaxInAmount(
              totalProductAmountWithDiscount(product, isTaxInclusive.value),
              product.tax_rate,
              isTaxInclusive.value
            )
            return total
          }, 0),
      }

      groups.push(group)

      return groups
    }, [])
})

const totalTax = computed(() => {
  return parseFloat(
    toFixed(
      taxes.value.reduce((total, tax) => {
        return total + parseFloat(toFixed(tax.raw_total, precision))
      }, 0),
      precision
    )
  )
})

function ensureCurrentProductsHasDraggableKey() {
  localProducts.value.forEach(product => {
    if (!product.draggable_key) {
      product.draggable_key = randomString()
    }
  })
}

function addNote(index) {
  productIdxNoteBeingAdded.value = index

  nextTick(() => {
    productNoteRef.value.focus()
  })
}

/**
 * Queue product for removal
 */
function removeProduct(index) {
  let product = localProducts.value[index]
  localProducts.value.splice(index, 1)

  if (productIdxNoteBeingAdded.value === index) {
    productIdxNoteBeingAdded.value = null
  }

  emit('productRemoved', {
    product: product,
    index: index,
  })

  if (product.id) {
    emit('update:removedProducts', [...props.removedProducts, ...[product.id]])
  }
}

function insertNewLine() {
  localProducts.value.push(blankProduct())
  updateProductsOrder()
}

function updateProductsOrder() {
  localProducts.value.forEach(
    (product, index) => (product.display_order = index + 1)
  )
}

defineExpose({ insertNewLine })
</script>
