import { registerUi } from './ui.js'

const register = () => registerUi(window.Alpine)
if (window.Alpine) register()
else document.addEventListener('alpine:init', register, { once: true })
