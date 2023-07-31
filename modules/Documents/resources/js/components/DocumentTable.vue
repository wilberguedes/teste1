<template>
  <div class="mb-2 block lg:hidden">
    <DocumentTableStatusPicker v-model="selectedStatus" />
  </div>

  <ResourceTable
    v-if="initialize"
    @loaded="$emit('loaded', $event)"
    resource-name="documents"
    :table-id="tableId"
    :data-request-query-string="dataRequestQueryString"
    :empty-state="{
      to: { name: 'create-document' },
      title: $t('documents::document.empty_state.title'),
      buttonText: $t('documents::document.create'),
      description: $t('documents::document.empty_state.description'),
    }"
  >
    <template #after-search="{ collection }">
      <div class="hidden lg:ml-6 lg:block">
        <DocumentTableStatusPicker v-model="selectedStatus" />
      </div>
      <div class="ml-auto flex items-center text-sm">
        <span
          class="font-medium text-neutral-800 dark:text-neutral-300"
          v-t="{
            path: 'documents::document.count.all',
            args: { count: collection.state.meta.total },
          }"
        />
      </div>
    </template>
    <template #title="{ row, formatted }">
      <div class="flex w-full justify-between">
        <router-link
          class="link grow"
          :to="{ name: 'edit-document', params: { id: row.id } }"
        >
          {{ formatted }}
        </router-link>
        <div class="ml-2 mt-px">
          <IMinimalDropdown>
            <IDropdownItem
              :href="row.public_url"
              icon="Eye"
              :text="$t('documents::document.view')"
            />
            <IDropdownItem
              v-if="row.authorizations.update && row.status === 'draft'"
              :to="{
                name: 'edit-document',
                params: { id: row.id },
                query: { section: 'send' },
              }"
              icon="PencilAlt"
              :text="$t('documents::document.send.send')"
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
    <template #status="{ formatted }">
      <TextBackground
        :color="statuses[formatted].color"
        class="inline-flex items-center justify-center rounded-full px-2.5 text-sm/5 font-normal dark:!text-white"
      >
        {{ $t('documents::document.status.' + formatted) }}
      </TextBackground>
    </template>
  </ResourceTable>
</template>
<script setup>
import { ref, computed } from 'vue'
import ResourceTable from '~/Core/resources/js/components/Table'
import TextBackground from '~/Core/resources/js/components/TextBackground.vue'
import DocumentTableStatusPicker from './DocumentTableStatusPicker.vue'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const emit = defineEmits(['deleted', 'loaded'])

const props = defineProps({
  tableId: { required: true, type: String },
  initialize: { default: true, type: Boolean },
})

const { t } = useI18n()
const router = useRouter()

const { reloadTable } = useTable()

const statuses = Innoclapps.config('documents.statuses')
const selectedStatus = ref(null)

const dataRequestQueryString = computed(() => ({
  status: selectedStatus.value,
}))

function clone(id) {
  Innoclapps.request()
    .post(`/documents/${id}/clone`)
    .then(({ data }) => {
      reloadTable(props.tableId)
      router.push({ name: 'edit-document', params: { id: data.id } })
    })
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/documents/${id}`)

  emit('deleted', id)
  reloadTable(props.tableId)

  Innoclapps.success(t('core::resource.deleted'))
}
</script>
