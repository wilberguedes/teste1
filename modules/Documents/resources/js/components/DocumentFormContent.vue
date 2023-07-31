<template>
  <div class="mx-auto max-w-6xl" v-show="visible">
    <div class="mb-8 flex justify-end space-x-2 text-right">
      <IModal
        id="saveAsTemplateModal"
        size="sm"
        @shown="templatesModalShownHandler"
        :ok-title="$t('core::app.save')"
        :ok-disabled="saveTemplateForm.busy"
        static-backdrop
        :title="$t('documents::document.template.save_as_template')"
        form
        @submit="saveContentAsTemplate"
        @keydown="saveTemplateForm.onKeydown($event)"
      >
        <IFormGroup
          label-for="name"
          :label="$t('documents::document.template.name')"
          required
        >
          <IFormInput
            ref="inputNameRef"
            v-model="saveTemplateForm.name"
            id="name"
          />
          <IFormError v-text="saveTemplateForm.getError('name')" />
        </IFormGroup>

        <IFormGroup>
          <IFormCheckbox
            id="is_shared"
            name="is_shared"
            v-model:checked="saveTemplateForm.is_shared"
            :label="$t('documents::document.template.share_with_team_members')"
          />
          <IFormError v-text="saveTemplateForm.getError('is_shared')" />
        </IFormGroup>
      </IModal>

      <IButtonIcon
        @click="showSettings = !showSettings"
        icon="AdjustmentsVertical"
        class="mr-2 mt-2 shrink-0 self-start"
      />

      <IButton
        @click="showSnippets"
        icon="Sparkles"
        size="sm"
        :disabled="document.status === 'accepted'"
        variant="white"
        class="shrink-0 self-start"
        :text="$t('core::contentbuilder.builder.Snippets')"
      />

      <IButton
        v-i-modal="'saveAsTemplateModal'"
        variant="white"
        v-show="form.content"
        size="sm"
        icon="Bookmark"
        class="shrink-0 self-start"
        :text="$t('documents::document.template.save_as_template')"
      />

      <div class="relative w-72">
        <ICustomSelect
          label="name"
          size="sm"
          :clearable="false"
          :loading="templatesAreBeingLoaded"
          :disabled="document.status === 'accepted'"
          :placeholder="$t('documents::document.template.insert_template')"
          :options="templatesForOptions"
          v-model="selectedTemplate"
          @option:selected="templateSelectedHandler"
        />
        <a
          v-show="!templatesAreBeingLoaded"
          href="#"
          @click.prevent.stop="loadTemplates"
          class="absolute right-9 top-2 mt-px text-neutral-400 hover:text-neutral-600 focus:outline-none"
        >
          <Icon icon="Refresh" class="h-4 w-4" />
        </a>
        <router-link
          target="_blank"
          :to="{ name: 'document-templates-index' }"
          class="link inline-flex items-center text-sm"
        >
          {{ $t('documents::document.template.manage') }}
          <Icon icon="ExternalLink" class="ml-2 h-4 w-4" />
        </router-link>
      </div>
    </div>

    <div
      v-show="showSettings"
      class="border-b border-neutral-200 pb-3 dark:border-neutral-600"
    >
      <h4
        class="mb-4 inline-flex w-full items-center border-b border-neutral-200 pb-2 text-sm font-medium text-neutral-700 dark:border-neutral-600 dark:text-neutral-100"
      >
        <Icon icon="Document" class="mr-1.5 h-4 w-4" />
        {{ $t('documents::pdf.settings') }}
      </h4>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          :label="$t('documents::pdf.padding')"
          for="pdf-padding"
          class="mt-2.5 w-32"
        />

        <div class="w-72">
          <IFormInput
            v-model="form.pdf.padding"
            id="pdf-padding"
            :placeholder="$t('documents::pdf.no_padding')"
          />

          <IFormError v-text="form.getError('pdf.padding')" />
        </div>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          :label="$t('documents::pdf.default_font')"
          for="pdf-font"
          class="mt-2.5 w-32"
        />

        <div class="w-72">
          <ICustomSelect
            input-id="pdf-font"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
            v-model="form.pdf.font"
            :options="fontNames"
          />
          <IFormError v-text="form.getError('pdf.font')" />
        </div>
        <span
          class="mt-2.5"
          v-i-tooltip="
            $t('documents::pdf.default_font_info', {
              fontName: 'DejaVu Sans',
            })
          "
        >
          <Icon icon="QuestionMarkCircle" class="h-5 w-5" />
        </span>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          :label="$t('documents::pdf.size')"
          for="pdf-size"
          class="mt-2.5 w-32"
        />

        <div class="w-72">
          <ICustomSelect
            input-id="pdf-size"
            v-model="form.pdf.size"
            :options="['a4', 'letter']"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
          />
          <IFormError v-text="form.getError('pdf.size')" />
        </div>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          :label="$t('documents::pdf.orientation')"
          for="pdf-orientation"
          class="mt-2.5 w-32"
        />

        <div class="w-72">
          <ICustomSelect
            input-id="pdf-orientation"
            v-model="form.pdf.orientation"
            :options="['portrait', 'landscape']"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
          />
          <IFormError v-text="form.getError('pdf.orientation')" />
        </div>
      </div>
    </div>

    <div class="mt-10">
      <IAlert class="mb-4" v-if="displayPlaceholdersMessage">
        {{ $t('documents::document.placeholders_replacement_info') }}
      </IAlert>

      <IAlert class="mb-4" v-if="displayProductsMissingMessage">
        <i18n-t
          scope="global"
          :keypath="'documents::document.products_snippet_missing'"
          tag="span"
          class="inline-flex"
        >
          <template #icon>
            <Icon icon="Plus" class="h-5 w-5" />
          </template>
        </i18n-t>
      </IAlert>

      <IAlert class="mb-4" v-if="displaySignaturesMissingMessage">
        <i18n-t
          scope="global"
          :keypath="'documents::document.signatures_snippet_missing'"
          tag="span"
          class="inline-flex"
        >
          <template #icon>
            <Icon icon="Plus" class="h-5 w-5" />
          </template>
        </i18n-t>
      </IAlert>

      <div
        class="prose prose-sm prose-neutral relative max-w-none dark:prose-invert"
      >
        <ContentBuilder
          ref="builderRef"
          v-model="form.content"
          :disabled="document.status === 'accepted'"
          :placeholders="placeholders"
        />
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import propsDefinition from './formSectionProps'
import { whenever } from '@vueuse/core'
import find from 'lodash/find'
import omit from 'lodash/omit'
import sortBy from 'lodash/sortBy'
import ContentBuilder from '~/Core/resources/js/components/ContentBuilder/ContentBuilder.vue'
import { addGoogleFontsStyle } from '~/Core/resources/js/components/ContentBuilder/utils'
import { useForm } from '~/Core/resources/js/composables/useForm'
import IAlert from '~/Core/resources/js/components/UI/IAlert.vue'

