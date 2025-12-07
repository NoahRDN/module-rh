<template>
  <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="page-title">
      <h1>Relevé de présence</h1>
      <span>Heures, heures sup, retards, absences</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <select class="select" v-model="employeId">
        <option value="">Employé</option>
        <option v-for="e in employes" :key="e.id" :value="e.id">{{ e.matricule }} - {{ e.nom }} {{ e.prenom }}</option>
      </select>
      <input class="input w-36" type="month" v-model="mois" />
      <button class="btn btn-secondary" @click="fetchReleve">Générer</button>
      <RouterLink class="btn" to="/paie-generation">Vers paie</RouterLink>
    </div>
  </div>

  <div class="grid gap-3 lg:grid-cols-4 mb-4">
    <div class="card stat-card">
      <p class="muted text-xs">Heures travaillées</p>
      <p class="text-2xl font-semibold">{{ totaux.heures_travaillees ?? 0 }}</p>
    </div>
    <div class="card stat-card">
      <p class="muted text-xs">Heures supplémentaires</p>
      <p class="text-2xl font-semibold">{{ totaux.heures_supplementaires ?? 0 }}</p>
    </div>
    <div class="card stat-card">
      <p class="muted text-xs">Retards (minutes)</p>
      <p class="text-2xl font-semibold">{{ totaux.retard_minutes ?? 0 }}</p>
    </div>
    <div class="card stat-card">
      <p class="muted text-xs">Absences</p>
      <p class="text-2xl font-semibold">{{ totaux.absences ?? 0 }}</p>
    </div>
  </div>

  <div class="card">
    <h3 class="text-lg font-semibold mb-2">Détails journaliers</h3>
    <table class="min-w-full text-sm">
      <thead class="border-b border-slate-800/60">
        <tr>
          <th class="py-2 text-left text-slate-400 text-xs">Jour</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures sup</th>
          <th class="py-2 text-left text-slate-400 text-xs">Retard (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Absent</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/60">
        <tr v-for="d in details" :key="d.jour" class="hover:bg-slate-800/30 transition">
          <td class="py-2">{{ d.jour }}</td>
          <td class="py-2">{{ d.heures_travaillees }}</td>
          <td class="py-2">{{ d.heures_supplementaires }}</td>
          <td class="py-2">{{ d.retard_minutes }}</td>
          <td class="py-2">
            <span class="chip" v-if="!d.absent">Présent</span>
            <span class="chip" style="background: rgba(248,113,113,0.15); color: #fca5a5;" v-else>Absent</span>
          </td>
        </tr>
        <tr v-if="!details.length">
          <td colspan="5" class="py-3 text-center text-slate-500">Aucune donnée</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'

const employes = ref([])
const employeId = ref('')
const mois = ref(new Date().toISOString().slice(0, 7))
const totaux = ref({})
const details = ref([])

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const fetchReleve = async () => {
  if (!employeId.value || !mois.value) return
  const { data } = await api.get('/v1/pointages/releve-paie', {
    params: { employe_id: employeId.value, mois: mois.value }
  })
  totaux.value = data.totaux || {}
  details.value = data.details || []
}

onMounted(fetchEmployes)
</script>

<style scoped>
.stat-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style>
