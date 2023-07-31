<template>
  <ISlideover
    @hidden="$emit('hidden')"
    :ok-title="$t('core::app.save')"
    :ok-disabled="form.busy"
    :ok-loading="form.busy"
    @submit="update"
    form
    :title="$t('users::user.edit')"
    :visible="true"
    static-backdrop
  >
    <FieldsPlaceholder v-if="!componentReady" />

    <div v-show="componentReady">
      <UserFormFields :form="form" is-edit />
    </div>
  </ISlideover>
</template>
<script setup>
import { ref } from 'vue'
import UserFormFields from '../components/UserFormFields.vue'
import reduce from 'lodash/reduce'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['updated', 'hidden'])

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const store = useStore()

const { currentUser } = useApp()

const { form } = useForm()
const componentReady = ref(false)
const originalLocale = ref(null)

function update() {
  store
    .dispatch('users/update', {
      form: form,
      id: route.params.id,
    })
    .then(user => {
      Innoclapps.success(t('core::resource.updated'))

      if (
        user.locale !== originalLocale.value &&
        user.id == currentUser.value.id
      ) {
        window.location.reload(true)
      } else {
        emit('updated', user)
        router.back()
      }
    })
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(
          t('core::app.form_validation_failed_with_sections'),
          3000
        )
      }

      return Promise.reject(e)
    })
}

function prepareComponent() {
  store.dispatch('users/get', route.params.id).then(user => {
    originalLocale.value = user.locale

    form.set({
      name: user.name,
      email: user.email,
      roles: user.roles.map(role => role.name),

      password: null,
      password_confirmation: null,

      timezone: user.timezone,
      locale: user.locale,
      date_format: user.date_format,
      time_format: user.time_format,
      first_day_of_week: user.first_day_of_week,

      notifications_settings: reduce(
        user.notifications.settings,
        function (obj, val) {
          obj[val.key] = val.availability
          return obj
        },
        {}
      ),
      super_admin: user.super_admin,
      access_api: user.access_api,
    })

    componentReady.value = true
  })
}

prepareComponent()
</script>
