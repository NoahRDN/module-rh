<template>
  <div class="layout">
    <SidebarNav />
    <div class="content">
      <TopBar :subtitle="subtitle" />
      <main class="main-panel">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SidebarNav from './SidebarNav.vue'
import TopBar from './TopBar.vue'

const route = useRoute()
const subtitle = computed(() => route.meta?.subtitle ?? 'Espace RH')
</script>

<style scoped>
.content {
  padding: 16px 18px 24px;
}

.main-panel {
  margin-top: 16px;
}
</style>
