<template>
  <form
    @submit.prevent="saveThemeStyle(colors)"
    @keydown="form.onKeydown($event)"
  >
    <ICard
      :header="$t('themestyle::style.theme_style')"
      class="mb-3"
      :overlay="!componentReady"
    >
      <div
        v-for="(options, color) in colors"
        :key="color"
        class="mb-14 last:mb-0"
      >
        <div class="mb-2 items-center md:flex">
          <div
            class="flex-grow text-center text-base font-medium text-neutral-800 dark:text-neutral-100 md:text-left"
          >
            {{ color.charAt(0).toUpperCase() + color.slice(1) }}
          </div>

          <div class="flex items-center justify-center space-x-3">
            <a
              v-if="!isEqual(defaultColors[color], colors[color])"
              href="#"
              :class="[
                'link mr-3 text-sm',
                { 'pointer-events-none': resetting },
              ]"
              @click.prevent="reset(color)"
              v-t="'core::app.reset'"
            />

            <div class="relative">
              <label
                for="lMax"
                class="absolute -top-2.5 left-2 inline-block bg-white px-1 text-xs font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-300"
                v-t="'themestyle::style.lightness_maximum'"
              />
              <input
                type="number"
                id="lMax"
                v-model="options.lMax"
                @input="generatePalette(color)"
                class="block w-full rounded-md border-0 py-1 text-neutral-900 shadow-sm ring-1 ring-inset ring-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-neutral-700 dark:focus:ring-primary-500 sm:text-sm/6"
              />
            </div>
            <div class="relative">
              <label
                for="lMin"
                class="absolute -top-2.5 left-2 inline-block bg-white px-1 text-xs font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-300"
                v-t="'themestyle::style.lightness_minimum'"
              />
              <input
                type="number"
                id="lMin"
                v-model="options.lMin"
                @input="generatePalette(color)"
                class="block w-full rounded-md border-0 py-1 text-neutral-900 shadow-sm ring-1 ring-inset ring-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-neutral-700 dark:focus:ring-primary-500 sm:text-sm/6"
              />
            </div>
            <DropdownSelectInput
              :items="shades"
              v-model="options.valueStop"
              @change="generatePalette(color)"
            />

            <IFormInput
              class="mr-1 h-8 w-8 p-0"
              type="color"
              :modelValue="options.hex"
              @input="generatePalette(color, $event)"
            />
          </div>
        </div>
        <div
          class="flex flex-col justify-between space-y-1 overflow-hidden md:flex-row md:space-x-1 md:space-y-0"
        >
          <div
            v-for="(swatch, index) in options.swatches"
            :key="index"
            class="relative"
          >
            <div
              v-if="swatch.stop === options.valueStop"
              class="absolute left-1/2 top-10 hidden h-2 -translate-x-1/2 transform items-center justify-center md:flex"
            >
              <div
                class="-mt-2 h-2 w-2 rounded-full shadow"
                :style="{ backgroundColor: getContrast(swatch.hex) }"
              />
            </div>

            <Swatch
              :swatch="swatch"
              :color="color"
              v-model:hex="swatch.hex"
              @update:hex="updateUI()"
            />
          </div>
        </div>
      </div>
      <template #footer>
        <IButton
          type="submit"
          :disabled="form.busy"
          :text="$t('core::app.save')"
        />
      </template>
    </ICard>
  </form>
</template>
<script setup>
import { ref } from 'vue'
import { useSettings } from '~/Core/resources/js/views/Settings/useSettings'
import debounce from 'lodash/debounce'
import each from 'lodash/each'
import cloneDeep from 'lodash/cloneDeep'
import isEqual from 'lodash/isEqual'
import { createSwatches } from '../createSwatches'
import Swatch from './SettingsThemeStyleSwatch.vue'
import hexRgb from 'hex-rgb'
import { rgbToHex } from '../helpers'
import { whenever } from '@vueuse/core'
import defaultVarsString from '../../../../../resources/css/variables.css?inline'
import { getContrast } from '@/utils'
import {
  DEFAULT_PALETTE_CONFIG,
  DEFAULT_STOP,
  SHADES as shades,
} from '../constants'

