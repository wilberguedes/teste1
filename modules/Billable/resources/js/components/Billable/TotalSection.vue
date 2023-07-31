<template>
  <div>
    <div class="mb-2 mt-4 grid grid-cols-12 gap-2">
      <div
        class="col-span-8 text-sm text-neutral-600 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right"
      >
        {{ $t('billable::billable.sub_total') }}

        <p
          v-show="hasDiscount"
          class="italic text-neutral-500 dark:text-neutral-300"
        >
          ({{
            $t('billable::billable.includes_discount', {
              amount: formatMoney(totalDiscount),
            })
          }})
        </p>
      </div>
      <div
        class="col-span-4 text-right text-sm text-neutral-600 dark:text-neutral-100 sm:col-span-3"
        v-text="formatMoney(subtotal)"
      />
    </div>
    <div
      v-for="tax in taxes"
      :key="tax.key"
      v-show="hasTax"
      class="mb-2 grid grid-cols-12 gap-2"
    >
      <div
        class="col-span-8 text-sm text-neutral-600 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right"
      >
        {{ tax.label }} ({{ tax.rate }}%)
      </div>
      <div
        class="col-span-4 text-right text-sm text-neutral-600 dark:text-neutral-100 sm:col-span-3"
      >
        <span>
          <span
            v-show="isTaxInclusive"
            v-t="'billable::billable.tax_amount_is_inclusive'"
          />
          {{ formatMoney(tax.raw_total) }}
        </span>
      </div>
    </div>

    <div class="grid grid-cols-12 gap-2">
      <div
        class="col-span-8 text-sm font-semibold text-neutral-900 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right"
        v-t="'billable::billable.total'"
      />
      <div
        class="col-span-4 text-right text-sm font-medium text-neutral-900 dark:text-neutral-100 sm:col-span-3"
        v-text="formatMoney(total)"
      />
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'

const props = defineProps({
  taxType: { required: true },
  total: { required: true },
  totalDiscount: { required: true },
  subtotal: { required: true },
  taxes: { required: true, default: () => [] },
})

const { formatMoney } = useAccounting()

const hasDiscount = computed(() => props.totalDiscount > 0)
const hasTax = computed(() => props.taxType !== 'no_tax')
const isTaxInclusive = computed(() => props.taxType === 'inclusive')
</script>
