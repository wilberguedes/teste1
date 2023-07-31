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
import moment from 'moment-timezone'

// import other locales as they are added
import 'moment/dist/locale/pt-br'
import 'moment/dist/locale/es'
import 'moment/dist/locale/ru'

import momentPhp from './momentPhp'
import { getLocale } from '@/utils'

const getMomentLocale = () => getLocale().replace('_', '-')

// If the locale is not imported, will fallback to en
moment.locale(
  moment.locales().indexOf(getMomentLocale()) === -1 ? 'en' : getMomentLocale()
)

momentPhp(moment)

window.moment = moment
