<template>
  <div class="flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Historique des contrats</h1>
        <p class="text-sm text-slate-500">Toutes les versions et renouvellements</p>
      </div>
    </div>
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6">
      <input class="input" placeholder="Numéro" v-model="filters.numero" />
      <input class="input" placeholder="Matricule" v-model="filters.matricule" />
      <input class="input" placeholder="Nom" v-model="filters.nom" />
      <input class="input" placeholder="Type" v-model="filters.type" />
      <input class="input" placeholder="Département" v-model="filters.departement" />
      <input class="input" placeholder="Poste" v-model="filters.poste" />
    </div>
    <div class="flex justify-end">
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
    </div>
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th class="cursor-pointer" @click="setSort('contrat')">Contrat ID {{ sortLabel('contrat') }}</th>
            <th class="cursor-pointer" @click="setSort('numero')">Numéro {{ sortLabel('numero') }}</th>
            <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
            <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
            <th class="cursor-pointer" @click="setSort('contrat_dates')">Contrat {{ sortLabel('contrat_dates') }}</th>
            <th>Période d'essai</th>
            <th class="cursor-pointer" @click="setSort('created_at')">Date création {{ sortLabel('created_at') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="h in historiquesFiltres" :key="h.id">
            <td>{{ h.contrat_id }}</td>
            <td>{{ h.numero || '—' }}</td>
            <td>{{ h.employe ? `${h.employe.matricule} - ${h.employe.nom} ${h.employe.prenom}` : '—' }}</td>
            <td>{{ h.type_contrat }}</td>
            <td>{{ fmt(h.date_debut) }} → {{ fmt(h.date_fin) || '—' }}</td>
            <td>{{ fmt(h.periode_essai_debut) || '—' }} → {{ fmt(h.periode_essai_fin) || '—' }}</td>
            <td>{{ fmt(h.created_at) }}</td>
          </tr>
          <tr v-if="!historiquesFiltres.length">
            <td colspan="7" class="muted">Aucun historique</td>
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

const historiques = ref([])
const filters = ref({ numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' })
const sortKey = ref('created_at')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchHistoriques = async () => {
  const { data } = await api.get('/v1/contrats-historiques', { params: { page: pagination.value.page } })
  historiques.value = data.data || []
  if (data.meta) {
    pagination.value = { page: data.meta.current_page, last_page: data.meta.last_page, total: data.meta.total }
  } else if (data.current_page !== undefined) {
    pagination.value = { page: data.current_page, last_page: data.last_page, total: data.total }
  }
}

const fmt = (d) => (d ? String(d).split('T')[0] : '')

const historiquesFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = historiques.value.filter((h) => {
    return (
      toStr(h.numero).includes(toStr(f.numero)) &&
      toStr(h.employe?.matricule).includes(toStr(f.matricule)) &&
      (`${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`).includes(toStr(f.nom)) &&
      toStr(h.type_contrat).includes(toStr(f.type)) &&
      toStr(h.employe?.departement?.nom).includes(toStr(f.departement)) &&
      toStr(h.employe?.poste?.nom).includes(toStr(f.poste))
    )
  })
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
    case 'numero': return toStr(h.numero)
    case 'employe': return `${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`
    case 'type': return toStr(h.type_contrat)
    case 'contrat_dates': return toStr(h.date_debut)
    case 'contrat': return Number(h.contrat_id) || 0
    case 'created_at':
    default:
      return toStr(h.created_at)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' } }

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchHistoriques()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchHistoriques()
  }
}

onMounted(fetchHistoriques)
</script>
