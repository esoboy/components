import { afterEach, expect, it, vi } from 'vitest'
import { provider, cleanupControllers } from './alpine-harness.js'
import countdown from '../../resources/js/alpine/countdown.js'
import { countdowns, format } from '../../resources/js/support/countdown.js'
afterEach(()=>{cleanupControllers();vi.useRealTimers();countdowns.clear();document.body.innerHTML=''})
it.each([[90061,'HH:mm:ss','25:01:01'],[90061,'D [days] HH:mm:ss','1 days 01:01:01'],[90061,'D天H时m分s秒','1天1时1分1秒'],[61,'ss','61'],[0,'mm:ss','00:00']])('formats %is with %s',(seconds,pattern,text)=>expect(format(seconds*1000,pattern)).toBe(text))
it('counts down from PHP time and emits finish once',()=>{
    vi.useFakeTimers();const data=provider(()=>countdown(false),'<div data-remaining="2000" data-format="ss"></div>');const finish=vi.fn();data.$el.addEventListener('finish',finish)
    expect(data.$el.textContent).toBe('02');vi.advanceTimersByTime(2000);expect(data.$el.textContent).toBe('00');expect(finish).toHaveBeenCalledOnce();vi.advanceTimersByTime(5000);expect(finish).toHaveBeenCalledOnce()
})
it('keeps a named timer through navigation and permits a server restart',()=>{
    vi.useFakeTimers();const data=provider(()=>countdown(false),'<div data-name="sale" data-remaining="10000" data-format="ss"></div>');vi.advanceTimersByTime(3000);data.destroy()
    const next=provider(()=>countdown(false),'<div data-name="sale" data-remaining="10000" data-format="ss"></div>');expect(next.$el.textContent).toBe('07');next.start({name:'sale',seconds:30});expect(next.$el.textContent).toBe('30')
})
it('blocks resending only after the server starts the verification timer',()=>{
    vi.useFakeTimers();const data=provider(()=>countdown(true),'<div data-name="sms" data-seconds="2" data-label-countdown="Wait :seconds" data-label-resend="Resend"><span data-verification-code-label>Send</span></div>');const event={preventDefault:vi.fn(),stopImmediatePropagation:vi.fn()}
    data.block(event);expect(event.preventDefault).not.toHaveBeenCalled();data.start({name:'sms'});data.block(event);expect(event.preventDefault).toHaveBeenCalledOnce();vi.advanceTimersByTime(2000);expect(data.$el.textContent).toBe('Resend')
})
