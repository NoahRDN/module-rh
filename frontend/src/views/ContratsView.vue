<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Contrats</h1>
      <span>Contrats actifs / historiques</span>
    </div>
    <select class="select" v-model="filterEmploye" @change="fetchContrats">
      <option value="">Tous les employés</option>
      <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
    </select>
  </div>

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Employé</th>
            <th>Type</th>
            <th>Début</th>
            <th>Fin</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in contrats" :key="c.id">
            <td>{{ c.employe?.matricule || '—' }}</td>
            <td>{{ c.type_contrat }}</td>
            <td>{{ c.date_debut }}</td>
            <td>{{ c.date_fin || '—' }}</td>
          </tr>
          <tr v-if="!contrats.length">
            <td colspan="4" class="muted">Aucun contrat</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="page-title">
        <h1>Nouveau contrat</h1>
        <span>Associer un employé</span>
      </div>
      <form class="grid" style="margin-top: 10px; gap: 10px;" @submit.prevent="createContrat">
        <select class="select" v-model="form.employe_id" required>
          <option value="">Employé</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <input class="input" v-model="form.type_contrat" placeholder="Type (CDI, CDD, Stage)" required />
        <input class="input" v-model="form.date_debut" placeholder="Date début" required />
        <input class="input" v-model="form.date_fin" placeholder="Date fin (optionnel)" />
        <input class="input" v-model="form.salaire_base" placeholder="Salaire base" required type="number" step="0.01" />
        <label class="muted"><input type="checkbox" v-model="form.renouvelable" /> Renouvelable</label>
        <button class="btn" type="submit">Enregistrer</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const contrats = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')

const form = ref({
  employe_id: '',
  type_contrat: 'CDI',
  date_debut: '',
  date_fin: '',
  salaire_base: '',
  renouvelable: false
})

const fetchContrats = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/contrats', { params })
  contrats.value = data.data || []
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const createContrat = async () => {
  try {
    const payload = { ...form.value }
    payload.date_fin = payload.date_fin || null
    await api.post('/v1/contrats', payload)
    message.value = 'Contrat créé'
    await fetchContrats()
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchContrats()
})
</script>
