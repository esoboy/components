import { registerModelFields } from './behaviors/model-field.js'
import { registerUi } from './ui.js'

registerModelFields()

const register = () => registerUi(window.Alpine)
if (window.Alpine) register()
else document.addEventListener('alpine:init', register, { once: true })
