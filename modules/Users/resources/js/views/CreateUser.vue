<template>
  <ISlideover
    @hidden="$emit('hidden')"
    @shown="() => $refs.formRef.$refs.name.focus()"
    :ok-disabled="form.busy"
    :ok-loading="form.busy"
    :ok-title="$t('core::app.create')"
    @keydown="form.onKeydown($event)"
    @submit="create"
    form
    :visible="true"
    static-backdrop
    :title="$t('users::user.create')"
  >
    <UserFormFields ref="formRef" :form="form" />
  </ISlideover>
</template>
<script setup>
import UserFormFields from '../components/UserFormFields.vue'
import reduce from 'lodash/reduce'
import { useStore } from 'vuex'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['created', 'hidden'])

const { t } = useI18n()
const router = useRouter()
const store = useStore()

const { form } = useForm({
  name: null,
  email: null,
  roles: [],

  password: null,
  password_confirmation: null,

  timezone: moment.tz.guess(),
  locale: 'en',
  date_format: store.state.settings.date_format,
  time_format: store.state.settings.time_format,
  first_day_of_week: store.state.settings.first_day_of_week,

  notifications_settings: reduce(
    Innoclapps.config('notifications_settings'),
    function (obj, val) {
      let channels = {}
      val.channels.forEach(channel => (channels[channel] = true))
      obj[val.key] = channels
      return obj
    },
    {}
  ),

  super_admin: false,
  access_api: false,

  avatar: null,
})

async function create() {
  const user = await store.dispatch('users/store', form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(
        t('core::app.form_validation_failed_with_sections'),
        3000
      )
    }
    return Promise.reject(e)
  })

  emit('created', user)

  Innoclapps.success(t('core::resource.created'))

  router.back()
}
</script>
