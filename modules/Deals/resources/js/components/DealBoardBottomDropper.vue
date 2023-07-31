<template>
  <div
    style="bottom: 0; right: 0"
    class="h-dropper fixed left-0 border border-neutral-300 bg-white shadow-sm dark:border-neutral-900 dark:bg-neutral-900 sm:left-56"
  >
    <IModal
      size="sm"
      id="markAsLostModal"
      :title="$t('deals::deal.actions.mark_as_lost')"
      form
      @submit="markAsLost(markingAsLostID)"
      :ok-disabled="markAsLostForm.busy"
      :ok-title="$t('deals::deal.actions.mark_as_lost')"
      ok-variant="danger"
      @hidden="markAsLostModalHidden"
    >
      <IFormGroup
        :label="$t('deals::deal.lost_reasons.lost_reason')"
        label-for="lost_reason"
        :optional="!setting('lost_reason_is_required')"
        :required="setting('lost_reason_is_required')"
      >
        <LostReasonField v-model="markAsLostForm.lost_reason" />
        <IFormError v-text="markAsLostForm.getError('lost_reason')" />
      </IFormGroup>
    </IModal>
    <div class="flex justify-end">
      <div
        class="h-dropper relative w-1/3 border-t-2 border-neutral-800 sm:w-1/5"
      >
        <draggable
          :modelValue="[]"
          :item-key="item => item.id"
          @change="movedToDelete"
          class="h-dropper dropper-delete dropper"
          :group="{ name: 'delete', put: true, pull: false }"
        >
          <template #item="{ element }"><div></div></template>
          <template #header>
            <div
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-800 dark:text-neutral-200"
              v-t="'core::app.delete'"
            />
          </template>
        </draggable>
      </div>
      <div
        class="h-dropper relative w-1/3 border-t-2 border-danger-500 sm:w-1/5"
      >
        <draggable
          @change="movedToLost"
          :modelValue="[]"
          :item-key="item => item.id"
          class="h-dropper dropper-lost dropper"
          :group="{ name: 'lost', put: true, pull: false }"
        >
          <template #item="{ element }"><div></div></template>
          <template #header>
            <div
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-800 dark:text-neutral-200"
              v-t="'deals::deal.status.lost'"
            />
          </template>
        </draggable>
      </div>
      <div
        class="h-dropper relative w-1/3 border-t-2 border-success-500 sm:w-1/5"
      >
        <draggable
          @change="movedToWon"
          :modelValue="[]"
          :item-key="item => item.id"
          class="h-dropper dropper-won dropper"
          :group="{ group: 'won', put: true, pull: false }"
        >
          <template #item="{ element }"><div></div></template>
          <template #header>
            <div
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-800 dark:text-neutral-200"
              v-t="'deals::deal.status.won'"
            />
          </template>
        </draggable>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
// https://stackoverflow.com/questions/51619243/vue-draggable-delete-item-by-dragging-into-designated-region
import draggable from 'vuedraggable'
import LostReasonField from './DealLostReasonField.vue'
import { throwConfetti } from '@/utils'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useForm } from '~/Core/resources/js/composables/useForm'

const emit = defineEmits(['deleted', 'won', 'refresh-requested'])

const props = defineProps({
  resourceId: { required: true },
})

const { setting } = useApp()

const markingAsLostID = ref(null)
const { form: markAsLostForm } = useForm({ lost_reason: null })

/**
 * Handle deal moved to delete dropper
 *
 * @param  {Object} e
 *
 * @return {Void}
 */
function movedToDelete(e) {
  if (e.added) {
    Innoclapps.request()
      .delete(`/deals/${e.added.element.id}`)
      .catch(() => {
        requestRefresh()
      })
      .then(() => emit('deleted', e.added.element))
  }
}

/**
 * Request board refresh
 *
 * @return {Void}
 */
function requestRefresh() {
  emit('refresh-requested')
}

/**
 * Handle deal moved to lost dropper
 *
 * @param  {Object} e
 *
 * @return {Void}
 */
function movedToLost(e) {
  if (e.added) {
    markingAsLostID.value = e.added.element.id
    Innoclapps.modal().show('markAsLostModal')
  }
}

/**
 * Handle the mark as lost modal hidden event
 *
 * @return {Void}
 */
function markAsLostModalHidden() {
  markAsLostForm.lost_reason = null
  markingAsLostID.value = null
  requestRefresh()
}

/**
 * Mark the deal as lost
 *
 * @param {Integer} id
 *
 * @return {Void}
 */
function markAsLost(id) {
  markAsLostForm
    .put(`/deals/${id}/status/lost`)
    .then(() => Innoclapps.modal().hide('markAsLostModal'))
}

/**
 * Mark the deal as lost
 *
 * @param {Integer} id
 *
 * @return {Void}
 */
function markAsWon(id) {
  Innoclapps.request()
    .put(`/deals/${id}/status/won`)
    .then(({ data }) => {
      throwConfetti()
      emit('won', data)
      requestRefresh()
    })
    .catch(() => requestRefresh())
}

/**
 * Handle deal moved to won dropper
 *
 * @param  {Object} e
 *
 * @return {Void}
 */
function movedToWon(e) {
  if (e.added) {
    markAsWon(e.added.element.id)
  }
}
</script>
<style>
.h-dropper {
  height: 75px;
}

.dropper .bottom-hidden {
  display: none;
}

.dropper-delete .sortable-chosen.sortable-ghost::before {
  background: black;
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}

.dropper-lost .sortable-chosen.sortable-ghost::before {
  background: red;
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}

.dropper-won .sortable-chosen.sortable-ghost::before {
  background: green;
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}
</style>
