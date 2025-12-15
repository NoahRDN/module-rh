<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Documents RH</h1>
      <span>Pièces jointes des employés</span>
    </div>
    <div class="flex gap-2 items-center">
      <select class="select" v-model="filterEmploye" @change="debouncedFetchDocs">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <RouterLink class="btn btn-secondary btn-sm" to="/documents/nouveau">+ Uploader</RouterLink>
    </div>
  </div>

  <div class="card">
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6 mb-3">
      <input class="input" placeholder="Matricule" v-model="filters.matricule" />
      <input class="input" placeholder="Nom" v-model="filters.nom" />
      <input class="input" placeholder="Type" v-model="filters.type" />
      <input class="input" placeholder="Fichier" v-model="filters.fichier" />
      <input class="input" placeholder="Expiration" v-model="filters.date_expiration" />
    </div>
    <div class="flex justify-end mb-2">
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
          <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
          <th class="cursor-pointer" @click="setSort('fichier')">Nom de fichier {{ sortLabel('fichier') }}</th>
          <th class="cursor-pointer" @click="setSort('expiration')">Date d'expiration {{ sortLabel('expiration') }}</th>
          <th>Fichier</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="d in docsFiltres" :key="d.id">
          <td>{{ d.employe?.matricule || '—' }}</td>
          <td>{{ d.type_document }}</td>
          <td>{{ fileName(d.fichier) }}</td>
          <td>{{ formatDate(d.date_expiration) || '—' }}</td>
          <td><a :href="d.url" target="_blank" rel="noopener">Ouvrir</a></td>
        </tr>
        <tr v-if="!docsFiltres.length">
          <td colspan="5" class="muted">Aucun document</td>
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

const docs = ref([])
const employes = ref([])
const filterEmploye = ref('')
const filters = ref({ matricule: '', nom: '', type: '', fichier: '', date_expiration: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchDocs = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
  const { data } = await api.get('/v1/documents', { params })
  docs.value = data.data || data || []
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
}

const debouncedFetchDocs = debounce(fetchDocs, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

onMounted(async () => {
  await Promise.all([fetchEmployes(), fetchDocs()])
})

const fileName = (path) => {
  if (!path) return '—'
  const parts = String(path).split(/[\\/]/)
  return parts[parts.length - 1] || path
}

const formatDate = (d) => {
  if (!d) return ''
  return String(d).split('T')[0]
}

const docsFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = docs.value.filter((d) =>
    toStr(d.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(d.type_document).includes(toStr(f.type)) &&
    toStr(fileName(d.fichier)).includes(toStr(f.fichier)) &&
    toStr(formatDate(d.date_expiration)).includes(toStr(f.date_expiration))
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

const getVal = (d, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'type': return toStr(d.type_document)
    case 'fichier': return toStr(fileName(d.fichier))
    case 'expiration': return toStr(formatDate(d.date_expiration))
    case 'employe':
    default:
      return `${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { matricule: '', nom: '', type: '', fichier: '', date_expiration: '' } }
const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchDocs()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchDocs()
  }
}
</script>
