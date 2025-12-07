<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Contrats</h1>
      <span>Contrats actifs / historiques</span>
    </div>
    <div class="flex items-center gap-2">
      <select class="select" v-model="filterEmploye" @change="fetchContrats">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <RouterLink class="btn whitespace-nowrap" to="/contrats/nouveau">+ Nouveau contrat</RouterLink>
    </div>
  </div>

  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Matricule</th>
          <th>Nom & Prénom</th>
          <th>Type</th>
          <th>Durée</th>
          <th>Date renouvellement</th>
          <th>Période d'essai</th>
          <th>Département</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="8" class="muted">Chargement...</td>
        </tr>
        <tr v-else v-for="c in contrats" :key="c.id">
          <td>{{ c.id }}</td>
          <td>{{ c.employe?.matricule || '—' }}</td>
          <td>{{ c.employe ? `${c.employe.nom} ${c.employe.prenom}` : '—' }}</td>
          <td>{{ c.type_contrat }}</td>
          <td>{{ c.date_debut }} → {{ c.date_fin || '—' }}</td>
          <td>{{ c.date_fin || '—' }}</td>
          <td>
            <div>
              <div>Début : {{ c.periode_essai_debut || '—' }}</div>
              <div>Fin : {{ c.periode_essai_fin || '—' }}</div>
            </div>
          </td>
          <td>{{ c.employe?.departement?.nom || '—' }}</td>
        </tr>
        <tr v-if="!contrats.length && !loading">
          <td colspan="8" class="muted">Aucun contrat</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const contrats = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']
const loading = ref(false)

const fetchContrats = async () => {
  loading.value = true
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/contrats', { params })
  contrats.value = data.data || []
  loading.value = false
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

onMounted(async () => {
  await fetchEmployes()
  await fetchContrats()
})
</script>
