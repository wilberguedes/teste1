<template>
  <FieldFormGroup
    :field="field"
    :label="label"
    :field-id="fieldId"
    :form-id="formId"
  >
    <IAlert v-if="!hasStages" class="mb-2">
      {{ $t('deals::deal.pipeline.missing_stages') }}
    </IAlert>

    <ICustomSelect
      :class="!hasStages ? 'hidden' : ''"
      v-model="value"
      :input-id="fieldId"
      :disabled="isReadonly"
      :filterable="filterable"
      :options="options"
      :loading="lazyLoadingOptions"
      :name="field.attribute"
      :label="field.labelKey"
      v-bind="field.attributes"
      @search="onSearch"
      @open="onDropdownOpen"
    >
      <template #no-options>{{ noOptionsText }}</template>

      <template #header>
        <span
          v-show="headerText"
          class="block px-3 py-2 text-sm text-neutral-500 dark:text-neutral-200"
        >
          {{ headerText }}
        </span>
      </template>
    </ICustomSelect>
  </FieldFormGroup>
</template>
<script setup>
import { ref, toRef, computed } from 'vue'
import FieldFormGroup from '~/Core/resources/js/components/Fields/FieldFormGroup.vue'
import propsDefinition from '~/Core/resources/js/components/Fields/props'
import { useSelectField } from '~/Core/resources/js/components/Fields/useSelectField'
import { useGlobalEventListener } from '~/Core/resources/js/composables/useGlobalEventListener'

const props = defineProps(propsDefinition)

const value = ref('')

const {
  field,
  label,
  fieldId,
  isReadonly,
  onSearch,
  onDropdownOpen,
  filterable,
  options,
  setOptions,
  lazyLoadingOptions,
  noOptionsText,
  headerText,
  prepareValue,
} = useSelectField(
  value,
  toRef(props, 'field'),
  props.formId,
  toRef(props, 'isFloating'),
  {
    // Initial options
    options: getOnlyRelatedStages(props.field.value?.pipeline_id),
  }
)

const hasStages = computed(() => options.value.length > 0)

/**
 * Get only stages that are related to the given pipeline
 */
function getOnlyRelatedStages(pipelineId) {
  return props.field.options.filter(
    stage => stage[props.field.dependsOn] == pipelineId
  )
}

/**
 * Handle the pipeline_id changed event
 */
function pipelineIdValueChangedHandler(pipeline) {
  // When the pipeline id is changed, we will set stages from the selected pipeline only
  setOptions(getOnlyRelatedStages(pipeline.id))

  // Make first stage selected
  value.value = prepareValue(pipeline.stages[0])
}

useGlobalEventListener(
  'field-pipeline_id-value-changed',
  pipelineIdValueChangedHandler
)
</script>
