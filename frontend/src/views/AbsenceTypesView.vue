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
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-slate-800/60">
            <th class="py-2 text-left text-slate-400 text-xs">Nom</th>
            <th class="py-2 text-left text-slate-400 text-xs">Payant</th>
            <th class="py-2 text-left text-slate-400 text-xs">Jours/an</th>
            <th class="py-2 text-left text-slate-400 text-xs">Description</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="t in types" :key="t.id" class="hover:bg-slate-800/30 transition">
            <td class="py-2 font-semibold text-slate-100">{{ t.nom }}</td>
            <td class="py-2">
              <span v-if="t.est_payant" class="chip">Payant</span>
              <span v-else class="chip" style="background: rgba(255,255,255,0.04); color: #cbd5e1;">Non payant</span>
            </td>
            <td class="py-2">{{ t.jours_annuels ?? '—' }}</td>
            <td class="py-2 text-slate-400 text-xs">{{ t.description || '—' }}</td>
          </tr>
          <tr v-if="!types.length">
            <td colspan="4" class="py-3 text-center text-slate-500">Aucun type</td>
          </tr>
        </tbody>
      </table>
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
import { ref, onMounted } from 'vue'
import api from '../services/api'

const types = ref([])
const search = ref('')
const message = ref('')
const form = ref({
  nom: '',
  description: '',
  est_payant: true,
  jours_annuels: ''
})

const fetchTypes = async () => {
  const { data } = await api.get('/v1/absences-types', { params: { search: search.value } })
  types.value = data.data || []
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
</script>
