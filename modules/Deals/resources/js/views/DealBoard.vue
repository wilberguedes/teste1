<template>
  <ILayout full :overlay="isLoading">
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />
      <div class="inline-flex items-center">
        <IButtonGroup class="mr-5">
          <IButton
            size="sm"
            :to="{ name: 'deal-index' }"
            class="relative focus:z-10"
            v-i-tooltip.bottom="$t('core::app.list_view')"
            variant="white"
            icon="Bars3"
            icon-class="w-4 h-4 text-neutral-500 dark:text-neutral-400"
          />
          <IButton
            class="relative bg-neutral-100 focus:z-10"
            size="sm"
            :to="{ name: 'deal-board' }"
            v-i-tooltip.bottom="$t('deals::board.board')"
            variant="white"
            icon="ViewColumns"
            icon-class="w-4 h-4 text-neutral-700 dark:text-neutral-100"
          />
        </IButtonGroup>

        <IButton
          @click="createDealRequested"
          icon="Plus"
          size="sm"
          :text="$t('deals::deal.create')"
        />
      </div>
    </template>

    <DealsBoard :board-id="resourceName" board-class="deals-board">
      <template #top>
        <div class="sm:flex sm:flex-wrap">
          <div
            v-if="filtersConfigured && hasRules"
            class="mb-1 mr-0 flex content-center space-x-1 sm:mb-0 sm:mr-1 sm:w-auto sm:flex-row"
          >
            <FiltersDropdown
              :identifier="filtersIdentifier"
              :view="filtersView"
              @apply="fetchAsNew"
              class="flex-1"
              placement="bottom-start"
            />

            <IButton
              variant="white"
              @click="toggleFiltersRules"
              v-show="hasRulesApplied && !rulesAreVisible"
              icon="PencilAlt"
            />

            <IButton
              v-show="!hasRulesApplied && !rulesAreVisible"
              variant="white"
              @click="toggleFiltersRules"
              :text="$t('core::filters.add_filter')"
              icon="Plus"
            />

            <!-- Filters -->
            <Filters
              v-if="filtersConfigured"
              :identifier="filtersIdentifier"
              :view="filtersView"
              :initial-apply="false"
              :active-filter-id="
                $route.query.filter_id
                  ? Number($route.query.filter_id)
                  : undefined
              "
              @apply="fetchAsNew"
            />
          </div>
          <SearchInput
            class="w-full sm:w-auto"
            @input="fetchAsNew"
            :placeholder="$t('core::app.search')"
            :disabled="hasRules && !rulesAreValid"
            v-model="query.q"
          />
          <div
            class="mt-4 flex w-full flex-wrap justify-between sm:ml-auto sm:mt-0 sm:w-auto md:justify-end"
          >
            <IModal
              size="sm"
              :hide-footer="true"
              :title="$t('deals::deal.pipeline.reorder')"
              v-model:visible="reoderPipelines"
            >
              <draggable
                v-model="pipelines"
                item-key="id"
                class="space-y-2 pb-2"
                handle=".pipeline-order-handle"
                v-bind="draggableOptions"
              >
                <template #item="{ element }">
                  <div
                    class="flex justify-between rounded border border-neutral-300 p-3 text-sm dark:border-neutral-700"
                  >
                    <p
                      class="font-medium text-neutral-700 dark:text-neutral-200"
                      v-text="element.name"
                    />
                    <IButtonIcon
                      icon="Selector"
                      class="pipeline-order-handle"
                    />
                  </div>
                </template>
              </draggable>
            </IModal>
            <div class="mr-2 w-52 flex-1" :class="{ blur: !filtersConfigured }">
              <IDropdown adaptive-width :text="activePipeline.name">
                <IDropdownItem
                  v-for="pipeline in pipelines"
                  :key="pipeline.id"
                  @click="setActivePipeline(pipeline)"
                  :text="pipeline.name"
                />
                <div
                  v-if="pipelines.length > 1 || canUpdateActivePipeline"
                  class="border-t border-neutral-200 py-1.5 dark:border-neutral-700"
                >
                  <IDropdownItem
                    v-if="pipelines.length > 1"
                    @click="reoderPipelines = true"
                    class="font-medium"
                    icon="Bars3BottomLeft"
                    :text="$t('deals::deal.pipeline.reorder')"
                  />
                  <IDropdownItem
                    v-if="canUpdateActivePipeline"
                    class="font-medium"
                    :to="{
                      name: 'edit-pipeline',
                      params: { id: activePipeline.id },
                    }"
                    icon="PencilAlt"
                    :text="$t('deals::deal.pipeline.edit')"
                  />
                </div>
              </IDropdown>
            </div>
            <div>
              <IButton
                variant="white"
                icon="SortAscending"
                v-i-tooltip="$t('core::app.sort')"
                v-i-modal="'boardSort'"
              />
              <BoardSortOptions @applied="handleSortApplied" />
            </div>
          </div>
        </div>
      </template>

      <BoardColumn
        v-for="[key, column] in stages"
        :key="column.name"
        :name="column.name"
        :column-id="column.id"
        v-model="column.cards"
        @drag-start="showBottomDropper = true"
        @drag-end="showBottomDropper = false"
        @updated="handleColumnUpdatedEvent"
        :move="onMoveCallback"
        :loader="loader"
        :board-id="resourceName"
      >
        <template #afterColumnHeader>
          <div class="flex items-center text-sm">
            <span
              v-if="
                column.win_probability === 100 ||
                isFilteringWonOrLostDeals ||
                summary[column.id].value === 0
              "
              class="text-neutral-600 dark:text-neutral-300"
              v-text="formatMoney(summary[column.id].value)"
            />
            <span
              v-else
              class="inline-flex items-center text-neutral-600 dark:text-neutral-300"
              v-i-tooltip.right="
                $t('deals::deal.stage.weighted_value', {
                  weighted_total: formatMoney(
                    summary[column.id].weighted_value
                  ),
                  win_probability: column.win_probability + '%',
                  total: formatMoney(summary[column.id].value),
                })
              "
            >
              <Icon icon="Scale" class="mr-1 h-4 w-4" />
              <span>
                {{ formatMoney(summary[column.id].weighted_value) }}
              </span>
            </span>
            <span class="mx-1 text-neutral-900 dark:text-neutral-300">-</span>
            <span class="text-neutral-700 dark:text-neutral-300">
              {{
                $t('deals::deal.count.all', { count: summary[column.id].count })
              }}
            </span>
          </div>
        </template>
        <template #topRight>
          <IButtonIcon
            icon="Plus"
            variant="primary"
            @click="createDealViaStage(column)"
          />
        </template>

        <template #card="{ card }">
 <div v-if="card.cf_whatsapp">
								<i-button @click="goToWhats(card.cf_whatsapp)" icon="whatsapp" size="sm">WhatsApp</i-button>
							</div>

          <DealBoardCard
            :deal-id="card.id"
            :display-name="card.display_name"
            :amount="card.amount || 0"
            :path="card.path"
            :status="card.status"
            :incomplete-activities-count="
              card.incomplete_activities_for_user_count
            "
            :expected-close-date="card.expected_close_date || undefined"
            :initial-swatch-color="card.swatch_color"
            :falls-behind-expected-close-date="
              card.falls_behind_expected_close_date
            "
            @create-activity-requested="createActivityForDeal = card"
            @swatch-color-updated="
              ;(card.swatch_color = $event), update(column)
            "
          />
        </template>
      </BoardColumn>
    </DealsBoard>

    <CreateDealModal
      v-model:visible="dealIsBeingCreated"
      @modal-hidden="dealCreateModalHidden"
      @created="dealCreated"
      :with-extended-submit-buttons="true"
      :go-to-list="false"
      v-bind="dealCreateProps"
    />

    <CreateActivityModal
      :visible="createActivityForDeal !== null"
      :deals="[createActivityForDeal]"
      :with-extended-submit-buttons="true"
      :go-to-list="false"
      @created="
        ({ isRegularAction }) => (
          isRegularAction ? (createActivityForDeal = null) : '', fetch()
        )
      "
      @modal-hidden="createActivityForDeal = null"
    />

    <!-- Deal Preview -->
    <PreviewModal />

    <BoardBottomDropper
      v-show="showBottomDropper"
      @update-request="updateRequest"
      @refresh-requested="fetch"
      @deleted="updateSummary"
      @won="updateSummary"
      :resource-id="resourceName"
    />
  </ILayout>
