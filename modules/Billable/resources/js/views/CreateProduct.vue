<template>
  <ISlideover
    @hidden="$router.back()"
    :title="$t('billable::product.create')"
    :visible="true"
    static-backdrop
    form
    @submit="create"
  >
    <FieldsPlaceholder v-if="fields.isEmpty()" />

    <FieldsGenerator
      focus-first
      :form-id="form.formId"
      :fields="fields"
      :is-floating="true"
      view="create"
    >
      <template #after-name-field v-if="trashedProduct !== null">
        <IAlert
          dismissible
          class="mb-3"
          @dismissed="
            ;(recentlyRestored.byName = false), (trashedProduct = null)
          "
        >
          {{ $t('billable::product.exists_in_trash_by_name') }}

          <div class="mt-4">
            <div class="-mx-2 -my-1.5 flex">
              <IButtonMinimal
                v-show="!recentlyRestored.byName"
                variant="info"
                @click="restoreTrashed(trashedProduct.id, 'byName')"
                :text="$t('core::app.soft_deletes.restore')"
              />
              <IButtonMinimal
                v-show="recentlyRestored.byName"
                variant="info"
                @click="
                  $router.replace({
                    name: 'view-product',
                    params: { id: trashedProduct.id },
                  })
                "
                :text="$t('core::app.view_record')"
              />
            </div>
          </div>
        </IAlert>
      </template>
    </FieldsGenerator>
    <template #modal-ok>
      <IDropdownButtonGroup
        type="submit"
        :disabled="form.busy"
        :loading="form.busy"
        :text="$t('core::app.create')"
      >
        <IDropdownItem
          @click="createAndAddAnother"
          :text="$t('core::app.create_and_add_another')"
        />
        <IDropdownItem
          @click="createAndGoToList"
          :text="$t('core::app.create_and_go_to_list')"
        />
      </IDropdownButtonGroup>
    </template>
  </ISlideover>
</template>
<script setup>
import { ref } from 'vue'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { watchDebounced } from '@vueuse/core'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'

const emit = defineEmits(['created', 'restored'])

const { t } = useI18n()
const router = useRouter()

const { fields, getCreateFields } = useResourceFields()

const { form } = useFieldsForm(fields)

const trashedProduct = ref(null)
const nameField = ref(null)
const recentlyRestored = ref({
  byName: false,
})

watchDebounced(
  () => nameField.value?.currentValue,
  newVal => {
    if (!newVal) {
      trashedProduct.value = null
      return
    }

    Innoclapps.request()
      .get('/trashed/products/search', {
        params: {
          q: newVal,
          search_fields: 'name:=',
        },
      })
      .then(({ data: products }) => {
        trashedProduct.value = products.length > 0 ? products[0] : null
      })
  },
  { debounce: 500 }
)

function create() {
  request().then(product => router.back())
}

function createAndAddAnother() {
  request().then(product => form.reset())
}

function createAndGoToList() {
  request().then(product => router.push('/products'))
}

async function request() {
  try {
    let product = await form.hydrate().post('/products')

    emit('created', product)

    Innoclapps.success(t('billable::product.created'))

    return product
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }
    return Promise.reject(e)
  }
}

function restoreTrashed(id, type) {
  Innoclapps.request()
    .post(`/trashed/products/${id}`)
    .then(() => {
      recentlyRestored.value[type] = true
      emit('restored', trashedProduct)
    })
}

function prepareComponent() {
  getCreateFields(Innoclapps.config('fields.groups.products'))
    .then(createFields => fields.value.set(createFields))
    .then(() => {
      nameField.value = fields.value.find('name')
    })
}

prepareComponent()
</script>
