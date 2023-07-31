<template>
  <div
    class="absolute inset-0 z-40 h-full max-h-full min-h-screen w-full bg-white dark:bg-neutral-900"
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
                class="link text-sm sm:text-base"
                @click.prevent="$emit('exit-requested')"
                v-t="'core::app.exit'"
              />

              <IFormSelect
                class="block sm:hidden"
                size="sm"
                :modelValue="activeSection"
                @update:modelValue="updateActiveSection($event)"
              >
                <option
                  v-for="section in sections"
                  :key="section.id"
                  :value="section.id"
                >
                  {{ section.name }}
                </option>
              </IFormSelect>

              <div class="hidden justify-center sm:flex">
                <a
                  v-for="(section, index) in sections"
                  :key="section.id"
                  href="#"
                  @click.prevent="updateActiveSection(section.id)"
                  :class="[
                    'flex items-center focus:outline-none',
                    section.id === activeSection
                      ? 'link'
                      : 'text-neutral-800 hover:text-neutral-600 dark:text-neutral-300 dark:hover:text-neutral-400',
                    index !== sections.length - 1 ? 'mr-6 space-x-4' : '',
                  ]"
                >
                  <div class="inline-flex">
                    <span v-text="section.name"></span>
                    <IBadge
                      v-show="section.badge"
                      :variant="section.badgeVariant || 'primary'"
                      size="circle"
                      wrapper-class="ml-1.5 -mt-px"
                    >
                      {{ section.badge }}
                    </IBadge>
                  </div>

                  <Icon
                    icon="ChevronRight"
                    v-if="index !== sections.length - 1"
                    class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400 dark:text-neutral-500"
                  />
                </a>
              </div>

              <slot name="actions"></slot>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- navbar end -->
    <div class="h-full min-h-full overflow-y-auto">
      <div class="px-4 py-6 sm:px-0">
        <div style="padding-bottom: 200px">
          <slot></slot>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, watch, onBeforeMount, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

const emit = defineEmits(['exit-requested', 'update:active-section'])

const props = defineProps({
  activeSection: { required: true, type: String },
  totalProducts: Number,
  totalSigners: Number,
  remainingSigners: Number,
})

const { t } = useI18n()

const sections = computed(() => [
  { name: t('documents::document.sections.details'), id: 'details' },
  {
    name: t('documents::document.sections.products'),
    id: 'products',
    badge: props.totalProducts,
  },
  {
    name: t('documents::document.sections.signature'),
    id: 'signature',
    // On create, show primary badge with total signers
    // On edit, if all signed, show success badge with total signers
    // On edit, if not all signed, show warning badge with total left to sign
    badge:
      props.totalSigners > 0 && props.remainingSigners > 0
        ? props.remainingSigners
        : props.totalSigners,
    badgeVariant:
      props.remainingSigners > 0
        ? 'warning'
        : props.remainingSigners === 0 && props.totalSigners > 0
        ? 'success'
        : 'primary',
  },
  { name: t('documents::document.sections.content'), id: 'content' },
  { name: t('documents::document.sections.send'), id: 'send' },
])

function updateActiveSection(section) {
  emit('update:active-section', section)
}

onBeforeMount(() => {
  document.body.classList.add('overflow-y-hidden')
})

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-y-hidden')
  document.body.classList.remove('document-section-' + props.activeSection)
})

watch(
  () => props.activeSection,
  (newVal, oldVal) => {
    document.body.classList.remove('document-section-' + oldVal)
    document.body.classList.add('document-section-' + newVal)
  },
  { immediate: true }
)
</script>
