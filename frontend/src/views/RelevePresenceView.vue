<template>
  <div class="rh-page releve-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Presence reporting</p>
        <h1>Relevé de présence</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Analysez les heures travaillées, retards, absences et heures supplémentaires avec une vue
          plus lisible, mieux hiérarchisée et cohérente avec le reste du module RH.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Journalier</span>
          <span class="pill">Hebdomadaire</span>
          <span class="pill">Mensuel</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="fetchReleve">
              <AppIcon name="refresh" :size="18" />
              <span>Générer</span>
            </button>
            <RouterLink class="btn" to="/paie-generation">
              <AppIcon name="wallet" :size="18" />
              <span>Vers paie</span>
            </RouterLink>
          </div>

          <div class="rh-hero-meta-list hero-meta-list">
            <p class="rh-hero-meta hero-meta">
              Collaborateur:
              <strong>{{ selectedEmployeLabel }}</strong>
            </p>
            <p class="rh-hero-meta hero-meta">
              Mode:
              <strong>{{ modeLabel }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="rh-metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="rh-metric-card">
        <span class="rh-metric-chip">{{ metric.tag }}</span>
        <p class="rh-metric-label">{{ metric.label }}</p>
        <p class="rh-metric-value">{{ metric.value }}</p>
        <p class="rh-metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <section class="card rh-section-card">
      <div class="rh-section-heading">
        <div>
          <p class="rh-section-kicker">Reporting controls</p>
          <h2>Choix du mode et de la période</h2>
        </div>
      </div>

      <p class="rh-section-copy">
        Sélectionnez le collaborateur, le mode d’analyse et la période à traiter pour générer le
        relevé correspondant.
      </p>

      <div class="rh-fields-grid controls-grid">
        <label class="rh-field-card">
          <span class="rh-field-label">Mode</span>
          <select class="select" v-model="mode">
            <option value="day">Journalier</option>
            <option value="week">Hebdomadaire</option>
            <option value="month">Mensuel</option>
          </select>
        </label>

        <label class="rh-field-card">
          <span class="rh-field-label">Employé</span>
          <select class="select" v-model="employeId">
            <option value="">Sélectionner</option>
            <option v-for="e in employes" :key="e.id" :value="e.id">
              {{ e.matricule }} - {{ e.nom }} {{ e.prenom }}
            </option>
          </select>
        </label>

        <label v-if="mode === 'day'" class="rh-field-card">
          <span class="rh-field-label">Jour</span>
          <input class="input" type="date" v-model="dateJour" />
        </label>

        <label v-else-if="mode === 'week'" class="rh-field-card">
          <span class="rh-field-label">Mois de référence</span>
          <input class="input" type="month" v-model="mois" />
        </label>

        <label v-else class="rh-field-card">
          <span class="rh-field-label">Année</span>
          <input class="input" type="number" min="2000" max="2100" v-model="yearOnly" />
        </label>
      </div>

      <div class="rh-action-row action-row-inline">
        <button class="btn" @click="fetchReleve" :disabled="!canGenerate">
          <AppIcon name="save" :size="18" />
          <span>Générer le relevé</span>
        </button>
      </div>
    </section>

    <article class="card rh-section-card" v-if="mode === 'day' && jourResume">
      <div class="rh-section-heading">
        <div>
          <p class="rh-section-kicker">Daily report</p>
          <h2>Journalier - {{ dateJour }}</h2>
        </div>
        <button class="btn btn-secondary btn-sm" @click="toggleDayDetails">
          {{ showDayDetails ? 'Masquer pointages' : 'Voir pointages' }}
        </button>
      </div>

      <div class="rh-table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Jour</th>
              <th>Heures</th>
              <th>HS week-end</th>
              <th>HS férié</th>
              <th>Retard (min)</th>
              <th>Pauses (min)</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ dateJour }}</td>
              <td>{{ formatNumber(jourResume.heures_travaillees) }}</td>
              <td>{{ formatNumber(isSundayDay ? jourResume.heures_supplementaires : 0) }}</td>
              <td>{{ formatNumber(jourResume.absence_justifiee ? jourResume.heures_supplementaires : 0) }}</td>
              <td>{{ formatNumber(jourResume.retard_minutes) }}</td>
              <td>{{ formatNumber(jourResume.minutes_pauses) }}</td>
              <td>
                <div class="chip-list">
                  <span v-for="chip in chips(jourResume)" :key="chip.label" class="chip" :style="chip.style">{{ chip.label }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showDayDetails && dayPointages.length" class="detail-block">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Raw events</p>
            <h2>Pointages de la journée</h2>
          </div>
        </div>

        <div class="rh-table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Type</th>
                <th>Horodatage</th>
                <th>Source</th>
                <th>Commentaire</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, idx) in dayPointages" :key="idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ p.type }}</td>
                <td>{{ p.pointe_a }}</td>
                <td>{{ p.source || '—' }}</td>
                <td>{{ p.commentaire || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </article>

    <article class="card rh-section-card" v-else-if="mode === 'week' && semaines.length">
      <div class="rh-section-heading">
        <div>
          <p class="rh-section-kicker">Weekly report</p>
          <h2>Synthèse hebdomadaire</h2>
        </div>
      </div>

      <div class="rh-table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Semaine</th>
              <th>Heures</th>
              <th>HS</th>
              <th>HS week-end</th>
              <th>HS férié</th>
              <th>Retards (min)</th>
              <th>Absences</th>
              <th>Abs. justifiées</th>
              <th>Détails</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(w, idx) in semaines" :key="w.label">
              <tr>
                <td>{{ idx + 1 }}</td>
                <td>{{ w.label }}</td>
                <td>{{ formatNumber(w.heures_travaillees) }}</td>
                <td>{{ formatNumber(w.heures_supplementaires) }}</td>
                <td>{{ formatNumber(w.hs_weekend) }}</td>
                <td>{{ formatNumber(w.hs_ferie) }}</td>
                <td>{{ formatNumber(w.retard_minutes) }}</td>
                <td>{{ w.absences }}</td>
                <td>{{ w.absences_justifiees }}</td>
                <td>
                  <button class="btn btn-secondary btn-sm" @click="selectWeek(w)">
                    {{ selectedWeekLabel === w.label && weekDetails.length ? 'Masquer' : 'Voir' }}
                  </button>
                </td>
              </tr>

              <tr
                v-if="selectedWeekLabel === w.label && weekDetails.length"
                class="inline-detail-row"
              >
                <td colspan="10" class="inline-detail-cell">
                  <div class="detail-block inline-detail-block">
                    <div class="rh-section-heading compact">
                      <div>
                        <p class="rh-section-kicker">Week details</p>
                        <h2>{{ selectedWeekLabel }}</h2>
                      </div>
                    </div>

                    <div class="rh-table-shell nested-table-shell">
                      <table class="table nested-table">
                        <thead>
                          <tr>
                            <th>Jour</th>
                            <th>Heures</th>
                            <th>HS week-end</th>
                            <th>HS férié</th>
                            <th>Retard (min)</th>
                            <th>Statut</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="d in weekDetails" :key="d.jour">
                            <td>{{ d.jour }}</td>
                            <td>{{ formatNumber(d.heures_travaillees) }}</td>
                            <td>{{ formatNumber(d.hs_weekend) }}</td>
                            <td>{{ formatNumber(d.hs_ferie) }}</td>
                            <td>{{ formatNumber(d.retard_minutes) }}</td>
                            <td>
                              <div class="chip-list">
                                <span
                                  v-for="chip in chips(d)"
                                  :key="chip.label"
                                  class="chip"
                                  :style="chip.style"
                                >
                                  {{ chip.label }}
                                </span>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </article>

    <article class="card rh-section-card" v-else-if="mode === 'month' && monthsData.length">
      <div class="rh-section-heading">
        <div>
          <p class="rh-section-kicker">Monthly report</p>
          <h2>Synthèse mensuelle {{ yearOnly }}</h2>
        </div>
      </div>

      <div class="rh-table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Mois</th>
              <th>Heures</th>
              <th>HS</th>
              <th>HS week-end</th>
              <th>HS férié</th>
              <th>Retards (min)</th>
              <th>Absences</th>
              <th>Détails</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="item in monthsData" :key="item.label">
              <tr>
                <td>{{ item.label }}</td>
                <td>{{ formatNumber(item.totaux.heures_travaillees) }}</td>
                <td>{{ formatNumber(item.totaux.heures_supplementaires) }}</td>
                <td>{{ formatNumber(item.totaux.hs_weekend) }}</td>
                <td>{{ formatNumber(item.totaux.hs_ferie) }}</td>
                <td>{{ formatNumber(item.totaux.retard_minutes) }}</td>
                <td>{{ item.totaux.absences }}</td>
                <td>
                  <button class="btn btn-secondary btn-sm" @click="toggleMonthDetailsFor(item)">
                    {{ selectedMonth === item.label && showMonthDetails ? 'Masquer' : 'Voir' }}
                  </button>
                </td>
              </tr>

              <tr
                v-if="selectedMonth === item.label && showMonthDetails && selectedMonthWeeks.length"
                class="inline-detail-row"
              >
                <td colspan="8" class="inline-detail-cell">
                  <div class="detail-block inline-detail-block">
                    <div class="rh-section-heading compact">
                      <div>
                        <p class="rh-section-kicker">Month details</p>
                        <h2>{{ selectedMonth }}</h2>
                      </div>
                    </div>

                    <div class="rh-table-shell nested-table-shell">
                      <table class="table nested-table">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Semaine</th>
                            <th>Heures</th>
                            <th>HS</th>
                            <th>HS week-end</th>
                            <th>HS férié</th>
                            <th>Retards (min)</th>
                            <th>Absences</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(w, idx) in selectedMonthWeeks" :key="w.label">
                            <td>{{ idx + 1 }}</td>
                            <td>{{ w.label }}</td>
                            <td>{{ formatNumber(w.heures_travaillees) }}</td>
                            <td>{{ formatNumber(w.heures_supplementaires) }}</td>
                            <td>{{ formatNumber(w.hs_weekend) }}</td>
                            <td>{{ formatNumber(w.hs_ferie) }}</td>
                            <td>{{ formatNumber(w.retard_minutes) }}</td>
                            <td>{{ w.absences }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </article>

    <div v-else class="card rh-loading-card">
      <p class="rh-loading-title">Aucun relevé affiché</p>
      <p class="muted">Choisissez un collaborateur, une période et lancez la génération.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const employes = ref([])
const employeId = ref('')
const mois = ref(new Date().toISOString().slice(0, 7))
const dateJour = ref(new Date().toISOString().slice(0, 10))
const mode = ref('month')
const yearOnly = ref(new Date().getFullYear())
const totaux = ref({})
const jourResume = ref(null)
const semaines = ref([])
const weekDetails = ref([])
const selectedWeekLabel = ref('')
const showMonthDetails = ref(false)
const showDayDetails = ref(false)
const dayPointages = ref([])
const monthsData = ref([])
const selectedMonth = ref('')
const selectedMonthWeeks = ref([])

const isSundayDay = computed(() => new Date(dateJour.value).getDay() === 0)

const selectedEmploye = computed(() =>
  employes.value.find((item) => String(item.id) === String(employeId.value)) || null,
)

const selectedEmployeLabel = computed(() => {
  if (!selectedEmploye.value) return 'Aucun'
  return `${selectedEmploye.value.matricule || 'EMP'} - ${selectedEmploye.value.nom || ''}`
}
)

const modeLabel = computed(() => {
  if (mode.value === 'day') return 'Journalier'
  if (mode.value === 'week') return 'Hebdomadaire'
  return 'Mensuel'
})

const canGenerate = computed(() => {
  if (!employeId.value) return false
  if (mode.value === 'day') return Boolean(dateJour.value)
  if (mode.value === 'week') return Boolean(mois.value)
  return Boolean(yearOnly.value)
})

const currentTotals = computed(() => {
  if (mode.value === 'day' && jourResume.value) {
    return {
      heures_travaillees: Number(jourResume.value.heures_travaillees || 0),
      heures_supplementaires: Number(jourResume.value.heures_supplementaires || 0),
      hs_weekend: isSundayDay.value ? Number(jourResume.value.heures_supplementaires || 0) : 0,
      hs_ferie: jourResume.value.absence_justifiee ? Number(jourResume.value.heures_supplementaires || 0) : 0,
      retard_minutes: Number(jourResume.value.retard_minutes || 0),
      absences: jourResume.value.absent ? 1 : 0,
    }
  }

  if (mode.value === 'month') {
    return monthsData.value.reduce(
      (accumulator, item) => ({
        heures_travaillees: accumulator.heures_travaillees + Number(item.totaux.heures_travaillees || 0),
        heures_supplementaires:
          accumulator.heures_supplementaires + Number(item.totaux.heures_supplementaires || 0),
        hs_weekend: accumulator.hs_weekend + Number(item.totaux.hs_weekend || 0),
        hs_ferie: accumulator.hs_ferie + Number(item.totaux.hs_ferie || 0),
        retard_minutes: accumulator.retard_minutes + Number(item.totaux.retard_minutes || 0),
        absences: accumulator.absences + Number(item.totaux.absences || 0),
      }),
      {
        heures_travaillees: 0,
        heures_supplementaires: 0,
        hs_weekend: 0,
        hs_ferie: 0,
        retard_minutes: 0,
        absences: 0,
      },
    )
  }

  return {
    heures_travaillees: Number(totaux.value.heures_travaillees || 0),
    heures_supplementaires: Number(totaux.value.heures_supplementaires || 0),
    hs_weekend: Number(totaux.value.hs_weekend || 0),
    hs_ferie: Number(totaux.value.hs_ferie || 0),
    retard_minutes: Number(totaux.value.retard_minutes || 0),
    absences: Number(totaux.value.absences || 0),
  }
})

const metricCards = computed(() => [
  {
    label: 'Heures travaillées',
    value: formatNumber(currentTotals.value.heures_travaillees),
    caption: 'Total visible selon le mode actif',
    tag: 'Hours',
  },
  {
    label: 'Heures sup.',
    value: formatNumber(currentTotals.value.heures_supplementaires),
    caption: 'Heures supplémentaires calculées',
    tag: 'OT',
  },
  {
    label: 'Retards',
    value: formatNumber(currentTotals.value.retard_minutes),
    caption: 'Minutes de retard remontées',
    tag: 'Delay',
  },
  {
    label: 'Absences',
    value: currentTotals.value.absences,
    caption: 'Absences détectées sur la période',
    tag: 'Abs',
  },
])

const chips = (row = {}) => {
  const list = []
  const add = (label, bg, color) => list.push({ label, style: `background:${bg};color:${color};` })
  if (row.ferie) add('Férié', 'rgba(56,189,248,0.15)', '#0f766e')
  else if (row.weekend) add('Week-end', 'rgba(148,163,184,0.15)', '#475569')
  else if (row.absence_justifiee) add('Absence justifiée', 'rgba(59,130,246,0.15)', '#2563eb')
  else if (row.absent) add('Absent', 'rgba(248,113,113,0.15)', '#dc2626')
  else if (row.present_partiel) add('Présence partielle', 'rgba(251,191,36,0.15)', '#d97706')
  else add('Présent', 'rgba(34,197,94,0.12)', '#15803d')

  if (!row.ferie && !row.weekend && Number(row.retard_minutes || 0) > 0) {
    add('Retard', 'rgba(249,115,22,0.15)', '#ea580c')
  }
  return list
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: 1 } })
  employes.value = data.data || data || []
}

