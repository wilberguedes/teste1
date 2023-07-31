<template>
  <ILayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />
      <div class="inline-flex items-center">
        <div class="mr-3 lg:mr-6">
          <IMinimalDropdown type="horizontal">
            <IDropdownItem
              icon="DocumentAdd"
              :to="{
                name: 'import-resource',
                params: { resourceName: 'products' },
              }"
              :text="$t('core::import.import')"
            />
            <IDropdownItem
              v-if="$gate.userCan('export products')"
              icon="DocumentDownload"
              @click="$iModal.show('export-modal')"
              :text="$t('core::app.export.export')"
            />
            <IDropdownItem
              icon="Trash"
              :to="{
                name: 'trashed-resource-records',
                params: { resourceName: 'products' },
              }"
              :text="$t('core::app.soft_deletes.trashed')"
            />
            <IDropdownItem
              icon="Cog"
              @click="customizeTable(tableId)"
              :text="$t('core::table.list_settings')"
            />
          </IMinimalDropdown>
        </div>

        <IButton
          :to="{ name: 'create-product' }"
          icon="Plus"
          size="sm"
          :text="$t('billable::product.create')"
        />
      </div>
    </template>
    <ResourceTable
      resource-name="products"
      :table-id="tableId"
      :empty-state="{
        to: { name: 'create-product' },
        title: $t('billable::product.empty_state.title'),
        buttonText: $t('billable::product.create'),
        description: $t('billable::product.empty_state.description'),
        secondButtonText: $t('core::import.from_file', { file_type: 'CSV' }),
        secondButtonIcon: 'DocumentAdd',
        secondButtonTo: {
          name: 'import-resource',
          params: { resourceName: 'products' },
        },
      }"
    >
      <template #after-search="{ collection }">
        <div class="ml-auto flex items-center text-sm">
          <span
            class="font-medium text-neutral-800 dark:text-neutral-300"
            v-t="{
              path: 'billable::product.count',
              args: { count: collection.state.meta.total },
            }"
          />
        </div>
      </template>

      <template #name="{ row, formatted }">
        <div class="flex w-full justify-between">
          <router-link
            class="link grow"
            :to="{ name: 'edit-product', params: { id: row.id } }"
          >
            {{ formatted }}
          </router-link>
          <div class="ml-2 mt-px">
            <IMinimalDropdown
              v-if="row.authorizations.update || row.authorizations.delete"
            >
              <IDropdownItem
                v-if="row.authorizations.update"
                :to="{ name: 'edit-product', params: { id: row.id } }"
                icon="PencilAlt"
                :text="$t('core::app.edit')"
              />
              <IDropdownItem
                @click="clone(row.id)"
                icon="Duplicate"
                :text="$t('core::app.clone')"
              />
              <IDropdownItem
                v-if="row.authorizations.delete"
                @click="destroy(row.id)"
                icon="Trash"
                :text="$t('core::app.delete')"
              />
            </IMinimalDropdown>
          </div>
        </div>
      </template>
    </ResourceTable>

    <ProductExport
      url-path="/products/export"
      resource-name="products"
      :title="$t('billable::product.export')"
    />
    <!-- Create, Edit -->
    <router-view
      @created="reloadTable(tableId)"
      @restored="reloadTable(tableId)"
      @updated="reloadTable(tableId)"
    />
  </ILayout>
</template>
<script setup>
import ResourceTable from '~/Core/resources/js/components/Table'
import ProductExport from '~/Core/resources/js/components/Export'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const { reloadTable, customizeTable } = useTable()
const router = useRouter()
const tableId = 'products'

function clone(id) {
  Innoclapps.request()
    .post(`/products/${id}/clone`)
    .then(({ data }) => {
      reloadTable(tableId)
      router.push({ name: 'edit-product', params: { id: data.id } })
    })
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/products/${id}`)

  reloadTable(tableId)

  Innoclapps.success(t('billable::product.deleted'))
}
</script>
