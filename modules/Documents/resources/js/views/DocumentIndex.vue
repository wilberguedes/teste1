<template>
  <ILayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />
      <div class="inline-flex items-center">
        <div class="mr-3 lg:mr-6">
          <IMinimalDropdown type="horizontal">
            <IDropdownItem
              icon="Trash"
              :to="{
                name: 'trashed-resource-records',
                params: { resourceName: 'documents' },
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
          :to="{ name: 'create-document' }"
          icon="Plus"
          size="sm"
          :text="$t('documents::document.create')"
        />
      </div>
    </template>

    <CardList v-if="showCards" resource-name="documents" />

    <DocumentTable
      :table-id="tableId"
      :initialize="initialize"
      @loaded="tableEmpty = $event.empty"
      @deleted="refreshIndex"
    />

    <!-- Create router view -->
    <router-view name="create" @created="refreshIndex" @sent="refreshIndex" />

    <!-- Edit router view -->
    <router-view
      name="edit"
      @changed="refreshIndex"
      @deleted="refreshIndex"
      @cloned="refreshIndex"
      :exit-using="() => $router.push({ name: 'document-index' })"
    />
  </ILayout>
</template>
<script setup>
import { ref, computed } from 'vue'
import CardList from '~/Core/resources/js/components/Cards/CardList.vue'
import DocumentTable from '../components/DocumentTable.vue'
import { onBeforeRouteUpdate, useRoute } from 'vue-router'
import { useTable } from '~/Core/resources/js/components/Table/useTable'

const route = useRoute()

const initialize = ref(route.meta.initialize)
const tableEmpty = ref(true)
const tableId = 'documents'

const { reloadTable, customizeTable } = useTable()

const showCards = computed(() => initialize.value && !tableEmpty.value)

function refreshIndex() {
  Innoclapps.$emit('refresh-cards')
  reloadTable(tableId)
}

/**
 * Before the cached route is updated
 * For all cases set that intialize index to be true
 * This helps when intially "initialize" was false
 * But now when the user actually sees the index, it should be updated to true
 */
onBeforeRouteUpdate((to, from, next) => {
  initialize.value = true

  next()
})
</script>