const fetchReleve = async () => {
  if (!canGenerate.value) return

  if (mode.value === 'day') {
    const { data } = await api.get('/v1/pointages/releve-journalier', {
      params: { employe_id: employeId.value, date: dateJour.value },
    })

    jourResume.value = data.resume
    dayPointages.value = data.pointages || []
    semaines.value = []
    weekDetails.value = []
    monthsData.value = []
    selectedMonth.value = ''
    selectedMonthWeeks.value = []
    showMonthDetails.value = false
    showDayDetails.value = false
    totaux.value = {
      heures_travaillees: data.resume?.heures_travaillees ?? 0,
      heures_supplementaires: data.resume?.heures_supplementaires ?? 0,
      hs_weekend: isSundayDay.value ? data.resume?.heures_supplementaires ?? 0 : 0,
      hs_ferie: data.resume?.absence_justifiee ? data.resume?.heures_supplementaires ?? 0 : 0,
      retard_minutes: data.resume?.retard_minutes ?? 0,
      absences: data.resume?.absent ? 1 : 0,
    }
    return
  }

  if (mode.value === 'week') {
    const { data } = await api.get('/v1/pointages/releve-paie', {
      params: { employe_id: employeId.value, mois: mois.value },
    })
    const details = data.details || []
    semaines.value = groupByWeek(details)
    weekDetails.value = []
    selectedWeekLabel.value = ''
    monthsData.value = []
    jourResume.value = null
    dayPointages.value = []
    totaux.value = buildTotalsFromDetails(details, data.totaux || {})
    return
  }

  const year = Number(yearOnly.value)
  const rows = []
  for (let month = 1; month <= 12; month += 1) {
    const monthLabel = `${year}-${String(month).padStart(2, '0')}`
    const { data } = await api.get('/v1/pointages/releve-paie', {
      params: { employe_id: employeId.value, mois: monthLabel },
    })
    const details = data.details || []
    rows.push({
      label: monthLabel,
      totaux: buildTotalsFromDetails(details, data.totaux || {}),
      weeks: mergeWeeks(groupByWeek(details)),
    })
  }
  monthsData.value = rows
  jourResume.value = null
  dayPointages.value = []
  semaines.value = []
  weekDetails.value = []
  selectedWeekLabel.value = ''
  selectedMonth.value = ''
  selectedMonthWeeks.value = []
  showMonthDetails.value = false
}

