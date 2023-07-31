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
import NotesTab from './components/RecordTabNote.vue'
import NotesTabPanel from './components/RecordTabNotePanel.vue'
import RecordTabTimelineNote from './components/RecordTabTimelineNote.vue'

if (window.Innoclapps) {
  Innoclapps.booting((Vue, router) => {
    Vue.component('NotesTab', NotesTab)
    Vue.component('NotesTabPanel', NotesTabPanel)
    Vue.component('RecordTabTimelineNote', RecordTabTimelineNote)
  })
}
