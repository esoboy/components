import { dialog, popover, registerTrigger } from './behaviors/overlay.js'
import { options, tabs, menubar, accordion, choiceGroup } from './behaviors/navigation.js'
import { disclosure, carousel, resizable, registerStep } from './behaviors/layout.js'
import { toast, toaster, code, messageScroller, tour } from './behaviors/content.js'
import { cropper, signature } from './behaviors/drawing.js'
import { slider } from './behaviors/slider.js'
import { rating } from './behaviors/rating.js'
import { number } from './behaviors/number.js'
import { otp } from './behaviors/otp.js'
import { sidebar } from './behaviors/sidebar.js'
import { drawer } from './behaviors/drawer.js'
import { mentions } from './behaviors/mentions.js'
import { questionnaire } from './behaviors/questionnaire.js'
import { scrollArea } from './behaviors/scroll-area.js'
import { navigationMenu } from './behaviors/navigation-menu.js'

const registered = new WeakSet()

export function registerUi(Alpine) {
    if (registered.has(Alpine)) return
    registered.add(Alpine)
    Alpine.data('uiDialog', dialog)
    Alpine.data('uiDrawer', drawer)
    Alpine.data('uiPopover', popover)
    Alpine.data('uiNavigationMenu', navigationMenu)
    registerTrigger(Alpine)
    Alpine.data('uiOptions', options)
    Alpine.data('uiAccordion', accordion)
    Alpine.data('uiChoiceGroup', choiceGroup)
    Alpine.data('uiTabs', tabs)
    Alpine.data('uiMenubar', menubar)
    Alpine.data('uiDisclosure', disclosure)
    Alpine.data('uiCarousel', carousel)
    Alpine.data('uiResizable', resizable)
    registerStep(Alpine)
    Alpine.data('uiToast', toast)
    Alpine.data('uiToaster', toaster)
    Alpine.data('uiCode', code)
    Alpine.data('uiMessageScroller', messageScroller)
    Alpine.data('uiTour', tour)
    Alpine.data('uiImage', () => ({ zoom: 1, rotation: 0 }))
    Alpine.data('uiCropper', cropper)
    Alpine.data('uiSignature', signature)
    Alpine.data('uiSlider', slider)
    Alpine.data('uiOtp', otp)
    Alpine.data('uiNumber', number)
    Alpine.data('uiRating', rating)
    Alpine.data('uiSidebar', sidebar)
    Alpine.data('uiQuestionnaire', questionnaire)
    Alpine.data('uiMentions', mentions)
    Alpine.data('uiScrollArea', scrollArea)
}
