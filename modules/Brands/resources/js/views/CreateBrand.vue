<template>
  <IModal
    size="sm"
    @hidden="$router.back"
    @shown="() => $refs.inputNameRef.focus()"
    :ok-title="$t('core::app.create')"
    :ok-disabled="form.busy"
    :visible="true"
    static-backdrop
    :title="$t('brands::brand.create')"
    form
    @submit="create"
    @keydown="form.onKeydown($event)"
  >
    <IFormGroup
      label-for="name"
      :label="$t('brands::brand.form.name')"
      required
    >
      <IFormInput
        v-model="form.name"
        id="name"
        ref="inputNameRef"
        @change="!form.display_name ? (form.display_name = $event) : undefined"
      />
      <IFormError v-text="form.getError('name')" />
    </IFormGroup>
    <IFormGroup
      label-for="display_name"
      :label="$t('brands::brand.form.display_name')"
      required
    >
      <IFormInput v-model="form.display_name" id="display_name" />
      <IFormError v-text="form.getError('display_name')" />
    </IFormGroup>
    <IFormGroup :label="$t('brands::brand.form.primary_color')">
      <IColorSwatches
        :allow-remove="false"
        v-model="form.config.primary_color"
        :swatches="swatches"
      />
      <IFormError v-text="form.getError('config.primary_color')" />
    </IFormGroup>

    <IFormGroup>
      <IFormCheckbox
        id="is_default"
        name="is_default"
        v-model:checked="form.is_default"
        :label="$t('brands::brand.form.is_default')"
      />
      <IFormError v-text="form.getError('is_default')" />
    </IFormGroup>

    <IFormGroup class="mt-4">
      <VisibilityGroupSelector
        v-model:type="form.visibility_group.type"
        v-model:dependsOn="form.visibility_group.depends_on"
      />
    </IFormGroup>
  </IModal>
</template>
<script setup>
import VisibilityGroupSelector from '~/Core/resources/js/components/VisibilityGroupSelector.vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useBrands } from '../composables/useBrands'
import { useForm } from '~/Core/resources/js/composables/useForm'

const { t } = useI18n()
const router = useRouter()

const { addBrand } = useBrands()

const swatches = Innoclapps.config('favourite_colors')

const { form } = useForm({
  name: '',
  display_name: '',
  is_default: false,
  config: {
    primary_color: '#4f46e5',
  },
  visibility_group: {
    type: 'all',
    depends_on: [],
  },
})

function create() {
  form.post('/brands').then(brand => {
    addBrand(brand)

    Innoclapps.success(t('brands::brand.created'))

    router.push({
      name: 'edit-brand',
      params: {
        id: brand.id,
      },
    })
  })
}
</script>
