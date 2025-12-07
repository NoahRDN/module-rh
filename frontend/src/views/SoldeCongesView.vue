<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Soldes de congés</h1>
      <span>Suivi par employé et par type</span>
    </div>
    <div class="flex items-center gap-2">
      <select class="select" v-model="filterEmploye" @change="fetchSoldes">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
      </select>
      <button class="btn btn-secondary" @click="accrue">+ Créditer 2,5 j/mois</button>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Employé</th>
            <th>Type</th>
            <th>Solde actuel</th>
            <th>Solde annuel</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in soldes" :key="s.id">
            <td>{{ s.employe ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}` : '—' }}</td>
            <td>{{ s.type?.nom || '—' }}</td>
            <td>{{ s.solde_actuel }}</td>
            <td>{{ s.solde_annuel }}</td>
          </tr>
          <tr v-if="!soldes.length">
            <td colspan="4" class="muted">Aucun solde</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const soldes = ref([])
const employes = ref([])
const types = ref([])
const filterEmploye = ref('')
const message = ref('')
const loading = ref(false)
const accrueMessage = ref('')

const form = ref({
  employe_id: '',
  type_id: '',
  solde_actuel: '',
  solde_annuel: ''
})

const fetchSoldes = async () => {
  loading.value = true
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/soldes-conges', { params })
  soldes.value = data.data || []
  loading.value = false
}

const fetchRefs = async () => {
  const [emps, tps] = await Promise.all([
    api.get('/v1/employes'),
    api.get('/v1/absences-types')
  ])
  employes.value = emps.data.data || []
  types.value = tps.data.data || []
}

const saveSolde = async () => {
  try {
    const payload = { ...form.value }
    payload.solde_actuel = Number(payload.solde_actuel)
    payload.solde_annuel = Number(payload.solde_annuel)
    await api.post('/v1/soldes-conges', payload)
    message.value = 'Solde enregistré'
    await fetchSoldes()
  } catch (e) {
    message.value = 'Erreur enregistrement'
  }
}

const accrue = async () => {
  try {
    await api.post('/v1/soldes-conges/accrue')
    accrueMessage.value = 'Soldes crédités (2,5 j/mois, max 3 ans)'
    await fetchSoldes()
  } catch (e) {
    accrueMessage.value = 'Erreur crédit soldes'
  }
}

onMounted(async () => {
  await Promise.all([fetchRefs(), fetchSoldes()])
})
</script>
