/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */
import { ref, unref, computed, onBeforeMount } from 'vue'
import { watchDebounced } from '@vueuse/core'
import findIndex from 'lodash/findIndex'
import find from 'lodash/find'
import { useRecordStore } from '~/Core/resources/js/composables/useRecordStore'
import { useI18n } from 'vue-i18n'
import { randomString } from '@/utils'
import { useStore } from 'vuex'
import { useForm } from '~/Core/resources/js/composables/useForm'
import { useSignature } from '../views/Emails/useSignature'

export function useMessageComposer(viaResource, resourceRecord) {
  const { t } = useI18n()
  const store = useStore()
  const { addSignature } = useSignature()
  const { addResourceRecordHasManyRelationship, incrementResourceRecordCount } =
    useRecordStore()

  const sending = ref(false)
  const customAssociationsValue = ref({})
  const attachmentsDraftId = ref(randomString())
  const attachments = ref([])
  const parsedSubject = ref(null)
  const subject = ref(null)

  const { form } = useForm({
    subject: null,
    message: addSignature(),
    to: [],
    cc: null,
    bcc: null,
    associations: {},
    with_task: false,
    task_date: null,
  })

  const placeholders = computed(() => store.state.fields.placeholders)

  const hasEmptyPlaceholders = computed(() => {
    if (!form.message) {
      return false
    }

    let template = document.createElement('template')
    template.innerHTML = form.message.trim() // Never return a text node of whitespace as the result

    const usedPlaceholders =
      template.content.firstChild.querySelectorAll('._placeholder')

    if (usedPlaceholders.length === 0) {
      return false
    }

    return (
      Array.from(usedPlaceholders).filter(p => p.value.trim() === '').length > 0
    )
  })

  const allPlaceholders = computed(() => {
    let all = []

    Object.keys(placeholders.value).forEach(group => {
      placeholders.value[group].placeholders.forEach(placeholder => {
        all.push(placeholder)
      })
    })

    return all
  })

  const allPlaceholdersInterpolations = computed(() => {
    return allPlaceholders.value.reduce((tags, p) => {
      const tag = [p.interpolation_start, p.interpolation_end]

      if (tags.length === 0) {
        tags.push(tag)
      } else {
        tags.forEach(i => {
          if (i[0] !== tag[0] && i[1] !== tag[1]) {
            tags.push(tag)
          }
        })
      }

      return tags
    }, [])
  })

  function showWillUsePlaceholdersIconToAssociateResourceRecord(
    record,
    selectedRecords,
    resourceName,
    isSelected,
    isSearching
  ) {
    if (isSearching || !isSelected) {
      return false
    }

    const isFirstSelectedRecord = selectedRecords[0] === record.id

    const isContactOrCompany =
      resourceName === 'contacts' || resourceName === 'companies'

    if (isContactOrCompany) {
      const firstRecipientByResource = form.to.filter(
        r => r.resourceName === resourceName
      )[0]

      if (!firstRecipientByResource) {
        return isFirstSelectedRecord
      }

      return firstRecipientByResource.id === record.id
    }

    return isFirstSelectedRecord
  }

  // When there are "contacts" or "companies" recipients, use placeholders from the first resource recipient,
  // otherwise, use placeholders from the first associated record.
  const resourcesForPlaceholders = computed(() => {
    const resources = []

    Object.keys(form.associations).forEach(resourceName => {
      if (form.associations[resourceName].length === 0) {
        return
      }

      const isContactOrCompany =
        resourceName === 'contacts' || resourceName === 'companies'

      if (isContactOrCompany) {
        const firstRecipientByResource = form.to.filter(
          r => r.resourceName === resourceName
        )[0]

        if (firstRecipientByResource) {
          resources.push({
            name: resourceName,
            id: firstRecipientByResource.id,
          })
        } else {
          resources.push({
            name: resourceName,
            id: form.associations[resourceName][0],
          })
        }
      } else {
        resources.push({
          name: resourceName,
          id: form.associations[resourceName][0],
        })
      }
    })

    return resources
  })

  const subjectPlaceholders = computed(() => {
    if (!placeholders.value || !subject.value) {
      return []
    }

    const usedPlaceholders = []

    allPlaceholdersInterpolations.value.forEach(i => {
      const matches = new RegExp(`${i[0]}\\s?(.*?)\\s?${i[1]}`).exec(
        subject.value
      )

      if (matches) {
        usedPlaceholders.push({
          interpolation_start: i[0],
          interpolation_end: i[1],
          tag: matches[1].trim(),
        })
      }
    })

    return usedPlaceholders
  })

  const hasInvalidSubjectPlaceholders = computed(() => {
    let value = false

    subjectPlaceholders.value.forEach(p => {
      if (!find(allPlaceholders.value, ['tag', p.tag])) {
        value = true
        return false
      }

      return true
    })

    return value
  })

  const subjectPlaceholdersSyntaxIsValid = computed(() => {
    if (!subject.value) {
      return true
    }

    let value = true

    allPlaceholders.value.concat(subjectPlaceholders.value).every(p => {
      if (subject.value.indexOf(p.tag) > -1) {
        if (
          !new RegExp(
            `${p.interpolation_start}\\s?${p.tag}\\s?${p.interpolation_end}`
          ).test(subject.value)
        ) {
          value = false
          return false
        }
      }
      return true
    })

    return value
  })

  const subjectContainsPlaceholders = computed(() => {
    return subjectPlaceholders.value.length > 0
  })

  const hasInvalidAddresses = computed(() => {
    return Boolean(
      form.errors.first('to.0.address') ||
        form.errors.first('cc.0.address') ||
        form.errors.first('bcc.0.address')
    )
  })

  const wantsCc = computed(() => form.cc !== null)
  const wantsBcc = computed(() => form.bcc !== null)

  function parsePlaceholdersForMessage() {
    _parsePlaceholders(form.message, 'input-fields').then(
      content => (form.message = content)
    )
  }

  function parsePlaceholdersForSubject() {
    if (
      !subjectContainsPlaceholders.value ||
      !subjectPlaceholdersSyntaxIsValid.value
    ) {
      return
    }

    _parsePlaceholders(subject.value, 'interpolation').then(
      content => (parsedSubject.value = content)
    )
  }

  async function _parsePlaceholders(content, type) {
    if (!content) {
      return
    }

    if (resourcesForPlaceholders.value.length === 0) {
      return content
    }

    return makeParsePlaceholdersRequest(
      resourcesForPlaceholders.value,
      content,
      type
    )
  }

  function handleCreatedFollowUpTask(task) {
    addResourceRecordHasManyRelationship(task, 'activities')
    incrementResourceRecordCount('incomplete_activities_for_user_count')
  }

  function sendRequest(url) {
    sending.value = true
    form.subject = parsedSubject.value || subject.value
    form.fill('attachments_draft_id', attachmentsDraftId)

    if (viaResource) {
      form.fill('via_resource', viaResource)
      form.fill('via_resource_id', unref(resourceRecord).id)
    }

    return Innoclapps.request()
      .post(url, form.data())
      .then(response => {
        form.reset()

        if (response.status !== 202) {
          Innoclapps.success(t('mailclient::inbox.message_sent'))
          Innoclapps.$emit('email-sent', response.data.message)
        } else {
          Innoclapps.info(t('mailclient::mail.message_queued_for_sending'))
        }

        if (response.data.createdActivity && viaResource) {
          handleCreatedFollowUpTask(response.data.createdActivity)
        }
      })
      .finally(() => (sending.value = false))
  }

  function setWantsCC() {
    form.cc = []
  }

  function setWantsBCC() {
    form.bcc = []
  }

  function handleAttachmentUploaded(media) {
    attachments.value.push(media)
  }

  function destroyPendingAttachment(media) {
    Innoclapps.request()
      .delete(`/media/pending/${media.pending_data.id}`)
      .then(() => {
        let index = findIndex(attachments.value, ['id', media.id])
        attachments.value.splice(index, 1)
      })
  }

  function handleRecipientSelectedEvent(recipients) {
    associateSelectedRecipients(recipients)
    parsePlaceholdersForMessage()
    parsePlaceholdersForSubject()
  }

  function handleToRecipientRemovedEvent(option) {
    dissociateRemovedRecipients(option)
    parsePlaceholdersForMessage()
    parsePlaceholdersForSubject()
  }

  /**
   * When a recipient is removed we will dissociate
   * the removed recipients from the associations component
   *
   * @param  {Object} option
   *
   * @return {Void}
   */
  function dissociateRemovedRecipients(option) {
    if (
      !option.resourceName ||
      !customAssociationsValue.value[option.resourceName] ||
      // Do not auto dissociated the primary record
      (viaResource === option.resourceName &&
        unref(resourceRecord).id === option.id)
    ) {
      return
    }

    let associateablesIndex = findIndex(
      customAssociationsValue.value[option.resourceName],
      ['id', option.id]
    )

    let modelIndex = form.associations[option.resourceName].findIndex(
      associatedId => associatedId === option.id
    )

    if (associateablesIndex !== -1) {
      customAssociationsValue.value[option.resourceName].splice(
        associateablesIndex,
        1
      )
    }

    if (modelIndex !== -1) {
      form.associations[option.resourceName].splice(modelIndex, 1)
    }
  }

  function associateMessageRecord(record, resourceName) {
    if (!customAssociationsValue.value[resourceName]) {
      customAssociationsValue.value[resourceName] = []
    }

    if (!find(customAssociationsValue.value[resourceName], ['id', record.id])) {
      customAssociationsValue.value[resourceName].push(record)
    }

    if (!form.associations.hasOwnProperty(resourceName)) {
      form.associations[resourceName] = []
    }

    if (!form.associations[resourceName].find(id => id == record.id)) {
      form.associations[resourceName].push(record.id)
    }
  }

  /**
   * When a recipient is selected we will associate automatically to the association component
   *
   * @param  {Array} records
   *
   * @return {Void}
   */
  function associateSelectedRecipients(records) {
    records
      .filter(record => Boolean(record.resourceName))
      .forEach(record => {
        record.disabled = true
        associateMessageRecord(
          { ...record, ...{ display_name: record.name } },
          record.resourceName
        )
      })
  }

  async function makeParsePlaceholdersRequest(resources, content, type) {
    if (!content) {
      return content
    }

    let { data } = await Innoclapps.request().post('/placeholders/' + type, {
      resources: resources,
      content: content,
    })

    return data
  }

  watchDebounced(
    subject,
    () => {
      parsePlaceholdersForSubject()
    },
    { debounce: 600 }
  )

  onBeforeMount(() => {
    store.dispatch('fields/fetchPlaceholders')
  })

  return {
    form,
    sending,
    customAssociationsValue,
    attachments,
    attachmentsDraftId,

    placeholders,
    resourcesForPlaceholders,
    parsedSubject,
    hasEmptyPlaceholders,
    subject,
    hasInvalidAddresses,
    wantsCc,
    wantsBcc,

    sendRequest,
    subjectContainsPlaceholders,
    parsePlaceholdersForMessage,
    parsePlaceholdersForSubject,
    subjectPlaceholdersSyntaxIsValid,
    showWillUsePlaceholdersIconToAssociateResourceRecord,
    hasInvalidSubjectPlaceholders,
    handleAttachmentUploaded,
    destroyPendingAttachment,
    associateSelectedRecipients,
    dissociateRemovedRecipients,
    associateMessageRecord,
    handleRecipientSelectedEvent,
    handleToRecipientRemovedEvent,
    setWantsBCC,
    setWantsCC,
  }
}
