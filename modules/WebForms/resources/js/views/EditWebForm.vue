<template>
  <div>
    <IOverlay :show="preparingComponent">
      <IModal
        size="sm"
        id="requiresFieldsModal"
        :title="$t('webforms::form.fields_action_required')"
        hide-footer
      >
        <p
          class="text-neutral-900 dark:text-neutral-100"
          v-t="'webforms::form.required_fields_needed'"
        />
      </IModal>
      <IModal
        size="sm"
        id="nonOptionalFieldRequiredModal"
        :cancel-title="$t('core::app.cancel')"
        :ok-title="$t('core::app.continue')"
        :ok-disabled="
          hasContactEmailAddressField &&
          !acceptsRequiredFields.email &&
          hasContactPhoneField &&
          !acceptsRequiredFields.phones
        "
        @ok="acceptRequiredFields"
        :title="$t('webforms::form.fields_action_required')"
      >
        <p
          class="mb-3 text-neutral-800 dark:text-neutral-100"
          v-t="'webforms::form.must_requires_fields'"
        />
        <IFormCheckbox
          id="require-contact-email"
          v-show="!contactEmailFieldIsRequired && hasContactEmailAddressField"
          v-model:checked="acceptsRequiredFields.email"
          name="require-contact-email"
          :label="$t('contacts::fields.contacts.email')"
        />
        <IFormCheckbox
          id="require-contact-phone"
          class="mt-2"
          v-show="!contactPhoneFieldIsRequired && hasContactPhoneField"
          v-model:checked="acceptsRequiredFields.phones"
          name="require-contact-phone"
          :label="$t('contacts::fields.contacts.phone')"
        />
      </IModal>
      <form
        @submit.prevent="beforeUpdateChecks"
        @keydown="form.onKeydown($event)"
        novalidate="true"
      >
        <ICard no-body actions-class="w-full sm:w-auto">
          <template #header>
            <div class="flex items-center">
              <router-link :to="{ name: 'web-forms-index' }">
                <Icon
                  icon="ChevronLeft"
                  class="h-5 w-5 text-neutral-500 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-400"
                />
              </router-link>
              <div class="ml-3 w-full">
                <div
                  class="border-b border-transparent focus-within:border-primary-500"
                >
                  <input
                    type="text"
                    name="name"
                    id="name"
                    class="block w-full border-0 border-b border-transparent bg-neutral-50 text-sm font-medium focus:border-primary-500 focus:ring-0 dark:bg-neutral-700 dark:text-white"
                    v-model="form.title"
                  />
                </div>

                <IFormError v-text="form.getError('title')" class="text-sm')" />
              </div>
            </div>
          </template>
          <template #actions>
            <div
              class="mt-5 flex w-full items-center justify-end space-x-2 sm:mt-0 sm:w-auto"
            >
              <div class="flex">
                <IActionMessage
                  v-show="form.recentlySuccessful"
                  :message="$t('core::app.saved')"
                  class="mr-2"
                />
                <IFormToggle
                  v-model="form.status"
                  value="active"
                  class="mr-2 border-r border-neutral-200 pr-4 dark:border-neutral-700"
                  unchecked-value="inactive"
                  :disabled="addingNewSection || form.busy"
                  :label="$t('webforms::form.active')"
                />
              </div>
              <a
                :href="form.public_url"
                target="_blank"
                rel="noopener noreferrer"
                :class="{ disabled: form.busy }"
                class="btn-white btn-sm btn rounded"
                v-t="'core::app.preview'"
              />
              <IButton
                size="sm"
                :loading="form.busy"
                @click="beforeUpdateChecks"
                :disabled="form.busy || addingNewSection"
                :text="$t('core::app.save')"
              />
            </div>
          </template>
          <div class="form-wrapper overflow-auto">
            <div class="m-auto max-w-full">
              <ITabGroup>
                <ITabList
                  centered
                  class="sticky top-0 z-10 bg-white dark:bg-neutral-900"
                >
                  <ITab :title="$t('webforms::form.editor')" />
                  <ITab :title="$t('webforms::form.submit_options')" />
                  <ITab :title="$t('webforms::form.style.style')" />
                  <ITab :title="$t('webforms::form.sections.embed.embed')" />
                </ITabList>
                <ITabPanels>
                  <ITabPanel>
                    <div
                      v-for="(section, index) in form.sections"
                      :key="index + section.type + section.attribute"
                      class="m-auto max-w-sm"
                    >
                      <component
                        :form="form"
                        :is="sectionComponents[section.type]"
                        :companies-fields="companiesFields"
                        :contacts-fields="contactsFields"
                        :deals-fields="dealsFields"
                        :index="index"
                        :available-resources="availableResources"
                        @remove-section-requested="removeSection(index)"
                        @update-section-requested="
                          updateSectionRequestedEvent(index, $event)
                        "
                        @create-section-requested="createSection(index, $event)"
                        :section="section"
                      />
                      <div
                        class="group relative flex flex-col items-center justify-center"
                        v-if="totalSections - 1 != index"
                      >
                        <div v-show="!addingNewSection" class="absolute">
                          <IButton
                            size="sm"
                            class="block transition-opacity delay-75 md:opacity-0 md:group-hover:opacity-100"
                            @click="newSection(index)"
                            icon="Plus"
                            variant="secondary"
                          />
                        </div>
                        <svg height="56" width="360">
                          <line
                            x1="180"
                            y1="0"
                            x2="180"
                            y2="56"
                            class="stroke-current stroke-1 text-neutral-900 dark:text-neutral-700"
                          />
                          Sorry, your browser does not support inline SVG.
                        </svg>
                      </div>
                    </div>
                  </ITabPanel>
                  <ITabPanel>
                    <ICardBody>
                      <h5
                        class="mb-3 text-lg font-semibold text-neutral-700 dark:text-neutral-100"
                        v-t="'webforms::form.success_page.success_page'"
                      />
                      <IFormGroup
                        :label="
                          $t('webforms::form.success_page.success_page_info')
                        "
                      >
                        <IFormRadio
                          class="mt-2"
                          :label="
                            $t('webforms::form.success_page.thank_you_message')
                          "
                          id="submitMessage"
                          value="message"
                          name="submit-action"
                          v-model="form.submit_data.action"
                        />

                        <IFormRadio
                          class="mt-2"
                          :label="$t('webforms::form.success_page.redirect')"
                          id="submitRedirect"
                          value="redirect"
                          name="submit-action"
                          v-model="form.submit_data.action"
                        />

                        <IFormError
                          v-text="form.getError('submit_data.action')"
                        />
                      </IFormGroup>
                      <div class="mb-3">
                        <div v-show="form.submit_data.action === 'message'">
                          <IFormGroup
                            :label="$t('webforms::form.success_page.title')"
                            label-for="success_title"
                            required
                          >
                            <IFormInput
                              v-model="form.submit_data.success_title"
                              :placeholder="
                                $t(
                                  'webforms::form.success_page.title_placeholder'
                                )
                              "
                            />
                            <IFormError
                              v-text="
                                form.getError('submit_data.success_title')
                              "
                            />
                          </IFormGroup>
                          <IFormGroup
                            :label="$t('webforms::form.success_page.message')"
                            optional
                          >
                            <Editor
                              :with-image="false"
                              v-model="form.submit_data.success_message"
                            />
                          </IFormGroup>
                        </div>
                        <div v-show="form.submit_data.action === 'redirect'">
                          <IFormGroup
                            :label="
                              $t('webforms::form.success_page.redirect_url')
                            "
                            label-for="success_redirect_url"
                            required
                          >
                            <IFormInput
                              type="url"
                              :placeholder="
                                $t(
                                  'webforms::form.success_page.redirect_url_placeholder'
                                )
                              "
                              v-model="form.submit_data.success_redirect_url"
                            />
                            <IFormError
                              v-text="
                                form.getError(
                                  'submit_data.success_redirect_url'
                                )
                              "
                            />
                          </IFormGroup>
                        </div>
                      </div>

                      <h5
                        class="mb-3 mt-8 text-lg font-semibold text-neutral-700 dark:text-neutral-100"
                        v-t="
                          'webforms::form.saving_preferences.saving_preferences'
                        "
                      />
                      <IFormGroup
                        label-for="title_prefix"
                        :label="
                          $t(
                            'webforms::form.saving_preferences.deal_title_prefix'
                          )
                        "
                        :description="
                          $t(
                            'webforms::form.saving_preferences.deal_title_prefix_info'
                          )
                        "
                        optional
                      >
                        <IFormInput
                          v-model="form.title_prefix"
                          id="title_prefix')"
                        />
                      </IFormGroup>
                      <IFormGroup
                        label-for="pipeline_id"
                        :label="$t('deals::fields.deals.pipeline.name')"
                        required
                      >
                        <ICustomSelect
                          :options="pipelines"
                          label="name"
                          input-id="pipeline_id"
                          :clearable="false"
                          @update:modelValue="stage = $event.stages[0]"
                          v-model="pipeline"
                        />
                        <IFormError
                          v-text="form.getError('submit_data.pipeline_id')"
                        />
                      </IFormGroup>
                      <IFormGroup
                        label-for="stage_id"
                        required
                        :label="$t('deals::fields.deals.stage.name')"
                      >
                        <ICustomSelect
                          :options="pipeline ? pipeline.stages : []"
                          label="name"
                          :clearable="false"
                          input-id="stage_id"
                          v-model="stage"
                        />
                        <IFormError
                          v-text="form.getError('submit_data.stage_id')"
                        />
                      </IFormGroup>
                      <IFormGroup
                        label-for="user_id"
                        :label="$t('deals::fields.deals.user.name')"
                        required
                      >
                        <ICustomSelect
                          :options="users"
                          :clearable="false"
                          label="name"
                          :reduce="user => user.id"
                          input-id="user_id"
                          v-model="form.user_id"
                        />
                        <IFormError v-text="form.getError('user_id')" />
                      </IFormGroup>
                      <IFormGroup
                        label-for="notifications"
                        :label="$t('webforms::form.notifications')"
                      >
                        <div
                          v-for="(email, index) in form.notifications"
                          :key="index"
                          class="mb-3 flex rounded-md shadow-sm"
                        >
                          <div
                            class="relative flex grow items-stretch focus-within:z-10"
                          >
                            <IFormInput
                              :rounded="false"
                              class="rounded-l-md"
                              type="email"
                              :placeholder="
                                $t(
                                  'webforms::form.notification_email_placeholder'
                                )
                              "
                              v-model="form.notifications[index]"
                            />
                            <IFormError
                              v-text="form.getError('notifications.' + index)"
                            />
                          </div>
                          <IButton
                            type="button"
                            icon="X"
                            variant="white"
                            :rounded="false"
                            @click="removeNotification(index)"
                            class="relative -ml-px rounded-r-md"
                          />
                        </div>

                        <a
                          v-show="
                            !emptyNotificationsEmails ||
                            totalNotifications === 0
                          "
                          href="#"
                          class="link text-sm"
                          @click.prevent="addNewNotification"
                          v-t="'webforms::form.new_notification'"
                        />
                      </IFormGroup>
                    </ICardBody>
                  </ITabPanel>
                  <ITabPanel>
                    <ICardBody>
                      <h5
                        class="mb-3 text-lg font-semibold text-neutral-700 dark:text-neutral-100"
                        v-t="'webforms::form.style.style'"
                      />

                      <IFormGroup
                        class="mt-3 w-full sm:w-[356px]"
                        :label="$t('core::app.locale')"
                        label-for="locale"
                      >
                        <ICustomSelect
                          input-id="locale"
                          v-model="form.locale"
                          :clearable="false"
                          :options="locales"
                        >
                        </ICustomSelect>
                        <IFormError v-text="form.getError('locale')" />
                      </IFormGroup>
                      <IFormGroup
                        :label="$t('webforms::form.style.primary_color')"
                      >
                        <IColorSwatches
                          :allow-remove="false"
                          v-model="form.styles.primary_color"
                          :swatches="swatches"
                        />
                        <IFormError
                          v-text="form.getError('styles.primary_color')"
                        />
                      </IFormGroup>
                      <IFormGroup
                        :label="$t('webforms::form.style.background_color')"
                      >
                        <IColorSwatches
                          :allow-remove="false"
                          v-model="form.styles.background_color"
                          :swatches="swatches"
                        />
                        <IFormError
                          v-text="form.getError('styles.background_color')"
                        />
                      </IFormGroup>

                      <h3
                        class="mb-2 mt-4 text-sm font-medium text-neutral-800 dark:text-neutral-100"
                        v-t="'webforms::form.style.logo'"
                      />

                      <LogoType
                        v-model="form.styles.logo"
                        :background-color="form.styles.background_color"
                        :primary-color="form.styles.primary_color"
                      />
                    </ICardBody>
                  </ITabPanel>
                  <ITabPanel>
                    <ICardBody>
                      <WebFormEmbed :form="form" />
                    </ICardBody>
                  </ITabPanel>
                </ITabPanels>
              </ITabGroup>
            </div>
          </div>
        </ICard>
      </form>
    </IOverlay>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import find from 'lodash/find'
