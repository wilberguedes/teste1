<template>
  <ResourceTable
    v-if="initialize"
    :resource-name="resourceName"
    :row-class="rowClass"
    :table-id="tableId"
    :empty-state="{
      to: { name: 'create-deal' },
      title: $t('deals::deal.empty_state.title'),
      buttonText: $t('deals::deal.create'),
      description: $t('deals::deal.empty_state.description'),
      secondButtonText: $t('core::import.from_file', { file_type: 'CSV' }),
      secondButtonIcon: 'DocumentAdd',
      secondButtonTo: {
        name: 'import-resource',
        params: { resourceName: 'deals' },
      },
    }"
    @loaded="$emit('loaded', $event)"
    v-bind="$attrs"
  >
    <template #after-search="{ collection }">
      <div class="ml-auto flex items-center text-sm">
        <span
          class="font-medium text-neutral-800 dark:text-neutral-300"
          v-text="formatMoney(collection.state.meta.summary?.value)"
        />
        <span
          v-show="
            !isFilteringWonOrLostDeals &&
            collection.state.meta.summary?.weighted_value > 0 &&
            collection.state.meta.summary?.weighted_value !==
              collection.state.meta.summary?.value
          "
          class="mx-1 text-neutral-900 dark:text-neutral-300"
          v-text="'-'"
        />
        <span
          v-show="
            !isFilteringWonOrLostDeals &&
            collection.state.meta.summary?.weighted_value > 0 &&
            collection.state.meta.summary?.weighted_value !==
              collection.state.meta.summary?.value
          "
          class="inline-flex items-center font-medium text-neutral-800 dark:text-neutral-300"
        >
          <Icon icon="Scale" class="mr-1 h-4 w-4" />
          <span>
            {{ formatMoney(collection.state.meta.summary?.weighted_value) }}
          </span>
        </span>
        <span class="mx-1 text-neutral-900 dark:text-neutral-300">-</span>
        <span
          class="font-medium text-neutral-800 dark:text-neutral-300"
          v-t="{
            path: 'deals::deal.count.all',
            args: { count: collection.state.meta.summary?.count },
          }"
        />
      </div>
    </template>
    <template #name="{ row, formatted }">
      <div class="flex w-full justify-between">
        <router-link
          class="link grow"
          :to="{ name: 'view-deal', params: { id: row.id } }"
        >
          {{ formatted }}
        </router-link>
        <div class="ml-2 mt-px">
          <IMinimalDropdown>
            <IDropdownItem
              icon="Clock"
              @click="activityBeingCreatedRow = row"
              :text="$t('activities::activity.create')"
            />
            <IDropdownItem
              @click="preview(row.id)"
              icon="Bars3CenterLeft"
              :text="$t('core::app.preview')"
            />
            <IDropdownItem
              v-if="row.authorizations.delete"
              @click="destroy(row.id)"
              :text="$t('core::app.delete')"
              icon="Trash"
            />
          </IMinimalDropdown>
        </div>
      </div>
    </template>
    <template #status="{ row, column, formatted }">
      <IBadge
        :variant="column.badgeVariants[row.displayAs.status]"
        :rounded="false"
        class="rounded"
      >
        {{ $t('deals::deal.status.' + formatted) }}
      </IBadge>
    </template>
  </ResourceTable>

  <CreateActivityModal
    :visible="activityBeingCreatedRow !== null"
    :deals="[activityBeingCreatedRow]"
    :with-extended-submit-buttons="true"
    :go-to-list="false"
    @created="
      ({ isRegularAction }) => (
        isRegularAction ? (activityBeingCreatedRow = null) : '',
        reloadTable(tableId)
      )
    "
    @modal-hidden="activityBeingCreatedRow = null"
  />
  <PreviewModal />
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref, computed } from 'vue'
import ResourceTable from '~/Core/resources/js/components/Table'
import { useStore } from 'vuex'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { useI18n } from 'vue-i18n'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'
import { useQueryBuilder } from '~/Core/resources/js/components/QueryBuilder/useQueryBuilder'

const emit = defineEmits(['deleted', 'loaded'])

const props = defineProps({
  tableId: { required: true, type: String },
  initialize: { default: true, type: Boolean },
})

const { t } = useI18n()
const store = useStore()

const { formatMoney } = useAccounting()
const { reloadTable } = useTable()

const resourceName = Innoclapps.config('resources.deals.name')
const activityBeingCreatedRow = ref(null)

const { findRule } = useQueryBuilder(props.tableId)

const isFilteringWonOrLostDeals = computed(() => {
  let rule = findRule('status')

  if (!rule) {
    return false
  }

  return rule.query.value === 'won' || rule.query.value === 'lost'
})

function rowClass(row) {
  return {
    'has-warning': true,
    'warning-confirmed': row.falls_behind_expected_close_date === true,
  }
}

function preview(id) {
  store.commit('recordPreview/SET_PREVIEW_RESOURCE', {
    resourceName: 'deals',
    resourceId: id,
  })
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/deals/${id}`)

  emit('deleted', id)

  reloadTable(props.tableId)

  Innoclapps.success(t('core::resource.deleted'))
}
</script>
