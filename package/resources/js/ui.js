/*
 * esoboy/ui
 *
 * Import once from the app's entry point:
 *   import '../../vendor/esoboy/ui/resources/js/ui.js'
 */

import { initConfirm } from './confirm.js'
import { initDialogs } from './dialog.js'
import { initDropdownMenus } from './dropdown-menu.js'
import { defineRadioGroup } from './radio-group.js'
import { initToasts } from './toast.js'
import { initTooltips } from './tooltip.js'

defineRadioGroup()
initConfirm()
initDialogs()
initDropdownMenus()
initToasts()
initTooltips()
