<template>
  <tr>
    <td
      width="30%"
      class="bg-white px-3 py-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      <div class="relative w-60 sm:w-auto">
        <ICustomSelect
          @option:selected="handleProductChangeEvent"
          @cleared="handleProductChangeEvent(null)"
          @search="onSearch"
          @open="handleProductDropdownOpen"
          :create-option-provider="createNewProductViaSelect"
          :option-label-provider="provideProductSelectFieldOptionLabel"
          :placeholder="$t('billable::product.choose_or_enter')"
          label="name"
          :taggable="true"
          :filterable="false"
          v-model="selectedProduct"
          :options="productsForDropdown"
          :loading="retrievingProducts"
        />

        <slot name="after-product-select" :product="product" :index="index" />

        <IFormTextarea
          v-show="selectedProduct"
          :modelValue="product.description"
          @update:modelValue="updateProduct({ description: $event })"
          class="mt-1"
          :name="'products' + '.' + index + '.description'"
          :placeholder="
            $t('billable::product.description') +
            ' ' +
            '(' +
            $t('core::app.optional') +
            ')'
          "
          :rows="3"
        />
      </div>
    </td>
    <td
      class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      <div class="w-32 sm:w-auto">
        <IFormNumericInput
          class="text-right"
          size="sm"
          decimal-separator="."
          :precision="2"
          :empty-value="1"
          :placeholder="$t('billable::product.quantity')"
          pattern=".*"
          :modelValue="product.qty"
          @update:modelValue="updateProduct({ qty: $event })"
        >
        </IFormNumericInput>
        <IFormInput
          size="sm"
          :placeholder="$t('billable::product.unit')"
          class="mt-1 text-right"
          :modelValue="product.unit"
          @update:modelValue="updateProduct({ unit: $event })"
        />
      </div>
    </td>
    <td
      class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      <div class="w-40 sm:w-auto">
        <IFormNumericInput
          class="text-right"
          size="sm"
          :placeholder="$t('billable::product.unit_price')"
          :minus="true"
          :modelValue="product.unit_price"
          @update:modelValue="updateProduct({ unit_price: $event })"
        />
      </div>
    </td>
    <td
      v-show="taxType !== 'no_tax'"
      class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      <div class="w-44 sm:w-auto">
        <div class="flex rounded-md shadow-sm">
          <div class="relative flex grow items-stretch focus-within:z-10">
            <IFormNumericInput
              :placeholder="$t('billable::product.tax_percent')"
              :precision="3"
              size="sm"
              :rounded="false"
              class="rounded-l-md"
              :minus="true"
              :max="100"
              :modelValue="product.tax_rate"
              @update:modelValue="updateProduct({ tax_rate: $event })"
            />
          </div>
          <div
            class="-ml-px flex items-center border border-neutral-300 bg-white px-1.5 dark:border-neutral-500 dark:bg-neutral-700"
            v-text="'%'"
          />
          <IPopover
            placement="bottom"
            class="max-w-sm"
            :shadow="false"
            :arrow="false"
            :bordered="false"
          >
            <IButton
              :rounded="false"
              size="sm"
              variant="white"
              class="-ml-px rounded-r-md"
              :text="modelValue.tax_label"
            />
            <template #popper>
              <IFormInput
                :modelValue="product.tax_label"
                @update:modelValue="updateProduct({ tax_label: $event })"
              />
            </template>
          </IPopover>
        </div>
      </div>
    </td>
    <td
      class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      <div class="relative w-44 rounded-md shadow-sm sm:w-auto">
        <IFormNumericInput
          v-if="modelValue.discount_type == 'fixed'"
          class="pr-12"
          size="sm"
          :placeholder="$t('billable::product.discount_amount')"
          :modelValue="product.discount_total"
          @update:modelValue="updateProduct({ discount_total: $event })"
        />
        <IFormNumericInput
          v-else
          class="pr-12"
          :placeholder="$t('billable::product.discount_percent')"
          :max="100"
          size="sm"
          :precision="2"
          :modelValue="product.discount_total"
          @update:modelValue="updateProduct({ discount_total: $event })"
        />

        <div class="absolute inset-y-0 right-0 flex items-center">
          <IFormSelect
            :modelValue="product.discount_type"
            @update:modelValue="updateProduct({ discount_type: $event })"
            :bordered="false"
            size="sm"
            class="bg-transparent bg-none text-right dark:bg-transparent"
          >
            <option
              v-for="dType in discountTypes"
              :key="dType.value"
              :value="dType.value"
              v-text="dType.label"
            />
          </IFormSelect>
        </div>
      </div>
    </td>
    <td
      class="bg-white px-2 py-4 text-right align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
    >
      {{ formatMoney(amountBeforeTaxWithDiscountApplied) }}
    </td>
    <slot></slot>
  </tr>
