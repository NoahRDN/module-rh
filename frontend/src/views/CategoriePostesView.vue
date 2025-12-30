<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Catégories de postes</h1>
        <p class="muted">Structure hiérarchique des postes</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="card gradient">
        <h3 class="card-title">{{ isEditing ? 'Modifier' : 'Ajouter' }} une catégorie</h3>
        <form class="grid gap-3" @submit.prevent="save">
          <input class="input" v-model="form.nom" placeholder="Nom (ex. Cadres)" required />
          <input class="input" v-model="form.code" placeholder="Code (optionnel)" />
          <textarea class="input" rows="3" v-model="form.description" placeholder="Description (optionnel)"></textarea>
          <div class="flex gap-2 items-center">
            <button class="btn" type="submit" :disabled="loading">
              {{ loading ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer' }}
            </button>
            <button v-if="isEditing" class="btn btn-secondary" type="button" @click="resetForm">Annuler</button>
          </div>
          <p class="muted" v-if="message">{{ message }}</p>
        </form>
      </div>

      <div class="card md:col-span-2">
        <div class="table-header">
          <h3>Catégories existantes</h3>
          <span class="badge">{{ categories.length }} catégorie(s)</span>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Code</th>
              <th>Description</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in categories" :key="cat.id" class="hover">
              <td class="font-semibold">{{ cat.nom }}</td>
              <td class="muted">{{ cat.code || '—' }}</td>
              <td class="muted">{{ cat.description || '—' }}</td>
              <td class="text-right flex-end">
                <button class="btn btn-xs" @click="edit(cat)">✏️</button>
                <button class="btn btn-xs btn-danger" @click="remove(cat)" :disabled="loadingDelete === cat.id">🗑️</button>
              </td>
            </tr>
            <tr v-if="!categories.length">
              <td colspan="4" class="muted text-center py-3">Aucune catégorie</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const categories = ref([])
const loading = ref(false)
const loadingDelete = ref(null)
const message = ref('')
const form = ref({ id: null, nom: '', code: '', description: '' })
const isEditing = computed(() => !!form.value.id)

const resetForm = () => {
  form.value = { id: null, nom: '', code: '', description: '' }
  message.value = ''
}

const fetchCategories = async () => {
  const { data } = await api.get('/v1/categories-postes')
  categories.value = data || []
}

const save = async () => {
  loading.value = true
  message.value = ''
  try {
    if (form.value.id) {
      await api.put(`/v1/categories-postes/${form.value.id}`, form.value)
    } else {
      await api.post('/v1/categories-postes', form.value)
    }
    resetForm()
    await fetchCategories()
    message.value = 'Enregistré.'
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement'
  } finally {
    loading.value = false
  }
}

const edit = (cat) => {
  form.value = { ...cat }
  message.value = ''
}

const remove = async (cat) => {
  if (!confirm(`Supprimer la catégorie "${cat.nom}" ?`)) return
  loadingDelete.value = cat.id
  try {
    await api.delete(`/v1/categories-postes/${cat.id}`)
    await fetchCategories()
  } catch (e) {
    message.value = e.response?.data?.message || 'Suppression impossible'
  } finally {
    loadingDelete.value = null
  }
}

onMounted(fetchCategories)
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 16px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.muted { color: #94a3b8; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
.gradient { background: linear-gradient(145deg, #0f172a, #1e293b); color: #e2e8f0; border-color: rgba(255,255,255,0.08); }
.card-title { font-weight: 700; margin-bottom: 10px; }
.input, textarea { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #fff; color: #0f172a; }
.gradient .input, .gradient textarea { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); color: #fff; }
.btn { padding: 8px 12px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.btn-secondary { background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); }
.btn-xs { padding: 6px 8px; font-size: 12px; }
.btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 10px; text-align: left; }
.table thead { background: #f8fafc; }
.hover:hover { background: #f1f5f9; }
.table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.badge { background: #e0f2fe; color: #0ea5e9; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 12px; }
.text-right { text-align: right; }
.flex-end { display: flex; gap: 8px; justify-content: flex-end; }
.grid { display: grid; }
.gap-3 { gap: 12px; }
.gap-4 { gap: 16px; }
.md\\:grid-cols-3 { grid-template-columns: repeat(1, 1fr); }
@media (min-width: 768px) {
  .md\\:grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
  .md\\:col-span-2 { grid-column: span 2 / span 2; }
}
</style>