import get from 'lodash/get'
import findIndex from 'lodash/findIndex'
import IntroductionSection from '../components/EditorSections/IntroductionSection.vue'
import SubmitButtonSection from '../components/EditorSections/SubmitButtonSection.vue'
import FileSection from '../components/EditorSections/FileSection.vue'
import FieldSection from '../components/EditorSections/FieldSection.vue'
import NewSection from '../components/EditorSections/NewSection.vue'
import MessageSection from '../components/EditorSections/MessageSection.vue'
import WebFormEmbed from './EditWebFormEmbed.vue'
import LogoType from './EditWebFormLogoType.vue'
import { useI18n } from 'vue-i18n'
import { isValueEmpty } from '@/utils'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useRoute } from 'vue-router'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useWebForms } from '../composables/useWebForms'
import { usePipelines } from '~/Deals/resources/js/composables/usePipelines'

const sectionComponents = {
  'field-section': FieldSection,
  'introduction-section': IntroductionSection,
  'submit-button-section': SubmitButtonSection,
  'file-section': FileSection,
  'new-section': NewSection,
  'message-section': MessageSection,
}

const { t } = useI18n()
const route = useRoute()
const { users, currentUser, locales, setPageTitle } = useApp()
const { fetchWebForm, setWebForm } = useWebForms()
const { orderedPipelines: pipelines } = usePipelines()

