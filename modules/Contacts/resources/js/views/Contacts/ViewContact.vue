<template>
  <ILayout :overlay="!componentReady">
    <div class="mx-auto max-w-7xl">
      <IAlert
        v-if="componentReady && $gate.denies('view', record)"
        class="mb-6"
        variant="warning"
      >
        {{ $t('core::role.view_non_authorized_after_record_create') }}
      </IAlert>

      <div v-if="componentReady" class="relative">
        <div
          class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900"
        >
          <div class="bg-white px-3 py-4 dark:bg-neutral-900 sm:p-6">
            <div class="flex grow flex-col lg:flex-row lg:items-center">
              <div
                class="overflow-hidden text-center lg:flex lg:grow lg:items-center lg:space-x-4 lg:text-left"
              >
                <IAvatar
                  size="lg"
                  :src="record.avatar_url"
                  :title="record.name"
                  class="lg:shrink-0 lg:self-start"
                />

                <div class="overflow-hidden space-y-2 lg:space-y-0">
                  <div class="lg:flex lg:items-center lg:space-x-4">
                    <h1 class="relative truncate">
                      <span
                        @click="
                          record.authorizations.update &&
                            $refs.namePopoverRef.show()
                        "
                        :class="[
                          'text-2xl font-bold text-neutral-900 dark:text-white',
                          {
                            'cursor-pointer rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700':
                              record.authorizations.update,
                          },
                        ]"
                        v-text="record.display_name"
                      />

                      <!-- Use popover separately so the truncate class works fine on the h1 tag -->
                      <IPopover
                        v-if="record.authorizations.update"
                        class="w-72"
                        @show="
                          ;(editFirstName = record.first_name),
                            (editLastName = record.last_name)
                        "
                        @hide="updateForm.errors.clear()"
                        ref="namePopoverRef"
                      >
                        <!-- Absolute centering, a tricky way to center the dropdown -->
                        <button
                          type="button"
                          class="hide absolute left-1/2 top-1/2 mt-3 h-0 w-0 -translate-x-1/2 -translate-y-1/2 transform"
                        />
                        <template #popper>
                          <div class="px-5 py-4">
                            <IFormGroup
                              required
                              :label="
                                $t('contacts::fields.contacts.first_name')
                              "
                              label-for="editFirstName"
                            >
                              <IFormInput
                                v-model="editFirstName"
                                id="editFirstName"
                                class="font-normal"
                                @keydown.enter="updateFullName"
                                @keydown="updateForm.errors.clear('first_name')"
                              />
                              <IFormError
                                v-text="updateForm.getError('first_name')"
                              />
                            </IFormGroup>
                            <IFormGroup
                              :label="$t('contacts::fields.contacts.last_name')"
                              label-for="editLastName"
                            >
                              <IFormInput
                                v-model="editLastName"
                                id="editLastName"
                                class="font-normal"
                                @keydown.enter="updateFullName"
                                @keydown="updateForm.errors.clear('last_name')"
                              />
                              <IFormError
                                v-text="updateForm.getError('last_name')"
                              />
                            </IFormGroup>

                            <div
                              class="-mx-5 -mb-4 mt-4 flex justify-end space-x-1 bg-neutral-100 px-4 py-3 dark:bg-neutral-900"
                            >
                              <IButton
                                size="sm"
                                variant="white"
                                :disabled="updateForm.busy"
                                :text="$t('core::app.cancel')"
                                @click="() => $refs.namePopoverRef.hide()"
                              />
                              <IButton
                                size="sm"
                                variant="primary"
                                :loading="updateForm.busy"
                                :disabled="updateForm.busy || !editFirstName"
                                :text="$t('core::app.save')"
                                @click="updateFullName"
                              />
                            </div>
                          </div>
                        </template>
                      </IPopover>
                    </h1>
                    <div class="inline-block lg:shrink-0">
                      <TagsSelectInput
                        :simple="true"
                        v-model="tags"
                        :disabled="!record.authorizations.update"
                        class="mt-0.5"
                        type="contacts"
                        @update:model-value="update({ tags: $event })"
                      />
                    </div>
                  </div>
                  <span
                    v-if="record.job_title"
                    class="block overflow-hidden truncate text-sm text-neutral-500 dark:text-neutral-200"
                  >
                    {{
                      isAssociateToOneCompany
                        ? $t('contacts::contact.works_at', {
                            job_title: record.job_title,
                            company: record.companies[0].name,
                          })
                        : record.job_title
                    }}
                  </span>
                </div>
              </div>
              <div
                class="ml-6 flex shrink-0 flex-col items-center lg:flex-row lg:space-x-3"
              >
                <IButton
                  v-once
                  v-show="record.authorizations.update"
                  variant="success"
                  class="mr-3 mt-5 lg:mt-0 lg:shrink-0"
                  icon="Plus"
                  :to="{ name: 'createDealViaContact' }"
                  :text="$t('deals::deal.add')"
                />

                <div
                  class="mt-5 flex shrink-0 justify-center space-x-3 lg:mt-0 lg:items-center lg:justify-normal"
                >
                  <div>
                    <DropdownSelectInput
                      v-if="record.authorizations.update"
                      :items="ownerDropdownOptions"
                      :modelValue="record.user_id"
                      value-key="id"
                      label-key="name"
                      @change="update({ user_id: $event.id })"
                    >
                      <template #label="{ label }">
                        <span
                          v-if="record.user"
                          class="inline-flex items-center"
                          v-i-tooltip.top="
                            $t('contacts::fields.contacts.user.name')
                          "
                        >
                          <IAvatar
                            size="xs"
                            class="mr-1.5"
                            :src="record.user.avatar_url"
                          />
                          {{ label }}
                        </span>
                        <span
                          v-else
                          v-t="'core::app.no_owner'"
                          class="text-neutral-500 dark:text-neutral-300"
                        />
                      </template>
                    </DropdownSelectInput>
                    <p
                      v-else-if="record.user"
                      class="inline-flex items-center text-sm text-neutral-800 dark:text-neutral-200"
                    >
                      <IAvatar
                        size="xs"
                        class="mr-1.5"
                        :src="record.user.avatar_url"
                      />
                      {{ record.user.name }}
                    </p>
                  </div>
                  <Actions
                    type="dropdown"
                    :ids="record.id || []"
                    :actions="record.actions || []"
                    :resource-name="resourceName"
                    @run="handleActionExecuted"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          v-once
          v-if="$gate.isSuperAdmin()"
          class="absolute -top-5 right-2 rotate-90 lg:-right-6 lg:top-1.5 lg:rotate-0"
        >
          <IMinimalDropdown placement="bottom-end">
            <IDropdownItem
              @click="sidebarBeingManaged = true"
              :text="$t('core::app.record_view.manage_sidebar')"
            />
          </IMinimalDropdown>
        </div>
      </div>

      <div class="mt-8" v-if="componentReady">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
          <div class="col-span-4 space-y-3">
            <div
              v-show="!sidebarBeingManaged"
              v-for="section in enabledSections"
              :key="section.id"
            >
              <component
                ref="sectionRefs"
                :is="
                  sectionComponents.hasOwnProperty(section.component)
                    ? sectionComponents[section.component]
                    : section.component
                "
                :contact="record"
                @refetch="fetchRecordAndSetInStore"
              />
            </div>
            <ManageViewSections
              :identifier="resourceSingular"
              v-model:sections="template.sections"
              v-model:show="sidebarBeingManaged"
              @saved="sidebarBeingManaged = false"
              class="-mt-3 inline"
            />
          </div>

          <div class="col-span-8 mt-4 lg:mt-0">
            <ITabGroup :default-index="defaultTabIndex">
              <ITabList
                centered
                :bordered="false"
                list-wrapper-class="rounded-md border border-neutral-200 bg-white py-0.5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900"
              >
                <component
                  v-for="tab in template.tabs"
                  :key="tab.id"
                  :is="
                    tabComponents.hasOwnProperty(tab.component)
                      ? tabComponents[tab.component]
                      : tab.component
                  "
                />
              </ITabList>
              <ITabPanels>
                <component
                  v-for="tab in template.tabs"
                  :key="tab.id"
                  :id="'tabPanel-' + tab.id"
                  :is="
                    tabComponents.hasOwnProperty(tab.panelComponent)
                      ? tabComponents[tab.panelComponent]
                      : tab.panelComponent
                  "
                  :resource-name="resourceName"
                  scroll-element="#main"
                />
              </ITabPanels>
            </ITabGroup>
          </div>
        </div>
      </div>
    </div>
    <template v-if="componentReady">
      <!-- Company, Deal Create -->
      <router-view
        :via-resource="resourceName"
        :go-to-list="false"
        @associated="fetchRecordAndSetInStore(), $router.back()"
        @created="
          ({ isRegularAction }) => (
            isRegularAction ? $router.back() : '', fetchRecordAndSetInStore()
          )
        "
        @modal-hidden="$router.back"
      />
    </template>

    <PreviewModal :via-resource="resourceName" @updated="updateFieldsValues" />
  </ILayout>
