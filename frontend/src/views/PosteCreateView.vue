<template>
  <div class="page">
    <div class="page-header">
      <h1>Créer un poste</h1>
      <RouterLink class="btn btn-secondary btn-sm" to="/postes">← Retour</RouterLink>
    </div>
    <div class="card">
      <form class="grid gap-3" @submit.prevent="submit">
        <input class="input" v-model="form.nom" placeholder="Nom" required />
        <textarea class="input" rows="3" v-model="form.description" placeholder="Description"></textarea>
        <select class="select" v-model="form.departement_id" required>
          <option value="">Département</option>
          <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
        </select>
        <select class="select" v-model="form.categorie">
          <option value="">Catégorie (optionnel)</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>
        <div class="flex gap-2 items-center">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <span class="muted" v-if="message">{{ message }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
const message = ref('')
const departements = ref([])
const categories = ref([])
const defaultCategories = ['Ouvriers', 'Employés', 'TAM', 'Cadres', 'Dirigeants']
const form = ref({ nom: '', description: '', departement_id: '', categorie: '' })

const loadDeps = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || data || []
}

const loadCategories = async () => {
  try {
    const { data } = await api.get('/v1/categories-postes')
    const payload = data || []
    categories.value = payload.length ? payload.map((c) => c.nom) : defaultCategories
  } catch (e) {
    categories.value = defaultCategories
  }
}

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.post('/v1/postes', form.value)
    router.push('/postes')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadDeps()
  await loadCategories()
})
</script>

<style scoped>
.page { padding: 20px; max-width: 800px; margin: 0 auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
.input, .select { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
.btn { padding: 10px 16px; border: none; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.muted { color: #94a3b8; }
</style>
