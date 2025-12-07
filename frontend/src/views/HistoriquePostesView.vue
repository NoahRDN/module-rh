<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Historique des postes</h1>
      <span>Mobilités internes</span>
    </div>
    <div class="flex items-center gap-2">
      <select class="select" v-model="filterEmploye" @change="fetchHistorique">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <RouterLink class="btn whitespace-nowrap" to="/historiques/nouveau">+ Nouvelle mobilité</RouterLink>
    </div>
  </div>

  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Employé</th>
          <th>Poste</th>
          <th>Département</th>
          <th>Motif</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="h in historiques" :key="h.id">
          <td>{{ h.date_changement }}</td>
          <td>{{ h.employe ? `${h.employe.matricule} - ${h.employe.nom} ${h.employe.prenom}` : '—' }}</td>
          <td>{{ h.poste?.nom || '—' }}</td>
          <td>{{ h.departement?.nom || '—' }}</td>
          <td class="muted">{{ h.motif || '—' }}</td>
        </tr>
        <tr v-if="!historiques.length">
          <td colspan="5" class="muted">Aucun mouvement</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
import { RouterLink } from 'vue-router'

const historiques = ref([])
const employes = ref([])
const filterEmploye = ref('')

const fetchHistorique = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/historiques-postes', { params })
  historiques.value = data.data || []
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

onMounted(async () => {
  await fetchEmployes()
  await fetchHistorique()
})
</script>
