<template>
  <div class="mb-5 flex items-center justify-between">
    <h3
      class="whitespace-nowrap text-lg/6 font-medium text-neutral-800 dark:text-white"
      v-t="'users::user.users'"
    />
    <div class="space-x-3">
      <IButton
        variant="secondary"
        size="sm"
        icon="Mail"
        :disabled="!componentReady"
        :to="{ name: 'invite-user' }"
        :text="$t('users::user.invite')"
      />
      <IButton
        variant="primary"
        size="sm"
        icon="Plus"
        :disabled="!componentReady"
        :to="{ name: 'create-user' }"
        :text="$t('users::user.create')"
      />
    </div>
  </div>

  <ResourceTable
    ref="table"
    resource-name="users"
    :table-id="tableId"
    @loaded="componentReady = true"
    :with-customize-button="true"
  >
    <template #name="{ row, formatted }">
      <router-link
        class="link"
        :to="{ name: 'edit-user', params: { id: row.id } }"
      >
        <IAvatar
          size="xs"
          :src="row.avatar_url"
          :title="row.name"
          class="mr-1"
        />
        {{ formatted }}
      </router-link>
    </template>
  </ResourceTable>
  <!-- Create, Edit -->
  <router-view
    name="createEdit"
    @created="reloadTable(tableId)"
    @updated="reloadTable(tableId)"
    @hidden="$router.push({ name: 'users-index' })"
  />
  <router-view name="invite" />
</template>
<script setup>
import { ref, onUnmounted } from 'vue'
import ResourceTable from '~/Core/resources/js/components/Table'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useStore } from 'vuex'
import { useTable } from '~/Core/resources/js/components/Table/useTable'
import { useGlobalEventListener } from '~/Core/resources/js/composables/useGlobalEventListener'

const store = useStore()

const { resetStoreState } = useApp()
const { reloadTable } = useTable()

const tableId = 'users'

const componentReady = ref(false)

function actionExecutedHandler(action) {
  if (action.destroyable) {
    action.ids.forEach(id => store.commit('users/REMOVE', id))
  }
}

useGlobalEventListener('action-executed', actionExecutedHandler)

onUnmounted(() => {
  /**
   * We need to reset the state in case changes are performed
   * because of the local cached data for the users
   */
  resetStoreState()
})
</script>
