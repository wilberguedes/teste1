<template>
  <div
    class="overflow-hidden border border-neutral-200 shadow dark:border-neutral-700 sm:rounded-md"
  >
    <div class="table-responsive sm:rounded-md">
      <table
        class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-600"
      >
        <thead class="bg-neutral-50 dark:bg-neutral-800">
          <th
            class="whitespace-nowrap bg-neutral-50 p-3 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-t="'billable::product.table_heading'"
          />
          <th
            class="whitespace-nowrap bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-t="'billable::product.qty'"
          />
          <th
            class="whitespace-nowrap bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-t="'billable::product.unit_price'"
          />
          <th
            v-show="billable.tax_type !== 'no_tax'"
            class="whitespace-nowrap bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-t="'billable::product.tax'"
          />
          <th
            class="whitespace-nowrap bg-neutral-50 px-2 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-show="billable.has_discount"
            v-t="'billable::product.discount'"
          />
          <th
            class="whitespace-nowrap bg-neutral-50 p-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-900 dark:text-neutral-200"
            v-t="'billable::product.amount'"
          />
        </thead>
        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-600">
          <tr v-for="product in billable.products" :key="product.id">
            <td
              class="w-80 whitespace-nowrap bg-white p-3 text-left align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
            >
              <div class="flex">
                <span
                  class="mr-2 mt-px"
                  v-i-tooltip.top.light="product.note"
                  v-if="product.note"
                >
                  <Icon
                    icon="ChatBubbleBottomCenterText"
                    class="h-4 w-4 text-neutral-500 hover:text-neutral-700 dark:text-white dark:hover:text-neutral-300"
                  />
                </span>
                <div>
                  <p class="font-medium text-neutral-800 dark:text-neutral-100">
                    {{ (product.sku ? product.sku + ': ' : '') + product.name }}
                  </p>
                  <div
                    class="mt-1 whitespace-pre-line text-neutral-600 dark:text-neutral-300"
                    v-show="product.description"
                    v-text="product.description"
                  />
                </div>
              </div>
            </td>
            <td
              class="whitespace-nowrap bg-white p-2 text-right align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
            >
              {{ product.qty }} {{ product.unit || '' }}
            </td>
            <td
              class="whitespace-nowrap bg-white p-2 text-right align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
              v-text="formatMoney(product.unit_price)"
            />
            <td
              v-show="billable.tax_type !== 'no_tax'"
              class="whitespace-nowrap bg-white p-2 text-right align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
            >
              {{ product.tax_label }} ({{ product.tax_rate }}%)
            </td>
            <td
              v-show="billable.has_discount"
              class="whitespace-nowrap bg-white p-2 text-right align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
            >
              <span v-show="product.discount_type === 'fixed'">
                {{ formatMoney(product.discount_total) }}
              </span>
              <span v-show="product.discount_type === 'percent'">
                {{ product.discount_total }}%
              </span>
            </td>
            <td
              class="whitespace-nowrap bg-white p-3 text-right align-top text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
              v-text="formatMoney(product.amount)"
            />
          </tr>

          <tr v-show="!hasProducts">
            <td
              :colspan="totalColumns"
              class="bg-white p-3 text-center text-sm text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100"
              v-t="'billable::product.resource_has_no_products'"
            />
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <TotalSection
    v-show="hasProducts"
    :tax-type="billable.tax_type"
    :total="billable.total"
    :total-discount="billable.total_discount"
    :subtotal="billable.subtotal"
    :taxes="billable.taxes"
  />
</template>
<script setup>
import TotalSection from './TotalSection.vue'
import { computed } from 'vue'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'

const props = defineProps({
  billable: { required: true, type: Object },
})

const { formatMoney } = useAccounting()

const totalColumns = computed(() =>
  props.billable.tax_type === 'no_tax' ? 4 : 5
)

const hasProducts = computed(() => props.billable.products.length > 0)
</script>
