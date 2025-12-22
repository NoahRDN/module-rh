<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Départements</h1>
      <span>Structure de l’entreprise</span>
    </div>
  </div>

  <div class="flex justify-end mb-3">
    <RouterLink class="btn" to="/departements/nouveau">+ Ajouter</RouterLink>
  </div>
  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="dep in departements" :key="dep.id">
          <td>{{ dep.nom }}</td>
          <td class="muted">{{ dep.description || '—' }}</td>
        </tr>
        <tr v-if="!departements.length">
          <td colspan="2" class="muted">Aucun département</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const departements = ref([])
const fetchDepartements = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || []
}

onMounted(fetchDepartements)
</script>
