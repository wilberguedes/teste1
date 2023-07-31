<template>
  <ICard
    :header="$t('brands::brand.brands')"
    :overlay="brandsAreBeingFetched"
    no-body
  >
    <template #actions>
      <IButton
        :to="{ name: 'create-brand' }"
        icon="plus"
        size="sm"
        :text="$t('brands::brand.create')"
      />
    </template>
    <ITable class="-mt-px">
      <thead>
        <tr>
          <th class="text-left" v-t="'core::app.id'" width="5%"></th>
          <th class="text-left" v-t="'brands::brand.brand'"></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="brand in brands" :key="brand.id">
          <td v-text="brand.id"></td>
          <td>
            <router-link
              class="link"
              :to="{ name: 'edit-brand', params: { id: brand.id } }"
            >
              {{ brand.name }}
            </router-link>
            <IBadge
              v-if="brand.is_default"
              :text="$t('core::app.is_default')"
              variant="primary"
              class="ml-2"
            />
          </td>
          <td class="flex justify-end">
            <IMinimalDropdown>
              <IDropdownItem
                :to="{ name: 'edit-brand', params: { id: brand.id } }"
                :text="$t('core::app.edit')"
              />

              <IDropdownItem
                @click="destroy(brand.id)"
                :text="$t('core::app.delete')"
              />
            </IMinimalDropdown>
          </td>
        </tr>
      </tbody>
    </ITable>
  </ICard>
</template>
<script setup>
import { useBrands } from '../composables/useBrands'

const { orderedBrands: brands, brandsAreBeingFetched, deleteBrand } = useBrands()

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  deleteBrand(id)
}
</script>
