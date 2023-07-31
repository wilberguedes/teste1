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
/**
 * Determine the type based on the given minutes
 *
 * @param  {Number} minutes
 *
 * @return {String}
 */
function determineReminderTypeBasedOnMinutes(minutes) {
  if (minutes < 59) {
    return 'minutes'
  } else if (minutes >= 10080) {
    return 'weeks'
  } else if (minutes >= 1440) {
    return 'days'
  }

  return 'hours'
}

/**
 * Determine the field value based on the given minutes
 *
 * @param  {Number} minutes
 *
 * @return {Number}
 */
function determineReminderValueBasedOnMinutes(minutes) {
  const type = determineReminderTypeBasedOnMinutes(minutes)

  if (type === 'minutes') {
    return minutes
  } else if (type === 'hours') {
    return minutes / 60
  } else if (type === 'days') {
    return minutes / 1440
  } else if (type === 'weeks') {
    return minutes / 10080
  }
}

export {
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
}