const swatches = Innoclapps.config('favourite_colors')

const acceptsRequiredFields = ref({
  email: true,
  phones: true,
})

const contactsFields = ref([])
const companiesFields = ref([])
const dealsFields = ref([])
const pipeline = ref(null)
const stage = ref(null)
const preparingComponent = ref(false)
const addingNewSection = ref(false)

const { form } = useForm({
  notifications: [],
  sections: [],
  styles: [],
  submit_data: [],
})

const availableResources = [
  {
    id: 'contacts',
    label: t('contacts::contact.contact'),
  },
  {
    id: 'companies',
    label: t('contacts::company.company'),
  },
  {
    id: 'deals',
    label: t('deals::deal.deal'),
  },
]

const totalNotifications = computed(() => form.notifications.length)

const emptyNotificationsEmails = computed(
  () => form.notifications.filter(isValueEmpty).length > 0
)

const totalSections = computed(() => form.sections.length)

const hasContactEmailAddressField = computed(
  () =>
    find(form.sections, {
      resourceName: 'contacts',
      attribute: 'email',
    }) !== undefined
)

const hasContactPhoneField = computed(
  () =>
    find(form.sections, {
      resourceName: 'contacts',
      attribute: 'phones',
    }) !== undefined
)

const contactEmailFieldIsRequired = computed(() => {
  if (!hasContactEmailAddressField.value) {
    return false
  }

  return (
    find(form.sections, {
      resourceName: 'contacts',
      attribute: 'email',
    }).isRequired === true
  )
})

