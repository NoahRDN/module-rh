<template>
  <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="page-title">
      <h1>Relevé de présence</h1>
      <span>Heures, heures sup, retards, absences</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <select class="select" v-model="mode">
        <option value="day">Journalier</option>
        <option value="week">Hebdomadaire</option>
        <option value="month">Mensuel</option>
      </select>
      <select class="select" v-model="employeId">
        <option value="">Employé</option>
        <option v-for="e in employes" :key="e.id" :value="e.id">{{ e.matricule }} - {{ e.nom }} {{ e.prenom }}</option>
      </select>
      <input v-if="mode === 'day'" class="input w-36" type="date" v-model="dateJour" />
      <input v-else class="input w-36" type="month" v-model="mois" />
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

  <div class="card" v-if="mode === 'day' && jourResume">
    <h3 class="text-lg font-semibold mb-2">Journalier</h3>
    <div class="grid gap-2 md:grid-cols-2">
      <div class="stat-card">
        <p class="muted text-xs">Heures travaillées</p>
        <p class="text-xl font-semibold">{{ jourResume.heures_travaillees }} h</p>
      </div>
      <div class="stat-card">
        <p class="muted text-xs">Retard</p>
        <p class="text-xl font-semibold">{{ jourResume.retard_minutes }} min</p>
      </div>
      <div class="stat-card">
        <p class="muted text-xs">Pauses</p>
        <p class="text-xl font-semibold">{{ jourResume.minutes_pauses }} min</p>
      </div>
    </div>
  </div>

  <div class="card" v-if="mode === 'month'">
    <h3 class="text-lg font-semibold mb-2">Détails journaliers</h3>
    <table class="min-w-full text-sm">
      <thead class="border-b border-slate-800/60">
        <tr>
          <th class="py-2 text-left text-slate-400 text-xs">Jour</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures sup</th>
          <th class="py-2 text-left text-slate-400 text-xs">Retard (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Dimanche</th>
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
            <span class="chip" v-if="d.dimanche">Dimanche</span>
            <span class="muted" v-else>—</span>
          </td>
          <td class="py-2">
            <span class="chip" v-if="!d.absent">Présent</span>
            <span class="chip" style="background: rgba(248,113,113,0.15); color: #fca5a5;" v-else>Absent</span>
          </td>
        </tr>
        <tr v-if="!details.length">
          <td colspan="6" class="py-3 text-center text-slate-500">Aucune donnée</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="card" v-if="mode === 'week'">
    <h3 class="text-lg font-semibold mb-2">Synthèse hebdomadaire</h3>
    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="w in semaines" :key="w.label" class="stat-card border border-slate-200 dark:border-slate-800 rounded-lg">
        <p class="font-semibold">Semaine {{ w.label }}</p>
        <p class="text-sm">Heures travaillées : {{ w.heures_travaillees }}</p>
        <p class="text-sm">Heures sup : {{ w.heures_supplementaires }}</p>
        <p class="text-sm">Retards : {{ w.retard_minutes }} min</p>
        <p class="text-sm">Absences : {{ w.absences }}</p>
        <p class="text-sm">Dimanches : {{ w.dimanches }}</p>
      </div>
    </div>
    <p v-if="!semaines.length" class="muted text-sm">Aucune donnée</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'

const employes = ref([])
const employeId = ref('')
const mois = ref(new Date().toISOString().slice(0, 7))
const dateJour = ref(new Date().toISOString().slice(0, 10))
const mode = ref('month')
const totaux = ref({})
const details = ref([])
const jourResume = ref(null)
const semaines = ref([])

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const fetchReleve = async () => {
  if (!employeId.value) return
  if (mode.value === 'day' && !dateJour.value) return
  if (mode.value !== 'day' && !mois.value) return

  if (mode.value === 'day') {
    const { data } = await api.get('/v1/pointages/releve-journalier', {
      params: { employe_id: employeId.value, date: dateJour.value }
    })
    jourResume.value = data.resume
    details.value = []
    semaines.value = []
    totaux.value = {
      heures_travaillees: data.resume?.heures_travaillees ?? 0,
      heures_supplementaires: data.resume?.heures_supplementaires ?? 0,
      retard_minutes: data.resume?.retard_minutes ?? 0,
      absences: data.resume?.absent ? 1 : 0
    }
    return
  }

  // month or week -> on s'appuie sur releve-paie qui applique les règles (40h/sem, dimanche)
  const { data } = await api.get('/v1/pointages/releve-paie', {
    params: { employe_id: employeId.value, mois: mois.value }
  })
  totaux.value = data.totaux || {}
  const det = data.details || []
  // enrichir pour affichage (dimanche)
  details.value = det.map((d) => ({
    ...d,
    dimanche: new Date(d.jour).getDay() === 0
  }))

  if (mode.value === 'week') {
    semaines.value = groupByWeek(det)
    details.value = []
  } else {
    semaines.value = []
  }
}

const groupByWeek = (list) => {
  const weeks = {}
  list.forEach((d) => {
    const date = new Date(d.jour)
    const label = weekLabel(date)
    if (!weeks[label]) {
      weeks[label] = {
        label,
        heures_travaillees: 0,
        heures_supplementaires: 0,
        retard_minutes: 0,
        absences: 0,
        dimanches: 0
      }
    }
    weeks[label].heures_travaillees += d.heures_travaillees || 0
    weeks[label].heures_supplementaires += d.heures_supplementaires || 0
    weeks[label].retard_minutes += d.retard_minutes || 0
    weeks[label].absences += d.absent ? 1 : 0
    if (date.getDay() === 0) weeks[label].dimanches += 1
  })
  return Object.values(weeks)
}

const weekLabel = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diffToMonday = (day === 0 ? -6 : 1 - day) // 0=dimanche
  const monday = new Date(d)
  monday.setDate(d.getDate() + diffToMonday)
  const end = new Date(monday)
  end.setDate(monday.getDate() + 6)
  const fmt = (dt) => dt.toISOString().slice(0, 10)
  return `${fmt(monday)} → ${fmt(end)}`
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
