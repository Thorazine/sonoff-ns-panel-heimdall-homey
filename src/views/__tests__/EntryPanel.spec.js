import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import EntryPanel from '../EntryPanel.vue'
import { useHeimdallStore } from '../../stores/heimdall'
import { createRouter, createWebHistory } from 'vue-router'

vi.mock('../../utils/audio', () => ({
  playUrgentBeep: vi.fn()
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', name: 'Start', component: { template: '<div>Start</div>' } }]
})

describe('EntryPanel', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders correctly', () => {
    const wrapper = mount(EntryPanel, {
      global: {
        plugins: [router]
      }
    })
    expect(wrapper.text()).toContain('WISSEN')
    expect(wrapper.text()).toContain('OK')
  })

  it('appends numbers and limits to 6 digits', async () => {
    const wrapper = mount(EntryPanel, {
      global: {
        plugins: [router]
      }
    })
    
    // Simulate pressing 1, 2, 3, 4, 5, 6, 7
    for (let i = 0; i < 7; i++) {
      await wrapper.findAll('button').filter(b => b.text() === '1')[0].trigger('click')
    }
    
    // Check component state
    expect(wrapper.vm.pin).toBe('111111')
  })

  it('clears input when WISSEN is clicked', async () => {
    const wrapper = mount(EntryPanel, {
      global: {
        plugins: [router]
      }
    })
    
    await wrapper.findAll('button').filter(b => b.text() === '1')[0].trigger('click')
    expect(wrapper.vm.pin).toBe('1')
    
    await wrapper.findAll('button').filter(b => b.text() === 'WISSEN')[0].trigger('click')
    expect(wrapper.vm.pin).toBe('')
  })
})
