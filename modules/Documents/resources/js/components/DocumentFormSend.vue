<template>
  <div class="mx-auto max-w-3xl" v-show="visible">
    <IAlert
      v-if="Boolean(form.originalData.send_at)"
      variant="info"
      class="mb-5"
    >
      {{
        $t('documents::document.send.is_scheduled', {
          date: localizedDateTime(form.originalData.send_at),
        })
      }}
    </IAlert>
    <div v-if="form.requires_signature" class="mb-6">
      <h3
        :class="[
          'mb-3 text-base font-medium text-neutral-800 dark:text-neutral-100',
          {
            hidden:
              filledSigners.length === 0 && document.status === 'accepted',
          },
        ]"
        v-t="'documents::document.send.send_to_signers'"
      />

      <p
        v-show="filledSigners.length === 0 && document.status !== 'accepted'"
        class="-mt-3 text-sm text-neutral-500 dark:text-neutral-300"
        v-t="'documents::document.send.send_to_signers_empty'"
      />

      <IFormCheckbox
        v-for="signer in filledSigners"
        :key="signer.email"
        v-model:checked="signer.send_email"
      >
        {{ signer.name + ' (' + signer.email + ')' }}

        <span
          v-if="signer.sent_at"
          class="text-neutral-500 dark:text-neutral-300"
        >
          -
          {{
            $t('documents::document.sent_at', {
              date: localizedDateTime(signer.sent_at),
            })
          }}
        </span>
      </IFormCheckbox>
    </div>

    <h3
      class="mb-3 text-base font-medium text-neutral-800 dark:text-neutral-100"
      v-t="'documents::document.recipients.recipients'"
    />

    <div class="table-responsive">
      <div
        class="overflow-auto border border-neutral-200 dark:border-neutral-800 sm:rounded-md"
      >
        <table
          class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700"
        >
          <thead class="bg-neutral-50 dark:bg-neutral-800">
            <tr>
              <th
                class="bg-neutral-50 p-2 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
              />
              <th
                class="bg-neutral-50 p-2 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'documents::document.recipients.recipient_name'"
              />
              <th
                class="bg-neutral-50 p-2 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'documents::document.recipients.recipient_email'"
              />
              <th
                class="bg-neutral-50 p-2 text-center text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                v-t="'documents::document.recipients.is_sent'"
              />
              <th></th>
            </tr>
          </thead>
          <tbody
            class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800"
          >
            <tr v-if="form.recipients.length === 0">
              <td
                colspan="5"
                class="bg-white p-3 align-middle text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                v-t="'documents::document.recipients.no_recipients'"
              />
            </tr>

            <tr v-for="(recipient, index) in form.recipients" :key="index">
              <td
                class="border-r border-neutral-200 bg-white p-2 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <span
                  class="ml-1 inline-flex min-w-full items-center justify-center"
                >
                  <IFormCheckbox v-model:checked="recipient.send_email" />
                </span>
              </td>
              <td
                class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <IFormInput
                  ref="recipientNameInputRef"
                  v-model="recipient.name"
                  @input="form.errors.clear('recipients.' + index + '.name')"
                  :placeholder="
                    $t('documents::document.recipients.enter_full_name')
                  "
                />
                <IFormError
                  v-text="form.getError('recipients.' + index + '.name')"
                />
              </td>
              <td
                class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <IFormInput
                  v-model="recipient.email"
                  @keyup.enter="insertEmptyRecipient"
                  @input="form.errors.clear('recipients.' + index + '.email')"
                  type="email"
                  :placeholder="
                    $t('documents::document.recipients.enter_email')
                  "
                />
                <IFormError
                  v-text="form.getError('recipients.' + index + '.email')"
                />
              </td>
              <td
                class="bg-white p-2 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <span class="inline-flex min-w-full justify-center">
                  <span
                    v-i-tooltip="
                      recipient.sent_at
                        ? localizedDateTime(recipient.sent_at)
                        : null
                    "
                    :class="[
                      'inline-block h-4 w-4 rounded-full',
                      recipient.sent_at ? 'bg-success-400' : 'bg-danger-400',
                    ]"
                  />
                </span>
              </td>

              <td
                class="bg-white p-2 text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
              >
                <IButtonIcon icon="X" @click="removeRecipient(index)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <a
      v-show="!emptyRecipientsExists"
      class="link mt-3 inline-block text-sm font-medium"
      href="#"
      @click.prevent="insertEmptyRecipient"
    >
      + {{ $t('documents::document.recipients.add') }}
    </a>

    <h3
      class="mb-3 mt-6 text-base font-medium text-neutral-800 dark:text-neutral-100"
      v-t="'documents::document.send.send'"
    />

    <p
      v-show="!selectedBrand"
      class="mt-3 text-sm text-neutral-500"
      v-t="'documents::document.send.select_brand'"
    />

    <div v-if="selectedBrand">
      <IFormGroup
        :label="$t('documents::document.send.send_from_account')"
        label-for="send_mail_account_id"
      >
        <ICustomSelect
          v-if="mailAccounts.length"
          :modelValue="mailAccount"
          input-id="send_mail_account_id"
          @update:modelValue="form.send_mail_account_id = $event.id"
          @option:selected="setActiveMailAccount(mailAccounts)"
          :clearable="false"
          :options="mailAccounts"
          label="email"
        />
        <IFormText v-else class="mt-2 inline-flex items-center">
          <Icon
            icon="ExclamationTriangle"
            class="mr-1 h-5 w-5 text-warning-500"
          />
          {{ $t('documents::document.send.connect_an_email_account') }}
        </IFormText>
      </IFormGroup>
      <IFormGroup
        :label="$t('documents::document.send.send_subject')"
        label-for="send_mail_subject"
        class="mt-4"
      >
        <IFormInput v-model="form.send_mail_subject" id="send_mail_subject" />
      </IFormGroup>
      <IFormGroup
        :label="$t('documents::document.send.send_body')"
        label-for="send_mail_body"
      >
        <Editor
          v-model="form.send_mail_body"
          id="send_mail_body"
          :with-image="false"
        />
      </IFormGroup>

      <div class="mb-4">
        <div class="inline-block">
          <IFormCheckbox
            v-model:checked="scheduleSend"
            :disabled="!document.id"
            v-i-tooltip="
              !document.id
                ? $t('documents::document.send.save_to_schedule')
                : null
            "
            @update:checked="!$event ? (form.send_at = null) : ''"
            :label="$t('documents::document.send.send_later')"
          />
        </div>

        <DatePicker
          v-if="scheduleSend && document.id"
          class="mt-3"
          :min-date="appDate()"
          mode="dateTime"
          :placeholder="$t('documents::document.send.select_schedule_date')"
          v-model="form.send_at"
          :required="true"
        />
      </div>
      <span
        class="inline-block"
        v-i-tooltip="
          document.authorizations && !document.authorizations.update
            ? $t('core::app.action_not_authorized')
            : ''
        "
      >
        <IButton
          v-show="!scheduleSend"
          @click="$emit('send-requested')"
          :loading="sending"
          icon="Mail"
          :text="$t('core::app.send')"
          :disabled="
            sending ||
            !isEligibleForSending ||
            (document.authorizations && !document.authorizations.update)
          "
        />
        <IButton
          v-show="scheduleSend"
          @click="$emit('save-requested')"
          :text="$t('documents::document.send.schedule')"
          :disabled="
            form.busy ||
            !isEligibleForSending ||
            !form.send_at ||
            (document.authorizations && !document.authorizations.update)
          "
          icon="Clock"
        />
      </span>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, inject, nextTick } from 'vue'
