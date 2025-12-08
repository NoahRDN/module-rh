<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Soldes de congés</h1>
      <span>Suivi par employé et par type</span>
    </div>
    <div class="flex items-center gap-2">
      <select class="select" v-model="filterEmploye" @change="debouncedFetchSoldes">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
      </select>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="grid gap-2 md:grid-cols-4 lg:grid-cols-6 mb-3">
        <input class="input" placeholder="Matricule" v-model="filters.matricule" />
        <input class="input" placeholder="Nom" v-model="filters.nom" />
        <input class="input" placeholder="Type" v-model="filters.type" />
        <input class="input" placeholder="Département" v-model="filters.departement" />
      </div>
      <div class="flex justify-end mb-2">
        <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
            <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
            <th class="cursor-pointer" @click="setSort('solde_actuel')">Solde actuel {{ sortLabel('solde_actuel') }}</th>
            <th class="cursor-pointer" @click="setSort('solde_annuel')">Solde annuel {{ sortLabel('solde_annuel') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in soldesFiltres" :key="s.id">
            <td>{{ s.employe ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}` : '—' }}</td>
            <td>{{ s.type?.nom || '—' }}</td>
            <td>{{ s.solde_actuel }}</td>
            <td>{{ s.solde_annuel }}</td>
          </tr>
          <tr v-if="!soldesFiltres.length">
            <td colspan="4" class="muted">Aucun solde</td>
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
import { onMounted, ref, computed } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'

const soldes = ref([])
const employes = ref([])
const types = ref([])
const filterEmploye = ref('')
const message = ref('')
const loading = ref(false)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const filters = ref({ matricule: '', nom: '', type: '', departement: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')

const form = ref({
  employe_id: '',
  type_id: '',
  solde_actuel: '',
  solde_annuel: ''
})

const fetchSoldes = async () => {
  loading.value = true
  const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
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
  const [emps, tps] = await Promise.all([
    api.get('/v1/employes', { params: { active_only: true } }),
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
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = soldes.value.filter((s) =>
    toStr(s.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(s.employe?.nom)} ${toStr(s.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(s.type?.nom).includes(toStr(f.type)) &&
    toStr(s.employe?.departement?.nom).includes(toStr(f.departement))
  )
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
    case 'type': return toStr(s.type?.nom)
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
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { matricule: '', nom: '', type: '', departement: '' } }
</script>