</template>
<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue'
import DealsBoard from '~/Core/resources/js/components/Board/Board.vue'
import BoardColumn from '~/Core/resources/js/components/Board/BoardColumn.vue'
import BoardBottomDropper from '../components/DealBoardBottomDropper.vue'
import FiltersDropdown from '~/Core/resources/js/components/Filters/FilterDropdown.vue'
import Filters from '~/Core/resources/js/components/Filters'
import BoardSortOptions from '../components/DealBoardSortOptions.vue'
import DealBoardCard from '../components/DealBoardCard.vue'
import omit from 'lodash/omit'
import find from 'lodash/find'
import map from 'lodash/map'
import reduce from 'lodash/reduce'
import draggable from 'vuedraggable'
import { useDraggable } from '~/Core/resources/js/composables/useDraggable'
import { useRoute } from 'vue-router'
import { useGate } from '~/Core/resources/js/composables/useGate'
import { useFilterable } from '~/Core/resources/js/components/Filters/useFilterable'
import { useQueryBuilder } from '~/Core/resources/js/components/QueryBuilder/useQueryBuilder'
import { useStorage } from '@vueuse/core'
import { useLoader } from '~/Core/resources/js/composables/useLoader'
import { usePipelines } from '../composables/usePipelines'
import { useGlobalEventListener } from '~/Core/resources/js/composables/useGlobalEventListener'
import { useAccounting } from '~/Core/resources/js/composables/useAccounting'
import { useI18n } from 'vue-i18n'

