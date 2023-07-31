<template>
  <ITable v-bind="$attrs">
    <thead>
      <tr>
        <th
          v-for="channel in allAvailableChannels"
          :key="'heading-' + channel"
          class="text-left"
          width="6%"
          scope="col"
          v-i-tooltip="$t('core::notifications.channels.' + channel)"
        >
          <Icon class="h-5 w-5" :icon="iconMaps[channel]" />
        </th>
        <th
          class="text-left"
          width="auto"
          v-text="$t('core::notifications.notification')"
        />
      </tr>
    </thead>
    <tbody>
      <tr v-for="notification in settings" :key="notification.key">
        <td v-for="channel in allAvailableChannels" :key="channel">
          <IFormCheckbox
            v-if="notification.channels.indexOf(channel) > -1"
            :id="channel + '-' + notification.key"
            v-model:checked="form.notifications_settings[notification.key][channel]"
          />
          <Icon class="h-5 w-5" icon="X" v-else />
        </td>
        <td>
          <p
            class="text-neutral-700 dark:text-neutral-300"
            v-text="notification.name"
          />
          <p
            v-show="notification.description"
            class="mt-1 text-sm text-neutral-500 dark:text-neutral-300"
            v-text="notification.description"
          />
        </td>
      </tr>
    </tbody>
  </ITable>
</template>
<script>
export default {
  inheritAttrs: false,
}
</script>
<script setup>
import map from 'lodash/map'
import uniq from 'lodash/uniq'
import flatten from 'lodash/flatten'

const props = defineProps({
  form: { required: true, type: Object },
})

const settings = Innoclapps.config('notifications_settings')

const iconMaps = {
  mail: 'Mail',
  database: 'Bell',
}

const allAvailableChannels = uniq(flatten(map(settings, 'channels')))
</script>