const colorTypes = []
const defaultVars = {}
const excludedShades = [0, 950, 1000]

parseDefaultVars()

const defaultColors = {}
const colors = ref({})
const resetting = ref(false)

colorTypes.forEach(color => {
  colors.value[color] = getDefaultConfig(color)
  defaultColors[color] = getDefaultConfig(color)
})

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

whenever(componentReady, () => {
  if (originalSettings.value.theme_style) {
    setColors(originalSettings.value.theme_style)
  }
})

function reset(color) {
  resetting.value = true
  let c = cloneDeep(colors.value)

  delete c[color]

  saveThemeStyle(c, () => {
    colors.value[color] = getDefaultConfig(color)
    updateUI()
    resetting.value = false
  })
}

function setColors(colorsJsonString) {
  let themeStyle = JSON.parse(colorsJsonString)

  each(themeStyle, (options, color) => {
    colors.value[color] = options
  })
}

function saveThemeStyle(colors, callback = null) {
  let c = cloneDeep(colors)

  each(c, (options, color) => {
    if (isEqual(defaultColors[color], options)) {
      delete c[color]
    }
  })

  form.theme_style_modified_at = moment.utc().valueOf()
  form.theme_style = JSON.stringify(c)

  submit(callback)
}

function updateUI() {
  each(colors.value, (options, color) => {
    options.swatches.forEach(swatch => {
      let property = `--color-${color}-${swatch.stop}`

      document.documentElement.style.setProperty(
        property,
        colorForTailwind(swatch.hex),
        'important'
      )
    })
  })
}

function getDefaultConfig(color) {
  return {
    valueStop: DEFAULT_STOP,
    lMax: DEFAULT_PALETTE_CONFIG.lMax,
    lMin: DEFAULT_PALETTE_CONFIG.lMin,
    hex: defaultVars[color][DEFAULT_STOP].hex,
    swatches: shades
      .filter(shade => !excludedShades.includes(shade))
      .map(shade => ({
        stop: shade,
        hex: defaultVars[color][shade].hex,
      })),
  }
}

function colorForTailwind(hex) {
  const rgb = hexRgb(hex, { format: 'array' })

  return rgb[0] + ', ' + rgb[1] + ', ' + rgb[2]
}

const generatePalette = debounce(function (color, hex) {
  let colorConfig = colors.value[color]

  if (!hex) {
    hex = colorConfig.hex
  }

  colors.value[color].hex = hex

  const paletteConfig = Object.assign({}, DEFAULT_PALETTE_CONFIG, {
    value: hex.substr(1, hex.length - 1),
    valueStop: colorConfig.valueStop,
    lMax: colorConfig.lMax,
    lMin: colorConfig.lMin,
  })

  const palette = createSwatches(paletteConfig).filter(
    swatch => !excludedShades.includes(swatch.stop)
  )

  colors.value[color].swatches = palette.map(p => ({
    hex: p.hex,
    stop: p.stop,
  }))

  updateUI()
}, 300)

function parseDefaultVars() {
  const regex = /--color-([a-z]+)-([0-9]+):\s[0-9]+,\s[0-9]+,\s[0-9]+/gm
  let m

  while ((m = regex.exec(defaultVarsString)) !== null) {
    // This is necessary to avoid infinite loops with zero-width matches
    if (m.index === regex.lastIndex) {
      regex.lastIndex++
    }

    let rgbVar = m[0]
    let colorType = m[1]
    let shade = m[2]

    if (colorTypes.indexOf(colorType) === -1) {
      colorTypes.push(m[1])
    }

    if (!defaultVars.hasOwnProperty(colorType)) {
      defaultVars[colorType] = {}
    }

    if (!defaultVars[colorType].hasOwnProperty(shade)) {
      defaultVars[colorType][shade] = []
    }

    const rgbArray = rgbVar
      .replaceAll(',', '')
      .split(' ')
      .map(c => c.trim())

    defaultVars[colorType][shade] = {
      rgb: rgbVar,
      hex: rgbToHex(rgbArray[1], rgbArray[2], rgbArray[3]),
    }
  }
}
</script>
