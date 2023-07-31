<template>
  <ICard
    class="group"
    :class="{
      'border border-primary-400': editing,
      'border border-transparent transition duration-75 hover:border-primary-400 dark:border dark:border-neutral-700':
        !editing,
    }"
  >
    <template #header>
      <p
        class="font-semibold text-neutral-800 dark:text-neutral-200"
        v-t="'webforms::form.sections.submit.button'"
      />
    </template>
    <template #actions>
      <IButtonIcon
        icon="PencilAlt"
        class="block md:hidden md:group-hover:block"
        icon-class="h-4 w-4"
        v-show="!editing"
        @click="setEditingMode"
      />
    </template>
    <div
      v-show="!editing"
      class="text-sm text-neutral-900 dark:text-neutral-300"
    >
      <p v-text="section.text"></p>
    </div>
    <div v-if="editing">
      <IFormGroup
        :label="$t('webforms::form.sections.submit.button_text')"
        label-for="text"
      >
        <IFormInput id="text" v-model="text" />
      </IFormGroup>
      <IFormGroup v-show="reCaptchaConfigured">
        <IFormCheckbox
          v-model:checked="spamProtected"
          name="spam_protected"
          id="spam_protected"
          :label="$t('webforms::form.sections.submit.spam_protected')"
        />
      </IFormGroup>
      <IFormGroup>
        <IFormCheckbox
          v-model:checked="privacyPolicyAcceptIsRequired"
          name="require_privacy_policy"
          id="require_privacy_policy"
          :label="$t('webforms::form.sections.submit.require_privacy_policy')"
        />
      </IFormGroup>
      <IFormGroup
        v-show="privacyPolicyAcceptIsRequired"
        :label="$t('webforms::form.sections.submit.privacy_policy_url')"
        label-for="privacy_policy_url"
      >
        <IFormInput v-model="privacyPolicyUrl" id="privacy_policy_url" />
      </IFormGroup>
      <div class="space-x-2 text-right">
        <IButton
          size="sm"
          @click="editing = false"
          variant="white"
          :text="$t('core::app.cancel')"
        />
        <IButton
          size="sm"
          @click="requestSectionSave"
          variant="secondary"
          :text="$t('core::app.save')"
        />
      </div>
    </div>
  </ICard>
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref } from 'vue'

const emit = defineEmits(['update-section-requested'])

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
})

const editing = ref(false)
const text = ref(null)
const spamProtected = ref(false)
const privacyPolicyAcceptIsRequired = ref(false)
const privacyPolicyUrl = ref(Innoclapps.config('privacyPolicyUrl'))
const reCaptchaConfigured = Innoclapps.config('reCaptcha.configured')

function requestSectionSave() {
  emit('update-section-requested', {
    text: text.value,
    spamProtected: spamProtected.value,
    privacyPolicyAcceptIsRequired: privacyPolicyAcceptIsRequired.value,
    privacyPolicyUrl: privacyPolicyUrl.value,
  })

  editing.value = false
}

function setEditingMode() {
  text.value = props.section.text
  spamProtected.value = props.section.spamProtected
  privacyPolicyAcceptIsRequired.value =
    props.section.privacyPolicyAcceptIsRequired
  privacyPolicyUrl.value = props.section.privacyPolicyUrl

  editing.value = true
}
</script>
