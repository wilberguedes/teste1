<template>
  <div class="mx-auto max-w-3xl" v-show="visible">
    <div class="relative">
      <ITabGroup>
        <ITabList>
          <ITab
            :title="$t('documents::document.document_details')"
            icon="AdjustmentsVertical"
          />
          <ITab
            :disabled="Object.keys(document).length === 0"
            :title="$t('documents::document.document_activity')"
            :badge="changelog.length"
            icon="DocumentText"
          />
        </ITabList>
        <ITabPanels>
          <ITabPanel>
            <IAlert
              v-if="document.status === 'accepted'"
              variant="info"
              class="mb-6"
            >
              {{ $t('documents::document.limited_editing') }}
            </IAlert>
            <div
              class="mt-3 mb-4 flex items-center md:absolute md:right-1 md:top-3.5 md:my-0 md:space-x-2.5"
            >
              <div
                class="order-last ml-auto mr-0 place-self-end md:-order-none md:mr-2.5"
              >
                <slot name="actions"></slot>
              </div>

              <TextBackground
                v-if="selectedDocumentType"
                :color="selectedDocumentType.swatch_color"
                class="mr-2.5 inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-sm/5 font-normal dark:!text-white md:mr-0"
              >
                <Icon
                  :icon="selectedDocumentType.icon"
                  class="mr-1.5 h-4 w-4 text-current"
                />
                {{ selectedDocumentType.name }}
              </TextBackground>

              <TextBackground
                :color="
                  document.status
                    ? statuses[document.status].color
                    : statuses.draft.color
                "
                class="mr-2.5 inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-sm/5 font-normal dark:!text-white md:mr-0"
              >
                {{
                  $t(
                    'documents::document.status.' +
                      (document.status ? document.status : statuses.draft.name)
                  )
                }}
              </TextBackground>
            </div>

            <slot name="top"></slot>

            <IFormGroup
              :label="$t('brands::brand.brand')"
              label-for="brand_id"
              required
            >
              <ICustomSelect
                :options="brands"
                :clearable="false"
                input-id="brand_id"
                :disabled="document.status === 'accepted'"
                label="name"
                v-model="selectedBrand"
                @update:modelValue="form.brand_id = $event.id"
              />
              <IFormError v-text="form.getError('brand_id')" />
            </IFormGroup>

            <IFormGroup
              :label="$t('documents::document.title')"
              label-for="title"
              required
            >
              <IFormInput
                v-model="form.title"
                id="title"
                :disabled="document.status === 'accepted'"
              />
              <IFormError v-text="form.getError('title')" />
            </IFormGroup>
            <IFormGroup
              :label="$t('documents::document.type.type')"
              label-for="document_type_id"
              required
            >
              <ICustomSelect
                :options="documentTypes"
                :clearable="false"
                input-id="document_type_id"
                label="name"
                v-model="selectedDocumentType"
                @update:modelValue="form.document_type_id = $event.id"
              />
              <IFormError v-text="form.getError('document_type_id')" />
            </IFormGroup>

            <IFormGroup
              :label="$t('documents::fields.documents.user.name')"
              label-for="user_id"
              required
            >
              <ICustomSelect
                label="name"
                input-id="user_id"
                :clearable="false"
                :options="users"
                :disabled="document.status === 'accepted'"
                v-model="selectedUser"
                @update:modelValue="form.user_id = $event ? $event.id : null"
              />
              <IFormError v-text="form.getError('user_id')" />
            </IFormGroup>
            <IFormGroup
              :label="$t('core::app.locale')"
              label-for="locale"
              required
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
            <h3
              class="my-2 text-sm font-medium text-neutral-800 dark:text-neutral-100"
              v-t="'documents::document.view_type.html_view_type'"
            />
            <FormViewTypes v-model="form.view_type" />
            <IFormError v-text="form.getError('view_type')" />
          </ITabPanel>
          <ITabPanel>
            <ul role="list" class="space-y-6">
              <li
                v-for="(log, idx) in changelog"
                :key="log.id"
                class="relative flex gap-x-4"
              >
                <div
                  :class="[
                    idx === changelog.length - 1 ? 'h-6' : '-bottom-6',
                    'absolute left-0 top-0 flex w-6 justify-center',
                  ]"
                >
                  <div class="w-px bg-neutral-200" />
                </div>

                <div
                  class="relative flex h-6 w-6 flex-none items-center justify-center bg-white"
                >
                  <div
                    class="h-1.5 w-1.5 rounded-full ring-1"
                    :class="
                      log.properties.type === 'success'
                        ? 'bg-success-200 ring-success-500'
                        : 'bg-neutral-100 ring-neutral-300'
                    "
                  />
                </div>
                <template v-if="log.properties.section">
                  <div
                    class="flex-auto rounded-md p-3 ring-1 ring-inset ring-neutral-200"
                  >
                    <div class="flex justify-between gap-x-4">
                      <div
                        class="py-0.5 text-sm leading-5 text-neutral-500"
                        v-text="
                          $t(log.properties.lang.key, log.properties.lang.attrs)
                        "
                      />
                      <time
                        :datetime="log.dateTime"
                        class="flex-none py-0.5 text-xs leading-5 text-neutral-500"
                        v-text="localizedDateTime(log.created_at)"
                      />
                    </div>
                    <div
                      class="mt-1.5 py-0.5 text-sm leading-5 text-neutral-500"
                    >
                      {{
                        $t(
                          log.properties.section.lang.key,
                          log.properties.section.lang.attrs || {}
                        )
                      }}
                      <ul class="flex-none">
                        <li
                          v-for="(data, sIdx) in log.properties.section.list"
                          :key="sIdx"
                          class="text-neutral-600 dark:text-neutral-400"
                          v-text="$t(data.lang.key, data.lang.attrs || {})"
                        />
                      </ul>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <p
                    class="flex-auto py-0.5 text-sm leading-5 text-neutral-500"
                    v-text="
                      $t(log.properties.lang.key, log.properties.lang.attrs)
                    "
                  />
                  <time
                    :datetime="log.dateTime"
                    class="flex-none py-0.5 text-xs leading-5 text-neutral-500"
                    v-text="localizedDateTime(log.created_at)"
                  />
                </template>
              </li>
            </ul>
          </ITabPanel>
        </ITabPanels>
      </ITabGroup>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, inject } from 'vue'
