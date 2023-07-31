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
export function useDraggable() {
  const draggableOptions = {
    delay: 15,
    delayOnTouchOnly: true,
    animation: 0,
    disabled: false,
    ghostClass: 'drag-ghost-rounded',
  }

  const scrollableDraggableOptions = {
    scroll: true,
    scrollSpeed: 50,
    forceFallback: true,
    ...draggableOptions.value,
  }

  return { draggableOptions, scrollableDraggableOptions }
}
