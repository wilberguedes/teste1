<template>
  <form @submit.prevent="submit" class="space-y-6" method="POST">
    <IAlert variant="success" :show="successMessage !== null">
      {{ successMessage }}

      <p class="mt-1">
        <!-- We will redirect to login as the user is already logged in and will be redirected to the HOME route -->
        <a
          :href="installationUrl + '/login'"
          class="link mt-1 font-medium"
          v-text="$t('core::dashboard.dashboard')"
        />
      </p>
    </IAlert>

    <IFormGroup :label="$t('auth.email_address')" label-for="email">
      <IFormInput
        type="email"
        id="email"
        name="email"
        v-model="form.email"
        autocomplete="email"
        autofocus
        required
      />
      <IFormError v-text="form.getError('email')" />
    </IFormGroup>

    <IFormGroup :label="$t('auth.password')" label-for="password">
      <IFormInput
        type="password"
        v-model="form.password"
        id="password"
        name="password"
        required
        autocomplete="new-password"
      />
      <IFormError v-text="form.getError('password')" />
    </IFormGroup>

    <IFormGroup
      :label="$t('auth.confirm_password')"
      label-for="password-confirm"
    >
      <IFormInput
        type="password"
        v-model="form.password_confirmation"
        id="password-confirm"
        name="password_confirmation"
        required
        autocomplete="new-password"
      />
    </IFormGroup>

    <div>
      <IButton
        type="submit"
        block
        @click="resetPassword"
        :disabled="requestInProgress"
        :loading="requestInProgress"
        :text="$t('passwords.reset_password')"
      />
    </div>
  </form>
</template>
<script setup>
import { ref } from 'vue'
import { useForm } from '~/Core/resources/js/composables/useForm'

const props = defineProps({
  email: String,
  token: { required: true, type: String },
})

const installationUrl = Innoclapps.config('url')
const requestInProgress = ref(false)
const successMessage = ref(null)

const { form } = useForm({
  token: props.token,
  email: props.email,
  password: null,
  password_confirmation: null,
})

async function resetPassword() {
  requestInProgress.value = true

  await Innoclapps.request().get(installationUrl + '/sanctum/csrf-cookie')

  form
    .post(installationUrl + '/password/reset')
    .then(data => (successMessage.value = data.message))
    .finally(() => (requestInProgress.value = false))
}
</script>
