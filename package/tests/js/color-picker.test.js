import { afterEach, expect, it, vi } from 'vitest'
import { provider, cleanupControllers } from './alpine-harness.js'
import colorPicker, {parseHex,hexToHsv,hsvToHex} from '../../resources/js/alpine/color-picker.js'
afterEach(()=>{cleanupControllers();document.body.innerHTML='';vi.restoreAllMocks()})
it.each([['#2563EB','#2563eb'],['f80','#ff8800'],['red',null],[null,null]])('normalizes %s',(input,expected)=>expect(parseHex(input)).toBe(expected))
it.each(['#2563eb','#22c55e','#a3a3a3','#ec4899','#000000'])('round trips %s through HSV',hex=>expect(hsvToHex(hexToHsv(hex))).toBe(hex))
const mount=(initial='#ff0000')=>{
    let current=initial;const wire={$get:()=>current,$set:vi.fn((_,v)=>current=v)}
    return provider(colorPicker,'<div data-placeholder="Pick"><button data-color-picker-trigger><span data-slot="color-picker-swatch"></span><span data-slot="color-picker-value"></span></button><div data-slot="color-picker-area" tabindex="0"><span data-slot="color-picker-thumb"></span></div><input data-color-picker-input><button data-color-picker-swatch="#22c55e"></button></div>',{binding:{property:'color',live:true},wire})
}
it('paints the bound value and validates typed hex',()=>{
    const data=mount();data.paint();expect(data.$el.querySelector('[data-slot="color-picker-value"]').textContent).toBe('#ff0000')
    data.hex('0f0');expect(data.value).toBe('#00ff00');data.hex('invalid');expect(data.value).toBe('#00ff00');data.hex('');expect(data.value).toBeNull()
})
it('supports swatches and keyboard movement in the area',()=>{
    const data=mount();const area=data.$el.querySelector('[data-slot="color-picker-area"]');data.key({target:area,key:'ArrowDown',shiftKey:true,preventDefault(){}});expect(data.value).toBe('#e60000')
    data.click({target:data.$el.querySelector('[data-color-picker-swatch]')});expect(data.value).toBe('#22c55e')
})
it('keeps hue meaningful for gray and black',()=>{
    const data=mount('#808080');data.hue=240;expect(data.hsv.h).toBe(240);data.color({s:1,v:1});expect(data.value).toBe('#0000ff')
})
it('uses the browser eyedropper as an input and handles cancellation',async()=>{
    const open=vi.fn().mockResolvedValue({sRGBHex:'#123456'});window.EyeDropper=class{open=open};const data=mount();await data.pick();expect(data.value).toBe('#123456');open.mockRejectedValue(new Error('cancel'));await expect(data.pick()).resolves.toBeUndefined()
})
