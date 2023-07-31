<template>
  <ICard
    :header="$t('users::team.teams')"
    no-body
    :overlay="teamsAreBeingFetched"
  >
    <template #actions>
      <IButton
        v-show="hasTeams"
        icon="Plus"
        :text="$t('users::team.add')"
        @click="teamIsBeingCreated = true"
        size="sm"
      />
    </template>
    <ul
      v-if="hasTeams"
      role="list"
      class="divide-y divide-neutral-200 dark:divide-neutral-700"
    >
      <li v-for="team in teamsByName" :key="team.id">
        <a
          href="#"
          @click.prevent="
            teamContentIsVisible[team.id] = !teamContentIsVisible[team.id]
          "
          class="group block hover:bg-neutral-50 dark:hover:bg-neutral-800/60"
        >
          <div class="flex items-center px-4 py-4 sm:px-6">
            <div
              class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
            >
              <div class="truncate">
                <div class="flex items-center text-sm">
                  <p
                    class="truncate font-medium text-primary-600 dark:text-primary-100"
                    v-text="team.name"
                  />
                  <a
                    href="#"
                    class="link ml-2 text-sm md:hidden md:group-hover:block"
                    @click.prevent.stop="prepareEdit(team)"
                    v-t="'core::app.edit'"
                  />
                  <a
                    href="#"
                    class="ml-2 text-sm text-danger-500 hover:text-danger-600 focus:outline-none md:hidden md:group-hover:block"
                    @click.prevent.stop="destroy(team.id)"
                    v-t="'core::app.delete'"
                  />
                </div>
                <div class="mt-2 flex">
                  <div
                    class="flex items-center text-sm text-neutral-500 dark:text-neutral-400"
                  >
                    <Icon
                      icon="Calendar"
                      class="mr-1.5 h-5 w-5 shrink-0 text-neutral-400 dark:text-neutral-300"
                    />
                    <p>
                      {{ $t('core::app.created_at') }}
                      {{ ' ' }}
                      <time :datetime="team.created_at">
                        {{ localizedDateTime(team.created_at) }}
                      </time>
                    </p>
                  </div>
                </div>
              </div>
              <div class="mt-4 shrink-0 sm:ml-5 sm:mt-0">
                <div class="flex -space-x-1 overflow-hidden">
                  <IAvatar
                    v-for="member in team.members"
                    :key="member.email"
                    v-i-tooltip="member.name"
                    :alt="member.name"
                    size="xs"
                    :src="member.avatar_url"
                    class="ring-2 ring-white dark:ring-neutral-900"
                  />
                </div>
              </div>
            </div>
            <div class="ml-5 shrink-0">
              <Icon icon="ChevronRight" class="h-5 w-5 text-neutral-400" />
            </div>
          </div>
        </a>
        <div v-show="teamContentIsVisible[team.id]" class="px-4 py-4 sm:px-6">
          <p
            class="mb-1 text-sm font-medium text-neutral-800 dark:text-neutral-200"
            v-t="'users::team.manager'"
          />

          <p
            v-text="team.manager.name"
            class="mb-3 text-sm text-neutral-700 dark:text-neutral-300"
          />
          <p
            class="my-2 text-sm font-medium text-neutral-800 dark:text-neutral-200"
            v-t="'users::team.members'"
          />
          <div
            v-for="member in team.members"
            :key="'info-' + member.email"
            class="mb-1 flex items-center space-x-1.5 last:mb-0"
          >
            <IAvatar :alt="member.name" size="xs" :src="member.avatar_url" />
            <p
              class="text-sm font-medium text-neutral-700 dark:text-neutral-300"
              v-text="member.name"
            />
          </div>

          <p
            v-show="team.description"
            class="mb-1 mt-3 text-sm font-medium text-neutral-800 dark:text-neutral-200"
            v-t="'users::team.description'"
          />
          <p
            v-text="team.description"
            class="text-sm text-neutral-700 dark:text-neutral-300"
          />
        </div>
      </li>
    </ul>
    <ICardBody v-else-if="!teamsAreBeingFetched">
      <IEmptyState
        @click="teamIsBeingCreated = true"
        :button-text="$t('users::team.add')"
        :title="$t('users::team.empty_state.title')"
        :description="$t('users::team.empty_state.description')"
      />
    </ICardBody>
  </ICard>
  <IModal
    :title="$t('users::team.create')"
    form
    @hidden="teamIsBeingCreated = false"
    @submit="create"
    @shown="() => $refs.nameInputCreateRef.focus()"
    :visible="teamIsBeingCreated"
    :ok-title="$t('core::app.create')"
    :ok-disabled="formCreate.busy"
  >
    <IFormGroup for="nameInputCreate" :label="$t('users::team.name')" required>
      <IFormInput
        id="nameInputCreate"
        ref="nameInputCreateRef"
        @keydown="formCreate.errors.clear('name')"
        v-model="formCreate.name"
      />
      <IFormError v-text="formCreate.getError('name')" />
    </IFormGroup>

    <IFormGroup label-for="user_id" :label="$t('users::team.manager')" required>
      <ICustomSelect
        :options="users"
        :clearable="false"
        label="name"
        :reduce="user => user.id"
        input-id="user_id"
        v-model="formCreate.user_id"
      />
      <IFormError v-text="formCreate.getError('user_id')" />
    </IFormGroup>

    <IFormGroup for="membersInputCreate" :label="$t('users::team.members')">
      <ICustomSelect
        :options="users"
        id="membersInputCreate"
        label="name"
        @update:modelValue="formCreate.errors.clear('members')"
        multiple
        v-model="formCreate.members"
        :reduce="option => option.id"
      />
    </IFormGroup>

    <IFormGroup
      for="descriptionInputCreate"
      :label="$t('users::team.description')"
    >
      <IFormTextarea
        @keydown="formCreate.errors.clear('description')"
        id="descriptionInputCreate"
        v-model="formCreate.description"
      />
      <IFormError v-text="formCreate.getError('description')" />
    </IFormGroup>
  </IModal>
  <IModal
    form
    @hidden=";(teamIsBeingEdited = null), formUpdate.reset()"
    @submit="update"
    :visible="teamIsBeingEdited !== null"
    :ok-title="$t('core::app.save')"
    :ok-disabled="formUpdate.busy"
    :title="$t('users::team.edit')"
  >
    <IFormGroup for="nameInputEdit" :label="$t('users::team.name')" required>
      <IFormInput
        id="nameInputEdit"
        ref="nameInputEdit"
        @keydown="formUpdate.errors.clear('name')"
        v-model="formUpdate.name"
      />
      <IFormError v-text="formUpdate.getError('name')" />
    </IFormGroup>

    <IFormGroup label-for="user_id" :label="$t('users::team.manager')" required>
      <ICustomSelect
        :options="users"
        :clearable="false"
        label="name"
        :reduce="user => user.id"
        input-id="user_id"
        v-model="formUpdate.user_id"
      />
      <IFormError v-text="formUpdate.getError('user_id')" />
    </IFormGroup>

    <IFormGroup for="membersInputEdit" :label="$t('users::team.members')">
      <ICustomSelect
        :options="users"
        id="membersInputEdit"
        label="name"
        @update:modelValue="formUpdate.errors.clear('members')"
        multiple
        v-model="formUpdate.members"
        :reduce="option => option.id"
      />
    </IFormGroup>

    <IFormGroup
      for="descriptionInputEdit"
      :label="$t('users::team.description')"
    >
      <IFormTextarea
        @keydown="formUpdate.errors.clear('description')"
        id="descriptionInputEdit"
        v-model="formUpdate.description"
      />
      <IFormError v-text="formUpdate.getError('description')" />
    </IFormGroup>
  </IModal>
