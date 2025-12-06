<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Départements</h1>
      <span>Structure de l’entreprise</span>
    </div>
  </div>

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
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

    <div class="card">
      <div class="page-title">
        <h1>Ajouter</h1>
        <span>Nouveau département</span>
      </div>
      <form class="grid" style="margin-top: 10px; gap: 10px;" @submit.prevent="createDepartement">
        <input class="input" v-model="form.nom" placeholder="Nom" required />
        <textarea class="input" rows="3" v-model="form.description" placeholder="Description"></textarea>
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
const form = ref({ nom: '', description: '' })
const message = ref('')

const fetchDepartements = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || []
}

const createDepartement = async () => {
  try {
    await api.post('/v1/departements', form.value)
    message.value = 'Département créé'
    form.value = { nom: '', description: '' }
    await fetchDepartements()
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(fetchDepartements)
</script>
