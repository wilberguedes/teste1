<template>
  <ILayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />
      <div class="inline-flex items-center">
        <IButton
          :to="{ name: 'create-document-template' }"
          icon="Plus"
          :text="$t('documents::document.template.create')"
          size="sm"
        />
      </div>
    </template>
    <ResourceTable
      resource-name="document-templates"
      :table-id="tableId"
      :empty-state="{
        to: { name: 'create-document-template' },
        title: $t('documents::document.template.empty_state.title'),
        buttonText: $t('documents::document.template.create'),
        description: $t('documents::document.template.empty_state.description'),
      }"
    >
      <template #name="{ row, formatted }">
        <div class="flex w-full justify-between">
          <router-link
            v-if="row.authorizations.update"
            class="link grow"
            :to="{ name: 'edit-document-template', params: { id: row.id } }"
          >
            {{ formatted }}
          </router-link>
          <span v-else v-text="formatted"></span>
          <div class="ml-2">
            <IMinimalDropdown>
              <IDropdownItem
                v-if="row.authorizations.update"
                :to="{ name: 'edit-document-template', params: { id: row.id } }"
                :text="$t('core::app.edit')"
                icon="PencilAlt"
              />
              <IDropdownItem
                @click="clone(row.id)"
                :text="$t('core::app.clone')"
                icon="Duplicate"
              />
              <IDropdownItem
                v-if="row.authorizations.delete"
                icon="Trash"
                @click="destroy(row.id)"
                :text="$t('core::app.delete')"
              />
            </IMinimalDropdown>
          </div>
        </div>
      </template>
    </ResourceTable>

    <!-- Create, Edit -->
    <router-view name="create" @created="reloadTable(tableId)" />
    <router-view name="edit" @updated="reloadTable(tableId)" />
  </ILayout>
</template>
<script setup>
import ResourceTable from '~/Core/resources/js/components/Table'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()
const { reloadTable } = useTable()

const tableId = 'document-templates'

function clone(id) {
  Innoclapps.request()
    .post(`/document-templates/${id}/clone`)
    .then(({ data }) => {
      reloadTable(tableId)
      router.push({ name: 'edit-document-template', params: { id: data.id } })
    })
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/document-templates/${id}`)

  reloadTable(tableId)

  Innoclapps.success(t('documents::document.template.deleted'))
}
</script>
