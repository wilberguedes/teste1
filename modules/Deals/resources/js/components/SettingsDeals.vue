<template>
  <div class="space-y-3">
    <PipelineIndex></PipelineIndex>

    <ICard no-body :header="$t('deals::deal.deals')" :overlay="!componentReady">
      <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
        <li class="px-4 py-4 sm:px-6">
          <div
            class="space-x-0 space-y-3 md:flex md:items-center md:justify-between md:space-y-0 lg:space-x-3"
          >
            <div>
              <h5
                class="font-medium leading-relaxed text-neutral-700 dark:text-neutral-100"
                v-t="'deals::deal.settings.allow_lost_reason_enter'"
              />
              <p
                class="text-sm text-neutral-600 dark:text-neutral-300"
                v-t="'deals::deal.settings.allow_lost_reason_enter_info'"
              />
            </div>
            <div>
              <IFormToggle
                :value="true"
                :unchecked-value="false"
                @change="submit"
                v-model="form.allow_lost_reason_enter"
              />
            </div>
          </div>
        </li>
        <li class="px-4 py-4 sm:px-6">
          <div
            class="space-x-0 space-y-3 md:flex md:items-center md:justify-between md:space-y-0 lg:space-x-3"
          >
            <div>
              <h5
                class="font-medium leading-relaxed text-neutral-700 dark:text-neutral-100"
                v-t="'deals::deal.settings.lost_reason_is_required'"
              />
              <p
                class="text-sm text-neutral-600 dark:text-neutral-300"
                v-t="'deals::deal.settings.lost_reason_is_required_info'"
              />
            </div>
            <div>
              <IFormToggle
                :value="true"
                :unchecked-value="false"
                @change="submit"
                v-model="form.lost_reason_is_required"
              />
            </div>
          </div>
        </li>
      </ul>
    </ICard>

    <LostReasonsIndex></LostReasonsIndex>
  </div>
</template>
<script setup>
import PipelineIndex from '../views/DealPipelineIndex.vue'
import LostReasonsIndex from '../views/DealLostReasonsIndex.vue'
import { useSettings } from '~/Core/resources/js/views/Settings/useSettings'

const { form, submit, isReady: componentReady } = useSettings()
</script>
