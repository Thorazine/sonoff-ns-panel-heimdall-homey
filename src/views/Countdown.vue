<template>
  <div class="w-full h-full bg-black flex flex-col items-center justify-center p-6 relative">
    <div v-if="!armed" class="flex flex-col items-center justify-center">
      <h2 class="text-2xl text-gray-400 font-light mb-8">ALARM SCHAKELT IN...</h2>
      
      <!-- Circular Progress / Countdown -->
      <div class="relative w-48 h-48 flex items-center justify-center bg-gray-900 rounded-full border-4 border-gray-800 shadow-[0_0_30px_rgba(0,255,204,0.15)]">
        <span class="text-7xl font-bold text-flashy">{{ timeLeft }}</span>
      </div>
      
      <button 
        @click="cancel"
        class="absolute bottom-10 px-8 py-3 rounded-xl text-red-500 border border-red-900 bg-red-950/30 active:bg-red-900/50 transition-colors"
      >
        ANNULEREN
      </button>
    </div>

    <div v-else class="flex flex-col items-center justify-center animate-fade-in">
      <svg class="w-32 h-32 text-flashy mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
      </svg>
      <h1 class="text-4xl text-flashy font-bold tracking-widest text-shadow-flashy">INGESCHAKELD</h1>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useHeimdallStore } from '../stores/heimdall'
import { playBeep, playArmedSound } from '../utils/audio'

const router = useRouter()
const route = useRoute()
const store = useHeimdallStore()

const defaultTime = parseInt(import.meta.env.VITE_COUNTDOWN_SECONDS) || 10
const timeLeft = ref(defaultTime)
const armed = ref(false)
let timer = null

const mode = route.query.mode || 'armed'

const tick = async () => {
  if (timeLeft.value > 0) {
    playBeep()
    timeLeft.value--
    timer = setTimeout(tick, 1000)
  } else {
    // Time is up, arm the system
    playArmedSound()
    try {
      await store.setMode(mode)
    } catch (e) {
      console.error('Failed to arm:', e)
    }
    armed.value = true
    setTimeout(() => {
      router.push('/')
    }, 2000)
  }
}

const cancel = () => {
  if (timer) clearTimeout(timer)
  router.push('/menu')
}

onMounted(() => {
  timer = setTimeout(tick, 1000)
})

onUnmounted(() => {
  if (timer) clearTimeout(timer)
})
</script>

<style scoped>
.text-flashy {
  color: var(--color-flashy);
}
.text-shadow-flashy {
  text-shadow: 0 0 10px var(--color-flashy);
}
.animate-fade-in {
  animation: fadeIn 0.5s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>