const props = defineProps({
  ...propsDefinition,
  ...{ isReady: { default: true, type: Boolean } },
})

const fonts = Innoclapps.config('contentbuilder.fonts')
const fontNames = computed(() => fonts.map(font => font['font-family']))

const builderRef = ref(null)
const inputNameRef = ref(null)
const templates = ref([])
const selectedTemplate = ref(null)
const templatesLoaded = ref(false)
const templatesAreBeingLoaded = ref(false)
const placeholders = Innoclapps.config('documents.placeholders')
const showSettings = ref(false)

const { form: saveTemplateForm } = useForm({
  name: '',
  content: '',
  is_shared: false,
})

watch(
  () => props.document.updated_at,
  () => {
    if (props.visible) {
      addUsedDocumentGoogleFonts()
    }
  }
)

whenever(
  () => props.visible,
  () => {
    !templatesLoaded.value && loadTemplates()
    addUsedDocumentGoogleFonts()
  },
  { immediate: true }
)

const contentHasPlaceholders = computed(
  () => props.form.content && props.form.content.indexOf('{{ ') > -1
)

const contentContainsPlaceholdersFromResources = computed(
  () =>
    props.form.content && props.form.content.match(/(contact.|deal.|company.)/)
)

const hasAssociations = computed(() => {
  let val = false

  Object.keys(props.document.associations || {}).forEach(resource => {
    if (props.document.associations[resource]?.length > 0) {
      val = true
    }
  })

  return val
})

const displayProductsMissingMessage = computed(
  () =>
    props.form.billable.products.length > 0 &&
    (!props.form.content ||
      props.form.content.indexOf('products-section') === -1)
)

const displaySignaturesMissingMessage = computed(
  () =>
    props.form.signers.length > 0 &&
    (!props.form.content ||
      props.form.content.indexOf('signatures-section') === -1)
)

const displayPlaceholdersMessage = computed(
  () =>
    props.isReady &&
    props.document.id &&
    !hasAssociations.value &&
    contentHasPlaceholders.value &&
    contentContainsPlaceholdersFromResources.value
)

// Removes content for performance reasons e.q. avoid watching long contents
const templatesForOptions = computed(() =>
  sortBy(templates.value, ['name', 'asc']).map(t => omit(t, ['content']))
)

function addUsedDocumentGoogleFonts() {
  addGoogleFontsStyle(props.document.google_fonts || [])
}

function showSnippets() {
  builderRef.value.viewSnippets()
}

function templatesModalShownHandler() {
  inputNameRef.value.focus()
  saveTemplateForm.content = props.form.content
}

/**
 * Save the current content as template
 */
function saveContentAsTemplate() {
  saveTemplateForm.post('/document-templates').then(template => {
    templates.value.push(template)
    Innoclapps.modal().hide('saveAsTemplateModal')
  })
}

/**
 * Template selected handler
 */
function templateSelectedHandler(option) {
  addGoogleFontsStyle(option.google_fonts)

  if (props.form.content === null) {
    props.form.content = ''
  }

  let template = find(templates.value, ['id', option.id])
  props.form.content += template.content

  if (template.view_type) {
    props.form.view_type = template.view_type
  }

  setTimeout(() => (selectedTemplate.value = null), 500)
}

/**
 * Fetch the document templates
 */
function loadTemplates() {
  templatesAreBeingLoaded.value = true
  Innoclapps.request()
    .get('document-templates', {
      params: { per_page: 100 },
    })
    .then(({ data: pagination }) => {
      templates.value = pagination.data
      templatesLoaded.value = true
    })
    .finally(() => (templatesAreBeingLoaded.value = false))
}

defineExpose({
  builderRef,
})
</script>
<style>
body:not(.document-section-content) #divSnippetHandle {
  display: none;
}
</style>
