<template>
  <div style="max-width:900px;margin:32px auto;">
    <h2>Dashboard RH</h2>
    <pre>{{ me }}</pre>
    <button @click="logout">Se déconnecter</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

const router = useRouter()
const me = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/me')
    me.value = data
  } catch (e) {
    router.push('/login')
  }
})

const logout = async () => {
  try {
    await api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('role')
  router.push('/login')
}
</script>