const defaulSort = {
  field: 'board_order',
  direction: 'asc',
}

const { gate } = useGate()
const { t } = useI18n()
const { setLoading, isLoading } = useLoader()

const lastUsedPipeline = useStorage('deals-board-last-pipeline')

const route = useRoute()
const { draggableOptions } = useDraggable()
const { formatMoney } = useAccounting()

const reoderPipelines = ref(false)

const resourceName = 'deals'
const filtersView = 'deals-board'
const filtersIdentifier = resourceName
const summary = ref({})
const query = ref({
  q: '',
  with_default_filter: true,
})
const createActivityForDeal = ref(null)
const createDealStage = ref(null)
const dealIsBeingCreated = ref(false)
const updateInProgress = ref(false)
// use Map to keep the keys in order
const stages = ref(new Map())
const recentlyCreatedDealsCount = ref(0)
const activePipeline = ref({})
const sortBy = ref(defaulSort)
const showBottomDropper = ref(false)
const filtersConfigured = ref(false)

const { orderedPipelines, setPipelines } = usePipelines()

const pipelines = computed({
  get() {
    return orderedPipelines.value
  },
  set(value) {
    const pipelinesWithDisplayOrder = map(value, (pipeline, index) =>
      Object.assign({}, pipeline, { user_display_order: index + 1 })
    )
    setPipelines(pipelinesWithDisplayOrder)
    savePipelinesOrder(pipelinesWithDisplayOrder)
  },
})

const {
  queryBuilderRules,
  resetQueryBuilderRules,
  rulesAreValid,
  hasRulesApplied,
  rulesAreVisible,
  toggleFiltersRules,
  findRule,
} = useQueryBuilder(filtersIdentifier, filtersView)

const { activeFilter, rules, hasRules, filters } = useFilterable(
  filtersIdentifier,
  filtersView
)

const isFilteringLostDeals = computed(() => {
  return findRule('status')?.query?.value === 'lost'
})

const isFilteringWonDeals = computed(() => {
  return findRule('status')?.query?.value === 'won'
})

const isFilteringWonOrLostDeals = computed(() => {
  return isFilteringWonDeals.value || isFilteringLostDeals.value
})

const canUpdateActivePipeline = computed(
  () =>
    activePipeline.value.authorizations &&
    activePipeline.value.authorizations.update
)

const urlPath = computed(() => '/deals/board/' + activePipeline.value.id)

