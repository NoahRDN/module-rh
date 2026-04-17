<template>
  <div class="layout">
    <SidebarNav />
    <div class="content">
      <TopBar :subtitle="subtitle" />
      <main class="main-panel">
        <RouterView />
      </main>
    </div>
    <!-- Chatbot flottant -->
    <ChatbotWidget />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SidebarNav from './SidebarNav.vue'
import TopBar from './TopBar.vue'
import ChatbotWidget from '../ChatbotWidget.vue'

const route = useRoute()
const subtitle = computed(() => route.meta?.subtitle ?? 'Espace RH')
</script>

<style scoped>
.content {
  min-width: 0;
  padding: 18px 22px 28px;
}

.main-panel {
  min-width: 0;
  margin-top: 22px;
}

@media (max-width: 1100px) {
  .content {
    padding: 18px;
  }
}
</style>
