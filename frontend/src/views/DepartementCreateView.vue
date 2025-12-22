<template>
  <div class="page">
    <div class="page-header">
      <h1>Nouveau département</h1>
      <RouterLink to="/departements" class="btn btn-secondary btn-sm">← Retour</RouterLink>
    </div>
    <div class="card">
      <form class="grid gap-3" @submit.prevent="submit">
        <input class="input" v-model="form.nom" placeholder="Nom" required />
        <textarea class="input" rows="3" v-model="form.description" placeholder="Description"></textarea>
        <div class="flex gap-2">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Envoi...' : 'Enregistrer' }}</button>
          <span class="muted" v-if="message">{{ message }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
const message = ref('')
const form = ref({ nom: '', description: '' })

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.post('/v1/departements', form.value)
    router.push('/departements')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.page { padding: 20px; max-width: 700px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
.input { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; }
.btn { padding: 10px 16px; border-radius: 10px; border: none; cursor: pointer; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.muted { color: #94a3b8; }
</style>
