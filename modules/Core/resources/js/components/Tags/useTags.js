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
import { ref, computed } from 'vue'
import orderBy from 'lodash/orderBy'
import { createGlobalState } from '@vueuse/core'

export const useTags = createGlobalState(() => {
  const tags = ref([])

  const tagsByDisplayOrder = computed(() =>
    orderBy(tags.value, 'display_order')
  )

  function idx(id) {
    return tags.value.findIndex(tag => tag.id == id)
  }

  function findTagById(id) {
    return tagsByDisplayOrder.value.find(t => t.id == id)
  }

  function findTagsByType(type) {
    return tagsByDisplayOrder.value.filter(t => t.type == type)
  }

  function setTags(list) {
    tags.value = list
  }

  function addTag(tag) {
    tags.value.push(tag)
  }

  function setTag(id, tag) {
    tags.value[idx(id)] = tag
  }

  function removeTag(id) {
    tags.value.splice(idx(id), 1)
  }

  return {
    tags,
    tagsByDisplayOrder,

    findTagById,
    findTagsByType,
    setTags,
    setTag,
    addTag,
    removeTag,
  }
})