const buildTotalsFromDetails = (details, apiTotals = {}) => {
  const totals = {
    heures_travaillees: Number(apiTotals.heures_travaillees || 0),
    heures_supplementaires: Number(apiTotals.heures_supplementaires || 0),
    hs_weekend: 0,
    hs_ferie: 0,
    retard_minutes: Number(apiTotals.retard_minutes || 0),
    absences: Number(apiTotals.absences || 0),
  }

  details.forEach((detail) => {
    if (isWeekend(detail)) totals.hs_weekend += Number(detail.heures_supplementaires || 0)
    if (isFerie(detail)) totals.hs_ferie += Number(detail.heures_supplementaires || 0)
  })

  if (!apiTotals.heures_travaillees) {
    totals.heures_travaillees = details.reduce((sum, detail) => sum + Number(detail.heures_travaillees || 0), 0)
  }
  if (!apiTotals.heures_supplementaires) {
    totals.heures_supplementaires = details.reduce(
      (sum, detail) => sum + Number(detail.heures_supplementaires || 0),
      0,
    )
  }
  if (!apiTotals.retard_minutes) {
    totals.retard_minutes = details.reduce((sum, detail) => sum + Number(detail.retard_minutes || 0), 0)
  }
  if (!apiTotals.absences) {
    totals.absences = details.filter((detail) => detail.absent).length
  }

  return totals
}

