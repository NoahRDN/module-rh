<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Calendrier entreprise</h1>
      <p class="text-sm text-slate-500">Vue Mois / Semaine / Jour</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <select class="select" v-model="filter.type" @change="debouncedFetchEvents">
        <option value="">Tous les types</option>
        <option value="conge">Congés</option>
        <option value="absence">Absences</option>
        <option value="ferie">Jours fériés</option>
        <option value="rh">Événements RH</option>
      </select>
      <input class="input" placeholder="Matricule" v-model="filter.matricule" @input="debouncedFetchEvents" />
      <input class="input" placeholder="Nom" v-model="filter.nom" @input="debouncedFetchEvents" />
      <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
    </div>
  </div>

  <div class="card rounded-2xl border border-slate-700/50 bg-gradient-to-br from-[var(--panel)] via-[var(--bg-soft)] to-[var(--panel)] shadow-2xl transition-colors duration-150">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
      <div class="flex items-center gap-2">
        <button class="btn btn-secondary" @click="prevRange">←</button>
        <button class="btn btn-secondary" @click="nextRange">→</button>
        <button class="btn btn-secondary" @click="goToday">Aujourd'hui</button>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn btn-secondary" :class="{ active: viewMode === 'month' }" @click="setView('month')">Mois</button>
        <button class="btn btn-secondary" :class="{ active: viewMode === 'week' }" @click="setView('week')">Semaine</button>
        <button class="btn btn-secondary" :class="{ active: viewMode === 'day' }" @click="setView('day')">Jour</button>
      </div>
      <div class="flex items-center gap-2 text-xs text-slate-400">
        <span class="inline-flex items-center gap-1"><span class="legend legend-conge"></span> Congés</span>
        <span class="inline-flex items-center gap-1"><span class="legend legend-absence"></span> Absences</span>
        <span class="inline-flex items-center gap-1"><span class="legend legend-ferie"></span> Fériés</span>
        <span class="inline-flex items-center gap-1"><span class="legend legend-rh"></span> RH</span>
      </div>
    </div>

    <h3 class="text-lg font-semibold mb-2 text-slate-100">{{ title }}</h3>

    <!-- Vue Mois -->
    <div v-if="viewMode === 'month'" class="calendar-grid">
      <div class="calendar-header" v-for="d in weekDays" :key="d">{{ d }}</div>
      <div
        v-for="cell in monthCells"
        :key="cell.dateStr"
        class="calendar-cell"
        :class="{ 'is-today': cell.isToday, 'is-other': cell.isOtherMonth }"
      >
        <div class="cell-top">
          <span class="text-xs">{{ cell.day }}</span>
        </div>
        <div class="cell-events">
          <div
            v-for="evt in eventsByDate(cell.dateStr)"
            :key="evt.id"
            class="event-pill"
            :class="badgeClass(evt.type)"
          >
            <template v-if="evt.type === 'ferie'">
              Férié · {{ evt.description || '—' }}
            </template>
            <template v-else>
              {{ evt.type }} - {{ evt.employe ? evt.employe.matricule : '' }}
              <span v-if="evt.meta?.type_conge_libelle"> · {{ evt.meta.type_conge_libelle }}</span>
              <span v-if="evt.description && evt.type !== 'ferie'"> · {{ evt.description }}</span>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Vue Semaine -->
    <div v-else-if="viewMode === 'week'" class="grid gap-2">
      <div v-for="day in weekRange" :key="day.dateStr" class="event-card">
        <div class="flex items-center justify-between">
          <p class="font-semibold text-slate-100">{{ day.label }}</p>
          <p class="text-xs text-slate-400">{{ day.dateStr }}</p>
        </div>
        <div class="mt-2 flex flex-col gap-1">
          <div
            v-for="evt in eventsByDate(day.dateStr)"
            :key="evt.id"
            class="event-pill"
            :class="badgeClass(evt.type)"
          >
            <template v-if="evt.type === 'ferie'">
              Férié · {{ evt.description || '—' }}
            </template>
            <template v-else>
              {{ evt.type }} · {{ evt.employe ? evt.employe.matricule : '' }} · {{ evt.description || '—' }}
              <span v-if="evt.meta?.type_conge_libelle"> · {{ evt.meta.type_conge_libelle }}</span>
            </template>
          </div>
          <p v-if="!eventsByDate(day.dateStr).length" class="muted text-xs">Aucun événement</p>
        </div>
      </div>
    </div>

    <!-- Vue Jour -->
    <div v-else class="event-card">
      <div class="flex items-center justify-between">
        <p class="font-semibold text-slate-100">{{ formatDate(currentDate) }}</p>
        <p class="text-xs text-slate-400">{{ currentDate.toDateString() }}</p>
      </div>
      <div class="mt-2 flex flex-col gap-2">
        <div
          v-for="evt in eventsByDate(formatDate(currentDate))"
          :key="evt.id"
          class="event-pill"
          :class="badgeClass(evt.type)"
        >
          <template v-if="evt.type === 'ferie'">
            Férié · {{ evt.description || '—' }} ({{ evt.date_debut }} → {{ evt.date_fin }})
          </template>
          <template v-else>
            {{ evt.type }} · {{ evt.employe ? evt.employe.matricule : '' }} · {{ evt.description || '—' }}
            <span v-if="evt.meta?.type_conge_libelle"> · {{ evt.meta.type_conge_libelle }}</span>
            ({{ evt.date_debut }} → {{ evt.date_fin }})
          </template>
        </div>
        <p v-if="!eventsByDate(formatDate(currentDate)).length" class="muted text-xs">Aucun événement</p>
      </div>
        
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'

