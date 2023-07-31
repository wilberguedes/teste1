<template>
  <form @submit.prevent="submit" class="space-y-6" method="POST">
    <IFormGroup :label="$t('auth.login')" label-for="email">
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
        ref="passwordRef"
        name="password"
        required
        autocomplete="current-password"
      />
      <IFormError v-text="form.getError('password')" />
    </IFormGroup>

    <IFormGroup v-if="reCaptcha.validate">
      <VueRecaptcha
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
        ref="reCaptchaRef"
      />
      <IFormError v-text="form.getError('g-recaptcha-response')" />
    </IFormGroup>

    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <IFormCheckbox
          id="remember"
          name="remember"
          v-model="form.remember"
          :label="$t('auth.remember_me')"
        />
      </div>

      <div class="text-sm" v-if="!setting('disable_password_forgot')">
        <a
          :href="installationUrl + '/password/reset'"
          class="link"
          v-t="'auth.forgot_password'"
        />
      </div>
    </div>

    <div>
      <IButton
        type="submit"
        block
        @click="login"
        :disabled="submitButtonIsDisabled"
        :loading="requestInProgress"
        :text="$t('auth.login')"
      />
    </div>
  </form>
</template>
<script setup>
import { ref, computed } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useApp } from '~/Core/resources/js/composables/useApp'

const { setting } = useApp()

const reCaptcha = Innoclapps.config('reCaptcha') || {}
const installationUrl = Innoclapps.config('url')
const passwordRef = ref(null)
const reCaptchaRef = ref(null)
const requestInProgress = ref(false)

const { form } = useForm({
  email: null,
  password: null,
  remember: null,
  'g-recaptcha-response': null,
})

const submitButtonIsDisabled = computed(() => requestInProgress.value)

async function login() {
  requestInProgress.value = true
  passwordRef.value.blur()

  await Innoclapps.request().get(installationUrl + '/sanctum/csrf-cookie')

  form
    .post(installationUrl + '/login')
    .then(data => (window.location.href = data.redirect_path))
    .finally(() => reCaptchaRef.value && reCaptchaRef.value.reset())
    .catch(() => (requestInProgress.value = false))
}

function handleReCaptchaVerified(response) {
  form.fill('g-recaptcha-response', response)
}
</script>
