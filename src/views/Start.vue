<template>
  <div 
    class="w-full h-full bg-black cursor-pointer flex items-center justify-center"
    @click="handleTap"
  >
    <p v-if="loading" class="text-flashy text-xl animate-pulse tracking-widest font-bold">LADEN...</p>
    <p v-if="error" class="text-red-500 text-xl font-bold">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useHeimdallStore } from '../stores/heimdall'

const router = useRouter()
const route = useRoute()
const store = useHeimdallStore()
const loading = ref(false)
const error = ref(null)

onMounted(() => {
  if (route.query.alarm === 'true') {
    router.replace('/entry?alarm=true')
  }
})

const handleTap = async () => {
  if (loading.value) return
  loading.value = true
  error.value = null
  
  const status = await store.fetchStatus()
  loading.value = false
  
  if (status === 'armed' || status === 'partially_armed') {
    router.push('/entry')
  } else if (status === 'disarmed') {
    router.push('/menu')
  } else {
    error.value = 'FOUT BIJ VERBINDEN'
    setTimeout(() => {
      error.value = null
    }, 3000)
  }
}
</script>

<style scoped>
.text-flashy {
  color: var(--color-flashy);
}
</style>
