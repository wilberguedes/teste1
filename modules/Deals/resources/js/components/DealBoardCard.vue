<template>
  <div
    v-if="swatchColor"
    class="bottom-hidden p-2"
    :style="{ background: swatchColor }"
  />
  <div
    class="bottom-hidden group px-4 py-2"
    :class="{
      'bg-danger-50/50': fallsBehindExpectedCloseDate,
      'opacity-50': status !== 'open',
    }"
  >
    <div class="flex">
      <div class="grow truncate">
        <a
          class="truncate text-sm font-medium text-neutral-900 focus:outline-none hover:text-neutral-500 dark:text-white"
          :href="path"
          @click.prevent="preview(dealId)"
          v-text="displayName"
        />
        <div class="mt-1 flex text-sm">
          <p
            :class="{
              'text-warning-500': incompleteActivitiesCount > 0,
              'text-success-500': incompleteActivitiesCount === 0,
            }"
            v-text="
              $t('activities::activity.count', incompleteActivitiesCount, {
                count: incompleteActivitiesCount,
              })
            "
          />
          <p
            v-if="amount"
            class="ml-2 text-neutral-700 dark:text-neutral-300"
            v-text="formatMoney(amount)"
          />
        </div>
        <p
          class="mt-1 text-xs text-neutral-700 dark:text-neutral-300"
          v-show="expectedCloseDate"
          v-text="localizedDate(expectedCloseDate)"
        />
      </div>
      <div>
        <div class="flex flex-col items-center space-y-1">
          <IMinimalDropdown>
            <IDropdownItem
              @click="$emit('create-activity-requested', dealId)"
              icon="Clock"
              :text="$t('activities::activity.create')"
            />
            <IDropdownItem
              @click="$router.push(path)"
              icon="Eye"
              :text="$t('core::app.view_record')"
            />
            <IDropdownItem
              @click="preview(dealId)"
              icon="Bars3CenterLeft"
              :text="$t('core::app.preview')"
            />
          </IMinimalDropdown>
          <IPopover class="w-56 max-w-xs" auto-placement>
            <IButtonIcon
              icon="ColorSwatch"
              class="opacity-100 md:opacity-0 md:group-hover:opacity-100"
            />

            <template #popper>
              <div class="px-4 py-3">
                <IColorSwatches
                  v-model="swatchColor"
                  :swatches="swatches"
                  :allow-custom="false"
                  @input="$emit('swatch-color-updated', $event)"
                />
              </div>
            </template>
          </IPopover>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useStore } from 'vuex'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'

defineEmits(['create-activity-requested', 'swatch-color-updated'])

const props = defineProps({
  dealId: { required: true, type: Number },
  displayName: { required: true, type: String },
  amount: { required: true, type: [String, Number] },
  path: { required: true, type: String },
  status: { required: true, type: String },
  incompleteActivitiesCount: { required: true, type: Number },
  expectedCloseDate: String,
  initialSwatchColor: String,
  fallsBehindExpectedCloseDate: Boolean,
})

const swatches = Innoclapps.config('favourite_colors')

const store = useStore()
const { localizedDate } = useDates()
const { formatMoney } = useAccounting()

const swatchColor = ref(props.initialSwatchColor)

function preview(id) {
  store.commit('recordPreview/SET_PREVIEW_RESOURCE', {
    resourceName: 'deals',
    resourceId: id,
  })
}
</script>
