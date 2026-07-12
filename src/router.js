import { createRouter, createWebHistory } from 'vue-router'
import Start from './views/Start.vue'
import Menu from './views/Menu.vue'
import Countdown from './views/Countdown.vue'
import EntryPanel from './views/EntryPanel.vue'

const routes = [
  { path: '/', name: 'Start', component: Start },
  { path: '/menu', name: 'Menu', component: Menu },
  { path: '/countdown', name: 'Countdown', component: Countdown },
  { path: '/entry', name: 'EntryPanel', component: EntryPanel }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
