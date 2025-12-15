<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Postes</h1>
      <span>Fonctions et rattachements</span>
    </div>
    <div class="flex gap-2 items-center">
      <select class="select" v-model="filterDep" @change="debouncedFetchPostes">
        <option value="">Tous les départements</option>
        <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
      </select>
      <RouterLink class="btn btn-secondary btn-sm" to="/postes/nouveau">+ Ajouter</RouterLink>
    </div>
  </div>

  <div class="card">
    <div class="grid gap-2 md:grid-cols-3 mb-2">
      <input class="input" placeholder="Nom" v-model="filters.nom" />
      <input class="input" placeholder="Département" v-model="filters.departement" list="deps-list" />
      <input class="input" placeholder="Catégorie" v-model="filters.categorie" list="cat-list" />
    </div>
    <div class="flex justify-end mb-2">
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
    </div>
    <datalist id="deps-list">
      <option v-for="d in departements" :key="d.id" :value="d.nom" />
    </datalist>
    <datalist id="cat-list">
      <option v-for="c in categories" :key="c" :value="c" />
    </datalist>
    <table class="table">
      <thead>
        <tr>
          <th class="cursor-pointer" @click="setSort('nom')">Nom {{ sortLabel('nom') }}</th>
          <th class="cursor-pointer" @click="setSort('departement')">Département {{ sortLabel('departement') }}</th>
          <th class="cursor-pointer" @click="setSort('categorie')">Catégorie {{ sortLabel('categorie') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in postesFiltrees" :key="p.id">
          <td>{{ p.nom }}</td>
          <td class="muted">{{ p.departement?.nom || '—' }}</td>
          <td><span class="chip">{{ p.categorie || '—' }}</span></td>
        </tr>
        <tr v-if="!postesFiltrees.length">
          <td colspan="3" class="muted">Aucun poste</td>
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

const departements = ref([])
const postes = ref([])
const filterDep = ref('')
const message = ref('')
const categories = ['Ouvriers', 'Employés', 'TAM', 'Cadres', 'Dirigeants']
const filters = ref({ nom: '', departement: '', categorie: '' })
const sortKey = ref('nom')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchPostes = async () => {
  const params = filterDep.value ? { departement_id: filterDep.value, page: pagination.value.page } : { page: pagination.value.page }
  const { data } = await api.get('/v1/postes', { params })
  postes.value = data.data || []
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

const debouncedFetchPostes = debounce(fetchPostes, 300)

const fetchDeps = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || []
}

const createPoste = async () => {
  try {
    await api.post('/v1/postes', form.value)
    message.value = 'Poste créé'
    form.value = { nom: '', description: '', departement_id: '', categorie: '' }
    await fetchPostes()
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

const postesFiltrees = computed(() => {
  const f = filters.value
  const toSearch = (v) => String(v || '').toLowerCase()
  let list = postes.value.filter((p) =>
    toSearch(p.nom).includes(toSearch(f.nom)) &&
    toSearch(p.departement?.nom).includes(toSearch(f.departement)) &&
    toSearch(p.categorie).includes(toSearch(f.categorie))
  )
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = key === 'departement' ? toSearch(a.departement?.nom) : key === 'categorie' ? toSearch(a.categorie) : toSearch(a.nom)
    const vb = key === 'departement' ? toSearch(b.departement?.nom) : key === 'categorie' ? toSearch(b.categorie) : toSearch(b.nom)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')

const resetFilters = () => {
  filters.value = { nom: '', departement: '', categorie: '' }
}

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchPostes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchPostes()
  }
}

onMounted(async () => {
  await fetchDeps()
  await fetchPostes()
})
</script>
