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
import { useLoader } from '~/Core/resources/js/composables/useLoader'

export const useTeams = createGlobalState(() => {
  const { setLoading, isLoading: teamsAreBeingFetched } = useLoader()

  const teams = ref([])

  const teamsByName = computed(() => orderBy(teams.value, 'name'))

  // Only excuted once
  fetchTeams()

  function idx(id) {
    return teams.value.findIndex(team => team.id == id)
  }

  function removeTeam(id) {
    teams.value.splice(idx(id), 1)
  }

  function addTeam(team) {
    teams.value.push(team)
  }

  function setTeam(id, team) {
    teams.value[idx(id)] = team
  }

  async function deleteTeam(id) {
    await Innoclapps.request().delete(`/teams/${id}`)
    removeTeam(id)
  }

  function fetchTeams() {
    setLoading(true)
    Innoclapps.request()
      .get('/teams')
      .then(({ data }) => (teams.value = data))
      .finally(() => setLoading(false))
  }

  return {
    teams,
    teamsByName,
    teamsAreBeingFetched,

    addTeam,
    removeTeam,
    setTeam,

    deleteTeam,
    fetchTeams,
  }
})
