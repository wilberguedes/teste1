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
import i18n from '~/Core/resources/js/i18n'

import CreateUser from './views/CreateUser.vue'
import EditUser from './views/EditUser.vue'
import InviteUser from './views/InviteUser.vue'
import ManageTeams from './views/ManageTeams.vue'

import RoleIndex from '~/Core/resources/js/views/Roles/RoleIndex.vue'
import CreateRole from '~/Core/resources/js/views/Roles/CreateRole.vue'
import EditRole from '~/Core/resources/js/views/Roles/EditRole.vue'

import SettingsManageUsers from './components/SettingsManageUsers.vue'

export default [
  {
    path: 'users',
    component: SettingsManageUsers,
    name: 'users-index',
    meta: { title: i18n.t('users::user.users') },
    children: [
      {
        path: 'create',
        name: 'create-user',
        components: {
          createEdit: CreateUser,
        },
        meta: { title: i18n.t('users::user.create') },
      },
      {
        path: ':id/edit',
        name: 'edit-user',
        components: {
          createEdit: EditUser,
        },
        meta: { title: i18n.t('users::user.edit') },
      },
      {
        path: 'invite',
        name: 'invite-user',
        components: {
          invite: InviteUser,
        },
        meta: { title: i18n.t('users::user.invite') },
      },
      {
        path: 'roles',
        name: 'role-index',
        components: {
          roles: RoleIndex,
        },
        meta: {
          title: i18n.t('core::role.roles'),
        },
        children: [
          {
            path: 'create',
            name: 'create-role',
            component: CreateRole,
            meta: { title: i18n.t('core::role.create') },
          },
          {
            path: ':id/edit',
            name: 'edit-role',
            component: EditRole,
            meta: { title: i18n.t('core::role.edit') },
          },
        ],
      },
      {
        path: 'teams',
        name: 'manage-teams',
        components: {
          teams: ManageTeams,
        },
        meta: {
          title: i18n.t('users::team.teams'),
        },
      },
    ],
  },
]
