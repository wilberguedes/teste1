<template>
  <ILayout>
    <div class="m-auto max-w-7xl">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
          <h3
            class="text-lg/6 font-medium text-neutral-900 dark:text-white"
            v-t="'core::app.avatar'"
          />
          <p
            class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
            v-t="'users::profile.avatar_info'"
          />
        </div>
        <div class="mt-5 md:col-span-2 md:mt-0">
          <ICard>
            <CropsAndUploadsImage
              name="avatar"
              :upload-url="`${$store.state.apiURL}/users/${currentUser.id}/avatar`"
              :image="currentUser.uploaded_avatar_url"
              :cropper-options="{ aspectRatio: 1 / 1 }"
              :choose-text="
                currentUser.uploaded_avatar_url
                  ? $t('core::app.change')
                  : $t('core::app.upload_avatar')
              "
              @cleared="clearAvatar"
              @success="handleAvatarUploaded"
            />
          </ICard>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-600" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="md:col-span-1">
            <h3
              class="text-lg/6 font-medium text-neutral-900 dark:text-white"
              v-t="'users::profile.profile'"
            />
            <p
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
              v-t="'users::profile.profile_info'"
            />
          </div>
          <div class="mt-5 md:col-span-2 md:mt-0">
            <form @submit.prevent="update" @keydown="form.onKeydown($event)">
              <ICard>
                <IFormGroup :label="$t('users::user.name')" label-for="name">
                  <IFormInput v-model="form.name" id="name" name="name" />
                  <IFormError v-text="form.getError('name')" />
                </IFormGroup>
                <IFormGroup :label="$t('users::user.email')" label-for="email">
                  <IFormInput
                    v-model="form.email"
                    id="email"
                    name="email"
                    type="email"
                  >
                  </IFormInput>
                  <IFormError v-text="form.getError('email')" />
                </IFormGroup>
                <IFormGroup
                  :label="$t('mailclient::mail.signature')"
                  label-for="mail_signature"
                  :description="$t('mailclient::mail.signature_info')"
                >
                  <Editor v-model="form.mail_signature" />
                  <IFormError v-text="form.getError('mail_signature')" />
                </IFormGroup>
                <template #footer>
                  <div class="text-right">
                    <IButton
                      @click="update"
                      :disabled="form.busy"
                      :text="$t('users::profile.update')"
                    />
                  </div>
                </template>
              </ICard>
            </form>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-600" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="md:col-span-1">
            <h3
              class="text-lg/6 font-medium text-neutral-900 dark:text-white"
              v-t="'users::user.localization'"
            />
            <p
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
              v-t="'users::profile.localization_info'"
            />
          </div>
          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard>
              <LocalizationFields
                :form="form"
                @update:firstDayOfWeek="form.first_day_of_week = $event"
                @update:timeFormat="form.time_format = $event"
                @update:dateFormat="form.date_format = $event"
                @update:locale="form.locale = $event"
                @update:timezone="form.timezone = $event"
              />
              <template #footer>
                <div class="text-right">
                  <IButton
                    @click="update"
                    :disabled="form.busy"
                    :text="$t('core::app.save')"
                  />
                </div>
              </template>
            </ICard>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-600" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="md:col-span-1">
            <h3
              class="text-lg/6 font-medium text-neutral-900 dark:text-white"
              v-t="'core::notifications.notifications'"
            />
            <p
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
              v-t="'users::profile.notifications_info'"
            />
          </div>
          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard no-body>
              <NotificationSettings :form="form" class="-mt-px" />
              <template #footer>
                <div class="text-right">
                  <IButton
                    @click="update"
                    :disabled="form.busy"
                    :text="$t('core::app.save')"
                  />
                </div>
              </template>
            </ICard>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-600" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="md:col-span-1">
            <h3
              class="text-lg/6 font-medium text-neutral-900 dark:text-white"
              v-t="'auth.password'"
            />
            <p
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
              v-t="'users::profile.password_info'"
            />
          </div>
          <div class="mt-5 md:col-span-2 md:mt-0">
            <form
              @submit.prevent="updatePassword"
              @keydown="formPassword.onKeydown($event)"
            >
              <ICard>
                <IFormGroup
                  :label="$t('auth.current_password')"
                  label-for="old_password"
                >
                  <IFormInput
                    v-model="formPassword.old_password"
                    id="old_password"
                    name="old_password"
                    type="password"
                    autocomplete="current-password"
                  >
                  </IFormInput>
                  <IFormError v-text="formPassword.getError('old_password')" />
                </IFormGroup>
                <IFormGroup>
                  <template #label>
                    <div class="flex">
                      <IFormLabel
                        class="mb-1 grow"
                        for="password"
                        :label="$t('auth.new_password')"
                      />

                      <a
                        class="link text-sm"
                        href="#"
                        v-i-toggle="'generate-password'"
                        v-t="'core::app.password_generator.heading'"
                      />
                    </div>
                  </template>

                  <IFormInput
                    v-model="formPassword.password"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                  >
                  </IFormInput>
                  <IFormError v-text="formPassword.getError('password')" />
                </IFormGroup>
                <IFormGroup
                  :label="$t('auth.confirm_password')"
                  label-for="password_confirmation"
                >
                  <IFormInput
                    v-model="formPassword.password_confirmation"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                  >
                  </IFormInput>
                  <IFormError
                    v-text="formPassword.getError('password_confirmation')"
                  />
                </IFormGroup>
                <div id="generate-password" style="display: none">
                  <PasswordGenerator />
                </div>
                <template #footer>
                  <div class="text-right">
                    <IButton
                      type="submit"
                      :disabled="formPassword.busy"
                      :text="$t('auth.change_password')"
                    />
                  </div>
                </template>
              </ICard>
            </form>
          </div>
        </div>
      </div>

      <div
        v-if="managedTeams.length > 0"
        class="hidden sm:block"
        aria-hidden="true"
      >
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-600" />
        </div>
      </div>

      <div v-if="managedTeams.length > 0" class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="md:col-span-1">
            <h3 class="text-lg/6 font-medium text-neutral-900 dark:text-white">
              {{ $t('users::team.your_teams', managedTeams.length) }}
            </h3>
            <p
              class="mt-1 text-sm text-neutral-600 dark:text-neutral-300"
              v-t="'users::team.managing_teams'"
            />
          </div>
          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard>
              <ul
                role="list"
                class="space-y-4 divide-y divide-neutral-200 dark:divide-neutral-700"
              >
                <li
                  v-for="team in managedTeams"
                  :key="team.id"
                  class="pt-4 first:pt-0"
                >
                  <p
                    class="truncate text-sm font-medium text-neutral-800 dark:text-neutral-100"
                    v-text="team.name"
                  />
                  <p
                    class="mb-2 mt-1 text-sm font-medium text-neutral-600 dark:text-neutral-300"
                    v-t="'users::team.members'"
                  />
                  <div
                    v-for="member in team.members"
                    :key="'info-' + member.email"
                    class="mb-1 flex items-center space-x-1.5 last:mb-0"
                  >
                    <IAvatar
                      :alt="member.name"
                      size="xs"
                      :src="member.avatar_url"
                    />
                    <p
                      class="text-sm font-medium text-neutral-700 dark:text-neutral-300"
                      v-text="member.name"
                    />
                  </div>
                </li>
              </ul>
            </ICard>
          </div>
        </div>
      </div>
    </div>
  </ILayout>
