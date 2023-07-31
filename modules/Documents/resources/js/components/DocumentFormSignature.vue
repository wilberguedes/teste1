<template>
  <div class="mx-auto max-w-3xl" v-show="visible">
    <h3
      class="mb-3 text-base font-medium text-neutral-800 dark:text-neutral-100"
      v-t="'documents::document.sections.signature'"
    />

    <RadioGroup v-model="form.requires_signature">
      <RadioGroupLabel class="sr-only">
        {{ $t('documents::document.sections.signature') }}
      </RadioGroupLabel>
      <div class="-space-y-px rounded-md bg-white">
        <RadioGroupOption
          as="template"
          v-for="(setting, settingIdx) in signatureOptions"
          :key="setting.name"
          :value="setting.value"
          v-slot="{ checked, active }"
        >
          <div
            :class="[
              document.status === 'accepted'
                ? 'pointer-events-none opacity-70'
                : '',
              settingIdx === 0 ? 'rounded-tl-md rounded-tr-md' : '',
              settingIdx === signatureOptions.length - 1
                ? 'rounded-bl-md rounded-br-md'
                : '',
              checked
                ? 'z-10 border-primary-200 bg-primary-50 dark:bg-primary-100'
                : 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800',
              'relative flex cursor-pointer border p-4 focus:outline-none',
            ]"
          >
            <span
              :class="[
                checked
                  ? 'border-transparent bg-primary-600'
                  : 'border-neutral-300 bg-white',
                active ? 'ring-2 ring-primary-500 ring-offset-2' : '',
                'mt-0.5 flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded-full border',
              ]"
              aria-hidden="true"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-white" />
            </span>
            <span class="ml-3 flex flex-col">
              <RadioGroupLabel
                as="span"
                :class="[
                  checked
                    ? 'text-primary-900'
                    : 'text-neutral-900 dark:text-neutral-200',
                  'block text-sm font-medium',
                ]"
              >
                {{ setting.name }}
              </RadioGroupLabel>
              <RadioGroupDescription
                as="span"
                :class="[
                  checked
                    ? 'text-primary-700'
                    : 'text-neutral-500 dark:text-neutral-400',
                  'block text-sm',
                ]"
              >
                {{ setting.description }}
              </RadioGroupDescription>
            </span>
          </div>
        </RadioGroupOption>
      </div>
    </RadioGroup>

    <div v-show="form.requires_signature">
      <h3
        class="mb-3 mt-6 text-base font-medium text-neutral-800 dark:text-neutral-100"
        v-t="'documents::document.signers.document_signers'"
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
                  class="bg-neutral-50 p-2 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                  v-t="'documents::document.signers.signer_name'"
                />
                <th
                  class="bg-neutral-50 p-2 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                  v-t="'documents::document.signers.signer_email'"
                />
                <th
                  class="bg-neutral-50 p-2 text-center text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                >
                  <span
                    v-t="'documents::document.signers.is_signed'"
                    class="hidden sm:block"
                  />
                </th>
                <th></th>
              </tr>
            </thead>
            <tbody
              class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800"
            >
              <tr v-if="form.signers.length === 0">
                <td
                  colspan="4"
                  class="bg-white p-3 align-middle text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                  v-t="'documents::document.signers.no_signers'"
                />
              </tr>

              <template
                v-for="(signer, index) in form.signers"
                :key="'signer-' + index"
              >
                <tr v-if="signer.signed_at">
                  <td
                    colspan="4"
                    class="bg-success-50 p-2 text-center text-sm dark:bg-neutral-800 dark:text-neutral-100"
                  >
                    <p class="text-success-500">
                      <span class="font-medium">
                        {{ $t('documents::document.signature.signature') }}:
                      </span>
                      <span
                        class="font-signature text-[1.4rem] text-success-700"
                        v-text="signer.signature"
                      />
                    </p>
                    <div class="mt-1 inline-flex flex-row space-x-2">
                      <p class="text-success-500">
                        <span class="font-medium">
                          {{ $t('documents::document.signature.signed_on') }}:
                        </span>
                        <span v-text="localizedDateTime(signer.signed_at)" />
                      </p>
                      <p class="text-success-500">
                        <span class="font-medium">
                          {{ $t('documents::document.signature.sign_ip') }}:
                        </span>
                        <span v-text="signer.sign_ip" />
                      </p>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td
                    class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                  >
                    <IFormInput
                      ref="signerNameInputRef"
                      v-model="signer.name"
                      @input="form.errors.clear('signers.' + index + '.name')"
                      :placeholder="
                        $t('documents::document.signers.enter_full_name')
                      "
                      :disabled="document.status === 'accepted'"
                    />
                    <IFormError
                      v-text="form.getError('signers.' + index + '.name')"
                    />
                  </td>
                  <td
                    class="bg-white p-2 align-top text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                  >
                    <IFormInput
                      v-model="signer.email"
                      @input="form.errors.clear('signers.' + index + '.email')"
                      @keyup.enter="insertEmptySigner"
                      :disabled="document.status === 'accepted'"
                      type="email"
                      :placeholder="
                        $t('documents::document.signers.enter_email')
                      "
                    />

                    <IFormError
                      v-text="form.getError('signers.' + index + '.email')"
                    />
                  </td>
                  <td
                    class="bg-white p-2 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                  >
                    <span class="inline-flex min-w-full justify-center">
                      <span
                        v-i-tooltip="
                          signer.signed_at
                            ? localizedDateTime(signer.signed_at)
                            : null
                        "
                        :class="[
                          'inline-block h-4 w-4 rounded-full',
                          signer.signed_at ? 'bg-success-400' : 'bg-danger-400',
                        ]"
                      />
                    </span>
                  </td>
                  <td
                    class="bg-white p-2 text-sm font-medium text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100"
                  >
                    <IButtonIcon icon="X" @click="removeSigner(index)" />
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
      <a
        v-show="!emptySignersExists && document.status !== 'accepted'"
        class="link mt-3 inline-block text-sm font-medium"
        href="#"
        @click.prevent="insertEmptySigner"
      >
        + {{ $t('documents::document.signers.add') }}
      </a>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, nextTick } from 'vue'
import {
  RadioGroup,
  RadioGroupDescription,
  RadioGroupLabel,
  RadioGroupOption,
} from '@headlessui/vue'
import propsDefinition from './formSectionProps'
import { isValueEmpty } from '@/utils'
import { useI18n } from 'vue-i18n'
import { useDates } from '~/Core/resources/js/composables/useDates'

const props = defineProps(propsDefinition)

const { t } = useI18n()

const { localizedDateTime } = useDates()

const signerNameInputRef = ref(null)

const signatureOptions = [
  {
    name: t('documents::document.signature.no_signature'),
    description: t('documents::document.signature.no_signature_description'),
    value: false,
  },
  {
    name: t('documents::document.signature.e_signature'),
    description: t('documents::document.signature.e_signature_description'),
    value: true,
  },
]

const emptySignersExists = computed(
  () =>
    props.form.signers.filter(
      signer => isValueEmpty(signer.name) || isValueEmpty(signer.email)
    ).length > 0
)

/**
 * Insert empty signer
 */
function insertEmptySigner() {
  props.form.signers.push({
    name: '',
    email: '',
    send_email: true,
  })

  nextTick(() => {
    signerNameInputRef.value[props.form.signers.length - 1].focus()
  })
}

/**
 * Remove signer
 */
function removeSigner(index) {
  props.form.signers.splice(index, 1)
}
</script>
