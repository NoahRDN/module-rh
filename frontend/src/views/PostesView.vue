<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Postes</h1>
      <span>Fonctions et rattachements</span>
    </div>
    <select class="select" v-model="filterDep" @change="fetchPostes">
      <option value="">Tous les départements</option>
      <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
    </select>
  </div>

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Département</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in postes" :key="p.id">
            <td>{{ p.nom }}</td>
            <td class="muted">{{ p.departement?.nom || '—' }}</td>
          </tr>
          <tr v-if="!postes.length">
            <td colspan="2" class="muted">Aucun poste</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="page-title">
        <h1>Ajouter</h1>
        <span>Nouveau poste</span>
      </div>
      <form class="grid" style="margin-top: 10px; gap: 10px;" @submit.prevent="createPoste">
        <input class="input" v-model="form.nom" placeholder="Nom" required />
        <textarea class="input" rows="3" v-model="form.description" placeholder="Description"></textarea>
        <select class="select" v-model="form.departement_id" required>
          <option value="">Département</option>
          <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
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

const departements = ref([])
const postes = ref([])
const filterDep = ref('')
const message = ref('')
const form = ref({ nom: '', description: '', departement_id: '' })

const fetchPostes = async () => {
  const params = filterDep.value ? { departement_id: filterDep.value } : {}
  const { data } = await api.get('/v1/postes', { params })
  postes.value = data.data || []
}

const fetchDeps = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || []
}

const createPoste = async () => {
  try {
    await api.post('/v1/postes', form.value)
    message.value = 'Poste créé'
    form.value = { nom: '', description: '', departement_id: '' }
    await fetchPostes()
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(async () => {
  await fetchDeps()
  await fetchPostes()
})
</script>
