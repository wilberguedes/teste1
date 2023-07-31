<template>
  <ICard :header="$t('deals::deal.pipeline.pipelines')" no-body>
    <template #actions>
      <IButton
        :to="{ name: 'create-pipeline' }"
        icon="plus"
        size="sm"
        :text="$t('deals::deal.pipeline.create')"
      />
    </template>
    <ITable class="-mt-px">
      <thead>
        <tr>
          <th class="text-left" v-t="'core::app.id'" width="5%"></th>
          <th class="text-left" v-t="'deals::deal.pipeline.pipeline'"></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="pipeline in pipelines" :key="pipeline.id">
          <td v-text="pipeline.id"></td>
          <td>
            <router-link
              class="link"
              :to="{ name: 'edit-pipeline', params: { id: pipeline.id } }"
            >
              {{ pipeline.name }}
            </router-link>
          </td>
          <td class="flex justify-end">
            <IMinimalDropdown>
              <IDropdownItem
                :to="{ name: 'edit-pipeline', params: { id: pipeline.id } }"
                :text="$t('core::app.edit')"
              />

              <IDropdownItem
                @click="destroy(pipeline.id)"
                :text="$t('core::app.delete')"
              />
            </IMinimalDropdown>
          </td>
        </tr>
      </tbody>
    </ITable>
  </ICard>
  <router-view></router-view>
</template>
<script setup>
import { useI18n } from 'vue-i18n'
import { usePipelines } from '../composables/usePipelines'

const { t } = useI18n()
const { orderedPipelines: pipelines, deletePipeline } = usePipelines()

async function destroy(id) {
  await Innoclapps.dialog().confirm()
  await deletePipeline(id)

  Innoclapps.success(t('deals::deal.pipeline.deleted'))
}
</script>
