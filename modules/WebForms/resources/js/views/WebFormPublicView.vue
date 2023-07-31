<template>
  <div
    :class="['m-auto', { 'w-full': isEmbedded, 'max-w-2xl': !isEmbedded }]"
    :style="{ '--primary-contrast': getContrast(primaryColor) }"
    dusk="web-form"
  >
    <img v-if="logo" :src="logo" class="mx-auto mb-3 mt-10 h-12 w-auto" />

    <ICard
      :rounded="!isEmbedded"
      :shadow="!isEmbedded"
      :class="[
        'm-4 sm:m-8 sm:p-3',
        {
          'my-5': !isEmbedded,
        },
      ]"
    >
      <!--     <div
          id="testDiv"
          class="flex text-white space-x-2 rounded-md items-center"
        ></div> -->
      <div v-if="showSuccessMessage">
        <h4
          class="text-lg text-neutral-800"
          v-text="submitData.success_title"
        />
        <div
          class="wysiwyg-text"
          v-show="submitData.success_message"
          v-html="submitData.success_message"
        />
      </div>
      <IAlert
        v-else-if="!hasDefinedSections"
        variant="warning"
        class="border border-warning-200"
      >
        {{ $t('webforms::form.no_sections') }}
      </IAlert>
      <form v-else @submit.prevent="submit" novalidate="true">
        <component
          v-for="(section, index) in filteredSections"
          :key="index"
          :is="fieldComponents[section.type]"
          :form="form"
          :section="section"
        />
      </form>
    </ICard>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import hexRgb from 'hex-rgb'
import FieldSection from '../components/DisplaySections/FieldSection.vue'
import FileSection from '../components/DisplaySections/FileSection.vue'
import IntroductionSection from '../components/DisplaySections/IntroductionSection.vue'
import MessageSection from '../components/DisplaySections/MessageSection.vue'
import SubmitButtonSection from '../components/DisplaySections/SubmitButtonSection.vue'
import filter from 'lodash/filter'
import map from 'lodash/map'
import { lightenDarkenColor, getContrast } from '@/utils'
import { useResourceFields } from '~/Core/resources/js/composables/useResourceFields'
import { useRoute } from 'vue-router'
import { useFieldsForm } from '~/Core/resources/js/components/Fields/useFieldsForm'

const props = defineProps({
  sections: { required: true, type: Array },
  styles: { required: true, type: Object },
  submitData: { required: true, type: Object },
  publicUrl: { required: true, type: String },
  logo: String,
})

const fieldComponents = {
  'field-section': FieldSection,
  'file-section': FileSection,
  'introduction-section': IntroductionSection,
  'message-section': MessageSection,
  'submit-button-section': SubmitButtonSection,
}

const route = useRoute()

const { fields } = useResourceFields(
  props.sections
    .filter(section => isFieldSection(section) && section.field)
    .map(section => section.field)
)

const { form } = useFieldsForm(fields)
const showSuccessMessage = ref(false)

/**
 * Get the sections filter with the missing fields and mapped with their actual fields
 */
const filteredSections = computed(() => {
  let mapped = map(props.sections, section => {
    if (isFieldSection(section)) {
      section.field = fields.value.find(section.requestAttribute)
    }
    return section
  })

  // We will check if any fields are not found (removed)
  return filter(mapped, section => {
    if (!isFieldSection(section)) {
      return true
    }

    // Field removed?
    if (!section.field) {
      return false
    }

    return true
  })
})

const hasDefinedSections = computed(() => filteredSections.value.length > 0)

const bgColor = computed(() => {
  if (route.query.hasOwnProperty('bgColor')) {
    return route.query.bgColor
  }

  return props.styles.background_color
})

const primaryColor = computed(() => {
  if (route.query.hasOwnProperty('primaryColor')) {
    return '#' + route.query.primaryColor
  }

  return props.styles.primary_color
})

/**
 * Check whether the form is embedded in an iframe
 */
const isEmbedded = computed(() => route.query.e === 'true')

/**
 * Convert the given hex color to Tailwind compatible rgb
 */
function colorForTailwind(hex) {
  const rgb = hexRgb(hex, { format: 'array' })

  return rgb[0] + ', ' + rgb[1] + ', ' + rgb[2]
}

/**
 * Check whether the given section is field section
 */
function isFieldSection(section) {
  return section.type === 'field-section'
}

/**
 * Submit the form
 *
 * @return {Void}
 */
function submit() {
  form
    .hydrate()
    .post(props.publicUrl)
    .then(data => {
      if (props.submitData.action === 'redirect') {
        if (window.top) {
          window.top.location.href = props.submitData.success_redirect_url
        } else {
          window.location.href = props.submitData.success_redirect_url
        }
      } else {
        showSuccessMessage.value = true
      }
    })
}

window.addEventListener('DOMContentLoaded', e => {
  document.body.style.backgroundColor = bgColor.value
  document.getElementById('app').style.backgroundColor = bgColor.value
})

// https://codepen.io/yonatankra/pen/POvYoG
// https://css-tricks.com/snippets/javascript/lighten-darken-color/
let originalStyles = new WeakMap() //  or a plain object storing ids

let nativeSupport = (function () {
  let bodyStyles = window.getComputedStyle(document.body)
  let fooBar = bodyStyles.getPropertyValue('--color-primary-50') // some variable from CSS
  return !!fooBar
})()

// Based on https://gist.github.com/tmanderson/98bbd05899995fd35443
function processCSSVariables(input) {
  let styles = Array.prototype.slice.call(
      document.querySelectorAll('style'),
      0
    ),
    defRE = /(\-\-[-\w]+)\:\s*(.*?)\;/g,
    overwrites = input || {}

  if (nativeSupport) {
    Object.keys(overwrites).forEach(function (property) {
      document.body.style.setProperty('--' + property, overwrites[property])
    })
    return
  }

  function refRE(name) {
    return new RegExp('var\\(\s*' + name + '\s*\\)', 'gmi')
  }

  styles.forEach(function (styleElement) {
    let content =
        originalStyles[styleElement] ||
        (originalStyles[styleElement] = styleElement.textContent),
      vars

    while ((vars = defRE.exec(content))) {
      content = content.replace(
        refRE(vars[1]),
        overwrites[vars[1].substr(2)] || vars[2]
      )
    }

    styleElement.textContent = content
  })
}

let c = i => {
  try {
    return colorForTailwind(lightenDarkenColor(primaryColor.value, i))
  } catch (err) {
    // When error is thrown because the color is too light or dark and in
    // this case, the hext won't be correct he colorForTailwind function will
    // throw an error for the hex, to be sure, just use the primary color
    return colorForTailwind(primaryColor.value)
  }
}

processCSSVariables({
  'color-primary-50': c(100),
  'color-primary-100': c(80),
  'color-primary-200': c(60),
  'color-primary-300': c(40),
  'color-primary-400': c(20),
  'color-primary-500': c(10),
  'color-primary-600': c(0),
  'color-primary-700': c(-10),
  'color-primary-800': c(-20),
  'color-primary-900': c(-30),
})

onMounted(() => {
  // Colors test code
  /*let htmlTest = ''
    ;[50, 100, 200, 300, 400, 500, 600, 700, 800, 900].forEach(key => {
      htmlTest += `<div class="h-10 w-10 rounded mb-4 text-center pt-2 bg-primary-${key}">${key}</div>`
    })
    document.getElementById('testDiv').innerHTML = htmlTest*/
})
</script>
