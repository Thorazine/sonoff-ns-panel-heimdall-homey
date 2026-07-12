<template>
  <div class="w-full h-full bg-black p-6 flex flex-col items-center justify-center gap-8">
    <h1 class="text-3xl text-gray-400 font-light tracking-wider mb-4">KIES MODUS</h1>
    
    <button 
      @click="selectMode('armed')"
      class="w-full py-6 rounded-2xl bg-gray-900 border border-gray-700 text-flashy text-2xl font-bold shadow-[0_0_15px_rgba(0,255,204,0.1)] active:scale-95 active:bg-gray-800 transition-all"
    >
      VOLLEDIG INGESCHAKELD
    </button>

    <button 
      @click="selectMode('partially_armed')"
      class="w-full py-6 rounded-2xl bg-gray-900 border border-gray-700 text-yellow-400 text-2xl font-bold shadow-[0_0_15px_rgba(250,204,21,0.1)] active:scale-95 active:bg-gray-800 transition-all"
    >
      GEDEELTELIJK INGESCHAKELD
    </button>
    
    <button 
      @click="goBack"
      class="mt-4 px-8 py-3 rounded-xl text-gray-500 border border-gray-800 active:bg-gray-900 transition-colors"
    >
      Annuleren
    </button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useHeimdallStore } from '../stores/heimdall'

const router = useRouter()
const store = useHeimdallStore()

const selectMode = (mode) => {
  // Pass the selected mode to the countdown page
  router.push({ path: '/countdown', query: { mode } })
}

const goBack = () => {
  router.push('/')
}
</script>

<style scoped>
.text-flashy {
  color: var(--color-flashy);
}
</style>
