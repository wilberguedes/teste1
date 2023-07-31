<template>
  <div>
    <SearchInput v-model="search" />

    <div class="mt-4 flex flex-wrap">
      <div
        v-for="placeholder in filteredPlaceholders"
        :key="placeholder.tag"
        class="relative mb-1 mr-1 flex items-center rounded border border-neutral-200 px-4 py-1 dark:border-neutral-700 dark:hover:border-neutral-600"
      >
        <a
          href="#"
          v-i-tooltip.top="
            `${placeholder.interpolation_start} ${placeholder.tag} ${placeholder.interpolation_end}`
          "
          :data-placeholder="`${placeholder.interpolation_start} ${placeholder.tag} ${placeholder.interpolation_end}`"
          @dragstart="onDragStart"
          :class="[
            'mr-1 text-sm text-neutral-600 hover:text-neutral-900 dark:text-neutral-200 dark:hover:text-neutral-400',
            {
              'pointer-events-none':
                justInsertedPlaceholder &&
                justInsertedPlaceholder.tag === placeholder.tag,
            },
          ]"
          @click.prevent="requestInsert(placeholder)"
          @mouseup.right="copyPlaceholder(placeholder)"
          @contextmenu.prevent
          v-text="placeholder.description"
        />
        <Icon
          v-show="
            justInsertedPlaceholder &&
            justInsertedPlaceholder.tag === placeholder.tag
          "
          icon="Check"
          class="absolute right-1 top-2 h-3 w-3 rounded-full text-neutral-600 dark:text-neutral-100"
        />
      </div>
      <slot></slot>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useClipboard } from '@vueuse/core'

const emit = defineEmits(['insert-requested'])
const props = defineProps(['placeholders'])

const search = ref(null)
const justInsertedPlaceholder = ref(null)

const { copy, isSupported } = useClipboard({
  source: '',
  legacy: true,
})

function copyPlaceholder(placeholder) {
  if (isSupported.value) {
    copy(`{{ ${placeholder.tag} }}`)

    Innoclapps.info('Placeholder copied')
  }
}

const filteredPlaceholders = computed(() => {
  if (!search.value) {
    return props.placeholders
  }

  return props.placeholders.filter(
    placeholder =>
      placeholder.description
        .toLowerCase()
        .includes(search.value.toLowerCase()) ||
      placeholder.tag.toLowerCase().includes(search.value.toLowerCase())
  )
})

function requestInsert(placeholder) {
  search.value = null
  justInsertedPlaceholder.value = placeholder
  emit('insert-requested', placeholder)
  setTimeout(() => (justInsertedPlaceholder.value = null), 3000)
}

function onDragStart(e) {
  e.dataTransfer.setData('text/plain', e.target.dataset.placeholder)
}
</script>
