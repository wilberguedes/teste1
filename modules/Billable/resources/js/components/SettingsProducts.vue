<template>
  <form @submit.prevent="submit" @keydown="form.onKeydown($event)">
    <ICard
      :header="$t('billable::product.products')"
      :overlay="!componentReady"
    >
      <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
        <div class="sm:col-span-2">
          <IFormGroup
            :label="$t('billable::product.tax_label')"
            label-for="tax_label"
            required
          >
            <IFormInput v-model="form.tax_label" id="tax_label" />
          </IFormGroup>
        </div>

        <div class="sm:col-span-2">
          <IFormGroup
            :label="$t('billable::product.tax_rate')"
            label-for="tax_rate"
          >
            <IFormNumericInput
              :placeholder="$t('billable::product.tax_percent')"
              :precision="3"
              :minus="true"
              v-model="form.tax_rate"
            >
            </IFormNumericInput>
          </IFormGroup>
        </div>
      </div>

      <IFormGroup
        class="mt-3 space-y-1"
        :label="$t('billable::product.settings.default_tax_type')"
        label-for="tax_type"
      >
        <IFormRadio
          v-for="taxType in taxTypes"
          :key="taxType"
          :label="$t('billable::billable.tax_types.' + taxType)"
          :id="taxType"
          v-model="form.tax_type"
          :value="taxType"
          name="tax_type"
        />
      </IFormGroup>
      <IFormGroup
        :label="$t('billable::product.settings.default_discount_type')"
        label-for="tax_type"
        class="mt-3 space-y-1"
      >
        <IFormRadio
          v-for="discountType in discountTypes"
          :key="discountType.value"
          :label="discountType.label"
          :id="discountType.value"
          v-model="form.discount_type"
          :value="discountType.value"
          name="discount_type"
        />
      </IFormGroup>
      <template #footer>
        <IButton
          type="submit"
          :disabled="form.busy"
          :text="$t('core::app.save')"
        />
      </template>
    </ICard>
  </form>
</template>
<script setup>
import { useSettings } from '~/Core/resources/js/views/Settings/useSettings'

const { form, isReady: componentReady, submit } = useSettings()

const taxTypes = Innoclapps.config('taxes.types')

const discountTypes = [
  { label: Innoclapps.config('currency.iso_code'), value: 'fixed' },
  { label: '%', value: 'percent' },
]
</script>