import propsDefinition from './formSectionProps'
import find from 'lodash/find'
import TextBackground from '~/Core/resources/js/components/TextBackground.vue'
import FormViewTypes from '../views/DocumentFormViewTypes.vue'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useDocumentTypes } from '../composables/useDocumentTypes'
import { useApp } from '~/Core/resources/js/composables/useApp'

const props = defineProps(propsDefinition)

const { localizedDateTime } = useDates()

const statuses = Innoclapps.config('documents.statuses')
const selectedUser = ref(null)
const selectedDocumentType = ref(null)
const selectedBrand = ref(null)

const brands = inject('brands')

const { users, locales } = useApp()
const { typesByName: documentTypes } = useDocumentTypes()

const changelog = computed(() => {
  if (!props.document.changelog) {
    return []
  }
  return props.document.changelog.slice().reverse()
})

function prepareComponent() {
  if (!props.form.brand_id) {
    selectedBrand.value = find(brands.value, brand => brand.is_default)

    if (selectedBrand.value) {
      props.form.set('brand_id', selectedBrand.value.id)
    }
  } else {
    selectedBrand.value = find(brands.value, ['id', props.form.brand_id])
  }

  if (props.form.document_type_id) {
    selectedDocumentType.value = find(documentTypes.value, [
      'id',
      props.form.document_type_id,
    ])
  }

  if (props.form.user_id) {
    selectedUser.value = find(users.value, ['id', props.form.user_id])
  }
}

prepareComponent()
</script>
