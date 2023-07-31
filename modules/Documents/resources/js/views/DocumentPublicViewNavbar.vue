<template>
  <div
    class="sticky top-0 z-50 drop-shadow-[0_1.5px_1.5px_rgba(0,0,0,0.15)]"
    :style="{ backgroundColor: backgroundColor }"
  >
    <div class="container mx-auto">
      <div class="mx-auto max-w-6xl">
        <div class="px-4 py-4">
          <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
              <a :href="publicUrl" class="focus:outline-none">
                <img
                  v-if="logo"
                  :src="logo"
                  :alt="brandName"
                  class="h-12 w-auto"
                />
                <Icon v-else icon="Document" class="h-8 w-8 text-neutral-400" />
              </a>
            </div>
            <div class="flex space-x-3 md:ml-3">
              <slot name="actions"></slot>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="py-6">
    <div class="container mx-auto">
      <div class="mx-auto max-w-6xl">
        <slot name="content"></slot>
      </div>
    </div>
  </div>
</template>
<script setup>
defineProps(['publicUrl', 'brandName', 'logo', 'backgroundColor'])
</script>