</template>
<script setup>
import { ref, computed, onBeforeMount } from 'vue'
import { useStore } from 'vuex'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useTeams } from '../composables/useTeams'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useApp } from '~/Core/resources/js/composables/useApp'

const store = useStore()

const { users } = useApp()

const { localizedDateTime } = useDates()

const { teamsByName, teamsAreBeingFetched, addTeam, deleteTeam, setTeam } =
  useTeams()

const teamIsBeingCreated = ref(false)
const teamIsBeingEdited = ref(null)
const modificationsPerformed = ref(false)
const teamContentIsVisible = ref({})

const { form: formCreate } = useForm(
  {
    name: null,
    description: null,
    user_id: null,
    members: [],
  },
  { resetOnSuccess: true }
)

const { form: formUpdate } = useForm(
  {
    name: null,
    description: null,
    user_id: null,
    members: [],
  },
  { resetOnSuccess: true }
)

const hasTeams = computed(() => teamsByName.value.length > 0)

function create() {
  formCreate.post('/teams').then(team => {
    addTeam(team)
    teamIsBeingCreated.value = false
    modificationsPerformed.value = true
  })
}

function update() {
  formUpdate.put(`/teams/${teamIsBeingEdited.value}`).then(team => {
    setTeam(team.id, team)
    teamIsBeingEdited.value = null
    modificationsPerformed.value = true
  })
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await deleteTeam(id)

  modificationsPerformed.value = true
}

function prepareEdit(team) {
  teamIsBeingEdited.value = team.id
  formUpdate.fill('name', team.name)
  formUpdate.fill('user_id', team.user_id)
  formUpdate.fill(
    'members',
    team.members.map(member => member.id)
  )
  formUpdate.fill('description', team.description)
}

onBeforeMount(() => {
  if (modificationsPerformed.value) {
    store.commit('table/RESET_SETTINGS')
  }
})
</script>
