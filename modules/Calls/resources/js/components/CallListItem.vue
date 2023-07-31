<template>
  <ICard
    v-show="!callBeingEdited"
    v-bind="$attrs"
    condensed
    :class="'call-' + callId"
    footer-class="inline-flex flex-col w-full"
  >
    <template #header>
      <div class="flex items-center">
        <a
          v-if="hasTextToCollapse"
          href="#"
          @click="collapsed = !collapsed"
          class="mr-2 mt-0.5 shrink-0 self-start text-neutral-500 dark:text-neutral-300 md:mt-0 md:self-auto"
        >
          <Icon
            :icon="collapsed ? 'ChevronRight' : 'ChevronDown'"
            class="h-4 w-4"
          />
        </a>

        <IAvatar
          v-once
          size="xs"
          class="mr-1.5 shrink-0 self-start md:self-auto"
          :src="user.avatar_url"
        />

        <div
          class="flex grow flex-col space-y-1 md:flex-row md:items-center md:space-x-3 md:space-y-0"
        >
          <i18n-t
            scope="global"
            keypath="calls::call.info_created"
            tag="span"
            class="grow text-sm text-neutral-700 dark:text-white"
          >
            <template #user>
              <span class="font-medium">
                {{ user.name }}
              </span>
            </template>
            <template #date>
              <span class="font-medium" v-text="localizedDateTime(callDate)" />
            </template>
          </i18n-t>
          <TextBackground
            :color="outcome.swatch_color"
            class="inline-flex shrink-0 items-center self-start rounded-md py-0.5 dark:!text-white sm:rounded-full"
          >
            <DropdownSelectInput
              v-if="authorizations.update"
              v-memo="[outcomeId]"
              :items="outcomesForDropdown"
              :modelValue="outcome"
              @change="update({ call_outcome_id: $event.id })"
              label-key="name"
              value-key="id"
            >
              <template v-slot="{ label, toggle }">
                <button
                  type="button"
                  @click="toggle"
                  class="flex w-full items-center justify-between rounded px-2.5 text-xs/5 focus:outline-none"
                >
                  {{ label }}
                  <Icon icon="ChevronDown" class="ml-1 h-4 w-4" />
                </button>
              </template>
            </DropdownSelectInput>
            <span v-else class="px-1 text-xs" v-text="outcome.name" />
          </TextBackground>
        </div>
        <div
          v-if="authorizations.update && authorizations.delete"
          class="ml-2 mt-px inline-flex self-start md:ml-5"
        >
          <IMinimalDropdown class="mt-1 md:mt-0.5">
            <IDropdownItem
              v-show="authorizations.update"
              @click="toggleEdit"
              :text="$t('core::app.edit')"
            />
            <IDropdownItem
              v-show="authorizations.delete"
              @click="destroy(callId)"
              :text="$t('core::app.delete')"
            />
          </IMinimalDropdown>
        </div>
      </div>
    </template>

    <TextCollapse
      v-if="collapsable"
      :text="body"
      :length="250"
      v-model:collapsed="collapsed"
      @hasTextToCollapse="hasTextToCollapse = $event"
      @dblclick="toggleEdit"
      class="wysiwyg-text"
    />

    <div v-else @dblclick="toggleEdit" class="wysiwyg-text" v-html="body" />

    <CommentsCollapse
      class="mt-6"
      :via-resource="viaResource"
      :commentable-id="callId"
      commentable-type="calls"
      :count="commentsCount"
      @update:count="
        updateResourceRecordHasManyRelationship(
          {
            id: callId,
            comments_count: $event,
          },
          'calls'
        )
      "
    />

    <template #footer>
      <AddComment
        class="self-end"
        @created="commentsAreVisible = true"
        :via-resource="viaResource"
        :commentable-id="callId"
        commentable-type="calls"
      />
    </template>
  </ICard>

  <EditCall
    v-if="callBeingEdited"
    @cancelled="callBeingEdited = false"
    @updated="callBeingEdited = false"
    :via-resource="viaResource"
    :call-id="callId"
  />
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref, computed } from 'vue'
import EditCall from './EditCall.vue'
import CommentsCollapse from '~/Comments/resources/js/components/CommentsCollapse.vue'
import AddComment from '~/Comments/resources/js/components/AddComment.vue'
import TextBackground from '~/Core/resources/js/components/TextBackground.vue'
import { useI18n } from 'vue-i18n'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useComments } from '~/Comments/resources/js/composables/useComments'
import { useCallOutcomes } from '../composables/useCallOutcomes'
import { useApp } from '~/Core/resources/js/composables/useApp'

const props = defineProps({
  callId: { required: true, type: Number },
  commentsCount: { required: true, type: Number },
  callDate: { required: true, type: String },
  body: { required: true, type: String },
  userId: { required: true, type: Number },
  outcomeId: { required: true, type: Number },
  viaResource: { required: true, type: String },
  authorizations: { required: true, type: Object },
  collapsable: Boolean,
})

const { t } = useI18n()
const { localizedDateTime } = useDates()
const { outcomesByName } = useCallOutcomes()
const { findUserById } = useApp()

const outcome = computed(() =>
  outcomesByName.value.find(o => o.id == props.outcomeId)
)

const outcomesForDropdown = computed(() =>
  outcomesByName.value.map(outcome => ({
    id: outcome.id,
    name: outcome.name,
  }))
)

const user = computed(() => findUserById(props.userId))

const {
  updateResourceRecordHasManyRelationship,
  removeResourceRecordHasManyRelationship,
  decrementResourceRecordCount,
  record,
} = useRecordStore()

const { commentsAreVisible } = useComments(props.callId, 'calls')

const callBeingEdited = ref(false)
const hasTextToCollapse = ref(false)
const collapsed = ref(true)

function update(payload = {}) {
  Innoclapps.request()
    .put(`/calls/${props.callId}`, {
      call_outcome_id: props.outcomeId,
      date: props.callDate,
      body: props.body,
      via_resource: props.viaResource,
      via_resource_id: record.value.id,
      ...payload,
    })
    .then(({ data }) => updateResourceRecordHasManyRelationship(data, 'calls'))
}

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/calls/${id}`)

  removeResourceRecordHasManyRelationship(id, 'calls')
  decrementResourceRecordCount('calls_count')

  Innoclapps.success(t('calls::call.deleted'))
}

function toggleEdit(e) {
  // The double click to edit should not work while in edit mode
  if (e.type == 'dblclick' && callBeingEdited.value) return
  // For double click event
  if (!props.authorizations.update) return

  callBeingEdited.value = !callBeingEdited.value
}
</script>
