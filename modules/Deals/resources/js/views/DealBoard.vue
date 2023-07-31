<template>
	<i-layout full :overlay="loading">
		<template #actions>
			<navbar-separator class="hidden lg:block" />
			<div class="inline-flex items-center">
				<i-button-group class="mr-5">
					<i-button
							  size="sm"
							  :to="{ name: 'deal-index' }"
							  class="relative focus:z-10"
							  v-i-tooltip="$t('app.list_view')"
							  variant="white"
							  icon="ViewList"
							  icon-class="w-4 h-4 text-neutral-500 dark:text-neutral-400" />
					<i-button
							  class="relative bg-neutral-100 focus:z-10"
							  size="sm"
							  :to="{ name: 'deal-board' }"
							  v-i-tooltip="$t('board.board')"
							  variant="white"
							  icon="ViewBoards"
							  icon-class="w-4 h-4 text-neutral-700 dark:text-neutral-100" />
				</i-button-group>

				<i-button @click="createDealRequested" icon="Plus" size="sm">{{
				$t('deal.create')
				}}</i-button>
			</div>
		</template>

		<deals-board
					 :columns="stages"
					 :board-id="resourceName"
					 board-class="deals-board"
					 @drag-start="showBottomDropper = true"
					 @drag-end="showBottomDropper = false"
					 @column-updated="handleColumnUpdatedEvent">
			<template #top>
				<div class="sm:flex sm:flex-wrap">
					<div
						 v-if="filtersConfigured && hasRules"
						 class="mb-1 mr-0 flex content-center space-x-1 sm:mb-0 sm:mr-1 sm:w-auto sm:flex-row">
						<filters-dropdown
										  :identifier="filtersIdentifier"
										  :view="filtersView"
										  @apply="fetch"
										  class="flex-1"
										  placement="bottom-start" />

						<i-button
								  variant="white"
								  @click="toggleFiltersRules"
								  v-show="hasRulesApplied && !rulesAreVisible"
								  icon="PencilAlt" />

						<i-button
								  variant="white"
								  @click="toggleFiltersRules"
								  v-show="!hasRulesApplied && !rulesAreVisible"
								  icon="Plus">
							{{ $t('filters.add_filter') }}
						</i-button>

						<!-- Filters -->
						<filters
								 v-if="filtersConfigured"
								 :identifier="filtersIdentifier"
								 :initial-apply="false"
								 :view="filtersView"
								 :active-filter-id="
								   $route.query.filter_id
								     ? Number($route.query.filter_id)
								     : undefined
								 "
								 @apply="fetch" />
					</div>
					<input-search
								  class="w-full sm:w-auto"
								  @input="fetch"
								  :placeholder="$t('app.search')"
								  :disabled="hasRules && !rulesAreValid"
								  v-model="query.q" />
					<div
						 class="mt-4 flex w-full flex-wrap justify-between sm:mt-0 sm:ml-auto sm:w-auto md:justify-end">
						<i-modal
								 size="sm"
								 :hide-footer="true"
								 :title="$t('deal.pipeline.reorder')"
								 v-model:visible="reoderPipelines">
							<draggable
									   v-model="pipelines"
									   item-key="id"
									   class="space-y-2 pb-2"
									   handle=".pipeline-order-handle"
									   v-bind="draggableOptions">
								<template #item="{ element }">
									<div
										 class="flex justify-between rounded border border-neutral-300 p-3 text-sm dark:border-neutral-700">
										<p
										   class="font-medium text-neutral-700 dark:text-neutral-200"
										   v-text="element.name" />
										<i-button-icon
													   icon="Selector"
													   class="pipeline-order-handle" />
									</div>
								</template>
							</draggable>
						</i-modal>
						<div class="mr-2 w-52 flex-1" :class="{ blur: !filtersConfigured }">
							<i-dropdown auto-size="min" :text="activePipeline.name">
								<i-dropdown-item
												 v-for="pipeline in pipelines"
												 :key="pipeline.id"
												 @click="setActivePipeline(pipeline)"
												 :text="pipeline.name" />
								<div
									 class="border-t border-neutral-200 py-1.5 dark:border-neutral-700"
									 v-if="pipelines.length > 1 || canUpdateActivePipeline">
									<i-dropdown-item
													 v-if="pipelines.length > 1"
													 @click="reoderPipelines = true"
													 class="font-medium"
													 icon="MenuAlt2"
													 :text="$t('deal.pipeline.reorder')" />
									<i-dropdown-item
													 v-if="canUpdateActivePipeline"
													 class="font-medium"
													 :to="{
													   name: 'edit-pipeline',
													   params: { id: activePipeline.id },
													 }"
													 icon="PencilAlt">{{ $t('deal.pipeline.edit') }}</i-dropdown-item>
								</div>
							</i-dropdown>
						</div>
						<div>
							<i-button
									  variant="white"
									  icon="SortAscending"
									  v-i-tooltip="$t('app.sort')"
									  v-i-modal="'boardSort'" />
							<board-sort-options
												:pipeline="activePipeline"
												v-model="sortBy"
												@sort-applied="fetch" />
						</div>
					</div>
				</div>
			</template>
			<template #afterColumnHeader="{ column }">
				<span class="text-sm text-neutral-900 dark:text-neutral-300">{{
				formatMoney(summary[column.id].value)
				}}</span>
				<span class="mx-1 text-neutral-900 dark:text-neutral-300">-</span>
				<span class="text-sm text-neutral-900 dark:text-neutral-300">{{
				$t('deal.count_total', { total: summary[column.id].count })
				}}</span>
			</template>
			<template #topRight="{ column }">
				<i-button-icon icon="Plus" @click="createDealViaStage(column)" />
			</template>

			<template #card="{ card, column }">
				<div
					 class="bottom-hidden p-2"
					 v-if="card.swatch_color"
					 :style="{ background: card.swatch_color }" />
				<div
					 class="bottom-hidden group p-4"
					 :class="{
					   'bg-danger-50/50': card.falls_behind_expected_close_date === true,
					 }">
					<div class="flex">
						<div class="grow truncate">
							<a
							   class="truncate text-sm font-medium text-neutral-900 hover:text-neutral-500 dark:text-white"
							   :href="card.path"
							   @click.prevent="preview(card.id)"
							   v-text="card.display_name" />

							<div class="mt-1 flex text-sm">
								<p
								   :class="{
								     'text-warning-500':
								       card.incomplete_activities_for_user_count > 0,
								     'text-success-500':
								       card.incomplete_activities_for_user_count === 0,
								   }"
								   v-text="
								     $tc(
								       'activity.count',
								       card.incomplete_activities_for_user_count,
								       { count: card.incomplete_activities_for_user_count }
								     )
								   " />
								<p
								   v-if="card.amount"
								   class="ml-2 text-neutral-700 dark:text-neutral-300"
								   v-text="formatMoney(card.amount)" />

							</div>
							<p
							   class="mt-1 text-xs text-neutral-700 dark:text-neutral-300"
							   v-show="card.expected_close_date">
								{{ $t('fields.deals.expected_close_date') }}:
								{{ localizedDate(card.expected_close_date) }}
							</p>
							<div v-if="card.cf_whatsapp">
								<i-button @click="goToWhats(card.cf_whatsapp)" icon="whatsapp" size="sm">WhatsApp</i-button>
							</div>
						</div>
						<div>
							<div class="flex flex-col items-center space-y-1">
								<i-minimal-dropdown>
									<i-dropdown-item @click="createActivityForDeal = card">{{
									$t('activity.create')
									}}</i-dropdown-item>
									<i-dropdown-item @click="$router.push(card.path)">{{
									$t('app.view_record')
									}}</i-dropdown-item>
									<i-dropdown-item @click="preview(card.id)">{{
									$t('app.preview')
									}}</i-dropdown-item>
								</i-minimal-dropdown>
								<i-popover>
									<i-button-icon
												   icon="ColorSwatch"
												   class="block md:hidden md:group-hover:block" />

									<template #popper>
										<div class="w-56 max-w-xs">
											<i-color-swatches
															  v-model="card.swatch_color"
															  :swatches="swatches"
															  :allow-custom="false"
															  @input="update(column)" />
										</div>
									</template>
								</i-popover>
							</div>
						</div>
					</div>
				</div>
			</template>
		</deals-board>
		<deal-create
					 @hidden="dealCreateModalHidden"
					 :visible="createDealModal === true"
					 @created="dealCreated"
					 v-bind="dealCreateProps" />
		<create-activity
						 :visible="createActivityForDeal !== null"
						 :hide-on-created="true"
						 :deals="[createActivityForDeal]"
						 @created="fetch"
						 @hidden="createActivityForDeal = null" />
		<!-- Deal Preview -->
		<preview-modal @action-executed="fetch" />
		<board-bottom-dropper
							  v-show="showBottomDropper"
							  :pipeline="activePipeline"
							  @update-request="updateRequest"
							  @refresh-requested="fetch"
							  @deleted="updateSummary"
							  @won="updateSummary"
							  :resource-id="resourceName" />
	</i-layout>
