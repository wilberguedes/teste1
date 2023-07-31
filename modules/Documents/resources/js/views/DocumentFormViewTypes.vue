<template>
  <RadioGroup v-model="type">
    <RadioGroupLabel class="sr-only">Select HTML view type</RadioGroupLabel>

    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-3 sm:gap-x-4">
      <RadioGroupOption
        as="template"
        v-for="viewType in types"
        :key="viewType.id"
        :value="viewType.id"
        v-slot="{ checked, active }"
      >
        <div
          :class="[
            checked
              ? 'border-transparent'
              : 'border-neutral-300 dark:border-neutral-700',
            active ? 'border-primary-500 ring-2 ring-primary-500' : '',
            'relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none dark:bg-neutral-800',
          ]"
        >
          <span class="flex flex-1">
            <span class="flex flex-col">
              <RadioGroupLabel
                as="span"
                class="block text-sm font-medium text-neutral-900 dark:text-white"
              >
                {{ viewType.title }}
              </RadioGroupLabel>
              <RadioGroupDescription
                as="span"
                class="mt-1 flex items-center text-sm text-neutral-500 dark:text-neutral-300"
              >
                {{ viewType.description }}
              </RadioGroupDescription>
              <RadioGroupDescription class="mt-auto">
                <div
                  class="mt-2 h-[104px] w-full overflow-hidden rounded border border-neutral-200 bg-white dark:border-neutral-600 dark:bg-neutral-400"
                >
                  <div v-if="viewType.id === 'nav-top'" class="flex flex-col">
                    <div
                      class="h-5 w-full bg-neutral-100 dark:bg-neutral-300"
                    />
                    <div class="mt-1 space-y-1 px-5 py-2">
                      <div
                        class="h-3 w-1/3 rounded bg-neutral-200 dark:bg-neutral-300"
                      />
                      <div
                        class="h-3 w-full rounded bg-neutral-100 dark:bg-neutral-200"
                      />
                      <div
                        class="mt h-3 w-1/2 rounded bg-neutral-100 dark:bg-neutral-200"
                      />
                      <div
                        class="mt h-3 w-full rounded bg-neutral-100 dark:bg-neutral-200"
                      />
                    </div>
                  </div>
                  <div v-else-if="viewType.id === 'nav-left'" class="flex">
                    <div class="h-[102px] w-6 bg-neutral-200" />
                    <div class="mt-1 flex-1 space-y-1 p-2">
                      <div class="h-3 w-1/3 rounded bg-neutral-200" />
                      <div class="h-3 w-full rounded bg-neutral-100" />
                      <div class="mt h-3 w-1/2 rounded bg-neutral-100" />
                      <div class="mt h-3 w-full rounded bg-neutral-100" />
                      <div class="h-3 w-1/3 rounded bg-neutral-100" />
                    </div>
                  </div>
                  <div
                    v-else-if="viewType.id === 'nav-left-full-width'"
                    class="flex"
                  >
                    <div
                      class="h-[102px] w-6 bg-neutral-200 dark:bg-neutral-200"
                    />
                    <div class="flex-1">
                      <div class="flex flex-col">
                        <div class="flex">
                          <div
                            class="flex h-8 w-full flex-col items-center justify-center space-y-1 bg-neutral-100 p-2.5 dark:bg-neutral-300"
                          >
                            <div
                              class="h-1.5 w-1/2 shrink-0 rounded bg-neutral-200"
                            />
                            <div
                              class="h-1.5 w-full shrink-0 rounded bg-neutral-200"
                            />
                          </div>
                          <div
                            class="flex h-8 w-full flex-col items-center justify-center space-y-1 bg-neutral-100 p-2.5 dark:bg-neutral-300"
                          >
                            <div
                              class="h-1.5 w-1/2 shrink-0 rounded bg-neutral-200"
                            />
                            <div
                              class="h-1.5 w-full shrink-0 rounded bg-neutral-200"
                            />
                          </div>
                        </div>
                        <div
                          class="flex h-12 w-full flex-col items-center justify-center space-y-1 bg-neutral-300 p-2.5 dark:bg-neutral-400"
                        >
                          <div
                            class="h-1.5 w-1/2 shrink-0 rounded bg-neutral-200 dark:bg-neutral-300"
                          />
                          <div
                            class="h-1.5 w-full shrink-0 rounded bg-neutral-200 dark:bg-neutral-300"
                          />
                          <div
                            class="h-1.5 w-full shrink-0 rounded bg-neutral-200 dark:bg-neutral-300"
                          />
                        </div>
                        <div
                          class="flex h-[22px] w-full flex-col justify-center space-y-1 bg-neutral-200/70 p-2"
                        >
                          <div
                            class="h-1.5 w-1/2 shrink-0 rounded bg-neutral-200"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </RadioGroupDescription>
            </span>
          </span>
          <Icon
            icon="CheckCircle"
            :class="[
              !checked ? 'invisible' : '',
              'h-5 w-5 shrink-0 text-primary-600',
            ]"
          />
          <span
            :class="[
              active ? 'border' : 'border-2',
              checked ? 'border-primary-500' : 'border-transparent',
              'pointer-events-none absolute -inset-px rounded-lg',
            ]"
            aria-hidden="true"
          />
        </div>
      </RadioGroupOption>
    </div>
  </RadioGroup>
</template>
<script setup>
import {
  RadioGroup,
  RadioGroupDescription,
  RadioGroupLabel,
  RadioGroupOption,
} from '@headlessui/vue'
import { useI18n } from 'vue-i18n'
import { useVModel } from '@vueuse/core'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({ modelValue: String })

const { t } = useI18n()

const types = [
  {
    id: 'nav-top',
    title: t('documents::document.view_type.nav_top.name'),
    description: t('documents::document.view_type.nav_top.description'),
  },
  {
    id: 'nav-left',
    title: t('documents::document.view_type.nav_left.name'),
    description: t('documents::document.view_type.nav_left.description', {
      headingTagName: Innoclapps.config(
        'documents.navigation_heading_tag_name'
      ),
    }),
  },
  {
    id: 'nav-left-full-width',
    title: t('documents::document.view_type.nav_left_full_width.name'),
    description: t(
      'documents::document.view_type.nav_left_full_width.description',
      {
        headingTagName: Innoclapps.config(
          'documents.navigation_heading_tag_name'
        ),
      }
    ),
  },
]

const type = useVModel(props, 'modelValue', emit)
</script>