const groupByWeek = (details) => {
  const weeks = {}
  details.forEach((detail) => {
    const date = new Date(detail.jour)
    const label = weekLabel(date)
    const weekend = isWeekend(detail, date)
    const ferie = isFerie(detail)

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
        days: [],
      }
    }

    weeks[label].heures_travaillees += Number(detail.heures_travaillees || 0)
    weeks[label].heures_supplementaires += Number(detail.heures_supplementaires || 0)
    weeks[label].retard_minutes += Number(detail.retard_minutes || 0)
    weeks[label].absences += detail.absent && !weekend && !detail.ferie ? 1 : 0
    weeks[label].absences_justifiees += detail.absence_justifiee ? 1 : 0
    if (weekend) weeks[label].hs_weekend += Number(detail.heures_supplementaires || 0)
    if (ferie) weeks[label].hs_ferie += Number(detail.heures_supplementaires || 0)
    weeks[label].days.push({
      ...detail,
      weekend,
      ferie,
      hs_weekend: weekend ? Number(detail.heures_supplementaires || 0) : 0,
      hs_ferie: ferie ? Number(detail.heures_supplementaires || 0) : 0,
    })
  })
  return Object.values(weeks)
}

const mergeWeeks = (weeks) => {
  const map = {}
  weeks.forEach((week) => {
    if (!map[week.label]) {
      map[week.label] = { ...week }
      return
    }

    map[week.label].heures_travaillees += week.heures_travaillees || 0
    map[week.label].heures_supplementaires += week.heures_supplementaires || 0
    map[week.label].hs_weekend += week.hs_weekend || 0
    map[week.label].hs_ferie += week.hs_ferie || 0
    map[week.label].retard_minutes += week.retard_minutes || 0
    map[week.label].absences += week.absences || 0
    map[week.label].absences_justifiees += week.absences_justifiees || 0
    map[week.label].days = (map[week.label].days || []).concat(week.days || [])
  })
  return Object.values(map)
}

