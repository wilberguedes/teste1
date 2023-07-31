<template>
  <div v-if="dealStatus === 'open'">
    <div class="inline-flex">
      <IButton
        v-if="dealStatus !== 'won'"
        variant="success"
        class="mr-2 px-5"
        :loading="requestInProgress['won']"
        :disabled="requestInProgress['won']"
        size="sm"
        v-i-tooltip="$t('deals::deal.actions.mark_as_won')"
        :text="$t('deals::deal.status.won')"
        @click="changeStatus('won')"
      />
      <IPopover
        v-if="dealStatus !== 'lost'"
        shift
        :title="$t('deals::deal.actions.mark_as_lost')"
        closeable
        class="w-80 max-w-xs sm:max-w-sm"
        ref="lostPopoverRef"
      >
        <IButton
          variant="danger"
          size="sm"
          class="px-5"
          v-i-tooltip="$t('deals::deal.actions.mark_as_lost')"
          :text="$t('deals::deal.status.lost')"
        />
        <template #popper>
          <div class="flex flex-col px-4 py-3">
            <IFormGroup
              :label="$t('deals::deal.lost_reasons.lost_reason')"
              label-for="lost_reason"
              :optional="!setting('lost_reason_is_required')"
              :required="setting('lost_reason_is_required')"
            >
              <LostReasonField v-model="markAsLostForm.lost_reason" />
              <IFormError v-text="markAsLostForm.getError('lost_reason')" />
            </IFormGroup>
            <IButton
              variant="danger"
              size="sm"
              block
              class="mt-4"
              :loading="requestInProgress['lost']"
              :disabled="requestInProgress['lost']"
              :text="$t('deals::deal.actions.mark_as_lost')"
              @click="changeStatus('lost')"
            />
          </div>
        </template>
      </IPopover>
    </div>
  </div>
  <div v-else class="flex items-center">
    <IBadge size="lg" :variant="dealStatus === 'won' ? 'success' : 'danger'">
      <Icon
        v-if="dealStatus === 'won'"
        icon="CheckBadge"
        class="mr-1.5 h-4 w-4 text-current"
      />

      <Icon
        v-else-if="dealStatus === 'lost'"
        icon="X"
        class="mr-1.5 h-4 w-4 text-current"
      />

      {{ $t('deals::deal.status.' + dealStatus) }}
    </IBadge>
    <div>
      <IButton
        size="sm"
        class="ml-2 px-5"
        :disabled="requestInProgress['open']"
        :loading="requestInProgress['open']"
        variant="white"
        :text="$t('deals::deal.reopen')"
        @click="changeStatus('open')"
      />
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import LostReasonField from './DealLostReasonField.vue'
import { useApp } from '~/Core/resources/js/composables/useApp'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { throwConfetti } from '@/utils'
import { useForm } from '~/Core/resources/js/composables/useForm'

const props = defineProps({
  dealId: { type: Number, required: true },
  dealStatus: { type: String, required: true },
  isFloating: { default: false, type: Boolean },
})

const { ensureRecordIsUpdated } = useRecordStore()

const { setting } = useApp()

const lostPopoverRef = ref(null)

const { form: markAsLostForm } = useForm(
  {
    lost_reason: null,
  },
  { resetOnSuccess: true }
)

const requestInProgress = reactive({
  won: false,
  lost: false,
  open: false,
})

function updateRecordInStore(record) {
  ensureRecordIsUpdated(record, 'deals', props.isFloating)
  Innoclapps.$emit('deals-record-updated', record)
}

function changeStatus(status) {
  if (status === 'lost') {
    markAsLost()
    return
  }

  requestInProgress[status] = true

  Innoclapps.request()
    .put(`/deals/${props.dealId}/status/${status}`)
    .then(({ data }) => {
      updateRecordInStore(data)

      if (status === 'won') {
        throwConfetti()
      }
    })
    .finally(() => (requestInProgress[status] = false))
}

function markAsLost() {
  requestInProgress['lost'] = true

  markAsLostForm
    .put(`/deals/${props.dealId}/status/lost`)
    .then(deal => {
      lostPopoverRef.value.hide()
      updateRecordInStore(deal)
    })
    .finally(() => (requestInProgress['lost'] = false))
}
</script>
