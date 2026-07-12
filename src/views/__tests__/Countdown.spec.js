import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Countdown from '../Countdown.vue'
import { useHeimdallStore } from '../../stores/heimdall'
import { createRouter, createWebHistory } from 'vue-router'

vi.mock('../../utils/audio', () => ({
  playBeep: vi.fn(),
  playArmedSound: vi.fn()
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', name: 'Start', component: { template: '<div>Start</div>' } }]
})

describe('Countdown', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders and shows initial countdown time', () => {
    const wrapper = mount(Countdown, {
      global: {
        plugins: [router]
      }
    })
    expect(wrapper.text()).toContain('ALARM SCHAKELT IN...')
  })
})
