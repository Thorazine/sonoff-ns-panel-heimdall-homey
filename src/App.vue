<template>
  <!-- Main container forced to 480x480 for NS Panel Pro -->
  <div 
    class="w-[480px] h-[480px] bg-black text-gray-300 relative overflow-hidden"
    @click="resetTimer"
    @touchstart="resetTimer"
  >
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

let inactivityTimer = null

const resetTimer = () => {
  if (inactivityTimer) clearTimeout(inactivityTimer)
  
  // Start a 30-second timer
  inactivityTimer = setTimeout(() => {
    // Only push if we aren't already on the start screen
    if (route.path !== '/') {
      router.push('/')
    }
  }, 30000)
}

onMounted(() => {
  resetTimer()
})

onUnmounted(() => {
  if (inactivityTimer) clearTimeout(inactivityTimer)
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
