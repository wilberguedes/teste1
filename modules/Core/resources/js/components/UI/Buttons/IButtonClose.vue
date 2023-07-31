<template>
  <IButton :icon="icon" />
</template>
<script setup>
defineProps({
  icon: { default: 'X', type: String },
})
</script>
