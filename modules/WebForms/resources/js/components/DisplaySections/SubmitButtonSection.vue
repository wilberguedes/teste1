<template>
  <div>
    <IFormGroup v-if="validateWithReCaptcha">
      <VueRecaptcha
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
      />
      <IFormError v-text="form.getError('g-recaptcha-response')" />
    </IFormGroup>
    <div class="mb-3 flex" v-if="section.privacyPolicyAcceptIsRequired">
      <IFormCheckbox
        v-model:checked="privacyPolicyAccepted"
        id="acceptPrivacyPolicy"
        @change="form.fill('_privacy-policy', $event)"
      />
      <div class="text-sm text-neutral-600 dark:text-neutral-300">
        <i18n-t
          scope="global"
          :keypath="'core::app.agree_to_privacy_policy'"
          class="inline-block w-full"
        >
          <template #privacyPolicyLink>
            <a
              :href="section.privacyPolicyUrl"
              class="link"
              v-t="'core::app.privacy_policy'"
            />
          </template>
        </i18n-t>
        <div>
          <IFormError v-text="form.getError('_privacy-policy')" />
        </div>
      </div>
    </div>
    <IButton
      type="submit"
      id="submitButton"
      size="lg"
      :disabled="form.busy"
      :loading="form.busy"
      block
      :text="section.text"
    />
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'
import propsDefinition from './props'

const props = defineProps(propsDefinition)

const reCaptcha = Innoclapps.config('reCaptcha') || {}
const privacyPolicyAccepted = ref(false)

const validateWithReCaptcha = computed(() => {
  if (!props.section.spamProtected) {
    return false
  }

  return reCaptcha.validate && reCaptcha.configured
})

function handleReCaptchaVerified(response) {
  props.form.fill('g-recaptcha-response', response)
}
</script>
<style>
#submitButton {
  color: var(--primary-contrast);
}
</style>
