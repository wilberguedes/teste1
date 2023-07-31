<template>
  <div v-show="visible" class="relative">
    <IButtonIcon
      icon="X"
      @click="$emit('update:visible', false)"
      class="absolute right-4 top-5 hidden sm:block"
    />
    <ITabGroup>
      <ITabList>
        <ITab
          v-for="(group, groupName) in placeholders"
          :key="groupName"
          :title="group.label"
        />
      </ITabList>
      <ITabPanels>
        <ITabPanel v-for="(group, groupName) in placeholders" :key="groupName">
          <PlaceholdersList
            :placeholders="group.placeholders"
            @insert-requested="insertPlaceholder($event, group.label)"
          >
            <IButtonIcon
              icon="X"
              @click="$emit('update:visible', false)"
              class="text-right sm:hidden"
            />
          </PlaceholdersList>
        </ITabPanel>
      </ITabPanels>
    </ITabGroup>
  </div>
</template>
<script setup>
import PlaceholdersList from './MailEditorPlacehodersList.vue'

const emit = defineEmits(['update:visible', 'inserted'])

defineProps({
  placeholders: { type: Object },
  visible: { type: Boolean, default: true },
})

function insertPlaceholder(placeholder, groupLabel) {
  let html = ''

  if (!placeholder.newlineable) {
    html = `<input type="text"
                        class="_placeholder"
                        data-tag="${placeholder.tag}"
                        placeholder="${placeholder.description} (${groupLabel})" /> `
  } else {
    html = `<textarea class="_placeholder"
                        rows="1"
                        data-tag="${placeholder.tag}"
                        placeholder="${placeholder.description} (${groupLabel})"></textarea> `
  }

  tinymce.activeEditor.execCommand('mceInsertContent', false, html)

  // Emits before the editor content is changed
  // in this case, add timeout
  setTimeout(() => emit('inserted'), 500)
}
</script>
