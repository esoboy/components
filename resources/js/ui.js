import { dialog, popover, registerTrigger } from './behaviors/overlay.js'
import { options } from './behaviors/keyboard.js'
import { registerStep } from './behaviors/step.js'
import { disclosure } from './behaviors/disclosure.js'
import { choiceGroup } from './components/toggle-group.js'
import { dataTable } from './components/data-table.js'
import { timePanel } from './components/time-panel.js'
import { toast, toaster } from './components/toast.js'
import { code } from './components/code-block.js'
import { previewImage } from './components/media-preview.js'
import { otp } from './components/input-otp.js'
import { number } from './components/number-input.js'
import { tabs } from './components/tabs.js'
import { menubar } from './components/menubar.js'
import { carousel } from './components/carousel.js'
import { resizable } from './components/resizable.js'
import { messageScroller } from './components/message-scroller.js'
import { tour } from './components/tour.js'
import { cropper } from './components/cropper.js'
import { signature } from './components/signature.js'
import { combobox } from './components/combobox.js'
import { command } from './components/command.js'
import { drawer } from './components/drawer.js'
import { navigationMenu } from './components/navigation-menu.js'
import { questionnaire } from './components/questionnaire.js'
import { scrollArea } from './components/scroll-area.js'
import { sidebar } from './components/sidebar.js'
import { slider } from './components/slider.js'

const registered = new WeakSet()
const revealedPasswords = new WeakMap()

function password() {
    return {
        revealed: false,
        init() {
            this.revealed = revealedPasswords.get(this.$root) ?? false
        },
        toggle() {
            this.revealed = !this.revealed
            revealedPasswords.set(this.$root, this.revealed)
        },
    }
}

export function registerUi(Alpine) {
    if (registered.has(Alpine)) return
    registered.add(Alpine)
    registerTrigger(Alpine)
    registerStep(Alpine)
    const behaviors = {
        uiDialog: dialog,
        uiDrawer: drawer,
        uiPopover: popover,
        uiCombobox: combobox,
        uiCommand: command,
        uiNavigationMenu: navigationMenu,
        uiDataTable: dataTable,
        uiTimePanel: timePanel,
        uiOptions: options,
        uiChoiceGroup: choiceGroup,
        uiTabs: tabs,
        uiMenubar: menubar,
        uiDisclosure: disclosure,
        uiCarousel: carousel,
        uiResizable: resizable,
        uiToast: toast,
        uiToaster: toaster,
        uiCode: code,
        uiMessageScroller: messageScroller,
        uiTour: tour,
        uiImage: previewImage,
        uiCropper: cropper,
        uiSignature: signature,
        uiSlider: slider,
        uiOtp: otp,
        uiNumber: number,
        uiPassword: password,
        uiSidebar: sidebar,
        uiQuestionnaire: questionnaire,
        uiScrollArea: scrollArea,
    }
    for (const [name, behavior] of Object.entries(behaviors)) Alpine.data(name, behavior)
}