</template>
<script setup>
import { ref, shallowRef, computed, onMounted } from 'vue'
import { totalProductAmountWithDiscount, blankProduct } from './utils'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'
import { useI18n } from 'vue-i18n'
import { useProducts } from '../../composables/useProducts'
import debounce from 'lodash/debounce'

const emit = defineEmits(['update:modelValue', 'productSelected'])

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  taxType: { required: true, type: String },
  index: { required: true, type: Number },
})

const { t } = useI18n()

const { formatMoney } = useAccounting()

const {
  limitedNumberOfActiveProducts: products,
  retrieveLimitedNumberOfActiveProducts,
  limitedNumberOfActiveProductsRetrieved,
  fetchActiveProducts,
  fetchProductByName,
} = useProducts()

const searchResults = shallowRef(null)

// use local var so the loader is shown only on one field not on all
const retrievingProducts = ref(false)

const product = ref(props.modelValue || [])

let selectedProduct = ref(null)

const productsForDropdown = computed(
  () => searchResults.value || products.value
)

function handleProductDropdownOpen() {
  if (limitedNumberOfActiveProductsRetrieved.value) {
    return
  }

  retrievingProducts.value = true

  retrieveLimitedNumberOfActiveProducts().then(
    () => (retrievingProducts.value = false)
  )
}

const onSearch = debounce((search, loading) => {
  if (search == '') {
    loading(false)
    searchResults.value = null
    return
  }

  loading(true)

  fetchActiveProducts({ q: search })
    .then(({ data }) => {
      searchResults.value = data
    })
    .finally(() => loading(false))
}, 500)

onMounted(() => {
  selectedProduct.value = props.modelValue || []
})

const discountTypes = [
  { label: Innoclapps.config('currency.iso_code'), value: 'fixed' },
  { label: '%', value: 'percent' },
]

/**
 * Get the amount before any tax calculations and with discount applied
 * for the last amount column
 */
const amountBeforeTaxWithDiscountApplied = computed(() => {
  return totalProductAmountWithDiscount(
    props.modelValue,
    props.taxType === 'inclusive'
  )
})

/**
 * Create new product for select
 */
function createNewProductViaSelect(newOption) {
  return blankProduct({
    name: newOption,
  })
}

/**
 * Provide the select field option label
 */
function provideProductSelectFieldOptionLabel(option) {
  if (option.sku) {
    // Allow sku in label to be searchable as well
    return option.sku + ': ' + option.name
  }

  return option.name
}

/**
 * Handle the product change event
 */
async function handleProductChangeEvent(product) {
  if (!product) {
    updateProduct({ name: null, product_id: null })
    return
  }

  const billableProduct = {
    product_id: product.id,
    name: product.name,
    description: product.description,
    unit_price: product.unit_price || 0,
    unit: product.unit,
    tax_rate: product.tax_rate || 0,
    tax_label: product.tax_label,
  }

  // We will try to find an existing product from the product list
  // based on the name user entered, users may enter names but don't realize
  // that the product already exists, in this case, we will help the user
  // to pre-use the product and prevent creating this product in storage on server side
  const productByName = await fetchProductByName(product.name)

  if (!productByName) {
    Innoclapps.info(
      t('billable::product.will_be_added_as_new', { name: product.name })
    )
  } else {
    // Update the product_id, as the value may be empty
    billableProduct.product_id = productByName.id

    // Add the latest selected product from search to the loaded products list
    if (!products.value.find(p => p.id == productByName.id)) {
      products.value.unshift(productByName)
    }
  }

  updateProduct(billableProduct)
  emit('productSelected', { product: product.value, index: props.index })
}

function updateProduct(property, value = null) {
  let modelValue = Object.assign(
    props.modelValue,
    typeof property === 'object' ? property : { [property]: value }
  )

  emit('update:modelValue', modelValue)
  product.value = modelValue
}
</script>
