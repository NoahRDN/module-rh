<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Types d'absence</h1>
      <p class="text-sm text-slate-500">Congés payés, maladie, exceptionnels</p>
    </div>
    <div class="flex w-full gap-2 lg:w-auto">
      <input class="input flex-1" placeholder="Rechercher un type" v-model="search" @input="fetchTypes" />
      <button class="btn btn-secondary" @click="fetchTypes">Actualiser</button>
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
      <h3 class="text-lg font-semibold mb-2">Catalogue des types</h3>
      <div class="grid gap-2 md:grid-cols-3 mb-3">
        <input class="input" placeholder="Nom" v-model="filters.nom" />
        <input class="input" placeholder="Payant (oui/non)" v-model="filters.payant" />
        <input class="input" placeholder="Jours annuels" v-model="filters.jours" />
      </div>
      <div class="flex justify-end mb-2">
        <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
      </div>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-slate-800/60">
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('nom')">Nom {{ sortLabel('nom') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('payant')">Payant {{ sortLabel('payant') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs cursor-pointer" @click="setSort('jours')">Jours/an {{ sortLabel('jours') }}</th>
            <th class="py-2 text-left text-slate-400 text-xs">Description</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="t in typesFiltres" :key="t.id" class="hover:bg-slate-800/30 transition">
            <td class="py-2 font-semibold text-slate-100">{{ t.nom }}</td>
            <td class="py-2">
              <span v-if="t.est_payant" class="chip">Payant</span>
              <span v-else class="chip" style="background: rgba(255,255,255,0.04); color: #cbd5e1;">Non payant</span>
            </td>
            <td class="py-2">{{ t.jours_annuels ?? '—' }}</td>
            <td class="py-2 text-slate-400 text-xs">{{ t.description || '—' }}</td>
          </tr>
          <tr v-if="!typesFiltres.length">
            <td colspan="4" class="py-3 text-center text-slate-500">Aucun type</td>
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

    <div class="card">
      <h2 class="text-lg font-semibold">Nouveau type</h2>
      <p class="text-sm text-slate-500 mb-3">Ajoute un type de congé / absence</p>
      <form class="space-y-3" @submit.prevent="createType">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Nom</label>
          <input class="input" v-model="form.nom" placeholder="Congé payé, Maladie, Exceptionnel..." required />
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Description</label>
          <textarea class="input" rows="3" v-model="form.description" placeholder="Règles, justificatifs, etc."></textarea>
        </div>
        <label class="text-sm text-slate-400 flex items-center gap-2">
          <input type="checkbox" v-model="form.est_payant" />
          Payant
        </label>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Jours annuels (optionnel)</label>
          <input class="input" v-model="form.jours_annuels" placeholder="Ex: 30" type="number" min="0" />
        </div>
        <button class="btn w-full" type="submit">Enregistrer</button>
        <p class="text-sm text-slate-500" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'

const types = ref([])
const search = ref('')
const message = ref('')
const filters = ref({ nom: '', payant: '', jours: '' })
const sortKey = ref('nom')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const form = ref({
  nom: '',
  description: '',
  est_payant: true,
  jours_annuels: ''
})

const fetchTypes = async () => {
  const { data } = await api.get('/v1/absences-types', { params: { search: search.value, page: pagination.value.page } })
  types.value = data.data || []
  if (data.meta) {
    pagination.value = { page: data.meta.current_page, last_page: data.meta.last_page, total: data.meta.total }
  } else if (data.current_page !== undefined) {
    pagination.value = { page: data.current_page, last_page: data.last_page, total: data.total }
  }
}

const createType = async () => {
  try {
    const payload = { ...form.value }
    payload.jours_annuels = payload.jours_annuels || null
    await api.post('/v1/absences-types', payload)
    message.value = 'Type ajouté'
    await fetchTypes()
  } catch (e) {
    message.value = 'Erreur'
  }
}

onMounted(fetchTypes)

const typesFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = types.value.filter((t) =>
    toStr(t.nom).includes(toStr(f.nom)) &&
    (toStr(t.est_payant ? 'oui' : 'non').includes(toStr(f.payant))) &&
    toStr(t.jours_annuels).includes(toStr(f.jours))
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

const getVal = (t, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'payant': return toStr(t.est_payant ? 'oui' : 'non')
    case 'jours': return Number(t.jours_annuels) || 0
    case 'nom':
    default:
      return toStr(t.nom)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { nom: '', payant: '', jours: '' } }
const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchTypes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchTypes()
  }
}
</script>