</template>
<script setup>
import { ref, computed, watch, onBeforeMount, onBeforeUnmount } from 'vue'
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useResource } from '~/Core/resources/js/composables/useResource'
import TagsSelectInput from '~/Core/resources/js/components/Tags/TagsSelectInput.vue'
import Actions from '~/Core/resources/js/components/Actions/Actions.vue'
import ManageViewSections from '~/Core/resources/js/components/ManageViewSections.vue'

import TimelineTab from '~/Core/resources/js/views/Timeline/RecordTabTimeline.vue'
import TimelineTabPanel from '~/Core/resources/js/views/Timeline/RecordTabTimelinePanel.vue'

import DetailsSection from '../../components/ViewContactSidebar/ViewContactDetails.vue'
import DealsSection from '../../components/ViewContactSidebar/ViewContactDeals.vue'
import CompaniesSection from '../../components/ViewContactSidebar/ViewContactCompanies.vue'
import MediaSection from '../../components/ViewContactSidebar/ViewContactMedia.vue'
import { useGlobalEventListener } from '~/Core/resources/js/composables/useGlobalEventListener'
import { watchOnce } from '@vueuse/core'

import { useForm } from '~/Core/resources/js/composables/useForm'

const tabComponents = {
  'timeline-tab': TimelineTab,
  'timeline-tab-panel': TimelineTabPanel,
}