const contactPhoneFieldIsRequired = computed(() => {
  if (!hasContactPhoneField.value) {
    return false
  }

  return (
    find(form.sections, {
      resourceName: 'contacts',
      attribute: 'phones',
    }).isRequired === true
  )
})

const requiresFields = computed(
  () => !hasContactEmailAddressField.value && !hasContactPhoneField.value
)

const requiresNonOptionalFields = computed(
  () => !contactEmailFieldIsRequired.value && !contactPhoneFieldIsRequired.value
)

function updateSectionRequestedEvent(index, data) {
  updateSection(index, data, false)

  if (requiresFields.value || requiresNonOptionalFields.value) {
    beforeUpdateChecks()
  } else {
    update()
  }
}

function removeCreateSection() {
  const newSectionIndex = findIndex(form.sections, {
    type: 'new-section',
  })

  if (newSectionIndex !== -1) {
    removeSection(newSectionIndex)
  }
}

function newSection(index) {
  addingNewSection.value = true

  form.sections.splice(index + 1, 0, {
    type: 'new-section',
    label: t('webforms::form.sections.new'),
  })
}

async function removeSection(index) {
  if (form.sections[index].type === 'new-section') {
    addingNewSection.value = false
    form.sections.splice(index, 1)
  } else {
    await Innoclapps.dialog().confirm()
    form.sections.splice(index, 1)
    updateSilentlyIfPossible()
  }
}

