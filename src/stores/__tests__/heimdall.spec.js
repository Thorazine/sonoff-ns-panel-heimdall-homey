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
    expect(store.deviceId).toBeNull()
  })

  it('getDeviceId fetches and returns device id', async () => {
    const store = useHeimdallStore()
    const mockApi = {
      get: vi.fn().mockResolvedValue({
        data: {
          device1: { id: 'device1', driverId: 'other', name: 'Other' },
          device2: { id: 'device2', driverId: 'surveillancemodeswitch', name: 'Surveillance' }
        }
      })
    }
    
    const id = await store.getDeviceId(mockApi)
    expect(id).toBe('device2')
    expect(store.deviceId).toBe('device2')
    
    // Test caching
    const cachedId = await store.getDeviceId(mockApi)
    expect(cachedId).toBe('device2')
    expect(mockApi.get).toHaveBeenCalledTimes(1)
  })

  it('fetchStatus updates status correctly', async () => {
    const store = useHeimdallStore()
    const mockGet = vi.fn().mockImplementation((url) => {
      if (url === '/device') {
        return Promise.resolve({
          data: { dev1: { id: 'dev1', driverId: 'surveillancemodeswitch', name: 'Surveillance' } }
        })
      }
      if (url === '/device/dev1') {
        return Promise.resolve({
          data: {
            capabilitiesObj: {
              homealarm_state: { value: 'armed' }
            }
          }
        })
      }
    })
    
    axios.create.mockReturnValue({ get: mockGet })
    
    const status = await store.fetchStatus()
    expect(status).toBe('armed')
    expect(store.status).toBe('armed')
    expect(store.error).toBeNull()
  })

  it('fetchStatus handles errors', async () => {
    // Silence expected console.error during this test
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    
    const store = useHeimdallStore()
    axios.create.mockReturnValue({
      get: vi.fn().mockRejectedValue(new Error('Network Error'))
    })
    
    const status = await store.fetchStatus()
    expect(status).toBeNull()
    expect(store.error).toBe('FOUT BIJ VERBINDEN')
    
    consoleSpy.mockRestore()
  })

  it('setMode updates mode correctly', async () => {
    const store = useHeimdallStore()
    const mockPut = vi.fn().mockResolvedValue({})
    const mockGet = vi.fn().mockResolvedValue({
      data: { dev1: { id: 'dev1', driverId: 'surveillancemodeswitch', name: 'Surveillance' } }
    })
    
    axios.create.mockReturnValue({ get: mockGet, put: mockPut })
    
    await store.setMode('partially_armed')
    expect(mockPut).toHaveBeenCalledWith('/device/dev1/capability/homealarm_state', { value: 'partially_armed' })
    expect(store.status).toBe('partially_armed')
  })

  it('disarmWithPin acts as setMode(disarmed)', async () => {
    const store = useHeimdallStore()
    const mockPut = vi.fn().mockResolvedValue({})
    const mockGet = vi.fn().mockResolvedValue({
      data: { dev1: { id: 'dev1', driverId: 'surveillancemodeswitch', name: 'Surveillance' } }
    })
    
    axios.create.mockReturnValue({ get: mockGet, put: mockPut })
    
    const result = await store.disarmWithPin('1234')
    expect(result).toBe(true)
    expect(mockPut).toHaveBeenCalledWith('/device/dev1/capability/homealarm_state', { value: 'disarmed' })
    expect(store.status).toBe('disarmed')
  })
})
