<template>
  <header class="topbar card">
    <div>
      <p class="title">Bonjour 👋</p>
      <p class="muted">{{ subtitle }}</p>
    </div>
    <div class="actions">
      <button class="btn btn-secondary" @click="logout">Déconnexion</button>
    </div>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import api from '../../services/api'

defineProps({
  subtitle: {
    type: String,
    default: ''
  }
})

const router = useRouter()

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (e) {
    // ignore
  }
  localStorage.removeItem('token')
  localStorage.removeItem('role')
  router.push('/login')
}
</script>

<style scoped>
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
