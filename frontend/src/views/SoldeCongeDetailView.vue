<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Détail solde de congé</h1>
      <span v-if="solde">Employé : {{ employeLabel }}</span>
    </div>
  </div>

  <div v-if="loading" class="card">Chargement…</div>
  <div v-else-if="error" class="card text-red-500">Erreur : {{ error }}</div>
  <div v-else-if="!solde" class="card">Aucune donnée</div>
  <div v-else class="grid gap-3">
    <div class="card grid md:grid-cols-2 gap-3">
      <div class="grid gap-1">
        <div class="muted">Employé</div>
        <div class="font-semibold">{{ employeLabel }}</div>
        <div class="muted">Type de congé</div>
        <div class="font-semibold">{{ solde.type_conge?.libelle || solde.type_conge_libelle || '—' }}</div>
        <div class="muted">Contrat</div>
        <div class="font-semibold">{{ contratLabel }}</div>
      </div>
      <div class="grid gap-1">
        <div class="flex justify-between"><span class="muted">Premier acquis</span><span>{{ formatDate(solde.premier_acquis || solde.acquis_first) || '—' }}</span></div>
        <div class="flex justify-between"><span class="muted">Expiration max</span><span>{{ expirationMax }}</span></div>
        <div class="flex justify-between"><span class="muted">Solde actuel</span><span class="font-semibold">{{ solde.solde_actuel }}</span></div>
      </div>
    </div>

    <div class="card">
      <div class="flex items-center justify-between mb-2">
        <div>
          <h2 class="text-base font-semibold">Historique des demandes</h2>
          <div class="muted text-xs">Filtré par employé & type</div>
        </div>
        <div class="flex gap-2">
          <input class="input" type="date" v-model="filters.from" />
          <input class="input" type="date" v-model="filters.to" />
          <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
        </div>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>Date début</th>
            <th>Date fin</th>
            <th>Statut</th>
            <th>Motif</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in demandes" :key="d.id">
            <td>{{ formatDate(d.date_debut) || '—' }}</td>
            <td>{{ formatDate(d.date_fin) || '—' }}</td>
            <td>{{ d.statut }}</td>
            <td>{{ d.motif || '—' }}</td>
          </tr>
          <tr v-if="!demandes.length">
            <td colspan="4" class="muted">Aucune demande</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { formatDateValue } from '../utils/formatters'

const route = useRoute()
const solde = ref(null)
const demandes = ref([])
const loading = ref(false)
const error = ref('')
const filters = ref({ from: '', to: '' })

const employeLabel = computed(() => {
  if (!solde.value) return ''
  const s = solde.value
  return s.employe ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}` : `${s.employe_matricule || ''} ${s.employe_nom || ''} ${s.employe_prenom || ''}`
})

const contratLabel = computed(() => {
  const s = solde.value
  if (!s) return '—'
  const type = s.contrat_type || s.contratType
  const fin = s.contrat_fin || s.contratFin
  if (type && fin) return `${type} — fin ${formatDate(fin)}`
  if (type) return type
  if (fin) return formatDate(fin)
  return '—'
})

const expirationMax = computed(() => {
  const s = solde.value
  if (!s) return '—'
  const defaultVal = s.expire_first || s.expire_le || '—'
  const contratType = (s.contrat_type || s.contratType || '').toLowerCase()
  const contratFin = s.contrat_fin || s.contratFin
  const acquisFirst = s.acquis_first || s.acquisFirst

  if (contratType !== 'cdd' || !contratFin || !acquisFirst) {
    return defaultVal
  }

  const contratFinDate = new Date(contratFin)
  const acquisDate = new Date(acquisFirst)
  if (isNaN(contratFinDate.getTime()) || isNaN(acquisDate.getTime())) {
    return defaultVal
  }

  const diffYears = Math.abs(contratFinDate - acquisDate) / (365.25 * 24 * 60 * 60 * 1000)
  if (diffYears <= 3) {
    return formatDate(contratFin)
  }
  return formatDate(defaultVal) || defaultVal
})

const formatDate = (value) => formatDateValue(value)

const fetchSolde = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/v1/soldes-conges/${route.params.id}`)
    solde.value = data
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

const fetchDemandes = async () => {
  if (!solde.value) return
  const params = {
    employe_id: solde.value.employe_id,
    type_id: solde.value.type_conge_id,
    from: filters.value.from || undefined,
    to: filters.value.to || undefined,
  }
  try {
    const { data } = await api.get('/v1/demandes-conges', { params })
    demandes.value = data.data || data || []
  } catch (e) {
    demandes.value = []
  }
}

const resetFilters = () => {
  filters.value = { from: '', to: '' }
  fetchDemandes()
}

watch(filters, fetchDemandes, { deep: true })

onMounted(async () => {
  await fetchSolde()
  await fetchDemandes()
})
</script>

<style scoped>
.page-title h1 { margin: 0; }
.muted { color: #94a3b8; }
.card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
.grid { display: grid; gap: 12px; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
.table th { text-align: left; background: #f8fafc; }
.input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; }
.btn { border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
</style>
