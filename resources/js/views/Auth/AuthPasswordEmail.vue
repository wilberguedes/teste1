<template>
  <form @submit.prevent="submit" class="space-y-6" method="POST">
    <IAlert variant="success" :show="successMessage !== null">
      {{ successMessage }}
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

    <IFormGroup v-if="reCaptcha.validate">
      <VueRecaptcha
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
        ref="reCaptchaRef"
      />
      <IFormError v-text="form.getError('g-recaptcha-response')" />
    </IFormGroup>

    <div>
      <IButton
        type="submit"
        block
        @click="sendPasswordResetEmail"
        :disabled="requestInProgress || !Boolean(form.email)"
        :loading="requestInProgress"
        :text="$t('passwords.send_password_reset_link')"
      />
    </div>
  </form>
</template>
<script setup>
import { ref } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'
import { useForm } from '~/Core/resources/js/composables/useForm'

const reCaptcha = Innoclapps.config('reCaptcha') || {}
const installationUrl = Innoclapps.config('url')
const reCaptchaRef = ref(null)
const requestInProgress = ref(false)
const successMessage = ref(null)

const { form } = useForm(
  {
    email: null,
    'g-recaptcha-response': null,
  },
  {
    resetOnSuccess: true,
  }
)

async function sendPasswordResetEmail() {
  requestInProgress.value = true
  successMessage.value = null

  await Innoclapps.request().get(installationUrl + '/sanctum/csrf-cookie')

  form
    .post(installationUrl + '/password/email')
    .then(data => {
      successMessage.value = data.message
    })
    .finally(() => {
      requestInProgress.value = false
      reCaptchaRef.value && reCaptchaRef.value.reset()
    })
}

function handleReCaptchaVerified(response) {
  form.fill('g-recaptcha-response', response)
}
</script>
