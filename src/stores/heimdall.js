import { defineStore } from 'pinia'
import axios from 'axios'

export const useHeimdallStore = defineStore('heimdall', {
  state: () => ({
    status: 'disarmed', // 'armed', 'partially_armed', 'disarmed'
    error: null,
    deviceId: null, // Dynamically find the Surveillance device
  }),
  actions: {
    async fetchStatus() {
      try {
        const response = await axios.get('api.php?action=status')
        if (response.data && response.data.status) {
          this.status = response.data.status
        }
        this.error = null
        return this.status
      } catch (err) {
        console.error('Failed to fetch status:', err)
        this.error = 'FOUT BIJ VERBINDEN'
        return null
      }
    },
    async setMode(mode) { // 'armed', 'partially_armed', 'disarmed'
      try {
        await axios.post('api.php?action=set_mode', { mode })
        this.status = mode
        this.error = null
      } catch (err) {
        console.error(`Failed to set mode ${mode}:`, err)
        this.error = 'Failed to set mode'
        throw err
      }
    },
    async disarmWithPin(pin) {
      try {
        const response = await axios.post('api.php?action=set_mode', { mode: 'disarmed', pin })
        if (response.data && response.data.success) {
          this.status = 'disarmed'
          this.error = null
          return true
        }
      } catch (err) {
        console.error('Failed to disarm:', err)
        if (err.response && err.response.status === 401) {
          this.error = 'FOUTIEVE CODE'
        } else {
          this.error = 'FOUT BIJ VERBINDEN'
        }
        return false
      }
    }
  }
})
