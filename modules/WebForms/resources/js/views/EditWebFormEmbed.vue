<template>
  <div>
    <h3
      class="mb-3 inline-flex items-center text-lg/6 font-semibold text-neutral-700 dark:text-neutral-100"
    >
      Share via link
      <IButtonCopy
        class="ml-3"
        :text="form.public_url"
        :success-message="$t('core::app.copied')"
        v-i-tooltip="$t('core::app.copy')"
      />
    </h3>
    <div
      class="select-all rounded-md border border-neutral-200 bg-neutral-100 p-2 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
    >
      {{ form.public_url }}
    </div>

    <h3
      class="mb-3 mt-8 text-lg/6 font-semibold text-neutral-700 dark:text-neutral-100"
    >
      Embed the form on your Website
    </h3>

    <ul class="ml-5 list-disc leading-7 text-neutral-900 dark:text-neutral-200">
      <li>Copy the code snippet below</li>
      <li>
        Paste the code right where you want the form to appear in your template
        or CMS editor
      </li>
    </ul>
    <p class="mb-2 mt-4 text-neutral-900 dark:text-neutral-200">
      <em class="font-italic"
        >* When entering the snippet to your CMS, make sure you are in “code
        editing” mode.</em
      >
    </p>
    <p class="mb-2 mt-4 text-neutral-900 dark:text-neutral-200">
      <em class="font-italic">
        * You must place the iframe snippet on a website that uses the same
        protocol like your installation, for example, if the current
        installation uses https:// URL protocol, you will need to add the iframe
        in a website that uses https:// URL protocol, adding on a non-https url,
        will prevent the form from loading.
      </em>
    </p>
    <div
      class="mt-3 select-all rounded-md border border-neutral-200 bg-neutral-100 p-2 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
    >
      {{ embedText }}
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps(['form'])

const embedText = computed(() => {
  return `<iframe src="${props.form.public_url}?e=true" frameborder="0" width="700" height="500" sandbox="allow-top-navigation allow-scripts allow-forms allow-same-origin"></iframe>`
})
</script>