</template>
<script>
const qs = require('qs')
import DealsBoard from '@/components/Board/Board'
import BoardBottomDropper from '@/views/Deals/BoardBottomDropper'
import FiltersDropdown from '@/components/Filters/FiltersDropdown'
import Filters from '@/components/Filters'
import Filterable from '@/components/Filters/Filterable'
import DealCreate from '@/views/Deals/CreateViaBoard'
import CreateActivity from '@/views/Activity/CreateSimple'
import BoardSortOptions from '@/views/Deals/BoardSortOptions'
import reduce from 'lodash/reduce'
import { formatMoney } from '@/utils'
import { mapActions } from 'vuex'
import find from 'lodash/find'
import map from 'lodash/map'
import draggable from 'vuedraggable'
import ProvidesDraggableOptions from '@/mixins/ProvidesDraggableOptions'

const defaulSort = {
	field: 'board_order',
	direction: 'asc',
}

export default {
	components: {
		draggable,
		DealsBoard,
		Filters,
		FiltersDropdown,
		BoardBottomDropper,
		DealCreate,
		CreateActivity,
		BoardSortOptions,
	},
	mixins: [Filterable, ProvidesDraggableOptions],
	data: () => ({
		reoderPipelines: false,
		swatches: Innoclapps.config.favourite_colors,
		resourceName: 'deals',
		summary: {},
		query: {
			q: '',
			with_default_filter: true,
		},
		createActivityForDeal: null,
		createDealModal: false,
		stages: [],
		updateInProgress: false,
		createDealStage: null,
		totalDealsCreated: 0,
		activePipeline: {},
		sortBy: defaulSort,
		loading: true,
		showBottomDropper: false,
		filtersConfigured: false,
	}),
	computed: {
		pipelines: {
			get() {
				return this.$store.state.pipelines.collection
			},
			set(value) {
				const pipelines = map(value, (pipeline, index) => {
					return Object.assign({}, pipeline, { user_display_order: index + 1 })
				})
				this.$store.commit('pipelines/SET', pipelines)
				this.savePipelinesOrder(pipelines)
			},
		},
		/**
		 * Get the filters identifier
		 */
		filtersIdentifier() {
			return this.resourceName
		},

		/**
		 * Get the filters view
		 */
		filtersView() {
			return 'deals-board'
		},

		/**
		 * Indicates whether the current user can update the active pipeline
		 *
		 */
		canUpdateActivePipeline() {
			return (
				this.activePipeline.authorizations &&
				this.activePipeline.authorizations.update
			)
		},

		/**
		 * The board url path for the request
		 *
		 * @return {String}
		 */
		urlPath() {
			return '/deals/board/' + this.activePipeline.id
		},

		/**
		 * Create props for deal create
		 *
		 * @return {Object}
		 */
		dealCreateProps() {
			let props = {}

			props['selected-pipeline'] = this.activePipeline

			if (this.createDealStage) {
				props['selected-stage-id'] = this.createDealStage
			}

			return props
		},

		/**
		 * Get the board request query string
		 *
		 * @return {String}
		 */
		requestQueryString() {
			return qs.stringify({
				order: this.sortBy,
				rules: this.rulesAreValid ? this.rules : [],
				...this.query,
			})
		},
	},
	methods: {
		...mapActions('deals', ['preview']),
		formatMoney,

		/**
		 * Save the pipelines display order
		 */
		savePipelinesOrder(pipelines) {
			Innoclapps.request()
				.post('/pipelines/order', {
					order: pipelines.map(pipeline => ({
						id: pipeline.id,
						display_order: pipeline.user_display_order,
					})),
				})
				.then(() => localForage.removeItem('deals-board-last-pipeline'))
		},

		/**
		 * Get the resource filters and rules
		 *
		 * @return {Promise}
		 */
		async fetchAndSetFilters() {
			const requests = [
				Innoclapps.request().get(this.resourceName + '/rules'),
				Innoclapps.request().get(this.resourceName + '/filters'),
			]

			let values = await Promise.all(requests)

			this.$store.dispatch('filters/setFiltersAndRules', {
				identifier: this.filtersIdentifier,
				rules: values[0].data,
				filters: values[1].data,
			})

			this.handleFiltersReady()
		},

		/**
		 * Handle filters ready event
		 *
		 * @return {Void}
		 */
		async handleFiltersReady() {
			this.loading = false
			let lastPipelneId = await localForage
				.getItem('deals-board-last-pipeline')
				.then((value, err) => (err ? null : value))

			if (lastPipelneId) {
				let pipeline = find(this.pipelines, ['id', lastPipelneId])

				if (pipeline) {
					this.setActivePipeline(pipeline)
					this.filtersConfigured = true
					return
				}
			}

			this.setActivePipeline(this.pipelines[0])
			this.filtersConfigured = true
		},


		goToWhats(whats) {
			window.open('https://wa.me/' + whats, '_blank');
		},

		/**
		 * When deal create modal is hidden
		 *
		 * Set the create data to false is reset createDealStage
		 * The createDealStage data must be resetted because if user
		 * click on the top button CREATE, the stage will be selected
		 *
		 * @return {Void}
		 */
		dealCreateModalHidden() {
			// If there are deals created, perform fetch
			// This helps not performing fetch each time the modal is hidden
			// e.q. user can click Create and add another too so in this case,
			// we will increment the totalDealsCreated data in the created event
			// and will refetch the board only when the modal is hidden
			if (this.totalDealsCreated > 0) {
				this.fetch()
			}

			this.createDealModal = false
			this.createDealStage = null
			this.totalDealsCreated = 0
		},

		/**
		 * New deal create via stage
		 * @param  {Object} stage
		 *
		 * @return {Void}
		 */
		createDealViaStage(stage) {
			this.createDealStage = stage.id
			this.createDealModal = true
		},

		/**
		 * On deal create reqeuested set the create data to true
		 * So the modal can be shown
		 *
		 * @return {Void}
		 */
		createDealRequested() {
			this.createDealModal = true
		},

		/**
		 * On deal create, refetch data and hide the modal
		 * @param {Object} data
		 *
		 * @return {Void}
		 */
		dealCreated(data) {
			this.totalDealsCreated++

			if (!data.wantAnother) {
				this.createDealModal = false
			}
		},

		/**
		 * Fetch deals in active pipeline
		 *
		 * @return {Void}
		 */
		fetch() {
			this.loading = true

			Innoclapps.request()
				.get(`${this.urlPath}?${this.requestQueryString}`)
				.then(({ data }) => {
					data.forEach(stage => {
						this.summary[stage.id] = stage.summary
					})

					this.stages = data
				})
				.finally(() => (this.loading = false))
		},

		/**
		 * Set active pipeline
		 * @param  {Object} pipeline
		 * @return {Void}
		 */
		setActivePipeline(pipeline) {
			localForage.setItem('deals-board-last-pipeline', pipeline.id)
			this.activePipeline = pipeline

			this.sortBy = pipeline.user_default_sort_data
				? this.cleanObject(pipeline.user_default_sort_data)
				: defaulSort

			this.fetch()
		},

		/**
		 * Update the board summary
		 *
		 * @return {Void}
		 */
		updateSummary() {
			Innoclapps.request()
				.get(`${this.urlPath}/summary?${this.requestQueryString}`)
				.then(({ data }) =>
					Object.keys(data).forEach(
						stageId => (this.summary[stageId] = data[stageId])
					)
				)
		},

		/**
		 * Update column deals order and stage belongings
		 *
		 * @param  {Object} stage
		 *
		 * @return {Void}
		 */
		update(stage) {
			this.updateRequest(
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
		},

		/**
		 * Perform an update request
		 *
		 * @param  {Array} data
		 *
		 * @return {Void}
		 */
		updateRequest(data) {
			this.updateInProgress = true

			Innoclapps.request()
				.post(this.urlPath, data)
				.finally(() => {
					this.updateInProgress = false
					this.updateSummary()
				})
		},

		/**
		 * Update the deals order and stage
		 * @param  {Object} event
		 * @return {Void}
		 */
		handleColumnUpdatedEvent(event) {
			this.update(event.column)
		},

		/**
		 * Check whether there is update in progress and show
		 * message before leaving
		 *
		 * @return {Void}
		 */
		checkUpdateInProgress() {
			if (this.updateInProgress) {
				window.confirm(
					'Update is in progress, please wait till the update finishes, if you still can see the message after few seconds, try to force-refresh the page.'
				)
			}
		},
	},
	created() {
		this.fetchAndSetFilters()
		// Allow passing filter_id via URL query string, e.q. when using highlights link
		if (this.$route.query.filter_id) {
			delete this.query.with_default_filter
			this.query.filter_id = this.$route.query.filter_id
		}

		window.addEventListener('beforeunload', this.checkUpdateInProgress)
	},
	mounted() {
		Innoclapps.$on('deals-record-updated', this.fetch)
		localForage.setItem('deals-board-view-default', true)
	},
	unmounted() {
		Innoclapps.$off('deals-record-updated', this.fetch)
		window.removeEventListener('beforeunload', this.checkUpdateInProgress)
	},
}
</script>
