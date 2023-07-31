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

import DocumentIndex from './views/DocumentIndex.vue'
import CreateDocument from './views/CreateDocument.vue'
import EditDocument from './views/EditDocument.vue'

import CreateDocumentTemplate from './views/Templates/CreateDocumentTemplate.vue'
import EditDocumentTemplate from './views/Templates/EditDocumentTemplate.vue'
import DocumentTemplateIndex from './views/Templates/DocumentTemplateIndex.vue'

export default [
  {
    path: '/documents',
    name: 'document-index',
    component: DocumentIndex,
    meta: {
      title: i18n.t('documents::document.documents'),
    },
    beforeEnter: (to, from) => {
      to.meta.initialize = to.name === 'document-index'
    },
    children: [
      {
        path: 'create',
        name: 'create-document',
        components: {
          create: CreateDocument,
        },
        meta: { title: i18n.t('documents::document.create') },
      },
      {
        path: ':id',
        name: 'view-document',
        components: {
          edit: EditDocument,
        },
        props: {
          resourceName: 'documents',
        },
      },
      {
        path: ':id/edit',
        name: 'edit-document',
        components: {
          edit: EditDocument,
        },
        props: {
          resourceName: 'documents',
        },
      },
    ],
  },
  {
    path: '/document-templates',
    name: 'document-templates-index',
    component: DocumentTemplateIndex,
    meta: {
      title: i18n.t('documents::document.template.templates'),
    },
    children: [
      {
        path: 'create',
        name: 'create-document-template',
        components: {
          create: CreateDocumentTemplate,
        },
        meta: {
          title: i18n.t('documents::document.template.create'),
        },
      },
      {
        path: ':id/edit',
        name: 'edit-document-template',
        components: {
          edit: EditDocumentTemplate,
        },
      },
    ],
  },
]