const weekLabel = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diffToMonday = day === 0 ? -6 : 1 - day
  const monday = new Date(d)
  monday.setDate(d.getDate() + diffToMonday)
  const end = new Date(monday)
  end.setDate(monday.getDate() + 6)
  return `${monday.toISOString().slice(0, 10)} → ${end.toISOString().slice(0, 10)}`
}

const isWeekend = (detail, dateObj) => {
  const date = dateObj || new Date(detail.jour)
  return Boolean(detail.weekend || date.getDay() === 0 || date.getDay() === 6)
}

const isFerie = (detail) => Boolean(detail.ferie)

const toggleDayDetails = () => {
  showDayDetails.value = !showDayDetails.value
}

const selectWeek = (week) => {
  if (selectedWeekLabel.value === week.label && weekDetails.value.length) {
    selectedWeekLabel.value = ''
    weekDetails.value = []
    return
  }

  selectedWeekLabel.value = week.label
  weekDetails.value = week.days || []
}

const toggleMonthDetailsFor = (monthObj) => {
  if (selectedMonth.value === monthObj.label && showMonthDetails.value) {
    selectedMonth.value = ''
    selectedMonthWeeks.value = []
    showMonthDetails.value = false
    return
  }
  selectedMonth.value = monthObj.label
  selectedMonthWeeks.value = monthObj.weeks || []
  showMonthDetails.value = true
}

const formatNumber = (value) =>
  new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(Number(value || 0))

onMounted(fetchEmployes)
</script>

<style scoped>
.controls-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.action-row-inline {
  justify-content: flex-start;
}

.detail-block {
  display: grid;
  gap: 14px;
}

.inline-detail-row {
  background: transparent;
}

.inline-detail-row:hover {
  background: transparent;
}

.inline-detail-cell {
  padding: 0 !important;
  border-bottom: 1px solid var(--border);
}

.inline-detail-block {
  padding: 18px 18px 6px;
  background: rgba(79, 70, 229, 0.03);
}

body[data-theme='dark'] .inline-detail-block {
  background: rgba(79, 70, 229, 0.08);
}

.nested-table-shell {
  margin-top: 4px;
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
}

.nested-table :deep(thead th) {
  background: transparent;
}

.chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

@media (max-width: 920px) {
  .controls-grid {
    grid-template-columns: 1fr;
  }
}
</style>