import propsDefinition from './formSectionProps'
import { isValueEmpty } from '@/utils'
import find from 'lodash/find'
import { useStore } from 'vuex'
import { useDates } from '~/Core/resources/js/composables/useDates'

const props = defineProps({
  ...propsDefinition,
  ...{ sending: { type: Boolean, default: false } },
})

const emit = defineEmits(['send-requested', 'save-requested'])

const store = useStore()
const { localizedDateTime, appDate } = useDates()
const brands = inject('brands')

const recipientNameInputRef = ref(null)
const mailAccount = ref(null)
const scheduleSend = ref(Boolean(props.form.send_at))

const selectedBrand = computed(() => {
  if (!props.form.brand_id) {
    return null
  }

  return find(brands.value, ['id', props.form.brand_id])
})

const mailAccounts = computed(() => store.getters['emailAccounts/accounts'])

const filledSigners = computed(() =>
  props.form.signers.filter(
    signer => !isValueEmpty(signer.name) && !isValueEmpty(signer.email)
  )
)

const filledAndEnabledRecipients = computed(() =>
  props.form.recipients.filter(
    recipient =>
      recipient.send_email &&
      !isValueEmpty(recipient.name) &&
      !isValueEmpty(recipient.email)
  )
)

const filledAndEnabledSignersRecipients = computed(() =>
  props.form.signers.filter(
    signer =>
      signer.send_email &&
      !isValueEmpty(signer.name) &&
      !isValueEmpty(signer.email)
  )
)

const emptyRecipientsExists = computed(
  () =>
    props.form.recipients.filter(
      recipient => isValueEmpty(recipient.name) || isValueEmpty(recipient.email)
    ).length > 0
)

const isEligibleForSending = computed(
  () =>
    !(
      !props.form.send_mail_body ||
      !props.form.send_mail_subject ||
      !props.form.send_mail_account_id ||
      (filledAndEnabledRecipients.value.length === 0 &&
        filledAndEnabledSignersRecipients.value.length === 0)
    )
)

function fetchAccounts() {
  return store.dispatch('emailAccounts/fetch')
}

function removeRecipient(index) {
  props.form.recipients.splice(index, 1)
}

function insertEmptyRecipient() {
  props.form.recipients.push({ name: '', email: '', send_email: true })

  nextTick(() => {
    recipientNameInputRef.value[props.form.recipients.length - 1].focus()
  })
}

function setActiveMailAccount(accounts) {
  if (props.form.send_mail_account_id) {
    mailAccount.value = find(accounts, [
      'id',
      Number(props.form.send_mail_account_id),
    ])
  } else {
    mailAccount.value = accounts.length ? accounts[0] : null

    if (mailAccount.value) {
      props.form.send_mail_account_id = mailAccount.value.id
    }
  }
}

watch(
  selectedBrand,
  (newVal, oldVal) => {
    if (
      (newVal && isValueEmpty(props.form.send_mail_body)) ||
      (oldVal &&
        props.form.send_mail_body === oldVal.config.document.mail_message[props.form.locale])
    ) {
      props.form.send_mail_body = newVal.config.document.mail_message[props.form.locale]
    }

    if (
      (newVal && isValueEmpty(props.form.send_mail_subject)) ||
      (oldVal &&
        oldVal.config.document.mail_subject[props.form.locale] === props.form.send_mail_subject)
    ) {
      props.form.send_mail_subject = newVal.config.document.mail_subject[props.form.locale]
    }
  },
  { immediate: true }
)

fetchAccounts().then(setActiveMailAccount)
</script>
