<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Historique des postes</h1>
      <span>Mobilités internes</span>
    </div>
    <div class="flex items-center gap-2">
      <select class="select" v-model="filterEmploye" @change="debouncedFetchHistorique">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <RouterLink class="btn whitespace-nowrap" to="/historiques/nouveau">+ Nouvelle mobilité</RouterLink>
    </div>
  </div>

  <div class="card">
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-5 mb-3">
      <input class="input" placeholder="Matricule" v-model="filters.matricule" />
      <input class="input" placeholder="Nom" v-model="filters.nom" />
      <input class="input" placeholder="Poste" v-model="filters.poste" />
      <input class="input" placeholder="Département" v-model="filters.departement" />
      <input class="input" placeholder="Motif" v-model="filters.motif" />
    </div>
    <div class="flex justify-end mb-2">
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th class="cursor-pointer" @click="setSort('date')">Date {{ sortLabel('date') }}</th>
          <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
          <th class="cursor-pointer" @click="setSort('poste')">Poste {{ sortLabel('poste') }}</th>
          <th class="cursor-pointer" @click="setSort('departement')">Département {{ sortLabel('departement') }}</th>
          <th class="cursor-pointer" @click="setSort('motif')">Motif {{ sortLabel('motif') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="h in historiquesFiltres" :key="h.id">
          <td>{{ h.date_changement }}</td>
          <td>{{ h.employe ? `${h.employe.matricule} - ${h.employe.nom} ${h.employe.prenom}` : '—' }}</td>
          <td>{{ h.poste?.nom || '—' }}</td>
          <td>{{ h.departement?.nom || '—' }}</td>
          <td class="muted">{{ h.motif || '—' }}</td>
        </tr>
        <tr v-if="!historiquesFiltres.length">
          <td colspan="5" class="muted">Aucun mouvement</td>
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
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import { RouterLink } from 'vue-router'

const historiques = ref([])
const employes = ref([])
const filterEmploye = ref('')
const filters = ref({ matricule: '', nom: '', poste: '', departement: '', motif: '' })
const sortKey = ref('date')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchHistorique = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
  const { data } = await api.get('/v1/historiques-postes', { params })
  historiques.value = data.data || []
  if (data.meta) {
    pagination.value = { page: data.meta.current_page, last_page: data.meta.last_page, total: data.meta.total }
  } else if (data.current_page !== undefined) {
    pagination.value = { page: data.current_page, last_page: data.last_page, total: data.total }
  }
}

const debouncedFetchHistorique = debounce(fetchHistorique, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const historiquesFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = historiques.value.filter((h) =>
    toStr(h.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(h.poste?.nom).includes(toStr(f.poste)) &&
    toStr(h.departement?.nom).includes(toStr(f.departement)) &&
    toStr(h.motif).includes(toStr(f.motif))
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

const getVal = (h, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'employe': return `${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`
    case 'poste': return toStr(h.poste?.nom)
    case 'departement': return toStr(h.departement?.nom)
    case 'motif': return toStr(h.motif)
    case 'date':
    default:
      return toStr(h.date_changement)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { matricule: '', nom: '', poste: '', departement: '', motif: '' } }

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchHistorique()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchHistorique()
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchHistorique()
})
</script>
