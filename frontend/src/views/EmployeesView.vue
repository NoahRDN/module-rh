<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Employés</h1>
      <p class="text-sm text-slate-500">Annuaire des collaborateurs</p>
    </div>
    <div class="flex w-full gap-2 lg:w-auto">
      <input class="input flex-1" placeholder="Rechercher (nom, prénom, matricule)" v-model="search" @input="handleSearch" />
      <RouterLink class="btn whitespace-nowrap" to="/employes/nouveau">+ Nouvel employé</RouterLink>
    </div>
  </div>

  <div class="card">
    <div class="flex items-center justify-between gap-2 mb-3">
      <div>
        <h3 class="text-lg font-semibold">Liste des employés</h3>
      </div>
    </div>
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6 mb-3">
      <input class="input" placeholder="Matricule" v-model="filters.matricule" list="matricules-list" />
      <input class="input" placeholder="Nom / Prénom" v-model="filters.nom" />
      <input class="input" placeholder="Email" v-model="filters.email" />
      <input class="input" placeholder="Poste" v-model="filters.poste" list="postes-list" />
      <input class="input" placeholder="Département" v-model="filters.departement" list="departements-list" />
      <input class="input" placeholder="Catégorie" v-model="filters.categorie" list="categories-list" />
    </div>
    <div class="flex justify-end mb-3">
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser les filtres</button>
    </div>
    <datalist id="matricules-list">
      <option v-for="m in optionsMatricules" :key="m" :value="m" />
    </datalist>
    <datalist id="postes-list">
      <option v-for="p in optionsPostes" :key="p" :value="p" />
    </datalist>
    <datalist id="departements-list">
      <option v-for="d in optionsDepartements" :key="d" :value="d" />
    </datalist>
    <datalist id="categories-list">
      <option v-for="c in optionsCategories" :key="c" :value="c" />
    </datalist>
    <div class="overflow-x-auto rounded-xl border border-slate-800/70">
      <table class="min-w-full text-sm text-left bg-slate-950/40 border border-slate-800 rounded-xl">
        <thead class="bg-slate-900 text-slate-100">
          <tr>
            <th class="px-4 py-3 font-semibold">Photo</th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('matricule')">
              Matricule <span class="text-xs">{{ sortLabel('matricule') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('nom')">
              Nom & Prénom <span class="text-xs">{{ sortLabel('nom') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('email')">
              Email <span class="text-xs">{{ sortLabel('email') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('poste')">
              Poste <span class="text-xs">{{ sortLabel('poste') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('categorie')">
              Catégorie <span class="text-xs">{{ sortLabel('categorie') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('departement')">
              Département <span class="text-xs">{{ sortLabel('departement') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold cursor-pointer" @click="setSort('actif')">
              Statut <span class="text-xs">{{ sortLabel('actif') }}</span>
            </th>
            <th class="px-4 py-3 font-semibold text-right">Fiche</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <template v-if="loading">
            <tr v-for="n in 5" :key="n" class="animate-pulse">
              <td class="px-4 py-4">
                <div class="h-12 w-12 rounded-full bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-20 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-32 rounded bg-slate-800 mb-2"></div>
                <div class="h-3 w-20 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-32 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-24 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-24 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4">
                <div class="h-3 w-20 rounded bg-slate-800"></div>
              </td>
              <td class="px-4 py-4 text-right">
                <div class="h-3 w-10 rounded bg-slate-800 ml-auto"></div>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="emp in filteredEmployes"
              :key="emp.id"
              class="hover:bg-slate-800/50 transition"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img
                    :src="photoUrl(emp)"
                    alt="photo"
                    class="h-12 w-12 rounded-full object-cover border border-slate-700 shadow-inner"
                    style="max-height: 48px; max-width: 48px;"
                  />
                </div>
              </td>
              <td class="px-4 py-3 font-semibold text-slate-100">{{ emp.matricule }}</td>
              <td>
                <div class="px-4 py-3">
                  <p class="font-semibold">{{ emp.nom }} {{ emp.prenom }}</p>
                </div>
              </td>
              <td>
                <div class="px-4 py-3">
                  <p class="text-xs text-slate-500">{{ emp.email || 'Email N/A' }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="chip">{{ emp.poste?.nom || '—' }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="chip chip-secondary">{{ emp.poste?.categorie || '—' }}</span>
              </td>
              <td class="px-4 py-3 text-slate-200">{{ emp.departement?.nom || '—' }}</td>
              <td class="px-4 py-3">
                <span class="chip" :class="emp.actif ? '' : 'muted'">{{ emp.actif ? 'Actif' : 'Inactif' }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink :to="`/employes/${emp.id}`" class="text-indigo-400 hover:underline text-sm">Voir</RouterLink>
              </td>
            </tr>
            <tr v-if="!employes.length">
              <td colspan="9" class="px-4 py-4 text-center text-slate-500">Aucun employé</td>
            </tr>
          </template>
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
import { RouterLink } from 'vue-router'

const employes = ref([])
const search = ref('')
const placeholder = ref('https://via.placeholder.com/80?text=EMP')
const loading = ref(false)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
let searchTimer = null
const filters = ref({
  matricule: '',
  nom: '',
  email: '',
  poste: '',
  departement: '',
  categorie: ''
})
const sortKey = ref('matricule')
const sortDir = ref('asc')
const optionsMatricules = computed(() => [...new Set(employes.value.map((e) => e.matricule).filter(Boolean))])
const optionsPostes = computed(() => [...new Set(employes.value.map((e) => e.poste?.nom).filter(Boolean))])
const optionsDepartements = computed(() => [...new Set(employes.value.map((e) => e.departement?.nom).filter(Boolean))])
const optionsCategories = computed(() => [...new Set(employes.value.map((e) => e.poste?.categorie).filter(Boolean))])

const fetchEmployes = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/employes', { params: { search: search.value, page: pagination.value.page } })
    employes.value = data.data || []
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
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchEmployes, 300)
}

const photoUrl = (emp) => {
  if (emp?.photo) return emp.photo
  const initials = `${emp?.nom?.[0] || ''}${emp?.prenom?.[0] || ''}` || 'EMP'
  return `https://ui-avatars.com/api/?background=0f172a&color=fff&name=${encodeURIComponent(initials)}`
}

const filteredEmployes = computed(() => {
  const f = filters.value
  const toSearch = (val) => String(val || '').toLowerCase()
  let list = employes.value.filter((e) => {
    return (
      toSearch(e.matricule).includes(toSearch(f.matricule)) &&
      (`${toSearch(e.nom)} ${toSearch(e.prenom)}`).includes(toSearch(f.nom)) &&
      toSearch(e.email).includes(toSearch(f.email)) &&
      toSearch(e.poste?.nom).includes(toSearch(f.poste)) &&
      toSearch(e.poste?.categorie).includes(toSearch(f.categorie)) &&
      toSearch(e.departement?.nom).includes(toSearch(f.departement))
    )
  })
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const valA = getSortVal(a, key)
    const valB = getSortVal(b, key)
    if (valA < valB) return dir === 'asc' ? -1 : 1
    if (valA > valB) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getSortVal = (emp, key) => {
  switch (key) {
    case 'nom':
      return `${emp.nom || ''} ${emp.prenom || ''}`.toLowerCase()
    case 'email':
      return (emp.email || '').toLowerCase()
    case 'poste':
      return (emp.poste?.nom || '').toLowerCase()
    case 'categorie':
      return (emp.poste?.categorie || '').toLowerCase()
    case 'departement':
      return (emp.departement?.nom || '').toLowerCase()
    case 'actif':
      return emp.actif ? 1 : 0
    case 'matricule':
    default:
      return (emp.matricule || '').toLowerCase()
  }
}

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortLabel = (key) => {
  if (sortKey.value !== key) return ''
  return sortDir.value === 'asc' ? '▲' : '▼'
}

const resetFilters = () => {
  filters.value = {
    matricule: '',
    nom: '',
    email: '',
    poste: '',
    departement: '',
    categorie: ''
  }
  search.value = ''
}

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchEmployes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchEmployes()
  }
}

onMounted(async () => {
  await fetchEmployes()
})
</script>
