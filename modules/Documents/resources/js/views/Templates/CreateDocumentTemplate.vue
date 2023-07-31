<template>
  <Teleport to="body">
    <div
      class="absolute inset-0 z-40 h-full max-h-full w-full bg-white dark:bg-neutral-900"
    >
      <!-- navbar start -->
      <div
        class="sticky top-0 z-50 border-b border-neutral-200 bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800"
      >
        <div class="container mx-auto">
          <div class="mx-auto max-w-6xl">
            <div class="px-3 py-4 sm:px-0">
              <div
                class="flex items-center justify-between space-x-4 sm:space-x-0"
              >
                <a
                  href="#"
                  class="link"
                  @click.prevent="$router.back"
                  v-t="'core::app.exit'"
                />

                <IDropdownButtonGroup
                  size="sm"
                  placement="bottom-end"
                  :disabled="form.busy"
                  :loading="form.busy"
                  :text="$t('core::app.save')"
                  @click="save"
                >
                  <IDropdownItem
                    @click="saveAndExit"
                    :text="$t('core::app.save_and_exit')"
                  />
                </IDropdownButtonGroup>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- navbar end -->
      <div class="h-full min-h-full overflow-y-auto">
        <div class="px-4 py-6 sm:px-0">
          <div class="container mx-auto max-w-6xl">
            <IFormGroup
              label-for="templateName"
              :label="$t('documents::document.template.name')"
              required
            >
              <IFormInput
                v-model="form.name"
                id="templateName"
                @input="form.errors.clear('name')"
              />
              <IFormError v-text="form.getError('name')" />
            </IFormGroup>

            <IFormGroup>
              <IFormCheckbox
                id="is_shared"
                name="is_shared"
                v-model:checked="form.is_shared"
                @change="form.errors.clear('is_shared')"
                :label="
                  $t('documents::document.template.share_with_team_members')
                "
              />
              <IFormError v-text="form.getError('is_shared')" />
            </IFormGroup>

            <IFormGroup class="mt-6">
              <div class="flex">
                <h3
                  class="text-sm font-medium text-neutral-800 dark:text-neutral-100"
                  v-t="'documents::document.view_type.html_view_type'"
                />
                <span
                  class="ml-1 self-end text-xs text-neutral-600 dark:text-neutral-200"
                  v-t="'core::app.optional'"
                />
              </div>
              <IFormText
                v-t="'documents::document.view_type.template_info'"
                class="-mt-px mb-3"
              />
              <FormViewTypes v-model="form.view_type" />
              <IFormError v-text="form.getError('view_type')" />
            </IFormGroup>

            <IFormGroup class="mt-6">
              <div
                class="prose prose-sm prose-neutral relative max-w-none dark:prose-invert"
                style="padding-bottom: 200px"
              >
                <ContentBuilder
                  v-model="form.content"
                  @update:modelValue="form.errors.clear('content')"
                  :placeholders="placeholders"
                  ref="builderRef"
                />
                <IFormError v-text="form.getError('content')" />
              </div>
            </IFormGroup>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
<script setup>
import { ref, nextTick, onBeforeMount, onBeforeUnmount } from 'vue'
import ContentBuilder from '~/Core/resources/js/components/ContentBuilder/ContentBuilder.vue'
import FormViewTypes from '../DocumentFormViewTypes.vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['created'])

const { t } = useI18n()
const router = useRouter()

const builderRef = ref(null)

const placeholders = Innoclapps.config('documents.placeholders')

const { form } = useForm({
  name: 'Template name',
  is_shared: false,
  content: '',
  view_type: null,
})

/**
 * Save the template and exit
 */
function saveAndExit() {
  save(false).then(() => router.back())
}

/**
 * Save the template
 */
async function save(redirectToEdit = true) {
  form.busy = true
  await builderRef.value.saveBase64Images()

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let template = await form.post('/document-templates').catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  })

  emit('created', template)

  if (redirectToEdit) {
    // Use replace so the exit link works well and returns to the previous location
    router.replace({
      name: 'edit-document-template',
      params: { id: template.id },
    })
  }

  return template
}

onBeforeMount(() => {
  document.body.classList.add('overflow-y-hidden')
})

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-y-hidden')
})
</script>
