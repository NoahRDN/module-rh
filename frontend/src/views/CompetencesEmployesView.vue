<template>
  <div class="page">
    <div class="page-header">
      <div class="page-title">
        <h1>Compétences des employés</h1>
        <span>Consulter et ajouter des compétences à un collaborateur</span>
      </div>
    </div>

    <div class="card filters-card">
      <div class="filters">
        <label class="filter">
          Employé
          <select class="select" v-model="employeId" @change="loadEmployeCompetences">
            <option value="">Choisir…</option>
            <option v-for="e in employes" :key="e.id" :value="e.id">
              {{ e.matricule }} - {{ e.nom }} {{ e.prenom }}
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
          Niveau (1-5)
          <input class="input" type="number" min="1" max="5" v-model.number="form.niveau" />
        </label>
        <label class="filter">
          Commentaire
          <input class="input" type="text" v-model="form.commentaire" placeholder="Optionnel" />
        </label>
        <button class="btn" :disabled="!employeId || !form.competence_id" @click="addCompetence">Ajouter</button>
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
            <th>Date</th>
            <th>Commentaire</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in competencesEmploye" :key="c.id">
            <td>{{ c.nom }}</td>
            <td>{{ c.niveau ?? c.pivot?.niveau ?? '—' }}</td>
            <td>{{ formatDate(c.date_evaluation || c.pivot?.date_evaluation) }}</td>
            <td>{{ c.commentaire !== undefined && c.commentaire !== null ? c.commentaire : (c.pivot?.commentaire || '—') }}</td>
            <td><button class="btn btn-secondary btn-xs" @click="removeCompetence(c.id)">Supprimer</button></td>
          </tr>
          <tr v-if="!competencesEmploye.length">
            <td colspan="5" class="muted">Aucune compétence associée</td>
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
import { formatDateValue } from '../utils/formatters'

const employes = ref([])
const competences = ref([])
const competencesEmploye = ref([])
const employeId = ref('')
const loading = ref(false)
const message = ref('')
const form = ref({ competence_id: '', niveau: 3, commentaire: '' })

const normalize = (res) => res?.data?.data || res?.data || res || []

const loadRefs = async () => {
  const [empRes, compRes] = await Promise.all([
    api.get('/v1/employes', { params: { all: 1 } }),
    competenceService.getCompetences({ actif: true })
  ])
  employes.value = normalize(empRes)
  competences.value = normalize(compRes)
}

const loadEmployeCompetences = async () => {
  if (!employeId.value) {
    competencesEmploye.value = []
    return
  }
  loading.value = true
  try {
    const res = await competenceService.getEmployeCompetences(employeId.value)
    const arr = normalize(res)
    competencesEmploye.value = Array.isArray(arr) ? arr : []
  } finally {
    loading.value = false
  }
}

const addCompetence = async () => {
  message.value = ''
  try {
    await competenceService.addEmployeCompetence(employeId.value, {
      competence_id: form.value.competence_id,
      niveau: form.value.niveau,
      commentaire: form.value.commentaire,
    })
    await loadEmployeCompetences()
    message.value = 'Compétence ajoutée'
    form.value = { competence_id: '', niveau: 3, commentaire: '' }
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur'
  }
}

const removeCompetence = async (competenceId) => {
  try {
    await competenceService.deleteEmployeCompetence(employeId.value, competenceId)
    await loadEmployeCompetences()
  } catch (e) {
    message.value = 'Suppression impossible'
  }
}

const formatDate = (d) => {
  return formatDateValue(d, { empty: '—' })
}

onMounted(loadRefs)
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 12px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.filters-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
.filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; align-items: end; }
.filter { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #475569; }
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
