import { createRouter, createWebHistory } from 'vue-router'
import AspirantesView from '@/views/AspirantesView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'aspirantes',
      component: AspirantesView
    }
  ]
})

export default router