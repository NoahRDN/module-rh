<template>
<header class="topbar card">
  <div>
    <p class="title">Bonjour 👋</p>
    <p class="muted">{{ subtitle }}</p>
  </div>
  <div class="actions">
    <button class="btn btn-secondary" @click="toggleTheme">
      {{ theme === 'dark' ? 'Mode clair' : 'Mode sombre' }}
    </button>
    <button class="btn btn-secondary" @click="logout">Déconnexion</button>
  </div>
</header>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

defineProps({
  subtitle: {
    type: String,
    default: ''
  }
})

const router = useRouter()
const theme = ref(localStorage.getItem('theme') || 'dark')

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

const applyTheme = () => {
  const target = document.documentElement || document.body
  if (theme.value === 'light') {
    target.setAttribute('data-theme', 'light')
    document.body.setAttribute('data-theme', 'light')
  } else {
    target.removeAttribute('data-theme')
    document.body.removeAttribute('data-theme')
  }
  localStorage.setItem('theme', theme.value)
}

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  applyTheme()
}

onMounted(applyTheme)
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