const sectionComponents = {
  'details-section': DetailsSection,
  'deals-section': DealsSection,
  'companies-section': CompaniesSection,
  'media-section': MediaSection,
}

const resourceName = Innoclapps.config('resources.contacts.name')

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const { users, setPageTitle } = useApp()

useGlobalEventListener('refresh-details-view', refreshRecordView)

const { singularName: resourceSingular } = useResource(resourceName)

const { setRecord, resetRecord, record } = useRecordStore()

const sectionRefs = ref([])
const namePopoverRef = ref(null)
const sidebarBeingManaged = ref(false)
const template = ref(Innoclapps.config('resources.contacts.frontend.view'))
const editFirstName = ref(null)
const editLastName = ref(null)
const tags = ref([])

const defaultTabIndex = route.query.section
  ? template.value.tabs.findIndex(tab => tab.id === route.query.section)
  : 0

const { form: updateForm } = useForm()

const ownerDropdownOptions = computed(() => {
  if (record.value.user) {
    return [
      ...users.value,
      {
        id: null,
        icon: 'X',
        prependIcon: true,
        name: t('core::app.no_owner'),
        class: 'border-t border-neutral-200 dark:border-neutral-700',
      },
    ].map(user => ({
      id: user.id,
      name: user.name,
      class: user.class || undefined,
      prependIcon: user.prependIcon || undefined,
      icon: user.icon || undefined,
    }))
  }

  return users.value
})

const isAssociateToOneCompany = computed(
  () => record.value.companies.length == 1
)

const enabledSections = computed(() =>
  template.value.sections.filter(section => section.enabled === true)
)

const componentReady = computed(() => Object.keys(record.value).length > 0)

async function fetchRecord() {
  let { data } = await Innoclapps.request().get(
    `/${resourceName}/${route.params.id}`
  )
  return data
}

async function fetchRecordAndSetInStore() {
  let newRecord = await fetchRecord()
  setRecord(newRecord)
  return newRecord
}

function refreshRecordView() {
  fetchRecordAndSetInStore().then(updateFieldsValues)
}

function boot() {
  resetRecord()

  if (router[resourceSingular.value]) {
    setRecord(router[resourceSingular.value])
    delete router[resourceSingular.value]
  } else {
    fetchRecordAndSetInStore()
  }
}

function handleActionExecuted(action) {
  if (!action.destroyable) {
    refreshRecordView()
  } else {
    router.push({ name: 'contact-index' })
  }
}

async function update(payload = {}) {
  let contact = await updateForm
    .clear()
    .set(payload)
    .put(`/contacts/${record.value.id}`)

  setRecord(contact)
  updateFieldsValues(contact)

  return contact
}

function updateFullName() {
  update({
    first_name: editFirstName.value,
    last_name: editLastName.value,
  }).then(() => namePopoverRef.value.hide())
}

function getDetailsSectionRef() {
  return sectionRefs.value[
    template.value.sections.findIndex(section => section.id === 'details')
  ]
}

watch(
  () => route.params,
  (newVal, oldVal) => {
    if (route.name === 'view-contact' && newVal.id !== oldVal.id) {
      boot()
    }
  }
)

onBeforeMount(boot)
onBeforeUnmount(resetRecord)

watchOnce(
  () => record.value.tags?.length,
  () => {
    tags.value = record.value.tags
  }
)

watch(
  () => record.value.display_name,
  newVal => {
    if (newVal) setPageTitle(newVal)
  }
)

onBeforeRouteUpdate((to, from) => {
  if (to.name === 'view-contact') {
    // Reset the page title when the route is updated
    // e.q. create deal then back to this route
    setPageTitle(record.value.display_name)
  }
})

function updateFieldsValues(record) {
  const detailsSectionRef = getDetailsSectionRef()
  // Perhaps the sidebar section item is not enabled?
  if (!detailsSectionRef) return

  detailsSectionRef.setFormValues(record)
}
</script>
