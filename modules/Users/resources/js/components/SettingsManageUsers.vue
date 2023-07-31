<template>
  <ITabGroup v-model="activeTabIndex">
    <ITabList>
      <ITab
        :title="$t('users::user.users')"
        icon="User"
        @activated="handleTabActivated"
      />
      <ITab
        :title="$t('core::role.roles')"
        icon="ShieldExclamation"
        @activated="handleTabActivated"
      />
      <ITab
        :title="$t('users::team.teams')"
        icon="UserGroup"
        @activated="handleTabActivated"
      />
    </ITabList>
    <ITabPanels>
      <!-- Make users tab lazy as ManageTeams is clearing the table settings in modifications -->
      <ITabPanel lazy>
        <UserIndex />
      </ITabPanel>
      <ITabPanel>
        <router-view name="roles" />
      </ITabPanel>
      <ITabPanel>
        <router-view name="teams" />
      </ITabPanel>
    </ITabPanels>
  </ITabGroup>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router'
import UserIndex from '../views/UserIndex.vue'

const route = useRoute()
const router = useRouter()
const activeTabIndex = ref(0)

function handleTabActivated() {
  if (
    activeTabIndex.value === 0 &&
    !['create-user', 'edit-user', 'invite-user'].includes(route.name)
  ) {
    router.push({ name: 'users-index' })
  } else if (
    activeTabIndex.value === 1 &&
    !['create-role', 'edit-role'].includes(route.name)
  ) {
    router.push({ name: 'role-index' })
  } else if (activeTabIndex.value === 2) {
    router.push({ name: 'manage-teams' })
  }
}

onBeforeRouteUpdate((to, from, next) => {
  // When clicking directly on the settings menu Users item
  if (to.name === 'users-index') {
    activeTabIndex.value = 0
  }

  next()
})

// Direct access support
if (['role-index', 'create-role', 'edit-role'].includes(route.name)) {
  activeTabIndex.value = 1
} else if (route.name === 'manage-teams') {
  activeTabIndex.value = 2
}
</script>
