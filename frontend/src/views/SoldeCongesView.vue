<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Soldes de congés</h1>
      <span>Suivi par employé et par type</span>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <span class="chip" :class="deductionClasses.leave">
            Prélèvement solde congé : {{ deductionText(deductionSettings.deduct_from_leave_balance) }}
          </span>
          <span class="chip" :class="deductionClasses.salary">
            Prélèvement salaire : {{ deductionText(deductionSettings.deduct_from_salary) }}
          </span>
        </div>
        <p class="text-xs text-slate-500 max-w-xl">
          Les absences/retards sont imputées selon ces réglages (configurés dans la page Horaires).
        </p>
      </div>
      <div class="grid gap-2 md:grid-cols-6 mb-3">
        <div class="grid gap-1">
          <select class="select" v-model="filterEmploye" @change="debouncedFetchSoldes">
            <option value="">Tous les employés</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Matricule</label>
          <input class="input" placeholder="Matricule" v-model="filters.matricule" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Nom</label>
          <input class="input" placeholder="Nom" v-model="filters.nom" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Type</label>
          <input class="input" placeholder="Type" v-model="filters.type" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Département</label>
          <input class="input" placeholder="Département" v-model="filters.departement" />
        </div>
        <!-- <div class="grid gap-1">
          <label class="text-xs text-slate-400">Du</label>
          <input class="input" type="date" v-model="filters.from" />
        </div> -->
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Simulation date actuelle</label>
          <input class="input" type="date" v-model="filters.simulation_date" />
        </div>
      </div>
      <div class="flex justify-end mb-2">
        <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
      </div>
      <p v-if="message" class="text-sm text-red-400 mb-2">{{ message }}</p>
      <table class="table">
        <thead>
          <tr>
            <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
            <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
            <th>Contrat</th>
            <th>Premier acquis</th>
            <th>Expiration max</th>
            <th>Acquis période</th>
            <th>Utilisé période</th>
            <th class="cursor-pointer" @click="setSort('solde_actuel')">Solde période {{ sortLabel('solde_actuel') }}</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in soldesFiltres" :key="s.id">
            <td>{{ s.employe ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}` : `${s.employe_matricule || ''} ${s.employe_nom || ''} ${s.employe_prenom || ''}` }}</td>
            <td>{{ s.type_conge?.libelle || s.type_conge_libelle || '—' }}</td>
            <td>{{ contratLabel(s) }}</td>
            <td>{{ s.premier_acquis || '—' }}</td>
            <td>{{ expirationMax(s) }}</td>
            <td>{{ s.acquis_periode ?? s.total_acquis ?? '—' }}</td>
            <td>{{ s.utilise_periode ?? s.total_utilise ?? '—' }}</td>
            <td>{{ s.solde_periode ?? s.solde_actuel }}</td>
            <td>
              <RouterLink class="btn btn-secondary btn-xs" :to="`/soldes-conges/${s.id}`">Détails</RouterLink>
            </td>
          </tr>
          <tr v-if="!soldesFiltres.length">
            <td colspan="8" class="muted">Aucun solde</td>
          </tr>
        </tbody>
      </table>
      <div class="flex items-center justify-between mt-3 text-sm text-slate-400">
        <span>Page {{ pagination.page }} / {{ pagination.last_page }} — {{ pagination.total }} lignes</span>
        <div class="flex items-center gap-2">
          <button class="btn btn-secondary text-xs" :disabled="pagination.page <= 1" @click="prevPage">Précédent</button>
          <button class="btn btn-secondary text-xs" :disabled="pagination.page >= pagination.last_page" @click="nextPage">Suivant</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import { debounce } from '../utils/debounce'

const soldes = ref([])
const employes = ref([])
const types = ref([])
const deductionSettings = ref({ deduct_from_leave_balance: true, deduct_from_salary: true })
const deductionClasses = computed(() => ({
  leave: deductionSettings.value.deduct_from_leave_balance ? 'chip-on' : 'chip-off',
  salary: deductionSettings.value.deduct_from_salary ? 'chip-on' : 'chip-off'
}))
const deductionText = (val) => (val ? 'Activé' : 'Désactivé')
const filterEmploye = ref('')
const message = ref('')
const loading = ref(false)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const filters = ref({ matricule: '', nom: '', type: '', departement: '', from: '', to: '', simulation_date: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')

const form = ref({
  employe_id: '',
  type_conge_id: '',
  solde_actuel: '',
  solde_annuel: ''
})

const periode = ref({
  employe_id: '',
  type_conge_id: '',
  from: '',
  to: ''
})
const soldePeriode = ref({ solde: null, from: null, to: null })
const messagePeriode = ref('')

const congesRange = ref({ from: '', to: '', employe_id: '' })
const demandesRange = ref([])

const fetchSoldes = async () => {
  loading.value = true
  message.value = ''

  // Interdire le futur
  if (filters.value.simulation_date) {
    const sim = new Date(filters.value.simulation_date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    if (sim > today) {
      message.value = 'Simulation interdite dans le futur. Choisissez une date au passé ou aujourd\'hui.'
      loading.value = false
      return
    }
  }

  const params = {
    page: pagination.value.page,
    employe_id: filterEmploye.value || undefined,
    from: filters.value.from || undefined,
    to: filters.value.to || undefined,
    simulation_date: filters.value.simulation_date || undefined,
    matricule: filters.value.matricule || undefined,
    nom: filters.value.nom || undefined,
    type: filters.value.type || undefined,
    departement: filters.value.departement || undefined
  }
  const { data } = await api.get('/v1/soldes-conges', { params })
  soldes.value = data.data || []
  if (data.meta) {
    pagination.value = {
      page: data.meta.current_page,
      last_page: data.meta.last_page,
      total: data.meta.total
    }
  } else if (data.current_page !== undefined) {
    pagination.value = {
      page: data.current_page,
      last_page: data.last_page,
      total: data.total
    }
  }
  loading.value = false
}

const debouncedFetchSoldes = debounce(fetchSoldes, 300)

const fetchRefs = async () => {
  const [emps, tps, worktime] = await Promise.all([
    api.get('/v1/employes', { params: { active_only: true } }),
    api.get('/v1/types-conges'),
    api.get('/v1/worktime').catch(() => ({ data: {} }))
  ])
  employes.value = emps.data.data || []
  types.value = tps.data.data || []
  if (worktime?.data) {
    deductionSettings.value = {
      deduct_from_leave_balance: worktime.data.deduct_from_leave_balance ?? true,
      deduct_from_salary: worktime.data.deduct_from_salary ?? true
    }
  }
}

const saveSolde = async () => {
  message.value = 'Lecture seule : le solde est calculé automatiquement.'
}

onMounted(async () => {
  await Promise.all([fetchRefs(), fetchSoldes()])
})

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchSoldes()
  }
}

const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchSoldes()
  }
}

const soldesFiltres = computed(() => {
  let list = soldes.value
  console.log(soldes)
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = getVal(a, key)
    const vb = getVal(b, key)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getVal = (s, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'type': return toStr(s.type_conge?.libelle)
    case 'solde_actuel': return Number(s.solde_actuel) || 0
    case 'solde_annuel': return Number(s.solde_annuel) || 0
    case 'employe':
    default:
      return `${toStr(s.employe?.nom)} ${toStr(s.employe?.prenom)}`
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

const expirationMax = (s) => {
  const defaultVal = s.expire_first || s.expire_le || '—'
  const contratType = (s.contrat_type || s.contratType || '').toLowerCase()
  const contratFin = s.contrat_fin || s.contratFin
  const acquisFirst = s.acquis_first || s.acquisFirst
  console.log("contrat type")
  console.log(contratType);
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
    return contratFin
  }
  return defaultVal
}

const contratLabel = (s) => {
  const type = s.contrat_type || s.contratType
  const fin = s.contrat_fin || s.contratFin
  if (!type && !fin) return '—'
  if (type && fin) return `${type} — fin ${fin}`
  if (type) return type
  return fin
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => {
  filters.value = { matricule: '', nom: '', type: '', departement: '', from: '', to: '', simulation_date: '' }
  pagination.value.page = 1
  debouncedFetchSoldes()
}

const router = useRouter()
const goToDetail = (s) => {
  if (!s?.id) return
  router.push(`/soldes-conges/${s.id}`)
}

// Rafraîchir quand la plage de dates change
watch(
  () => [filters.value.from, filters.value.to, filters.value.simulation_date, filters.value.matricule, filters.value.nom, filters.value.type, filters.value.departement],
  () => {
    pagination.value.page = 1
    debouncedFetchSoldes()
  }
)

const calculerPeriode = async () => {
  messagePeriode.value = ''
  soldePeriode.value = { solde: null, from: null, to: null }
  if (!periode.value.employe_id) {
    messagePeriode.value = 'Sélectionne un employé'
    return
  }
  try {
    const params = { ...periode.value }
    const { data } = await api.get('/v1/soldes-conges/periode', { params })
    soldePeriode.value = data
  } catch (e) {
    messagePeriode.value = e.response?.data?.message || 'Erreur lors du calcul'
  }
}

const fetchCongesRange = async () => {
  try {
    const params = { ...congesRange.value }
    const { data } = await api.get('/v1/demandes-conges', { params })
    demandesRange.value = data.data || data || []
  } catch (e) {
    demandesRange.value = []
  }
}
</script>

<style scoped>
.chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}
.chip-on { background: rgba(34,197,94,0.12); color: #166534; }
.chip-off { background: rgba(248,113,113,0.15); color: #b91c1c; }
</style>
