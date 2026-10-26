/*
 * esoboy/ui
 *
 * Import once from the app's entry point:
 *   import '../../vendor/esoboy/ui/resources/js/ui.js'
 */

import { initAvatars } from './avatar.js'
import { defineCalendar } from './calendar.js'
import { defineCombobox } from './combobox.js'
import { defineCommand } from './command.js'
import { initConfirm } from './confirm.js'
import { defineDatePicker } from './date-picker.js'
import { initDialogs } from './dialog.js'
import { initDropdownMenus } from './dropdown-menu.js'
import { initInputGroups } from './input-group.js'
import { defineInputOtp } from './input-otp.js'
import { defineRadioGroup } from './radio-group.js'
import { defineSelect } from './select.js'
import { defineSlider } from './slider.js'
import { initToasts } from './toast.js'
import { initTooltips } from './tooltip.js'

defineRadioGroup()
defineSelect()
defineCommand()
defineCombobox()
defineCalendar()
defineDatePicker()
defineSlider()
defineInputOtp()
initAvatars()
initConfirm()
initDialogs()
initDropdownMenus()
initInputGroups()
initToasts()
initTooltips()
