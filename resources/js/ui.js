import { dialog, popover, registerTrigger } from './behaviors/overlay.js'
import { options } from './behaviors/keyboard.js'
import { registerStep } from './behaviors/step.js'
import { disclosure } from './behaviors/disclosure.js'
import { traceDisclosure } from './components/trace.js'
import { choiceGroup } from './components/toggle-group.js'
import { dataTable, dataTableColumns } from './components/data-table.js'
import { timePanel } from './components/time-panel.js'
import { pagination } from './components/pagination.js'
import { toast, toaster } from './components/toast.js'
import { code } from './components/code-block.js'
import { previewImage } from './components/media-preview.js'
import { iconSelect } from './components/icon-select.js'
import { otp } from './components/input-otp.js'
import { number } from './components/number-input.js'
import { accordion } from './components/accordion.js'
import { tabs } from './components/tabs.js'
import { menubar } from './components/menubar.js'
import { carousel } from './components/carousel.js'
import { resizable } from './components/resizable.js'
import { messageScroller } from './components/message-scroller.js'
import { tour } from './components/tour.js'
import { cropper } from './components/cropper.js'
import { signature } from './components/signature.js'
import { combobox } from './components/combobox.js'
import { drawer } from './components/drawer.js'
import { mentions } from './components/mentions.js'
import { navigationMenu } from './components/navigation-menu.js'
import { questionnaire } from './components/questionnaire.js'
import { rating } from './components/rating.js'
import { scrollArea } from './components/scroll-area.js'
import { sidebar } from './components/sidebar.js'
import { slider } from './components/slider.js'

const registered = new WeakSet()

export function registerUi(Alpine) {
    if (registered.has(Alpine)) return
    registered.add(Alpine)
    Alpine.data('uiDialog', dialog)
    Alpine.data('uiDrawer', drawer)
    Alpine.data('uiPopover', popover)
    Alpine.data('uiCombobox', combobox)
    Alpine.data('uiIconSelect', iconSelect)
    Alpine.data('uiNavigationMenu', navigationMenu)
    Alpine.data('uiTraceDisclosure', traceDisclosure)
    Alpine.data('uiDataTable', dataTable)
    Alpine.data('uiDataTableColumns', dataTableColumns)
    Alpine.data('uiPagination', pagination)
    Alpine.data('uiTimePanel', timePanel)
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
    Alpine.data('uiImage', previewImage)
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
