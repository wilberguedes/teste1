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
import randomString from './randomString'
import isValueEmpty from './isValueEmpty'
import passiveEventArg from './passiveEventArg'
import isVisible from './isVisible'
import getLocale from './getLocale'
import windowState from './windowState'
import strTitle from './strTitle'
import strTruncate from './strTruncate'
import getContrast from './getContrast'
import lightenDarkenColor from './lightenDarkenColor'
import timelineLabels from './timelineLabels'
import formatBytes from './formatBytes'
import shadeColor from './shadeColor'
import throwConfetti from './throwConfetti'
import isPurchaseKey from './isPurchaseKey'
import isDarkMode from './isDarkMode'

import {
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
} from './reminders'

export {
  randomString,
  isValueEmpty,
  passiveEventArg,
  isVisible,
  getLocale,
  windowState,
  strTitle,
  strTruncate,
  getContrast,
  lightenDarkenColor,
  timelineLabels,
  formatBytes,
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
  shadeColor,
  throwConfetti,
  isPurchaseKey,
  isDarkMode,
}
