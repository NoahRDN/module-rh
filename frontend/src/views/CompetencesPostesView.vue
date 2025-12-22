<template>
  <div class="page">
    <div class="page-header">
      <div class="page-title">
        <h1>Compétences des postes</h1>
        <span>Compétences requises par poste</span>
      </div>
    </div>

    <div class="card filters-card">
      <div class="filters">
        <label class="filter">
          Poste
          <select class="select" v-model="posteId" @change="loadPosteCompetences">
            <option value="">Choisir…</option>
            <option v-for="p in postes" :key="p.id" :value="p.id">
              {{ p.nom }} ({{ p.departement?.nom || '—' }})
            </option>
          </select>
        </label>
        <label class="filter">
          Compétence
          <select class="select" v-model="form.competence_id">
            <option value="">Ajouter une compétence…</option>
            <option v-for="c in competences" :key="c.id" :value="c.id">{{ c.nom }}</option>
          </select>
        </label>
        <label class="filter">
          Niveau requis (1-5)
          <input class="input" type="number" min="1" max="5" v-model.number="form.niveau_requis" />
        </label>
        <label class="filter checkbox">
          <input type="checkbox" v-model="form.obligatoire" />
          Obligatoire
        </label>
        <label class="filter">
          Poids
          <input class="input" type="number" min="1" max="25" v-model.number="form.poids" />
        </label>
        <button class="btn" :disabled="!posteId || !form.competence_id" @click="addCompetence">Ajouter</button>
      </div>
      <p class="muted" v-if="message">{{ message }}</p>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="spinner-big"></div>
      <p>Chargement…</p>
    </div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Compétence</th>
            <th>Niveau</th>
            <th>Obligatoire</th>
            <th>Poids</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in competencesPoste" :key="c.id">
            <td>{{ c.nom }}</td>
            <td>{{ c.niveau_requis ?? c.pivot?.niveau_requis ?? '—' }}</td>
            <td>{{ (c.obligatoire ?? c.pivot?.obligatoire) ? 'Oui' : 'Non' }}</td>
            <td>{{ c.poids ?? c.pivot?.poids ?? '—' }}</td>
            <td><button class="btn btn-secondary btn-xs" @click="removeCompetence(c.id)">Supprimer</button></td>
          </tr>
          <tr v-if="!competencesPoste.length">
            <td colspan="5" class="muted">Aucune compétence requise</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import competenceService from '../services/competenceService'
import api from '../services/api'

const postes = ref([])
const competences = ref([])
const competencesPoste = ref([])
const posteId = ref('')
const loading = ref(false)
const message = ref('')
const form = ref({ competence_id: '', niveau_requis: 3, obligatoire: false, poids: 10 })

const normalize = (res) => res?.data?.data || res?.data || res || []

const loadRefs = async () => {
  const [pRes, cRes] = await Promise.all([
    api.get('/v1/postes', { params: { all: 1 } }),
    competenceService.getCompetences({ actif: true })
  ])
  postes.value = normalize(pRes)
  competences.value = normalize(cRes)
}

const loadPosteCompetences = async () => {
  if (!posteId.value) {
    competencesPoste.value = []
    return
  }
  loading.value = true
  try {
    const res = await competenceService.getPosteCompetences(posteId.value)
    const arr = normalize(res)
    competencesPoste.value = Array.isArray(arr) ? arr : []
  } finally {
    loading.value = false
  }
}

const addCompetence = async () => {
  message.value = ''
  try {
    await competenceService.addPosteCompetence(posteId.value, form.value)
    await loadPosteCompetences()
    message.value = 'Compétence ajoutée'
    form.value = { competence_id: '', niveau_requis: 3, obligatoire: false, poids: 10 }
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur'
  }
}

const removeCompetence = async (competenceId) => {
  try {
    await competenceService.deletePosteCompetence(posteId.value, competenceId)
    await loadPosteCompetences()
  } catch (e) {
    message.value = 'Suppression impossible'
  }
}

onMounted(loadRefs)
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 12px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.page-title h1 { margin: 0; }
.page-title span { color: #64748b; }
.filters-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
.filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; align-items: end; }
.filter { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #475569; }
.filter.checkbox { flex-direction: row; align-items: center; gap: 6px; }
.input, .select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; }
.btn { border: none; background: linear-gradient(135deg, #0ea5e9, #2563eb); color: #fff; padding: 10px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.btn-xs { padding: 6px 8px; font-size: 12px; }
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; background: #fff; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
.muted { color: #94a3b8; }
.loading-overlay { min-height: 200px; display: flex; align-items: center; justify-content: center; gap: 10px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
.spinner-big { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #0ea5e9; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
