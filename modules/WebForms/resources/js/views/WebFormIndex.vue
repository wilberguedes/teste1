<template>
  <ICard
    :header="$t('webforms::form.forms')"
    no-body
    :overlay="formsAreBeingFetched"
  >
    <template #actions>
      <IButton
        v-show="hasForms"
        @click="redirectToCreate"
        size="sm"
        icon="Plus"
        :text="$t('webforms::form.create')"
      />
    </template>
    <div v-if="!formsAreBeingFetched">
      <TransitionGroup
        v-if="hasForms"
        name="flip-list"
        tag="ul"
        class="divide-y divide-neutral-200 dark:divide-neutral-700"
      >
        <li v-for="form in listForms" :key="form.id">
          <div :class="{ 'opacity-70': form.status === 'inactive' }">
            <div class="flex items-center px-4 py-4 sm:px-6">
              <div
                class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
              >
                <div class="truncate">
                  <div class="flex">
                    <a
                      href="#"
                      class="link flex items-center truncate text-sm font-medium"
                      @click.prevent="redirectToEdit(form.id)"
                    >
                      <span class="mr-1">
                        {{ form.title }}
                      </span>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                      </svg>
                    </a>
                  </div>
                  <div class="mt-2 flex">
                    <div class="flex items-center space-x-4 text-sm">
                      <a
                        :href="form.public_url"
                        class="link"
                        target="_blank"
                        rel="noopener noreferrer"
                        v-t="'core::app.preview'"
                      />
                      <p class="text-neutral-800 dark:text-neutral-300">
                        {{
                          $t('webforms::form.total_submissions', {
                            total: form.total_submissions,
                          })
                        }}
                      </p>
                      <p
                        v-if="findPipelineById(form.submit_data.pipeline_id)"
                        class="flex items-center rounded-md bg-primary-50 px-2 text-sm text-primary-800"
                      >
                        {{
                          findPipelineById(form.submit_data.pipeline_id).name
                        }}
                        <Icon icon="ChevronRight" class="mx-1 mt-px h-3 w-3" />
                        {{
                          findPipelineStageById(
                            form.submit_data.pipeline_id,
                            form.submit_data.stage_id
                          ).name
                        }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="mt-4 shrink-0 sm:ml-5 sm:mt-0">
                  <IFormToggle
                    class="mr-4"
                    @change="toggleStatus(form)"
                    :label="$t('webforms::form.active')"
                    value="active"
                    unchecked-value="inactive"
                    :modelValue="form.status"
                  />
                </div>
              </div>
              <div class="ml-5 shrink-0">
                <IMinimalDropdown>
                  <IDropdownItem
                    @click="redirectToEdit(form.id)"
                    :text="$t('core::app.edit')"
                  />

                  <IDropdownItem
                    @click="destroy(form.id)"
                    :text="$t('core::app.delete')"
                  />
                </IMinimalDropdown>
              </div>
            </div>
          </div>
        </li>
      </TransitionGroup>
      <div v-else class="p-7">
        <button
          type="button"
          @click="redirectToCreate"
          class="relative flex w-full flex-col items-center rounded-lg border-2 border-dashed border-neutral-300 p-6 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 hover:border-neutral-400 dark:border-neutral-400 dark:hover:border-neutral-300 sm:p-12"
        >
          <Icon
            icon="Bars3BottomLeft"
            class="mx-auto h-12 w-12 text-primary-500 dark:text-primary-400"
          />

          <span
            class="mt-2 block font-medium text-neutral-900 dark:text-neutral-100"
            v-t="'webforms::form.create'"
          />

          <p
            class="mt-2 block max-w-2xl text-sm text-neutral-600 dark:text-neutral-300"
            v-t="'webforms::form.info'"
          />
        </button>
      </div>
    </div>
  </ICard>
  <!-- Create -->
  <router-view></router-view>
</template>
<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useWebForms } from '../composables/useWebForms'
import { usePipelines } from '~/Deals/resources/js/composables/usePipelines'

const { t } = useI18n()
const router = useRouter()

const {
  webFormsOrderedByNameAndStatus: listForms,
  formsAreBeingFetched,
  setWebForm,
  deleteWebForm,
} = useWebForms()

const { findPipelineById, findPipelineStageById } = usePipelines()

const { form: toggleStatusForm } = useForm({ status: null })

const hasForms = computed(() => listForms.value.length > 0)

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await deleteWebForm(id)

  Innoclapps.success(t('webforms::form.deleted'))
}

function redirectToCreate() {
  router.push({
    name: 'web-form-create',
  })
}

function redirectToEdit(id) {
  router.push({
    name: 'web-form-edit',
    params: {
      id: id,
    },
  })
}

function toggleStatus(form) {
  toggleStatusForm.fill(
    'status',
    form.status === 'active' ? 'inactive' : 'active'
  )

  toggleStatusForm.put(`/forms/${form.id}`).then(data => {
    setWebForm(data.id, data)
  })
}
</script>