</template>
<script setup>
import { unref, computed } from 'vue'
import CropsAndUploadsImage from '~/Core/resources/js/components/CropsAndUploadsImage.vue'
import LocalizationFields from '~/Core/resources/js/views/Settings/LocalizationFields.vue'
import PasswordGenerator from '~/Core/resources/js/components/PasswordGenerator.vue'
import NotificationSettings from '../components/UserNotificationSettings.vue'
import reduce from 'lodash/reduce'
import cloneDeep from 'lodash/cloneDeep'
import { useStore } from 'vuex'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useTeams } from '../composables/useTeams'

const { t } = useI18n()
const store = useStore()
const route = useRoute()
const { currentUser, resetStoreState } = useApp()

const user = unref(currentUser)

const { form } = useForm()
const { form: formPassword } = useForm({}, { resetOnSuccess: true })

const { teams } = useTeams()

const managedTeams = computed(() =>
  teams.value.filter(team => team.manager.id === currentUser.value.id)
)

let originalLocale = null

function handleAvatarUploaded(updatedUser) {
  store.commit('users/UPDATE', {
    id: updatedUser.id,
    item: updatedUser,
  })

  user.avatar = updatedUser.avatar
  user.avatar_url = updatedUser.avatar_url
  // Update form avatar with new value
  // to prevent using the old value if the user saves the profile
  form.avatar = user.avatar
}

function clearAvatar() {
  if (!user.avatar) {
    return
  }

  store
    .dispatch('users/removeAvatar', user.id)
    .then(data => (form.avatar = data.avatar))
}

function update() {
  store.dispatch('users/updateProfile', form).then(() => {
    Innoclapps.success(t('users::profile.updated'))

    if (originalLocale !== form.locale) {
      window.location.reload()
    } else {
      resetStoreState()
    }
  })
}

function updatePassword() {
  formPassword.put('/profile/password').then(() => {
    Innoclapps.success(t('users::profile.password_updated'))
  })
}

function prepareComponent() {
  originalLocale = user.locale

  form.set({
    name: user.name,
    email: user.email,
    mail_signature: user.mail_signature,
    date_format: user.date_format,
    time_format: user.time_format,
    first_day_of_week: user.first_day_of_week,
    timezone: user.timezone,
    locale: user.locale,
    notifications_settings: reduce(
      cloneDeep(user.notifications.settings),
      (obj, val, key) => {
        obj[val.key] = val.availability
        return obj
      },
      {}
    ),
  })

  formPassword.set({
    old_password: null,
    password: null,
    password_confirmation: null,
  })
}

prepareComponent()
</script>
