<template>
  <IModal
    id="boardSort"
    size="sm"
    form
    @submit="apply"
    :ok-disabled="!sortBy.field"
    :ok-title="$t('core::app.apply')"
    :title="$t('deals::deal.sort_by')"
  >
    <div class="flex">
      <div class="mr-2 grow">
        <IFormSelect v-model="sortBy.field">
          <option
            value="expected_close_date"
            v-t="'deals::fields.deals.expected_close_date'"
          />
          <option value="created_at" v-t="'core::app.creation_date'" />
          <option value="amount" v-t="'deals::fields.deals.amount'" />
          <option value="name" v-t="'deals::deal.name'" />
        </IFormSelect>
      </div>
      <div>
        <IFormSelect v-model="sortBy.direction">
          <option value="asc">
            Asc (<span v-t="'core::app.ascending'"></span>)
          </option>
          <option value="desc">
            Desc (<span v-t="'core::app.descending'"></span>)
          </option>
        </IFormSelect>
      </div>
    </div>
  </IModal>
</template>
<script setup>
import { ref } from 'vue'

const emit = defineEmits(['applied'])

const sortBy = ref({
  field: null,
  direction: 'asc',
})

function hideModal() {
  Innoclapps.modal().hide('boardSort')
}

function apply() {
  emit('applied', sortBy.value)
  hideModal()
}
</script>
