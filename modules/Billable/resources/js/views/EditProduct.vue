<template>
  <ISlideover
    @hidden="$router.back()"
    :visible="true"
    static-backdrop
    form
    @submit="update"
    :ok-disabled="
      form.busy || (fields.isNotEmpty() && !product.authorizations.update)
    "
    :ok-loading="form.busy"
    :ok-title="$t('core::app.save')"
    :title="$t('billable::product.edit')"
  >
    <FieldsPlaceholder v-if="fields.isEmpty()" />

    <FieldsGenerator :fields="fields" view="update" :form-id="form.formId" />
  </ISlideover>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useProducts } from '../composables/useProducts'

const emit = defineEmits(['updated'])

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { fetchProduct } = useProducts()

const { fields, getUpdateFields } = useResourceFields()

const { form } = useFieldsForm(fields)

const product = ref(null)

function update() {
  form
    .hydrate()
    .put(`/products/${route.params.id}`)
    .then(product => {
      emit('updated', product)
      Innoclapps.success(t('billable::product.updated'))
      router.back()
    })
}

function prepareComponent() {
  Promise.all([
    fetchProduct(route.params.id),
    getUpdateFields(
      Innoclapps.config('fields.groups.products'),
      route.params.id
    ),
  ]).then(values => {
    fields.value.set(values[1]).populate(values[0])
    product.value = values[0]
  })
}

prepareComponent()
</script>