const dealCreateProps = computed(() => {
  let object = {}
  let hiddenFields = []

  object['pipeline'] = activePipeline.value
  hiddenFields.push('pipeline_id')

  if (createDealStage.value) {
    object['stage-id'] = createDealStage.value
    hiddenFields.push('stage_id')
  }

  object['hidden-fields'] = hiddenFields

  return object
})

function getRequestQueryStringParams() {
  return {
    order: sortBy.value,
    rules: rulesAreValid.value ? queryBuilderRules.value : [],
    ...query.value,
  }
}

function onMoveCallback(evt, originalEvent) {
  if (gate.denies('update', evt.draggedContext.element)) {
    return false
  }
}

function handleSortApplied(sort) {
  // user applies sort,
  // makes http request with the new sort to retrieve sorted data from the back-end
  // after deals added to the front end, save each state new board_order
  sortBy.value = sort

  fetch().then(() => {
    sortBy.value = defaulSort
    stages.value.forEach(stage => update(stage))
    Innoclapps.info(t('deals::board.columns_sorted'))
  })
}

function savePipelinesOrder(pipelines) {
  Innoclapps.request()
    .post('/pipelines/order', {
      order: pipelines.map(pipeline => ({
        id: pipeline.id,
        display_order: pipeline.user_display_order,
      })),
    })
    .then(() => {
      lastUsedPipeline.value = undefined
    })
}

async function prepareFilters() {
  const requests = [
    Innoclapps.request().get(resourceName + '/rules'),
    Innoclapps.request().get('/filters/' + resourceName),
  ]

  let values = await Promise.all(requests)

  // We will remove the pipeline_id rule as the deals are queried based on active pipeline
  rules.value = values[0].data.filter(rule => rule.id !== 'pipeline_id')
  filters.value = values[1].data

  handleFiltersReady()
}

async function handleFiltersReady() {
  setLoading(false)
  let lastPipelneId = lastUsedPipeline.value

  let active = lastPipelneId
    ? find(pipelines.value, ['id', Number(lastPipelneId)])
    : undefined

  if (!active) {
    active = pipelines.value[0]
  }

  setActivePipeline(active)
  filtersConfigured.value = true
}

/**
 * When deal create modal is hidden
 *
 * Set the create data to false is reset createDealStage
 * The createDealStage data must be resetted because if user
 * click on the top button CREATE, the stage will be selected
 */

function dealCreateModalHidden() {
  // If there are deals created, perform fetch
  // This helps not performing fetch each time the modal is hidden
  // e.q. user can click Create and add another too so in this case,
  // we will increment the recentlyCreatedDealsCount data in the created event
  // and will refetch the board only when the modal is hidden
  if (recentlyCreatedDealsCount.value > 0) {
    fetch()
  }

  createDealStage.value = null
  recentlyCreatedDealsCount.value = 0
}

function createDealViaStage(stage) {
  createDealStage.value = Number(stage.id)
  dealIsBeingCreated.value = true
}

/**
 * On deal create reqeuested set the create data to true, so the modal can be shown
 */
function createDealRequested() {
  dealIsBeingCreated.value = true
}

/**
 * On deal create, refetch data and hide the modal
 */
function dealCreated(data) {
  recentlyCreatedDealsCount.value++

  if (data.isRegularAction) {
    dealIsBeingCreated.value = false
  }
}

async function loader(columnId, $state) {
  let column = stages.value.get(columnId)

  if (!column.hasOwnProperty('infinityState')) {
    column.infinityState = $state
  }

  column.page += 1

  const { data: stage } = await Innoclapps.request().get(
    `${urlPath.value}/${column.id}`,
    {
      params: {
        ...getRequestQueryStringParams(),
        page: column.page,
      },
    }
  )

  if (stage.cards.length === 0) {
    $state.complete()
  } else {
    stages.value.set(
      stage.id,
      Object.assign(
        stages.value.get(stage.id),
        omit(stage, ['cards', 'summary'])
      )
    )

    stage.cards.forEach(card => {
      stages.value.get(stage.id).cards.push(card)
    })

    $state.loaded()
  }
}

function fetchAsNew() {
  fetch(false)
}

