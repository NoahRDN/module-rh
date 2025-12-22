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
      <input v-else-if="mode === 'week'" class="input w-36" type="month" v-model="mois" />
      <input v-else class="input w-24" type="number" min="2000" max="2100" v-model="yearOnly" />
      <button class="btn btn-secondary" @click="fetchReleve">Générer</button>
      <RouterLink class="btn" to="/paie-generation">Vers paie</RouterLink>
    </div>
  </div>

  <div class="card" v-if="mode === 'day' && jourResume">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-lg font-semibold">Journalier</h3>
      <button class="btn btn-secondary btn-xs" @click="toggleDayDetails">{{ showDayDetails ? 'Masquer pointages' : 'Voir pointages' }}</button>
    </div>
    <table class="min-w-full text-sm">
      <thead class="border-b border-slate-800/60">
        <tr>
          <th class="py-2 text-left text-slate-400 text-xs">Jour</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS week-end</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS férié</th>
          <th class="py-2 text-left text-slate-400 text-xs">Retard (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Pauses (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Statut</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="py-2">{{ dateJour }}</td>
          <td class="py-2">{{ jourResume.heures_travaillees }}</td>
          <td class="py-2">{{ isSundayDay ? jourResume.heures_supplementaires : 0 }}</td>
          <td class="py-2">{{ jourResume.absence_justifiee ? jourResume.heures_supplementaires : 0 }}</td>
          <td class="py-2">{{ jourResume.retard_minutes }}</td>
          <td class="py-2">{{ jourResume.minutes_pauses }}</td>
          <td class="py-2">
            <span
              v-for="chip in chips(jourResume)"
              :key="chip.label"
              class="chip"
              :style="chip.style"
            >{{ chip.label }}</span>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="showDayDetails && dayPointages.length" class="mt-4">
      <h4 class="text-md font-semibold mb-2">Pointages de la journée</h4>
      <table class="min-w-full text-sm">
        <thead class="border-b border-slate-800/60">
          <tr>
            <th class="py-2 text-left text-slate-400 text-xs">#</th>
            <th class="py-2 text-left text-slate-400 text-xs">Type</th>
            <th class="py-2 text-left text-slate-400 text-xs">Horodatage</th>
            <th class="py-2 text-left text-slate-400 text-xs">Source</th>
            <th class="py-2 text-left text-slate-400 text-xs">Commentaire</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="(p, idx) in dayPointages" :key="idx">
            <td class="py-2">{{ idx + 1 }}</td>
            <td class="py-2">{{ p.type }}</td>
            <td class="py-2">{{ p.pointe_a }}</td>
            <td class="py-2">{{ p.source || '—' }}</td>
            <td class="py-2">{{ p.commentaire || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" v-if="mode === 'month'">
    <h3 class="text-lg font-semibold mb-2">Synthèse mensuelle</h3>
    <table class="min-w-full text-sm">
      <thead class="border-b border-slate-800/60">
        <tr>
          <th class="py-2 text-left text-slate-400 text-xs">Mois</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS week-end</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS férié</th>
          <th class="py-2 text-left text-slate-400 text-xs">Retards (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Absences</th>
          <th class="py-2 text-left text-slate-400 text-xs">Détails</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(m, idx) in monthsData" :key="m.label">
          <td class="py-2">{{ m.label }}</td>
          <td class="py-2">{{ m.totaux.heures_travaillees }}</td>
          <td class="py-2">{{ m.totaux.heures_supplementaires }}</td>
          <td class="py-2">{{ m.totaux.hs_weekend }}</td>
          <td class="py-2">{{ m.totaux.hs_ferie }}</td>
          <td class="py-2">{{ m.totaux.retard_minutes }}</td>
          <td class="py-2">{{ m.totaux.absences }}</td>
          <td class="py-2">
            <button class="btn btn-secondary btn-xs" @click="toggleMonthDetailsFor(m)">
              {{ selectedMonth === m.label && showMonthDetails ? 'Masquer' : 'Voir' }}
            </button>
          </td>
        </tr>
        <tr v-if="!monthsData.length">
          <td colspan="8" class="py-3 text-center text-slate-500">Aucune donnée</td>
        </tr>
      </tbody>
    </table>

    <div v-if="showMonthDetails && selectedMonthWeeks.length" class="mt-4">
      <h4 class="text-md font-semibold mb-2">Détails hebdomadaires ({{ selectedMonth }})</h4>
      <table class="min-w-full text-sm">
        <thead class="border-b border-slate-800/60">
          <tr>
            <th class="py-2 text-left text-slate-400 text-xs">#</th>
            <th class="py-2 text-left text-slate-400 text-xs">Semaine</th>
            <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
            <th class="py-2 text-left text-slate-400 text-xs">HS</th>
            <th class="py-2 text-left text-slate-400 text-xs">HS week-end</th>
            <th class="py-2 text-left text-slate-400 text-xs">HS férié</th>
            <th class="py-2 text-left text-slate-400 text-xs">Retards (min)</th>
            <th class="py-2 text-left text-slate-400 text-xs">Absences</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="(w, idx) in selectedMonthWeeks" :key="w.label">
            <td class="py-2">{{ idx + 1 }}</td>
            <td class="py-2">{{ w.label }}</td>
            <td class="py-2">{{ w.heures_travaillees }}</td>
            <td class="py-2">{{ w.heures_supplementaires }}</td>
          <td class="py-2">{{ w.hs_weekend }}</td>
            <td class="py-2">{{ w.hs_ferie }}</td>
            <td class="py-2">{{ w.retard_minutes }}</td>
            <td class="py-2">{{ w.absences }}</td>
          </tr>
          <tr v-if="!selectedMonthWeeks.length">
            <td colspan="8" class="py-3 text-center text-slate-500">Aucune donnée</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" v-if="mode === 'week'">
    <h3 class="text-lg font-semibold mb-2">Synthèse hebdomadaire</h3>
    <table class="min-w-full text-sm">
      <thead class="border-b border-slate-800/60">
        <tr>
          <th class="py-2 text-left text-slate-400 text-xs">#</th>
          <th class="py-2 text-left text-slate-400 text-xs">Semaine</th>
          <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS week-end</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS férié</th>
          <th class="py-2 text-left text-slate-400 text-xs">Retards (min)</th>
          <th class="py-2 text-left text-slate-400 text-xs">Absences</th>
          <th class="py-2 text-left text-slate-400 text-xs">Abs. justifiées</th>
          <th class="py-2 text-left text-slate-400 text-xs">Détails</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/60">
        <tr v-for="(w, idx) in semaines" :key="w.label">
          <td class="py-2">{{ idx + 1 }}</td>
          <td class="py-2">{{ w.label }}</td>
          <td class="py-2">{{ w.heures_travaillees }}</td>
          <td class="py-2">{{ w.hs_weekend === null ? w.heures_supplementaires : 0 }}</td>
          <td class="py-2">{{ w.hs_weekend }}</td>
          <td class="py-2">{{ w.hs_ferie }}</td>
          <td class="py-2">{{ w.retard_minutes }}</td>
          <td class="py-2">{{ w.absences }}</td>
          <td class="py-2">{{ w.absences_justifiees }}</td>
          <td class="py-2">
            <button class="btn btn-secondary btn-xs" @click="selectWeek(w)">Voir</button>
          </td>
        </tr>
        <tr v-if="!semaines.length">
          <td colspan="6" class="py-3 text-center text-slate-500">Aucune donnée</td>
        </tr>
      </tbody>
    </table>

      <div v-if="weekDetails.length" class="mt-4">
        <h4 class="text-md font-semibold mb-2">Détails de {{ selectedWeekLabel }}</h4>
        <table class="min-w-full text-sm">
          <thead class="border-b border-slate-800/60">
            <tr>
              <th class="py-2 text-left text-slate-400 text-xs">Jour</th>
              <th class="py-2 text-left text-slate-400 text-xs">Heures</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS week-end</th>
          <th class="py-2 text-left text-slate-400 text-xs">HS férié</th>
              <th class="py-2 text-left text-slate-400 text-xs">Retard (min)</th>
              <th class="py-2 text-left text-slate-400 text-xs">Statut</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr v-for="d in weekDetails" :key="d.jour">
              <td class="py-2">{{ d.jour }}</td>
              <td class="py-2">{{ d.heures_travaillees }}</td>
              <td class="py-2">{{ d.weekend ? d.heures_supplementaires : 0 }}</td>
              <td class="py-2">{{ d.ferie ? d.heures_supplementaires : 0 }}</td>
              <td class="py-2">{{ d.retard_minutes }}</td>
            <td class="py-2">
              <span
                v-for="chip in chips(d)"
                :key="chip.label"
                class="chip"
                :style="chip.style"
              >{{ chip.label }}</span>
            </td>
          </tr>
          <tr v-if="!weekDetails.length">
            <td colspan="6" class="py-3 text-center text-slate-500">Aucune donnée</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { computed } from 'vue'

const employes = ref([])
const employeId = ref('')
const mois = ref(new Date().toISOString().slice(0, 7))
const dateJour = ref(new Date().toISOString().slice(0, 10))
const mode = ref('month')
const yearOnly = ref(new Date().getFullYear())
const totaux = ref({})
const details = ref([])
const jourResume = ref(null)
const semaines = ref([])
const weekDetails = ref([])
const selectedWeekLabel = ref('')
const showMonthDetails = ref(false)
const showDayDetails = ref(false)
const dayPointages = ref([])
const isSundayDay = computed(() => new Date(dateJour.value).getDay() === 0)
const monthsData = ref([])
const selectedMonth = ref('')
const selectedMonthWeeks = ref([])
const chips = (row = {}) => {
  const list = []
  const add = (label, bg, color) => list.push({ label, style: `background:${bg};color:${color};` })
  if (row.ferie) add('Férié', 'rgba(56,189,248,0.15)', '#67e8f9')
  else if (row.weekend) add('Week-end', 'rgba(148,163,184,0.15)', '#cbd5e1')
  else if (row.absence_justifiee) add('Absence justifiée', 'rgba(59,130,246,0.15)', '#93c5fd')
  else if (row.absent) add('Absent', 'rgba(248,113,113,0.15)', '#fca5a5')
  else if (row.present_partiel) add('Présence partielle', 'rgba(251,191,36,0.15)', '#facc15')
  else add('Présent', 'rgba(34,197,94,0.12)', '#86efac')

  if (!row.ferie && !row.weekend && row.retard_minutes > 0) {
    add('Retard', 'rgba(249,115,22,0.15)', '#fb923c')
  }
  return list
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: 1 } })
  employes.value = data.data || data || []
}

const fetchReleve = async () => {
  if (!employeId.value) return
  if (mode.value === 'day' && !dateJour.value) return
  if (mode.value === 'week' && !mois.value) return
  if (mode.value === 'month' && !yearOnly.value) return

  if (mode.value === 'day') {
    const { data } = await api.get('/v1/pointages/releve-journalier', {
      params: { employe_id: employeId.value, date: dateJour.value }
    })
    jourResume.value = data.resume
    dayPointages.value = data.pointages || []
    details.value = []
    semaines.value = []
    showDayDetails.value = false
    totaux.value = {
      heures_travaillees: data.resume?.heures_travaillees ?? 0,
      heures_supplementaires: data.resume?.heures_supplementaires ?? 0,
      retard_minutes: data.resume?.retard_minutes ?? 0,
      absences: data.resume?.absent ? 1 : 0
    }
    return
  }

  // month or week -> on s'appuie sur releve-paie qui applique les règles (40h/sem, dimanche)
  let det = []
  let totauxWeek = {}
  if (mode.value === 'week') {
    const { data } = await api.get('/v1/pointages/releve-paie', {
      params: { employe_id: employeId.value, mois: mois.value }
    })
    det = data.details || []
    totauxWeek = data.totaux || {}
  } else {
    // mode month : synthèse par mois pour l'année sélectionnée
    const year = yearOnly.value
    monthsData.value = []
    for (let m = 1; m <= 12; m++) {
      const moisStr = `${year}-${String(m).padStart(2, '0')}`
      const { data } = await api.get('/v1/pointages/releve-paie', {
        params: { employe_id: employeId.value, mois: moisStr }
      })
      const d = data.details || []
      const weeks = mergeWeeks(groupByWeek(d))
      const hsWeekend = d.filter((x) => isWeekend(x)).reduce((s, x) => s + (x.heures_supplementaires || 0), 0)
      const hsFerie = d.filter((x) => isFerie(x)).reduce((s, x) => s + (x.heures_supplementaires || 0), 0)
      monthsData.value.push({
        label: moisStr,
        totaux: {
          heures_travaillees: data.totaux?.heures_travaillees || 0,
          heures_supplementaires: data.totaux?.heures_supplementaires || 0,
          hs_weekend: hsWeekend,
          hs_ferie: hsFerie,
          retard_minutes: data.totaux?.retard_minutes || 0,
          absences: data.totaux?.absences || 0,
        },
        weeks,
      })
    }
    selectedMonth.value = ''
    selectedMonthWeeks.value = []
    showMonthDetails.value = false
  }

  // enrichir pour affichage (dimanche)
  details.value = det.map((d) => ({
    ...d,
    dimanche: new Date(d.jour).getDay() === 0
  }))

  if (mode.value === 'week') {
    semaines.value = groupByWeek(det)
    details.value = []
    weekDetails.value = []
    selectedWeekLabel.value = ''
    totaux.value = totauxWeek
  } else {
    // mode month : recalcul des totaux sur le mois affiché
    semaines.value = []
    monthWeeks.value = groupByWeek(det)
    const dimanches = details.value.filter((d) => new Date(d.jour).getDay() === 0).length
    const absents = details.value.filter((d) => d.absent).length
    totaux.value = {
      heures_travaillees: details.value.reduce((s, d) => s + (d.heures_travaillees || 0), 0),
      heures_supplementaires: details.value.reduce((s, d) => s + (d.heures_supplementaires || 0), 0),
      hs_weekend: details.value.filter((d) => isWeekend(d)).reduce((s, d) => s + (d.heures_supplementaires || 0), 0),
      hs_ferie: details.value.filter((d) => isFerie(d)).reduce((s, d) => s + (d.heures_supplementaires || 0), 0),
      retard_minutes: details.value.reduce((s, d) => s + (d.retard_minutes || 0), 0),
      absences: absents,
      dimanches
    }
    showMonthDetails.value = false
  }
}

const groupByWeek = (list) => {
  const weeks = {}
  list.forEach((d) => {
    const date = new Date(d.jour)
    const label = weekLabel(date)
    const weekendFlag = isWeekend(d, date)
    const ferieFlag = isFerie(d)
    if (!weeks[label]) {
      weeks[label] = {
        label,
        heures_travaillees: 0,
        heures_supplementaires: 0,
        hs_weekend: 0,
        hs_ferie: 0,
        retard_minutes: 0,
        absences: 0,
        absences_justifiees: 0,
        dimanches: 0,
        days: []
      }
    }
    weeks[label].heures_travaillees += d.heures_travaillees || 0
    weeks[label].heures_supplementaires += d.heures_supplementaires || 0
    if (weekendFlag) {
      weeks[label].hs_weekend += d.heures_supplementaires || 0
    }
    if (ferieFlag) {
      weeks[label].hs_ferie += d.heures_supplementaires || 0
    }
    weeks[label].retard_minutes += d.retard_minutes || 0
    weeks[label].absences += (d.absent && !weekendFlag && !d.ferie) ? 1 : 0
    weeks[label].absences_justifiees += d.absence_justifiee ? 1 : 0
    if (date.getDay() === 0) weeks[label].dimanches += 1
    weeks[label].days.push({
      ...d,
      dimanche: date.getDay() === 0,
      weekend: weekendFlag,
      ferie: ferieFlag
    })
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

const toggleMonthDetails = () => {
  showMonthDetails.value = !showMonthDetails.value
}

const toggleDayDetails = () => {
  showDayDetails.value = !showDayDetails.value
}

const selectWeek = (week) => {
  selectedWeekLabel.value = week.label
  weekDetails.value = (week.days || []).map((d) => {
    const weekendFlag = d.weekend || isWeekend(d)
    const ferieFlag = d.ferie || isFerie(d)
    return {
      ...d,
      weekend: weekendFlag,
      ferie: ferieFlag,
      hs_weekend: weekendFlag ? (d.heures_supplementaires || 0) : 0,
      hs_ferie: ferieFlag ? (d.heures_supplementaires || 0) : 0
    }
  })
}

// Fusionne des semaines ayant le même label (utile quand on accumule plusieurs mois)
const mergeWeeks = (weeks) => {
  const map = {}
  weeks.forEach((w) => {
    if (!map[w.label]) {
      map[w.label] = { ...w }
    } else {
      map[w.label].heures_travaillees += w.heures_travaillees || 0
      map[w.label].heures_supplementaires += w.heures_supplementaires || 0
      map[w.label].hs_weekend += w.hs_weekend || 0
      map[w.label].hs_ferie += w.hs_ferie || 0
      map[w.label].retard_minutes += w.retard_minutes || 0
      map[w.label].absences += w.absences || 0
      map[w.label].absences_justifiees += w.absences_justifiees || 0
      map[w.label].dimanches += w.dimanches || 0
      map[w.label].days = (map[w.label].days || []).concat(w.days || [])
    }
  })
  return Object.values(map)
}

const isWeekend = (d, dateObj) => {
  const dt = dateObj || new Date(d.jour)
  return !!(d.weekend || dt.getDay() === 0 || dt.getDay() === 6)
}

const isFerie = (d) => !!d.ferie

const toggleMonthDetailsFor = (monthObj) => {
  if (selectedMonth.value === monthObj.label && showMonthDetails.value) {
    showMonthDetails.value = false
    selectedMonth.value = ''
    selectedMonthWeeks.value = []
    return
  }
  selectedMonth.value = monthObj.label
  selectedMonthWeeks.value = monthObj.weeks || []
  showMonthDetails.value = true
}
</script>

<style scoped>
.stat-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style>
