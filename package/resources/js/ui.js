/*
 * esoboy/ui
 *
 * Every component but the heavy ones (resources/js/heavy), bundled into dist/ui.js
 * and loaded by @uiScripts. Each module registers its custom elements or document
 * listeners; the order matters where one element uses another (Calendar before
 * Date Picker).
 */

import * as accordion from './components/accordion.js'
import * as anchor from './components/anchor.js'
import * as avatar from './components/avatar.js'
import * as backToTop from './components/back-to-top.js'
import * as calendar from './components/calendar.js'
import * as carousel from './components/carousel.js'
import * as cascader from './components/cascader.js'
import * as codeBlock from './components/code-block.js'
import * as collapsible from './components/collapsible.js'
import * as colorPicker from './components/color-picker.js'
import * as combobox from './components/combobox.js'
import * as command from './components/command.js'
import * as confirm from './components/confirm.js'
import * as countdown from './components/countdown.js'
import * as cropper from './components/cropper.js'
import * as dataTable from './components/data-table.js'
import * as datePicker from './components/date-picker.js'
import * as dialog from './components/dialog.js'
import * as drawer from './components/drawer.js'
import * as dropdownMenu from './components/dropdown-menu.js'
import * as emojiPicker from './components/emoji-picker.js'
import * as fileUpload from './components/file-upload.js'
import * as hoverCard from './components/hover-card.js'
import * as imagePreview from './components/image-preview.js'
import * as inputGroup from './components/input-group.js'
import * as inputOtp from './components/input-otp.js'
import * as mentions from './components/mentions.js'
import * as messageScroller from './components/message-scroller.js'
import * as navigationMenu from './components/navigation-menu.js'
import * as numberInput from './components/number-input.js'
import * as passwordInput from './components/password-input.js'
import * as questionnaire from './components/questionnaire.js'
import * as radioGroup from './components/radio-group.js'
import * as rating from './components/rating.js'
import * as resizable from './components/resizable.js'
import * as scrollArea from './components/scroll-area.js'
import * as select from './components/select.js'
import * as sidebar from './components/sidebar.js'
import * as slider from './components/slider.js'
import * as tabs from './components/tabs.js'
import * as tagsInput from './components/tags-input.js'
import * as theme from './components/theme.js'
import * as toast from './components/toast.js'
import * as tooltip from './components/tooltip.js'
import * as tour from './components/tour.js'
import * as transfer from './components/transfer.js'
import * as tree from './components/tree.js'
import * as verificationCodeButton from './components/verification-code-button.js'
import * as watermark from './components/watermark.js'

const components = [
    radioGroup, select, command, combobox, mentions, calendar, datePicker, slider, inputOtp,
    tabs, sidebar, accordion, collapsible, scrollArea, resizable, carousel,
    messageScroller, fileUpload, dataTable, cascader, tree, tagsInput, numberInput,
    passwordInput, verificationCodeButton, countdown, colorPicker, rating, transfer, imagePreview, watermark, emojiPicker, cropper,
    anchor, avatar, backToTop, codeBlock, confirm, dialog, drawer, dropdownMenu, hoverCard, inputGroup,
    navigationMenu, questionnaire, theme, toast, tooltip, tour,
]

for (const component of components) component.register()
