<template>
  <div
    class="rounded-lg border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900"
  >
    <div class="px-4 py-5 sm:px-6">
      <h2
        class="flex items-center justify-between font-medium text-neutral-800 dark:text-white"
      >
        <span>
          {{ cardTitle }}
          <span
            v-show="total > 0"
            class="text-sm font-normal text-neutral-400"
            v-text="'(' + total + ')'"
          />
        </span>
        <span>
          <a
            v-if="total > limit && !showAll"
            href="#"
            class="link shrink-0 text-sm font-normal"
            @click.prevent="showAll = true"
            v-t="'core::app.show_all'"
          />
          <a
            v-if="total > limit && showAll"
            href="#"
            class="link shrink-0 text-sm font-normal"
            @click.prevent="showAll = false"
            v-t="'core::app.show_less'"
          />
          <span
            v-if="total > limit"
            v-show="!showAll"
            class="ml-1 text-xs font-normal text-neutral-400"
            v-t="{ path: 'core::app.has_more', args: { count: total - limit } }"
          />
        </span>
      </h2>
      <ul
        class="divide-y divide-neutral-200 dark:divide-neutral-700"
        :class="{ '-mb-4': total > 0 }"
      >
        <li
          v-for="(deal, index) in iterable"
          :key="deal.id"
          v-show="index <= limit - 1 || showAll"
          class="group flex items-center space-x-3 py-4"
        >
          <div class="shrink-0">
            <IBadge
              :variant="
                deal.status === 'won'
                  ? 'success'
                  : deal.status === 'lost'
                  ? 'danger'
                  : 'neutral'
              "
              :text="$t('deals::deal.status.' + deal.status)"
            />
          </div>

          <div class="min-w-0 flex-1 truncate">
            <a
              :href="deal.path"
              class="text-sm font-medium text-neutral-800 focus:outline-none hover:text-neutral-500 dark:text-white dark:hover:text-neutral-300"
              @click.prevent="preview(deal.id)"
              v-text="deal.display_name"
            />
            <p
              class="text-sm text-neutral-500 dark:text-neutral-300"
              v-text="deal.stage.name"
            />
          </div>
          <div class="block shrink-0 md:hidden md:group-hover:block">
            <div class="flex items-center space-x-1">
              <IButton
                v-show="$gate.allows('view', deal)"
                size="sm"
                variant="white"
                :to="deal.path"
                :text="$t('core::app.view_record')"
              />
              <slot name="actions" :deal="deal"></slot>
            </div>
          </div>
        </li>
      </ul>
      <p
        v-show="!hasDeals"
        class="text-sm text-neutral-500 dark:text-neutral-300"
        v-text="emptyText"
      />
      <slot name="tail"></slot>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import castArray from 'lodash/castArray'
import orderBy from 'lodash/orderBy'
import { useStore } from 'vuex'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  deals: { type: [Object, Array], required: true },
  limit: { type: Number, default: 3 },
  emptyText: String,
  title: String,
})

const { t } = useI18n()
const store = useStore()

const showAll = ref(false)

const localDeals = computed(() => castArray(props.deals))

const wonSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'won'),
    deal => new Date(deal.won_date),
    'desc'
  )
)

const lostSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'lost'),
    deal => new Date(deal.lost_date),
    'desc'
  )
)

const openSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'open'),
    deal => new Date(deal.created_at)
  )
)

const iterable = computed(() => [
  ...openSorted.value,
  ...lostSorted.value,
  ...wonSorted.value,
])

const total = computed(() => localDeals.value.length)

const hasDeals = computed(() => total.value > 0)

const cardTitle = computed(() => props.title || t('deals::deal.deals'))

function preview(id) {
  store.commit('recordPreview/SET_PREVIEW_RESOURCE', {
    resourceName: 'deals',
    resourceId: id,
  })
}
</script>
