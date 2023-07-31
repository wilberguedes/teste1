<template>
  <ICustomSelect
    v-if="(!manualLostReason && lostReasons.length > 0) || !allowCustom"
    :options="lostReasons"
    :input-id="manualLostReason ? `${attribute}-hidden` : attribute"
    @update:modelValue="$emit('update:modelValue', $event ? $event.name : null)"
    label="name"
  />

  <div v-show="manualLostReason">
    <IFormTextarea
      :modelValue="modelValue"
      :id="!manualLostReason ? `${attribute}-hidden` : attribute"
      @update:modelValue="$emit('update:modelValue', $event)"
      rows="2"
    />
  </div>

  <IFormText
    v-if="lostReasons.length > 0 && allowCustom"
    class="mt-2 inline-flex items-center space-x-1"
  >
    <a
      href="#"
      tabindex="-1"
      class="focus:outline-none"
      @click.prevent="manualLostReason = !manualLostReason"
      v-t="
        `deals::deal.lost_reasons.${
          manualLostReason
            ? 'choose_lost_reason'
            : 'choose_lost_reason_or_enter'
        }`
      "
    />
    <a
      href="#"
      class="link"
      @click.prevent="manualLostReason = !manualLostReason"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M13 7l5 5m0 0l-5 5m5-5H6"
        />
      </svg>
    </a>
  </IFormText>
</template>
<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { useLostReasons } from '../composables/useLostReasons'
const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: String,
  allowCustom: {
    type: Boolean,
    default() {
      return Innoclapps.config('options.allow_lost_reason_enter')
    },
  },
  attribute: { default: 'lost_reason', type: String },
})

const manualLostReason = ref(false)

const { lostReasonsByName: lostReasons } = useLostReasons()

if (lostReasons.value.length === 0 && props.allowCustom) {
  manualLostReason.value = true
}

onMounted(() => {
  nextTick(() => {
    if (props.modelValue) {
      manualLostReason.value = true
    }
  })
})
</script>