const events = ref([])
const filter = ref({ type: '', matricule: '', nom: '' })
const viewMode = ref('month')
const currentDate = ref(new Date())
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const fetchEvents = async () => {
  const params = {}
  if (filter.value.type) params.type = filter.value.type
  if (filter.value.matricule) params.matricule = filter.value.matricule
  if (filter.value.nom) params.nom = filter.value.nom
  const { data } = await api.get('/v1/calendrier-evenements', { params })
  console.log('Fetched events:', data);
  events.value = data.data || []
}

const debouncedFetchEvents = debounce(fetchEvents, 300)

const formatDate = (date) => {
  const d = new Date(date)
  // Corrige le décalage de fuseau pour éviter le glissement de jour dans le calendrier
  const tzOffset = d.getTimezoneOffset()
  d.setMinutes(d.getMinutes() - tzOffset)
  return d.toISOString().slice(0, 10)
}

const startOfWeek = (date) => {
  const d = new Date(date)
  const day = (d.getDay() + 6) % 7 // lundi=0+1; 
  d.setDate(d.getDate() - day)
  return d
}

const addDays = (date, days) => {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  return d  
}

const monthCells = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const start = startOfWeek(firstDay)
  const cells = []
  for (let i = 0; i < 42; i++) {
    const d = addDays(start, i)
    cells.push({
      date: d,
      dateStr: formatDate(d),
      day: d.getDate(),
      isOtherMonth: d.getMonth() !== month,
      isToday: formatDate(d) === formatDate(new Date())
    })
  }
  return cells
})

const weekRange = computed(() => {
  const start = startOfWeek(currentDate.value)
  const days = []
  for (let i = 0; i < 7; i++) {
    const d = addDays(start, i)
    days.push({ date: d, dateStr: formatDate(d), label: weekDays[i] })
  }
  return days
})

const title = computed(() => {
  if (viewMode.value === 'month') {
    return currentDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  }
  if (viewMode.value === 'week') {
    const start = weekRange.value[0]?.dateStr
    const end = weekRange.value[6]?.dateStr
    return `Semaine du ${start} au ${end}`
  }
  return `Jour ${formatDate(currentDate.value)}`
})

const eventsByDate = (dateStr) => {
  return events.value.filter((e) => {
    const isDateBetween = (date, start, end) => {
      const d = new Date(date)
      const s = new Date(start)
      const e = new Date(end)

      d.setHours(0,0,0,0)
      s.setHours(0,0,0,0)
      e.setHours(0,0,0,0)

      return d >= s && d <= e
    }

    // utilisation
    if (isDateBetween(dateStr, e.date_debut, e.date_fin)) {
      console.log('Filtering events for date:', dateStr)
    }

    return isDateBetween(dateStr, e.date_debut, e.date_fin) && (!filter.value.type || e.type === filter.value.type)
  })
}

const setView = (mode) => {
  viewMode.value = mode
}

const goToday = () => {
  currentDate.value = new Date()
}

const prevRange = () => {
  const d = new Date(currentDate.value)
  if (viewMode.value === 'month') d.setMonth(d.getMonth() - 1)
  else if (viewMode.value === 'week') d.setDate(d.getDate() - 7)
  else d.setDate(d.getDate() - 1)
  currentDate.value = d
}

const resetFilters = () => {
  filter.value = { type: '', matricule: '', nom: '' }
  fetchEvents()
}

const nextRange = () => {
  const d = new Date(currentDate.value)
  if (viewMode.value === 'month') d.setMonth(d.getMonth() + 1)
  else if (viewMode.value === 'week') d.setDate(d.getDate() + 7)
  else d.setDate(d.getDate() + 1)
  currentDate.value = d
}

const badgeClass = (type) => {
  switch (type) {
    case 'conge':
      return 'badge-conge'
    case 'absence':
      return 'badge-absence'
    case 'ferie':
      return 'badge-ferie'
    default:
      return 'badge-rh'
  }
}

onMounted(fetchEvents)
</script>

<style scoped>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
  padding: 8px;
  border-radius: 16px;
}
.calendar-header {
  text-align: center;
  font-size: 12px;
  color: var(--muted);
  padding: 6px 0;
}
.calendar-cell {
  min-height: 100px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 12px;
  padding: 6px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
  transition: background 120ms ease, border-color 120ms ease;
}
.calendar-cell.is-other {
  opacity: 0.4;
}
.calendar-cell.is-today {
  border-color: rgba(52, 211, 153, 0.4);
  box-shadow: 0 6px 20px rgba(52, 211, 153, 0.12);
}
.cell-top {
  display: flex;
  justify-content: flex-end;
  color: var(--muted);
}
.cell-events {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 6px;
}
.event-card {
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
  padding: 12px 14px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}
.event-pill {
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 8px;
  color: var(--bg);
  font-weight: 700;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
}
.badge-conge { background: rgba(16, 185, 129, 0.8); color: #0b1222; }
.badge-absence { background: rgba(59, 130, 246, 0.85); color: #0b1222; }
.badge-ferie { background: rgba(148, 163, 184, 0.8); color: #0b1222; }
.badge-rh { background: rgba(234, 179, 8, 0.9); color: #0b1222; }
.legend {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
}
.legend-conge { background: #34d399; }
.legend-absence { background: #60a5fa; }
.legend-ferie { background: #cbd5e1; }
.legend-rh { background: #fbbf24; }
.btn.active {
  border-color: rgba(34, 197, 94, 0.3);
  background: rgba(34, 197, 94, 0.12);
}
</style>
