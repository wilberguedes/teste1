<template>
  <IFormGroup>
    <MediaUpload
      v-model="files"
      :drop="true"
      :multiple="section.multiple"
      :show-upload-button="false"
      :automatic-upload="false"
      :icon="false"
      :name="section.requestAttribute"
      :input-id="section.requestAttribute"
      style-classes="group block rounded-md border border-dashed border-neutral-300 dark:border-neutral-400 w-full py-4 sm:py-5 hover:border-neutral-400 cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-700/60 font-medium"
      wrapper-classes=""
    >
      <template #upload-text>
        <div class="flex flex-col items-center">
          <Icon
            icon="CloudArrowUp"
            class="h-7 w-7 text-neutral-600 dark:text-neutral-300 dark:group-hover:text-white"
          />
          <p
            class="mt-1 max-w-sm text-sm text-neutral-700 dark:text-neutral-100 dark:group-hover:text-white"
            v-html="section.label"
          />
        </div>
      </template>
    </MediaUpload>
    <IFormError v-text="form.getError(section.requestAttribute)" />
  </IFormGroup>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import MediaUpload from '~/Core/resources/js/components/Media/MediaUpload.vue'
import propsDefinition from './props'

const props = defineProps(propsDefinition)

props.form.set(
  props.section.requestAttribute,
  props.section.multiple ? [] : null
)

const files = ref([])
const totalFiles = computed(() => files.value.length)

watch(totalFiles, () => {
  const attribute = props.section.requestAttribute
  if (files.value.length === 0) {
    props.form.fill(attribute, props.section.multiple ? [] : null)
  } else {
    if (props.section.multiple) {
      props.form.fill(
        attribute,
        files.value.map(file => file.file)
      )
    } else {
      props.form.fill(attribute, files.value[0].file)
    }
  }
})
</script>