async function fetch(refresh = true) {
  let originalInfinityState = {}

  if (refresh) {
    let pages = {}

    stages.value.forEach(stage => {
      pages[stage.id] = stage.page
    })

    query.value.pages = pages
  } else {
    delete query.value.pages

    // We will check before full refresh if the stage has infinity state
    // and we will paused it, then later attach it again to the stage.
    // we are pausing the infinity loader because the scroll is invoked
    // when pre refresh the column had scroll and now the new data of the column does not have scroll
    // e.q. open board with open deals filter, load more stages, change filter to won deals, the infinity
    // will be invoked because the scrollbar changes, but we don't want that as this a full refresh
    stages.value.forEach(stage => {
      if (stage.infinityState) {
        stage.infinityState.pause()
        originalInfinityState[stage.id] = stage.infinityState
      }
    })
  }

  setLoading(true)

  try {
    const { data } = await Innoclapps.request().get(urlPath.value, {
      params: getRequestQueryStringParams(),
    })

    if (!refresh) {
      stages.value = new Map()
      summary.value = {}
    }

    data.forEach(stage => {
      stage.page = refresh ? stages.value.get(stage.id)?.page || 1 : 1
      stages.value.set(stage.id, omit(stage, ['summary']))

      // Add the infinity state again to the stage and resume it.
      nextTick(() => {
        setTimeout(() => {
          if (originalInfinityState.hasOwnProperty(stage.id)) {
            stages.value.set(
              stage.id,
              Object.assign(stages.value.get(stage.id), {
                infinityState: originalInfinityState[stage.id],
              })
            )

            stages.value.get(stage.id).infinityState.resume()
            delete originalInfinityState[stage.id]
          }
        }, 300)
      })
      summary.value[stage.id] = stage.summary
    })

    return data
  } finally {
    setLoading(false)
    delete query.value.pages
  }
}

function setActivePipeline(pipeline) {
  lastUsedPipeline.value = pipeline.id
  activePipeline.value = pipeline

  fetchAsNew()
}

function updateSummary() {
  Innoclapps.request()
    .get(`${urlPath.value}/summary`, {
      params: getRequestQueryStringParams(),
    })
    .then(({ data }) =>
      Object.keys(data).forEach(
        stageId => (summary.value[stageId] = data[stageId])
      )
    )
}

/**
 * Update column deals order and stage belongings
 */
function update(stage) {
  updateRequest(
    reduce(
      stage.cards,
      (result, deal, key) => {
        result.push({
          id: deal.id,
          board_order: key + 1,
          stage_id: stage.id,
          swatch_color: deal.swatch_color ? deal.swatch_color : null,
        })
        return result
      },
      []
    )
  )
}

/**
 * Perform an update request
 */
function updateRequest(data) {
  updateInProgress.value = true

  Innoclapps.request()
    .post(urlPath.value, data)
    .finally(() => {
      updateInProgress.value = false
      updateSummary()
    })
}

/**
 * Update the deals order and stage
 */
function handleColumnUpdatedEvent(event) {
  update(stages.value.get(event.columnId))
}

/**
 * Check whether there is update in progress and show message before leaving
 */
function checkUpdateInProgress() {
  if (updateInProgress.value) {
    window.confirm(
      'Update is in progress, please wait till the update finishes, if you still can see the message after few seconds, try to force-refresh the page.'
    )
  }
}

prepareFilters()

// Allow passing filter_id via URL query string, e.q. when using highlights link
if (route.query.filter_id) {
  delete query.value.with_default_filter
  query.value.filter_id = route.query.filter_id
} else if (activeFilter.value) {
  // We will check if there is an active filter already applied in store
  // helps keeping the previous filter applied when navigating from the page
  // where the filters are mounted and then going back
  delete query.value.with_default_filter
  query.value.filter_id = activeFilter.value.id
}

window.addEventListener('beforeunload', checkUpdateInProgress)

useGlobalEventListener('deals-record-updated', fetch)

onUnmounted(() => {
  resetQueryBuilderRules()
  rulesAreVisible.value = false
  window.removeEventListener('beforeunload', checkUpdateInProgress)
})
</script>
