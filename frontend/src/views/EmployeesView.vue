<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Employés</h1>
      <span>Annuaire et création rapide</span>
    </div>
    <div class="actions">
      <input class="input" placeholder="Rechercher (nom, prénom, matricule)" v-model="search" @input="fetchEmployes" />
    </div>
  </div>

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Matricule</th>
            <th>Nom</th>
            <th>Poste</th>
            <th>Département</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in employes" :key="emp.id">
            <td>{{ emp.matricule }}</td>
            <td>{{ emp.nom }} {{ emp.prenom }}</td>
            <td>{{ emp.poste?.nom || '—' }}</td>
            <td>{{ emp.departement?.nom || '—' }}</td>
          </tr>
          <tr v-if="!employes.length">
            <td colspan="4" class="muted">Aucun employé</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="page-title">
        <h1>Créer un employé</h1>
        <span>Formulaire minimal</span>
      </div>
      <form class="grid" style="margin-top: 10px; gap: 10px;" @submit.prevent="createEmploye">
        <input class="input" v-model="form.matricule" placeholder="Matricule" required />
        <input class="input" v-model="form.nom" placeholder="Nom" required />
        <input class="input" v-model="form.prenom" placeholder="Prénom" required />
        <input class="input" v-model="form.email" placeholder="Email" required type="email" />
        <input class="input" v-model="form.date_embauche" placeholder="Date embauche (YYYY-MM-DD)" required />
        <select class="select" v-model="form.departement_id">
          <option value="">Département (optionnel)</option>
          <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
        </select>
        <select class="select" v-model="form.poste_id">
          <option value="">Poste (optionnel)</option>
          <option v-for="p in postes" :key="p.id" :value="p.id">{{ p.nom }}</option>
        </select>
        <button class="btn" type="submit">Enregistrer</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const employes = ref([])
const departements = ref([])
const postes = ref([])
const search = ref('')
const message = ref('')

const form = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  date_embauche: '',
  departement_id: '',
  poste_id: ''
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { search: search.value } })
  employes.value = data.data || []
}

const fetchRefs = async () => {
  const [deps, pos] = await Promise.all([
    api.get('/v1/departements'),
    api.get('/v1/postes')
  ])
  departements.value = deps.data.data || []
  postes.value = pos.data.data || []
}

const createEmploye = async () => {
  try {
    const payload = { ...form.value }
    payload.departement_id = payload.departement_id || null
    payload.poste_id = payload.poste_id || null
    await api.post('/v1/employes', payload)
    message.value = 'Employé créé'
    await fetchEmployes()
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(async () => {
  await Promise.all([fetchEmployes(), fetchRefs()])
})
</script>
