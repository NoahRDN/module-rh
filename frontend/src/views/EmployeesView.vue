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
    <div class="overflow-x-auto rounded-xl border border-slate-800/70">
      <table class="min-w-full text-sm text-left bg-slate-950/40 border border-slate-800 rounded-xl">
        <thead class="bg-slate-900 text-slate-100">
          <tr>
            <th class="px-4 py-3 font-semibold">Photo</th>
            <th class="px-4 py-3 font-semibold">Matricule</th>
            <th class="px-4 py-3 font-semibold">Nom & Prénom</th>
            <th class="px-4 py-3 font-semibold">Email</th>
            <th class="px-4 py-3 font-semibold">Poste</th>
            <th class="px-4 py-3 font-semibold">Département</th>
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
              <td class="px-4 py-4 text-right">
                <div class="h-3 w-10 rounded bg-slate-800 ml-auto"></div>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="emp in employes"
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
              <td class="px-4 py-3 text-slate-200">{{ emp.departement?.nom || '—' }}</td>
              <td class="px-4 py-3 text-right">
                <RouterLink :to="`/employes/${emp.id}`" class="text-indigo-400 hover:underline text-sm">Voir</RouterLink>
              </td>
            </tr>
            <tr v-if="!employes.length">
              <td colspan="7" class="px-4 py-4 text-center text-slate-500">Aucun employé</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
import { RouterLink } from 'vue-router'

const employes = ref([])
const search = ref('')
const placeholder = ref('https://via.placeholder.com/80?text=EMP')
const loading = ref(false)
let searchTimer = null

const fetchEmployes = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/employes', { params: { search: search.value } })
    employes.value = data.data || []
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchEmployes, 300)
}

const photoUrl = (emp) => emp.photo || placeholder.value

onMounted(async () => {
  await fetchEmployes()
})
</script>