function updateSection(index, data, forceUpdate = true) {
  form.sections[index] = Object.assign({}, form.sections[index], data)

  if (forceUpdate) {
    update(true)
  }
}

function createSection(fromIndex, data) {
  form.sections.splice(fromIndex + 1, 0, data)
  updateSilentlyIfPossible()
  removeCreateSection()
}

/**
 * Update the form if possible
 *
 * The function will check if the required fields criteria is met
 * If yes, will silently perform update, used when user is creating, updating and removed section
 * So the form is automatically saved with click on SAVE on the section button
 */
function updateSilentlyIfPossible() {
  if (!requiresFields.value && !requiresNonOptionalFields.value) {
    update(true)
  }
}

function setDefaultSectionsIfNeeded() {
  if (totalSections.value === 0) {
    form.sections.push({
      type: 'introduction-section',
      message: '',
      title: '',
    })

    form.sections.push({
      type: 'submit-button-section',
      text: t('webforms::form.sections.submit.default_text'),
    })
  }
}

function removeNotification(index) {
  form.notifications.splice(index, 1)
}

function addNewNotification() {
  form.notifications.push('')

  if (form.notifications.length === 1) {
    form.notifications[0] = currentUser.value.email
  }
}

function beforeUpdateChecks() {
  if (requiresFields.value) {
    Innoclapps.modal().show('requiresFieldsModal')
    return
  } else if (requiresNonOptionalFields.value) {
    Innoclapps.modal().show('nonOptionalFieldRequiredModal')
    return
  }

  update()
}

function acceptRequiredFields() {
  if (hasContactEmailAddressField.value && acceptsRequiredFields.value.email) {
    updateSection(
      findIndex(form.sections, {
        resourceName: 'contacts',
        attribute: 'email',
      }),
      {
        isRequired: true,
      },
      false
    )
  }

  if (hasContactPhoneField.value && acceptsRequiredFields.value.phones) {
    updateSection(
      findIndex(form.sections, {
        resourceName: 'contacts',
        attribute: 'phones',
      }),
      {
        isRequired: true,
      },
      false
    )
  }

  update()

  Innoclapps.modal().hide('nonOptionalFieldRequiredModal')
}

function update(silent = false) {
  form.submit_data.pipeline_id = pipeline.value ? pipeline.value.id : null
  form.submit_data.stage_id = stage.value ? stage.value.id : null

  removeCreateSection()

  form
    .put(`/forms/${route.params.id}`)
    .then(webForm => {
      setWebForm(webForm.id, webForm)

      if (!silent) {
        Innoclapps.success(t('webforms::form.updated'))
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

function isReadOnly(field) {
  return field.readonly || get(field, 'attributes.readonly')
}

function filterFields(fields, excludedAttributes) {
  return fields.filter(
    field =>
      field.showOnCreation === true &&
      (excludedAttributes.indexOf(field.attribute) === -1 || isReadOnly(field))
  )
}

async function getResourcesFields() {
  let { data } = await Innoclapps.request().get(
    '/fields/settings/bulk/create?intent=create',
    {
      params: {
        groups: ['contacts', 'companies', 'deals'],
      },
    }
  )

  contactsFields.value = filterFields(data.contacts, ['user_id', 'source_id'])

  dealsFields.value = filterFields(data.deals, [
    'user_id',
    'pipeline_id',
    'stage_id',
  ])

  companiesFields.value = filterFields(data.companies, [
    'user_id',
    'parent_company_id',
    'source_id',
  ])
}

function prepareComponent() {
  // We will get the fields from settings as these
  // are the fields the user is allowed to interact and use them in forms
  preparingComponent.value = true
  getResourcesFields().finally(() => {
    fetchWebForm(route.params.id).then(webForm => {
      setPageTitle(webForm.title)
      form.clear().set(webForm)

      pipeline.value = pipelines.value.filter(
        pipeline => pipeline.id == webForm.submit_data.pipeline_id
      )[0]

      stage.value = pipeline.value.stages.filter(
        stage => stage.id == webForm.submit_data.stage_id
      )[0]

      setDefaultSectionsIfNeeded()
      preparingComponent.value = false
    })
  })
}

prepareComponent()
</script>
<style>
@media (min-width: 640px) {
  .form-wrapper {
    height: calc(100vh - (var(--navbar-height) + 220px));
  }
}
</style>
