<template>
  <div class="layout" :class="{ collapsed: !sidebarOpen }">
    <SidebarNav :open="sidebarOpen" />
    <div class="content">
      <TopBar :subtitle="subtitle" :sidebar-open="sidebarOpen" @toggle-sidebar="toggleSidebar" />
      <main class="main-panel">
        <RouterView />
      </main>
    </div>
    <!-- Chatbot flottant -->
    <ChatbotWidget />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import SidebarNav from './SidebarNav.vue'
import TopBar from './TopBar.vue'
import ChatbotWidget from '../ChatbotWidget.vue'

const route = useRoute()
const subtitle = computed(() => route.meta?.subtitle ?? 'Espace RH')
const sidebarOpen = ref(true)

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<style scoped>
.content {
  min-width: 0;
  padding: 0 0 28px;
}

.main-panel {
  min-width: 0;
  margin-top: 18px;
  padding: 0 18px;
}

.layout.collapsed {
  grid-template-columns: 0 minmax(0, 1fr);
}

@media (max-width: 1100px) {
  .content {
    padding: 0 0 20px;
  }

  .main-panel {
    padding: 0 14px;
  }
}
</style>
