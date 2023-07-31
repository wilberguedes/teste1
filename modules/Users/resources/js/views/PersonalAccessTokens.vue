<template>
  <ILayout>
    <div class="mx-auto max-w-5xl">
      <ICard
        no-body
        :header="$t('core::api.personal_access_tokens')"
        :overlay="tokensAreBeingLoaded"
      >
        <template #actions>
          <IButton
            v-show="totalTokens > 0"
            @click="showCreateTokenForm"
            icon="plus"
            :text="$t('core::api.create_token')"
            size="sm"
          />
        </template>
        <ITable v-if="totalTokens > 0" class="-mt-px">
          <thead>
            <tr>
              <th class="text-left" v-t="'core::api.token_name'"></th>
              <th class="text-left" v-t="'core::api.token_last_used'"></th>
              <th class="text-left" v-t="'core::app.created_at'"></th>
              <th class="text-left"></th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="token in tokens" :key="token.id">
              <td class="align-middle" v-text="token.name" />

              <td class="align-middle">
                {{
                  token.last_used_at
                    ? localizedDateTime(token.last_used_at)
                    : 'N/A'
                }}
              </td>
              <td class="align-middle">
                {{ localizedDateTime(token.created_at) }}
              </td>
              <!-- Delete Button -->
              <td class="align-middle">
                <IButton
                  @click="revoke(token)"
                  variant="white"
                  icon="Trash"
                  size="sm"
                  :text="$t('core::api.revoke_token')"
                />
              </td>
            </tr>
          </tbody>
        </ITable>
        <ICardBody v-else-if="!tokensAreBeingLoaded">
          <IEmptyState
            @click="showCreateTokenForm"
            :title="$t('core::api.no_tokens')"
            :button-text="$t('core::api.create_token')"
            :description="$t('core::api.empty_state.description')"
          />
        </ICardBody>
      </ICard>
      <IModal
        @shown="() => $refs.createTokenNameRef.focus()"
        form
        @submit="create"
        @keydown="form.onKeydown($event)"
        size="sm"
        v-model:visible="showCreateTokenModal"
        :ok-disabled="form.busy"
        :ok-title="$t('core::app.create')"
        :cancel-title="$t('core::app.cancel')"
        :title="$t('core::api.create_token')"
      >
        <IFormGroup
          :label="$t('core::api.token_name')"
          label-for="name"
          required
        >
          <IFormInput
            id="name"
            name="name"
            ref="createTokenNameRef"
            v-model="form.name"
          />
          <IFormError v-text="form.getError('name')" />
        </IFormGroup>
      </IModal>
      <IModal
        static-backdrop
        hide-footer
        v-model:visible="showAccessTokenModal"
        :title="$t('core::api.personal_access_token')"
      >
        <p
          class="mb-5 font-semibold text-warning-600"
          v-t="'core::api.after_token_created_info'"
        />
        <p
          class="select-all break-all rounded-md border border-neutral-300 p-4 text-neutral-900 dark:text-neutral-200"
          v-text="plainTextToken"
        />
      </IModal>
    </div>
  </ILayout>
</template>
<script setup>
import { ref, computed, shallowReactive } from 'vue'
import { useDates } from '~/Core/resources/js/composables/useDates'
import { useForm } from '~/Core/resources/js/composables/useForm'

const plainTextToken = ref(null)
const showAccessTokenModal = ref(false)
const showCreateTokenModal = ref(false)
const tokensAreBeingLoaded = ref(false)
const tokens = shallowReactive([])
const { form } = useForm({ name: '' }, { resetOnSuccess: true })
const { localizedDateTime } = useDates()

const totalTokens = computed(() => tokens.length)

function prepareComponent() {
  getTokens()
}

function getTokens() {
  tokensAreBeingLoaded.value = true
  Innoclapps.request()
    .get('/personal-access-tokens')
    .then(response => {
      tokens.push(...response.data)
    })
    .finally(() => (tokensAreBeingLoaded.value = false))
}

function showCreateTokenForm() {
  showCreateTokenModal.value = true
}

function create() {
  plainTextToken.value = null

  form.post('/personal-access-tokens').then(response => {
    tokens.push(response.accessToken)
    showAccessToken(response.plainTextToken)
  })
}

function showAccessToken(token) {
  showCreateTokenModal.value = false
  plainTextToken.value = token
  showAccessTokenModal.value = true
}

async function revoke(token) {
  await Innoclapps.dialog().confirm()
  await Innoclapps.request().delete(`/personal-access-tokens/${token.id}`)

  tokens.splice(
    tokens.findIndex(t => t.id === token.id),
    1
  )
}

prepareComponent()
</script>
