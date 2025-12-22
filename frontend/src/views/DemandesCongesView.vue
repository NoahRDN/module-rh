<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Demandes de congés</h1>
      <p class="text-sm text-slate-500">Workflow manager + RH</p>
    </div>
    <div class="flex w-full gap-2 lg:w-auto">
      <button class="btn btn-secondary" @click="fetchDemandes">Actualiser</button>
      <RouterLink class="btn" to="/demandes-conges/nouveau">+ Nouvelle</RouterLink>
    </div>
  </div>

  <div class="card">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">Demandes en cours</h3>
        <span class="muted text-sm">Manager → RH</span>
      </div>
      <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6 mb-3">
        <select class="select" v-model="filterEmploye" @change="debouncedFetchDemandes">
          <option value="">Tous les employés</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <input class="input" placeholder="Matricule" v-model="filters.matricule" />
        <input class="input" placeholder="Nom" v-model="filters.nom" />
        <input class="input" placeholder="Type" v-model="filters.type" />
        <input class="input" placeholder="Statut" v-model="filters.statut" />
        <input class="input" placeholder="Début" v-model="filters.date_debut" />
        <input class="input" placeholder="Fin" v-model="filters.date_fin" />
      </div>
      <div class="flex justify-end mb-2">
        <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
      </div>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-slate-800/60">
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('debut')">Période {{ sortLabel('debut') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('statut')">Statut {{ sortLabel('statut') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="d in demandesFiltrees" :key="d.id" class="hover:bg-slate-800/30 transition">
            <td class="py-2">
              <p class="font-semibold text-slate-100">{{ d.employe?.matricule || '—' }}</p>
              <p class="text-xs text-slate-500">{{ d.employe ? `${d.employe.nom} ${d.employe.prenom}` : '—' }}</p>
            </td>
            <td class="py-2">{{ d.type_conge?.libelle || d.type?.nom || '—' }}</td>
            <td class="py-2 text-xs text-slate-300">{{ d.date_debut }} → {{ d.date_fin }}</td>
            <td class="py-2">
              <span class="px-2 py-1 rounded-full text-xs" :class="badgeClass(d.statut)">
                {{ d.statut }}
              </span>
            </td>
            <td class="py-2 flex flex-wrap gap-2">
              <template v-if="canAct(d)">
              <button class="btn btn-secondary text-xs" @click="approveManager(d.id)">Valider ✓</button>
              <button class="btn btn-secondary text-xs" style="padding: 6px 10px; background: rgba(239,68,68,0.12); color: #fca5a5;" @click="reject(d.id)">Refuser</button>
              </template>
              <span v-else class="text-xs text-slate-500">—</span>
            </td>
          </tr>
          <tr v-if="!demandesFiltrees.length">
            <td colspan="5" class="py-3 text-center text-slate-500">Aucune demande</td>
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
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { debounce } from '../utils/debounce'

const demandes = ref([])
const employes = ref([])
const filterEmploye = ref('')
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const filters = ref({ matricule: '', nom: '', type: '', statut: '', date_debut: '', date_fin: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')

const badgeClass = (statut) => {
  switch (statut) {
    case 'rh_valide':
      return 'bg-green-50 text-green-600'
    case 'manager_valide':
    case 'en_attente':
      return 'bg-blue-50 text-blue-600'
    case 'rejete':
      return 'bg-red-50 text-red-600'
    default:
      return 'bg-slate-100 text-slate-600'
  }
}

const fetchDemandes = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
  const { data } = await api.get('/v1/demandes-conges', { params })
  demandes.value = data.data || []
  if (data.meta) {
    pagination.value = { page: data.meta.current_page, last_page: data.meta.last_page, total: data.meta.total }
  } else if (data.current_page !== undefined) {
    pagination.value = { page: data.current_page, last_page: data.last_page, total: data.total }
  }
}

const debouncedFetchDemandes = debounce(fetchDemandes, 300)

const fetchRefs = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

const approveManager = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/manager-approve`)
  await fetchDemandes()
}

const reject = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/reject`)
  await fetchDemandes()
}

const demandesFiltrees = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = demandes.value.filter((d) =>
    toStr(d.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`).includes(toStr(f.nom)) &&
    (toStr(d.type_conge?.libelle).includes(toStr(f.type)) || toStr(d.type?.nom).includes(toStr(f.type))) &&
    toStr(d.statut).includes(toStr(f.statut)) &&
    toStr(d.date_debut).includes(toStr(f.date_debut)) &&
    toStr(d.date_fin).includes(toStr(f.date_fin))
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
    case 'type': return toStr(d.type_conge?.libelle || d.type?.nom)
    case 'debut': return toStr(d.date_debut)
    case 'statut': return toStr(d.statut)
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
const resetFilters = () => { filters.value = { matricule: '', nom: '', type: '', statut: '', date_debut: '', date_fin: '' } }

const canAct = (demande) => demande.statut === 'en_attente' || demande.statut === 'manager_valide'

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchDemandes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchDemandes()
  }
}

onMounted(async () => {
  await fetchRefs()
  await fetchDemandes()
})
</script>
