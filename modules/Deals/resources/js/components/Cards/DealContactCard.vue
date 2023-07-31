<template>
  <ContactCard
    :contacts="contacts"
    :empty-text="$t('deals::deal.no_contacts_associated')"
  >
    <template #actions="{ contact }">
      <IButton
        v-show="authorizeDissociate"
        size="sm"
        variant="white"
        v-i-tooltip.left="$t('contacts::contact.dissociate')"
        icon="X"
        @click="dissociateContact(contact.id)"
      />
    </template>
    <template #tail>
      <IButton
        v-if="showCreateButton"
        class="mt-6"
        variant="white"
        block
        @click="$emit('create-requested')"
        :text="$t('contacts::contact.add')"
      />
    </template>
  </ContactCard>
</template>
<script setup>
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useI18n } from 'vue-i18n'
import ContactCard from '~/Contacts/resources/js/components/Cards/ContactCard.vue'

const emit = defineEmits(['dissociated', 'create-requested'])

const props = defineProps({
  contacts: { required: true, type: Array },
  dealId: { required: true, type: Number },
  authorizeDissociate: { required: true, type: Boolean },
  showCreateButton: { required: true, type: Boolean },
})

const { t } = useI18n()

const { record: contact, removeResourceRecordHasManyRelationship } =
  useRecordStore()

async function dissociateContact(id) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`associations/deals/${props.dealId}`, {
    data: {
      contacts: [id],
    },
  })

  emit('dissociated', id)

  // When preview is shown in contact single resource view
  // We need to actually remove the relation
  if (contact.value && contact.value.id == id) {
    removeResourceRecordHasManyRelationship(props.dealId, 'deals')
  }

  Innoclapps.success(t('core::resource.dissociated'))
}
</script>
