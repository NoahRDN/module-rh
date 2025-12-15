<template>
  <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="page-title">
      <h1 class="text-2xl font-semibold">Jours fériés</h1>
      <span>Gestion du calendrier</span>
    </div>
    <div class="flex gap-2">
      <button class="btn btn-secondary" @click="fetchFeries">Actualiser</button>
      <RouterLink class="btn" to="/jours-feries/nouveau">+ Ajouter</RouterLink>
    </div>
  </div>

  <div class="card">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold">Liste</h3>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Date</th>
          <th>Récurent</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="f in feries" :key="f.id">
          <td>{{ f.nom }}</td>
          <td>{{ f.date }}</td>
          <td>{{ f.recurrent ? 'Oui' : 'Non' }}</td>
          <td class="flex gap-2">
            <button class="btn btn-secondary btn-xs danger" @click="remove(f)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="!feries.length">
          <td colspan="4" class="muted text-center">Aucun jour férié</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'

const feries = ref([])

const fetchFeries = async () => {
  const { data } = await api.get('/v1/jours-feries')
  feries.value = data.data || []
}

const remove = async (f) => {
  if (!confirm(`Supprimer ${f.nom} ?`)) return
  try {
    await api.delete(`/v1/jours-feries/${f.id}`)
    await fetchFeries()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchFeries)
</script>

<style scoped>
.card { border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border-bottom: 1px solid var(--border); padding: 8px; text-align: left; }
.muted { color: #94a3b8; }
.btn.danger { color: #ef4444; }
</style>
