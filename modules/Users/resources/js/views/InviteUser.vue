<template>
  <IModal
    size="sm"
    @hidden="$router.back"
    @shown="handleModalShown"
    :ok-disabled="form.busy"
    form
    @submit="invite"
    @keydown="form.onKeydown($event)"
    :ok-title="$t('users::user.send_invitation')"
    static-backdrop
    :visible="true"
    :title="$t('users::user.invite')"
  >
    <p class="text-sm text-neutral-700 dark:text-white">
      {{
        $t('users::user.invitation_expires_after_info', {
          total: invitationExpiresAfter,
        })
      }}
    </p>

    <div
      class="mb-4 border-b border-neutral-200 pt-4 dark:border-neutral-700"
    />

    <div class="mb-3 flex">
      <IFormLabel
        class="grow text-neutral-900 dark:text-neutral-100"
        :label="$t('users::user.email')"
        for="email0"
        required
      />
      <IButtonIcon
        v-i-tooltip="$t('core::app.add_another')"
        icon="Plus"
        @click="addEmail"
      />
    </div>

    <div class="relative mb-3" v-for="(email, index) in form.emails">
      <IFormInput
        ref="emailsRef"
        type="email"
        v-model="form.emails[index]"
        :id="'email' + index"
        :placeholder="$t('users::user.email')"
        @keydown.enter.prevent.stop="addEmail"
        @keydown="form.errors.clear('emails.' + index)"
      />

      <IButtonIcon
        v-show="index > 0"
        icon="X"
        @click="removeEmail(index)"
        class="absolute right-3 top-2.5"
      />
      <IFormError v-text="form.getError('emails.' + index)" />
    </div>

    <IFormGroup :label="$t('core::role.roles')" label-for="roles" class="mt-3">
      <ICustomSelect
        input-id="roles"
        :placeholder="$t('users::user.roles')"
        v-model="form.roles"
        :options="rolesNames"
        label="name"
        :multiple="true"
      />
    </IFormGroup>
    <IFormGroup :label="$t('users::team.teams')" label-for="teams">
      <ICustomSelect
        input-id="teams"
        :placeholder="$t('users::team.teams')"
        v-model="form.teams"
        :options="teams"
        label="name"
        :reduce="team => team.id"
        :multiple="true"
      />
    </IFormGroup>
    <div
      :class="[
        'flex items-center rounded-md border-2 px-5 py-4 shadow-sm',
        form.super_admin
          ? 'border-primary-400'
          : 'border-neutral-200 dark:border-neutral-400',
      ]"
    >
      <div class="grow">
        <p
          class="text-sm text-neutral-900 dark:text-neutral-200"
          v-t="'users::user.super_admin'"
        />
        <small
          class="text-neutral-700 dark:text-neutral-300"
          v-t="'users::user.as_super_admin_info'"
        />
      </div>
      <div class="ml-3">
        <IFormToggle
          v-model="form.super_admin"
          @change="handleSuperAdminChange"
        />
      </div>
    </div>
    <div
      :class="[
        'mt-3 flex items-center rounded-md border-2 px-5 py-4 shadow-sm',
        form.access_api
          ? 'border-primary-400'
          : 'border-neutral-200 dark:border-neutral-400',
      ]"
    >
      <div class="grow">
        <p
          class="text-sm text-neutral-900 dark:text-neutral-200"
          v-t="'users::user.enable_api'"
        />
        <small
          class="text-neutral-700 dark:text-neutral-300"
          v-t="'users::user.allow_api_info'"
        />
      </div>
      <div class="ml-3">
        <IFormToggle v-model="form.access_api" :disabled="form.super_admin" />
      </div>
    </div>
  </IModal>
</template>
<script setup>
import { ref, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useTeams } from '../composables/useTeams'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useRoles } from '~/Core/resources/js/views/Roles/useRoles'

const invitationExpiresAfter = Innoclapps.config('invitation.expires_after')

const { t } = useI18n()
const router = useRouter()

const emailsRef = ref([])

const { rolesNames } = useRoles()
const { teamsByName: teams } = useTeams()

const { form } = useForm({
  emails: [''],
  access_api: false,
  super_admin: false,
  roles: [],
})

const totalEmails = computed(() => form.emails.length)

function addEmail() {
  form.emails.push('')

  nextTick(() => {
    emailsRef.value[totalEmails.value - 1].focus()
  })
}

function removeEmail(index) {
  form.emails.splice(index, 1)

  nextTick(() => {
    if (form.emails[totalEmails.value - 1] === '') {
      emailsRef.value[totalEmails.value - 1].focus()
    }
  })
}

function handleModalShown() {
  nextTick(() => {
    emailsRef.value[0].focus()
  })
}

function handleSuperAdminChange(val) {
  if (val) {
    form.access_api = true
  }
}

function invite() {
  form.post('/users/invite').then(() => {
    Innoclapps.success(t('users::user.invited'))
    router.back()
  })
}
</script>
