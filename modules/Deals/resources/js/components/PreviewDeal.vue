<template>
  <div>
    <div class="mb-5 rounded-md border border-primary-300 px-4 py-3 md:px-5">
      <div class="flex flex-col items-center md:flex-row">
        <DealStagePopover
          class="shrink-0 text-neutral-800 hover:text-neutral-600 dark:text-neutral-200 dark:hover:text-neutral-400 md:mr-3"
          :deal-id="record.id"
          :pipeline-id="record.pipeline_id"
          :stage-id="record.stage_id"
          :status="record.status"
          :authorized-to-update="record.authorizations.update"
          is-floating
        />
        <p
          class="block text-sm text-neutral-500 md:hidden"
          v-text="beenInStageText"
        />
        <DealStatusChange
          class="my-2 md:my-0 md:ml-auto"
          :deal-id="record.id"
          :deal-status="record.status"
          is-floating
        />
      </div>
      <p
        class="-mt-1.5 hidden text-center text-sm text-neutral-500 dark:text-neutral-300 md:block md:text-left"
        v-text="beenInStageText"
      />
    </div>
    <FieldsGenerator
      :fields="fields"
      :form-id="form.formId"
      :collapsed="fieldsCollapsed"
      view="update"
      :is-floating="true"
    >
      <template #after="{ fields }">
        <FieldsCollapseButton
          v-if="hasCollapsableFields"
          v-model:collapsed="fieldsCollapsed"
          :total="totalCollapsableFields"
          class="mb-3"
        />
      </template>
    </FieldsGenerator>

    <DealContactCard
      class="mb-3 sm:mb-5"
      @dissociated="removeResourceRecordHasManyRelationship($event, 'contacts')"
      :deal-id="record.id"
      :contacts="record.contacts"
      :authorize-dissociate="record.authorizations.update"
      :show-create-button="record.authorizations.update"
      @create-requested="contactBeingCreated = true"
    />

    <DealCompanyCard
      class="mb-3 sm:mb-5"
      @dissociated="
        removeResourceRecordHasManyRelationship($event, 'companies')
      "
      @create-requested="companyBeingCreated = true"
      :deal-id="record.id"
      :companies="record.companies"
      :authorize-dissociate="record.authorizations.update"
      :show-create-button="record.authorizations.update"
    />

    <MediaCard
      class="mb-3 sm:mb-5"
      resource-name="deals"
      :resource-id="record.id"
      :media="record.media"
      :authorize-delete="record.authorizations.update"
      :is-floating="true"
      @uploaded="addResourceRecordMedia"
      @deleted="removeResourceRecordMedia"
    />
  </div>

  <CreateContactModal
    v-model:visible="contactBeingCreated"
    :overlay="false"
    :deals="[record]"
    @created="refreshPreview(), (contactBeingCreated = false)"
    @restored="refreshPreview(), (contactBeingCreated = false)"
  />

  <CreateCompanyModal
    v-model:visible="companyBeingCreated"
    :overlay="false"
    :deals="[record]"
    @created="refreshPreview(), (companyBeingCreated = false)"
    @restored="refreshPreview(), (companyBeingCreated = false)"
  />
</template>
<script setup>
import { ref, inject, computed, onBeforeUnmount } from 'vue'
import DealStatusChange from '../components/DealStatusChange.vue'
import DealStagePopover from '../components/DealStagePopover.vue'
import MediaCard from '~/Core/resources/js/components/Media/ResourceRecordMediaCard.vue'
import DealCompanyCard from '../components/Cards/DealCompanyCard.vue'
import DealContactCard from '../components/Cards/DealContactCard.vue'
import FieldsCollapseButton from '~/Core/resources/js/components/Fields/ButtonCollapse.vue'
import { useI18n } from 'vue-i18n'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'

const props = defineProps({
  record: Object,
  form: Object,
  fields: Object,
  hasCollapsableFields: Boolean,
  totalCollapsableFields: Number,
})

const fieldsCollapsed = ref(true)

const companyBeingCreated = ref(false)
const contactBeingCreated = ref(false)

const setDescription = inject('setDescription')
const refreshPreview = inject('refreshPreviewRecord')

const { t } = useI18n()
const { localizedDateTime } = useDates()

const {
  addResourceRecordMedia,
  removeResourceRecordMedia,
  removeResourceRecordHasManyRelationship,
} = useRecordStore('recordPreview', 'record')

setDescription(
  `${t('core::app.created_at')} ${localizedDateTime(props.record.created_at)}`
)

onBeforeUnmount(() => {
  setDescription(null)
})

const beenInStageText = computed(() => {
  const duration = moment.duration({
    seconds: props.record.time_in_stages[props.record.stage.id],
  })

  return t('deals::deal.been_in_stage_time', {
    time: duration.humanize(),
  })
})
</script>
