<template>
  <ILayout>
    <CardList v-if="showCards" resource-name="deals" />

    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <div class="inline-flex items-center">
        <div class="mr-3 lg:mr-6">
          <IMinimalDropdown type="horizontal">
            <IDropdownItem
              icon="DocumentAdd"
              :to="{
                name: 'import-deal',
              }"
              :text="$t('core::import.import')"
            />
            <IDropdownItem
              v-if="$gate.userCan('export deals')"
              icon="DocumentDownload"
              @click="$iModal.show('export-modal')"
              :text="$t('core::app.export.export')"
            />
            <IDropdownItem
              icon="Trash"
              :to="{
                name: 'trashed-resource-records',
                params: { resourceName: 'deals' },
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
        <IButtonGroup class="mr-5">
          <IButton
            size="sm"
            class="relative bg-neutral-100 focus:z-10"
            :to="{ name: 'deal-index' }"
            v-i-tooltip.bottom="$t('core::app.list_view')"
            variant="white"
            icon="Bars3"
            icon-class="w-4 h-4 text-neutral-700 dark:text-neutral-100"
          />
          <IButton
            size="sm"
            class="relative focus:z-10"
            :to="{ name: 'deal-board' }"
            v-i-tooltip.bottom="$t('deals::board.board')"
            variant="white"
            icon="ViewColumns"
            icon-class="w-4 h-4 text-neutral-500 dark:text-neutral-400"
          />
        </IButtonGroup>

        <IButton
          :to="{ name: 'create-deal' }"
          icon="Plus"
          size="sm"
          :text="$t('deals::deal.create')"
        />
      </div>
    </template>

    <DealTable
      :table-id="tableId"
      :initialize="initialize"
      @loaded="tableEmpty = $event.empty"
      @deleted="refreshIndex"
      :filter-id="
        $route.query.filter_id ? Number($route.query.filter_id) : undefined
      "
    />

    <DealExport
      url-path="/deals/export"
      resource-name="deals"
      :filters-view="tableId"
      :title="$t('deals::deal.export')"
    />

    <!-- Create -->
    <router-view
      name="create"
      :redirect-to-view="true"
      @created="
        ({ isRegularAction }) => (!isRegularAction ? refreshIndex() : undefined)
      "
      @modal-hidden="$router.back"
    />
  </ILayout>
</template>
<script setup>
import { ref, computed } from 'vue'
import DealTable from '../components/DealTable.vue'
import CardList from '~/Core/resources/js/components/Cards/CardList.vue'
import DealExport from '~/Core/resources/js/components/Export'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { onBeforeRouteUpdate, useRoute } from 'vue-router'

const route = useRoute()

const initialize = ref(route.meta.initialize)
const tableEmpty = ref(true)
const tableId = 'deals'

const { reloadTable, customizeTable } = useTable()

const showCards = computed(() => initialize.value && !tableEmpty.value)

function refreshIndex() {
  Innoclapps.$emit('refresh-cards')
  reloadTable(tableId)
}

/**
 * For all cases intialize index to be true
 * This helps when intially "initialize" was false
 * But now when the user actually sees the index, it should be updated to true
 */
onBeforeRouteUpdate((to, from, next) => {
  initialize.value = true

  next()
})
</script>
