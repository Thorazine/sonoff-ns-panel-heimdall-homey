import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useHeimdallStore } from '../heimdall'
import axios from 'axios'

vi.mock('axios')

describe('Heimdall Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializes with default state', () => {
    const store = useHeimdallStore()
    expect(store.status).toBe('disarmed')
    expect(store.error).toBeNull()
  })

  it('fetchStatus updates status correctly', async () => {
    const store = useHeimdallStore()
    axios.get.mockResolvedValue({
      data: { status: 'armed' }
    })
    
    const status = await store.fetchStatus()
    expect(status).toBe('armed')
    expect(store.status).toBe('armed')
    expect(store.error).toBeNull()
    expect(axios.get).toHaveBeenCalledWith('api.php?action=status')
  })

  it('fetchStatus handles errors', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    
    const store = useHeimdallStore()
    axios.get.mockRejectedValue(new Error('Network Error'))
    
    const status = await store.fetchStatus()
    expect(status).toBeNull()
    expect(store.error).toBe('FOUT BIJ VERBINDEN')
    
    consoleSpy.mockRestore()
  })

  it('setMode updates mode correctly', async () => {
    const store = useHeimdallStore()
    axios.post.mockResolvedValue({})
    
    await store.setMode('partially_armed')
    expect(axios.post).toHaveBeenCalledWith('api.php?action=set_mode', { mode: 'partially_armed' })
    expect(store.status).toBe('partially_armed')
  })

  it('disarmWithPin acts as setMode(disarmed) when PIN is valid', async () => {
    const store = useHeimdallStore()
    axios.post.mockResolvedValue({ data: { success: true } })
    
    const result = await store.disarmWithPin('1234')
    expect(result).toBe(true)
    expect(axios.post).toHaveBeenCalledWith('api.php?action=set_mode', { mode: 'disarmed', pin: '1234' })
    expect(store.status).toBe('disarmed')
  })

  it('disarmWithPin returns false and sets error when PIN is invalid', async () => {
    const store = useHeimdallStore()
    axios.post.mockRejectedValue({ response: { status: 401 } })
    
    const result = await store.disarmWithPin('9999')
    expect(result).toBe(false)
    expect(store.error).toBe('FOUTIEVE CODE')
  })
})
