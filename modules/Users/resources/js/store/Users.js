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
import PersistentResourceCrud from '@/store/actions/PersistentResourceCrud'
import PersistentResourceMutations from '@/store/mutations/PersistentResourceMutations'
import PersistentResourceGetters from '@/store/getters/PersistentResourceGetters'
import findIndex from 'lodash/findIndex'
import i18n from '~/Core/resources/js/i18n'

function currentUserIndex(state) {
  return findIndex(state.collection, [
    'id',
    Number(Innoclapps.config('user_id')),
  ])
}

const state = {
  collection: [],
  dataFetched: false,
  endpoint: '/users',
  notificationsEndpoint: '/notifications',
}

const mutations = {
  ...PersistentResourceMutations,

  /**
   * Add new notification to the current user
   *
   * @param {Object} state
   * @param {Object} item
   */
  NEW_NOTIFICATION(state, item) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.latest.unshift(item)
    state.collection[index].notifications.unread_count =
      state.collection[index].notifications.unread_count + 1

    Innoclapps.$emit('new-notification-added', item)
  },

  /**
   * Remove notifications from the current user
   *
   * @param {Object} state
   * @param {Object} notification
   */
  REMOVE_NOTIFICATION(state, notification) {
    const index = currentUserIndex(state)

    if (
      !notification.read_at &&
      state.collection[index].notifications.unread_count > 0
    ) {
      state.collection[index].notifications.unread_count =
        state.collection[index].notifications.unread_count - 1
    }

    const notificationIndex = findIndex(
      state.collection[index].notifications.latest,
      ['id', notification.id]
    )

    // Perhaps not in the top navigation dropdown
    if (notificationIndex !== -1) {
      state.collection[index].notifications.latest.splice(notificationIndex, 1)
    }
  },

  /**
   * Set the unread count notifications for the current user
   *
   * @param {Object} state
   * @param {Number} total
   */
  SET_TOTAL_UNREAD_NOTIFICATIONS(state, total) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.unread_count = total
  },

  /**
   * Set all notifications are read for the current user
   *
   * @param {Object} state
   */
  SET_ALL_NOTIFICATIONS_AS_READ(state) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.latest.forEach(notification => {
      notification.read_at = moment().format('YYYY-MM-DD HH:mm:ss')
    })
  },

  /**
   * Add current user dashboard
   */
  ADD_DASHBOARD(state, dashboard) {
    const index = currentUserIndex(state)

    state.collection[index].dashboards.push(dashboard)

    if (dashboard.is_default) {
      // Update previous is_default to false
      state.collection[index].dashboards.forEach((d, index) => {
        if (d.id != dashboard.id) {
          state.collection[index].dashboards[index].is_default = false
        }
      })
    }
  },

  /**
   * Update current user dashboard
   */
  UPDATE_DASHBOARD(state, dashboard) {
    const index = currentUserIndex(state)
    const dashboardIndex = findIndex(state.collection[index].dashboards, [
      'id',
      Number(dashboard.id),
    ])

    state.collection[index].dashboards[dashboardIndex] = Object.assign(
      {},
      state.collection[index].dashboards[dashboardIndex],
      dashboard
    )

    if (dashboard.is_default) {
      // Update previous is_default to false
      state.collection[index].dashboards.forEach((d, didx) => {
        if (d.id != dashboard.id) {
          state.collection[index].dashboards[didx].is_default = false
        }
      })
    }
  },

  /**
   * Add current user dashboard
   */
  REMOVE_DASHBOARD(state, id) {
    const index = currentUserIndex(state)
    const dashboardIndex = findIndex(state.collection[index].dashboards, [
      'id',
      Number(id),
    ])

    state.collection[index].dashboards.splice(dashboardIndex, 1)
  },
}

const getters = {
  ...PersistentResourceGetters,

  /**
   * Get the current user
   *s
   * @param  {Object} state
   *
   * @return {Object}
   */
  current(state) {
    return state.collection[currentUserIndex(state)]
  },

  /**
   * Current user total notifications
   *
   * @param  {Object} state
   *
   * @return {Number}
   */
  totalNotifications(state) {
    const index = currentUserIndex(state)

    if (!state.collection[index].notifications.latest) {
      return 0
    }

    return state.collection[index].notifications.latest.length
  },

  /**
   * Indicates whether the current user has unread notifications
   *
   * @param  {Object} state
   *
   * @return {Boolean}
   */
  hasUnreadNotifications(state) {
    const index = currentUserIndex(state)

    return state.collection[index].notifications.unread_count > 0
  },

  /**
   * Localize the given notification
   *
   * @param  {Object} state
   *
   * @return {String}
   */
  localizeNotification: state => notification => {
    if (notification.data.lang) {
      return i18n.t(notification.data.lang.key, notification.data.lang.attrs)
    }

    return notification.data.message
  },
}

const actions = {
  ...PersistentResourceCrud,

  /**
   * Update user profile
   *
   * @param  {Function} options.commit
   * @param  {Object} form
   *
   * @return {Void}
   */
  async updateProfile({ commit }, form) {
    let data = await form.put('/profile')

    // Update user in the collection too
    commit('UPDATE', {
      id: data.id,
      item: data,
    })
  },

  /**
   * Make a request to remove the given user avatar
   *
   * @param  {Function} options.commit
   * @param  {Number} id
   *
   * @return {Object}
   */
  async removeAvatar({ commit }, id) {
    let { data } = await Innoclapps.request().delete(`/users/${id}/avatar`)

    commit('UPDATE', {
      id: data.id,
      item: data,
    })

    return data
  },

  /**
   * Destroy notification
   *
   * @param  {Function} options.commit
   * @param  {Object} options.state
   * @param  {Object} notification
   *
   * @return {Object}
   */
  async destroyNotification({ commit, state }, notification) {
    await Innoclapps.dialog().confirm()

    await Innoclapps.request().delete(
      `${state.notificationsEndpoint}/${notification.id}`
    )

    commit('REMOVE_NOTIFICATION', notification)

    return notification
  },

  /**
   * Mark all notifications are read for the current user
   *
   * @param  {Function} options.commit
   * @param  {Object} options.state
   * @param  {Object} options.getters
   *
   * @return {Void}
   */
  async markAllNotificationsAsRead({ commit, state, getters }) {
    if (getters.hasUnreadNotifications) {
      await Innoclapps.request().put(state.notificationsEndpoint)

      commit('SET_ALL_NOTIFICATIONS_AS_READ')
      commit('SET_TOTAL_UNREAD_NOTIFICATIONS', 0)
    }
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
}
