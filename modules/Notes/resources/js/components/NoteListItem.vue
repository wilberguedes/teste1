<template>
  <ICard
    v-show="!noteBeingEdited"
    v-bind="$attrs"
    condensed
    :class="'note-' + noteId"
    footer-class="inline-flex flex-col w-full"
  >
    <template #header>
      <div class="flex space-x-1.5">
        <a
          v-if="hasTextToCollapse"
          href="#"
          @click="collapsed = !collapsed"
          class="mr-2 mt-0.5 text-neutral-500 dark:text-neutral-300"
        >
          <Icon
            :icon="collapsed ? 'ChevronRight' : 'ChevronDown'"
            class="h-4 w-4"
          />
        </a>

        <IAvatar size="xs" :src="user.avatar_url" v-once />

        <div class="flex grow items-center" v-once>
          <i18n-t
            scope="global"
            keypath="notes::note.info_created"
            tag="span"
            class="text-sm text-neutral-700 dark:text-white"
          >
            <template #user>
              <span class="font-medium">
                {{ user.name }}
              </span>
            </template>
            <template #date>
              <span class="font-medium" v-text="localizedDateTime(createdAt)" />
            </template>
          </i18n-t>
        </div>
        <IMinimalDropdown
          v-if="authorizations.update && authorizations.delete"
          class="ml-2 self-start md:ml-5"
        >
          <IDropdownItem
            v-show="authorizations.update"
            @click="toggleEdit"
            :text="$t('core::app.edit')"
          />
          <IDropdownItem
            v-show="authorizations.delete"
            @click="destroy(noteId)"
            :text="$t('core::app.delete')"
          />
        </IMinimalDropdown>
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
      :commentable-id="noteId"
      commentable-type="notes"
      :count="commentsCount"
      @update:count="
        updateResourceRecordHasManyRelationship(
          {
            id: noteId,
            comments_count: $event,
          },
          'notes'
        )
      "
    />

    <template #footer>
      <AddComment
        class="self-end"
        @created="commentsAreVisible = true"
        :via-resource="viaResource"
        :commentable-id="noteId"
        commentable-type="notes"
      />
    </template>
  </ICard>
  <EditNote
    v-if="noteBeingEdited"
    @cancelled="noteBeingEdited = false"
    @updated="noteBeingEdited = false"
    :via-resource="viaResource"
    :noteId="noteId"
    :body="body"
  />
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import { ref, computed } from 'vue'
import EditNote from './EditNote.vue'
import CommentsCollapse from '~/Comments/resources/js/components/CommentsCollapse.vue'
import AddComment from '~/Comments/resources/js/components/AddComment.vue'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useI18n } from 'vue-i18n'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useComments } from '~/Comments/resources/js/composables/useComments'
import { useApp } from '~/Core/resources/js/composables/useApp'

const props = defineProps({
  noteId: { required: true, type: Number },
  commentsCount: { required: true, type: Number },
  createdAt: { required: true, type: String },
  body: { required: true, type: String },
  userId: { required: true, type: Number },
  authorizations: { required: true, type: Object },
  viaResource: { required: true, type: String },
  collapsable: Boolean,
})

const { t } = useI18n()
const { localizedDateTime } = useDates()
const { findUserById } = useApp()

const {
  removeResourceRecordHasManyRelationship,
  updateResourceRecordHasManyRelationship,
  decrementResourceRecordCount,
} = useRecordStore()

const user = computed(() => findUserById(props.userId))

const { commentsAreVisible } = useComments(props.noteId, 'notes')

const noteBeingEdited = ref(false)
const hasTextToCollapse = ref(false)
const collapsed = ref(true)

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/notes/${id}`)

  removeResourceRecordHasManyRelationship(id, 'notes')
  decrementResourceRecordCount('notes_count')

  Innoclapps.success(t('notes::note.deleted'))
}

function toggleEdit(e) {
  // The double click to edit should not work while in edit mode
  if (e.type == 'dblclick' && noteBeingEdited.value) return
  // For double click event
  if (!props.authorizations.update) return

  noteBeingEdited.value = !noteBeingEdited.value
}
</script>
