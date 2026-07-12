<template>
  <div class="w-full h-full flex flex-col items-center justify-start pt-6 pb-2 relative bg-black">
    
    <!-- Flashing Border Overlay -->
    <div 
      v-if="isFlashing" 
      class="absolute inset-0 border-8 border-red-600 animate-pulse pointer-events-none z-50"
    ></div>
    
    <!-- Lockout Screen -->
    <div v-if="isLockedOut" class="flex flex-col items-center justify-center animate-fade-in z-20 h-full">
      <svg class="w-32 h-32 text-red-500 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
      </svg>
      <h1 class="text-3xl text-red-500 font-bold tracking-widest mb-2">GEBLOKKEERD</h1>
      <p class="text-2xl text-gray-300 font-bold">Probeer opnieuw in {{ lockoutTimeLeft }}s</p>
    </div>

    <!-- Success Screen -->
    <div v-else-if="disarmed" class="flex flex-col items-center justify-center animate-fade-in z-20 h-full">
      <svg class="w-32 h-32 text-flashy mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <h1 class="text-3xl text-flashy font-bold tracking-widest text-shadow-flashy">GEDEACTIVEERD</h1>
    </div>

    <!-- Main Entry Panel -->
    <div v-else class="w-full flex flex-col items-center justify-start relative z-10 h-full pt-6">
      <!-- Alarm Countdown Timer -->
      <div v-if="alarmMode" class="text-center mb-4">
        <h2 class="text-xl text-red-500 font-bold tracking-widest mb-1">
          {{ isFlashing ? 'ALARM GEACTIVEERD' : 'ALARM GAAT AF IN' }}
        </h2>
        <div v-if="!isFlashing" class="text-4xl font-extrabold text-red-500">
          {{ alarmTimeLeft }}s
        </div>
      </div>

      <!-- PIN Display -->
      <div class="w-3/4 h-16 bg-gray-900 border-2 border-gray-700 rounded-xl flex items-center justify-center mb-6 shadow-inner relative z-10">
        <div class="flex gap-2">
          <div 
            v-for="i in 6" 
            :key="i"
            class="w-4 h-4 rounded-full transition-colors duration-200"
            :class="i <= pin.length ? 'bg-flashy shadow-[0_0_10px_rgba(0,255,204,0.8)]' : 'bg-gray-800'"
          ></div>
        </div>
      </div>

      <!-- Error message -->
      <p v-if="errorMsg" class="text-red-500 mb-2 font-bold absolute top-2 z-10">{{ errorMsg }}</p>

      <!-- Keypad -->
      <div class="grid grid-cols-3 gap-4 mb-4 w-3/4 relative z-10">
        <button 
          v-for="num in [1, 2, 3, 4, 5, 6, 7, 8, 9]" 
          :key="num"
          @click="append(num)"
          class="h-14 rounded-full bg-gray-800 text-gray-300 text-2xl font-semibold border border-gray-700 active:bg-flashy active:text-black transition-colors"
        >
          {{ num }}
        </button>
        
        <button 
          @click="clear"
          class="h-14 rounded-full bg-gray-800 text-gray-400 text-lg font-medium border border-gray-700 active:bg-gray-600 transition-colors"
        >
          WISSEN
        </button>
        
        <button 
          @click="append(0)"
          class="h-14 rounded-full bg-gray-800 text-gray-300 text-2xl font-semibold border border-gray-700 active:bg-flashy active:text-black transition-colors"
        >
          0
        </button>

        <button 
          @click="submit"
          :disabled="loading || pin.length === 0"
          class="h-14 rounded-full bg-flashy/20 text-flashy text-lg font-bold border border-flashy/50 active:bg-flashy active:text-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          OK
        </button>
      </div>

      <!-- Actions -->
      <div class="flex gap-4 w-3/4 mt-auto relative z-10 pb-2">
        <button 
          @click="cancelToStart"
          class="flex-1 py-4 rounded-xl text-gray-400 bg-gray-900 border border-gray-800 active:bg-gray-800"
        >
          Annuleren
        </button>
      </div>
    </div>
    <!-- Error Screen -->
    <div v-if="showErrorScreen" class="absolute inset-0 flex flex-col items-center justify-center bg-red-950/90 z-30 animate-fade-in">
      <svg class="w-32 h-32 text-red-500 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
      <h1 class="text-3xl text-red-500 font-bold tracking-widest">FOUTIEVE CODE</h1>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useHeimdallStore } from '../stores/heimdall'
import { playUrgentBeep, playErrorSound, playDisarmedSound } from '../utils/audio'

const router = useRouter()
const route = useRoute()
const store = useHeimdallStore()

const pin = ref('')
const errorMsg = ref('')
const loading = ref(false)
const showErrorScreen = ref(false)

// Lockout logic
const failedAttempts = ref(0)
const isLockedOut = ref(false)
const lockoutTimeLeft = ref(0)
let lockoutTimer = null

// Alarm push logic
const alarmMode = route.query.alarm === 'true'
const alarmTimeLeft = ref(10)
const isFlashing = ref(false)
const disarmed = ref(false)
let alarmTimer = null

const startLockoutTimer = () => {
  if (lockoutTimer) clearTimeout(lockoutTimer)
  lockoutTimer = setTimeout(() => {
    if (lockoutTimeLeft.value > 0) {
      lockoutTimeLeft.value--
      startLockoutTimer()
    } else {
      isLockedOut.value = false
      failedAttempts.value = 0
      pin.value = ''
    }
  }, 1000)
}

const append = (num) => {
  if (pin.value.length < 6) {
    pin.value += num.toString()
    errorMsg.value = ''
  }
}

const clear = () => {
  pin.value = ''
  errorMsg.value = ''
}

const submit = async () => {
  if (!pin.value) return
  loading.value = true
  errorMsg.value = ''

  const success = await store.disarmWithPin(pin.value)
  loading.value = false

  if (success) {
    failedAttempts.value = 0
    playDisarmedSound()
    if (alarmTimer) clearTimeout(alarmTimer)
    isFlashing.value = false
    disarmed.value = true
    setTimeout(() => {
      router.push('/')
    }, 2000)
  } else {
    failedAttempts.value++
    playErrorSound()
    
    if (failedAttempts.value >= 5) {
      isLockedOut.value = true
      lockoutTimeLeft.value = 60
      showErrorScreen.value = false
      startLockoutTimer()
    } else {
      showErrorScreen.value = true
      setTimeout(() => {
        showErrorScreen.value = false
        pin.value = ''
      }, 1000)
    }
  }
}

const cancelToStart = () => {
  if (alarmTimer) clearTimeout(alarmTimer)
  if (lockoutTimer) clearTimeout(lockoutTimer)
  router.push('/')
}

const tickAlarm = () => {
  if (alarmTimeLeft.value > 0) {
    playUrgentBeep()
    alarmTimeLeft.value--
    alarmTimer = setTimeout(tickAlarm, 1000)
  } else {
    // Time is up! Flash red continuously.
    isFlashing.value = true
    // Optionally continue some sound, but user just said "border should flash red"
  }
}

onMounted(() => {
  if (alarmMode) {
    // Start countdown
    alarmTimer = setTimeout(tickAlarm, 1000)
  }
})

onUnmounted(() => {
  if (alarmTimer) clearTimeout(alarmTimer)
})
</script>

<style scoped>
.text-flashy {
  color: var(--color-flashy);
}
.bg-flashy {
  background-color: var(--color-flashy);
}
</style>
