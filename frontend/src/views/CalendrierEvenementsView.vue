<template>
  <div class="rh-page calendrier-page">
    <section class="rh-hero">
      <div class="rh-hero-copy">
        <p class="rh-hero-kicker">Company calendar</p>
        <h1>Calendrier entreprise</h1>
        <p class="rh-hero-subtitle">
          Visualisez les congés, absences, jours fériés et événements RH dans une interface plus
          structurée, alignée avec le tableau de bord et les autres vues métier.
        </p>

        <div class="rh-hero-pills">
          <span class="pill">Vue mois</span>
          <span class="pill">Vue semaine</span>
          <span class="pill">Vue jour</span>
        </div>
      </div>

      <div class="rh-hero-actions">
        <div class="rh-panel">
          <div class="rh-controls-grid filters-grid">
            <label class="rh-field-card">
              <span class="rh-field-label">Type</span>
              <select class="select" v-model="filter.type" @change="debouncedFetchEvents">
                <option value="">Tous les types</option>
                <option value="conge">Congés</option>
                <option value="absence">Absences</option>
                <option value="ferie">Jours fériés</option>
                <option value="rh">Événements RH</option>
              </select>
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Matricule</span>
              <input class="input" placeholder="EMP-001" v-model="filter.matricule" @input="debouncedFetchEvents" />
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Nom</span>
              <input class="input" placeholder="Nom collaborateur" v-model="filter.nom" @input="debouncedFetchEvents" />
            </label>
          </div>

          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="resetFilters">Réinitialiser</button>
            <button class="btn" @click="fetchEvents">Actualiser</button>
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

    <section class="rh-content-grid">
      <article class="card rh-section-card">
        <div class="rh-section-heading">
          <div>
            <p class="rh-section-kicker">Calendar view</p>
            <h2>{{ title }}</h2>
          </div>
          <span class="rh-section-chip">{{ viewLabel }}</span>
        </div>

        <p class="rh-section-copy">
          Naviguez dans la période active et alternez entre la vue mensuelle, hebdomadaire et
          journalière sans quitter le référentiel d’événements.
        </p>

        <div class="calendar-toolbar">
          <div class="toolbar-group">
            <button class="btn btn-secondary btn-sm" @click="prevRange">Précédent</button>
            <button class="btn btn-secondary btn-sm" @click="nextRange">Suivant</button>
            <button class="btn btn-secondary btn-sm" @click="goToday">Aujourd'hui</button>
          </div>

          <div class="toolbar-group">
            <button class="btn btn-secondary btn-sm" :class="{ active: viewMode === 'month' }" @click="setView('month')">Mois</button>
            <button class="btn btn-secondary btn-sm" :class="{ active: viewMode === 'week' }" @click="setView('week')">Semaine</button>
            <button class="btn btn-secondary btn-sm" :class="{ active: viewMode === 'day' }" @click="setView('day')">Jour</button>
          </div>
        </div>

        <div class="legend-list">
          <span class="legend-item"><span class="legend legend-conge"></span> Congés</span>
          <span class="legend-item"><span class="legend legend-absence"></span> Absences</span>
          <span class="legend-item"><span class="legend legend-ferie"></span> Fériés</span>
          <span class="legend-item"><span class="legend legend-rh"></span> RH</span>
        </div>

        <div v-if="viewMode === 'month'" class="calendar-grid">
          <div class="calendar-header" v-for="day in weekDays" :key="day">{{ day }}</div>
          <div
            v-for="cell in monthCells"
            :key="cell.dateStr"
            class="calendar-cell"
            :class="{ today: cell.isToday, muted: cell.isOtherMonth }"
          >
            <div class="cell-top">
              <span>{{ cell.day }}</span>
            </div>

            <div class="cell-events">
              <div v-for="evt in eventsByDate(cell.dateStr)" :key="eventKey(evt)" class="event-pill" :class="badgeClass(evt.type)">
                <template v-if="evt.type === 'ferie'">
                  Férié · {{ evt.description || '—' }}
                </template>
                <template v-else>
                  {{ formatType(evt.type) }} · {{ evt.employe?.matricule || 'RH' }}
                  <span v-if="evt.meta?.type_conge_libelle"> · {{ evt.meta.type_conge_libelle }}</span>
                </template>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="viewMode === 'week'" class="agenda-grid">
          <article v-for="day in weekRange" :key="day.dateStr" class="agenda-card">
            <div class="agenda-head">
              <div>
                <p class="agenda-label">{{ day.label }}</p>
                <h3>{{ formatDisplayDate(day.date) }}</h3>
              </div>
              <span class="chip">{{ eventsByDate(day.dateStr).length }}</span>
            </div>

            <div class="agenda-list" v-if="eventsByDate(day.dateStr).length">
              <div v-for="evt in eventsByDate(day.dateStr)" :key="eventKey(evt)" class="event-pill agenda-pill" :class="badgeClass(evt.type)">
                <span>{{ formatType(evt.type) }}</span>
                <span>{{ evt.employe?.matricule || evt.description || 'Événement RH' }}</span>
              </div>
            </div>

            <div v-else class="rh-empty-state compact">
              <p>Aucun événement</p>
              <span>Cette journée ne contient aucun signal.</span>
            </div>
          </article>
        </div>

        <article v-else class="day-card">
          <div class="agenda-head">
            <div>
              <p class="agenda-label">Vue jour</p>
              <h3>{{ formatDisplayDate(currentDate) }}</h3>
            </div>
            <span class="chip">{{ eventsByDate(formatDate(currentDate)).length }}</span>
          </div>

          <div class="agenda-list" v-if="eventsByDate(formatDate(currentDate)).length">
            <div v-for="evt in eventsByDate(formatDate(currentDate))" :key="eventKey(evt)" class="day-row">
              <div class="day-badge" :class="badgeClass(evt.type)">{{ formatType(evt.type) }}</div>
              <div class="day-copy">
                <p>{{ evt.description || evt.meta?.type_conge_libelle || 'Événement RH' }}</p>
                <span>{{ evt.date_debut }} → {{ evt.date_fin }}</span>
              </div>
              <span class="chip">{{ evt.employe?.matricule || 'Global' }}</span>
            </div>
          </div>

          <div v-else class="rh-empty-state compact">
            <p>Aucun événement</p>
            <span>Aucun élément n’est planifié sur cette date.</span>
          </div>
        </article>
      </article>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Overview</p>
            <h2>Résumé période</h2>
          </div>
        </div>

        <p class="rh-summary-intro">
          Contrôlez rapidement la densité des événements, le type dominant et les prochains éléments à
          venir.
        </p>

        <div class="rh-overview-grid">
          <article v-for="card in overviewCards" :key="card.label" class="rh-overview-card">
            <span class="rh-overview-chip">{{ card.tag }}</span>
            <p class="rh-overview-label">{{ card.label }}</p>
            <p class="rh-overview-value">{{ card.value }}</p>
            <p class="rh-overview-copy">{{ card.copy }}</p>
          </article>
        </div>

        <div class="upcoming-list" v-if="upcomingEvents.length">
          <div v-for="evt in upcomingEvents" :key="eventKey(evt)" class="upcoming-item">
            <div class="upcoming-top">
              <span class="day-badge" :class="badgeClass(evt.type)">{{ formatType(evt.type) }}</span>
              <span class="upcoming-date">{{ formatDate(evt.date_debut) }}</span>
            </div>
            <p>{{ evt.description || evt.meta?.type_conge_libelle || 'Événement RH' }}</p>
            <span>{{ evt.employe?.matricule || 'Global' }}</span>
          </div>
        </div>

        <div class="rh-notes-card">
          <h3>Repères rapides</h3>
          <ul>
            <li>La vue mois sert à repérer les charges de période et les collisions.</li>
            <li>La vue semaine accélère le suivi opérationnel d’une période courte.</li>
            <li>Les filtres identifiant et nom s’appliquent directement aux événements récupérés.</li>
          </ul>
        </div>
      </aside>
    </section>
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
  events.value = data.data || []
}

const debouncedFetchEvents = debounce(fetchEvents, 300)

const formatDate = (date) => {
  const d = new Date(date)
  const tzOffset = d.getTimezoneOffset()
  d.setMinutes(d.getMinutes() - tzOffset)
  return d.toISOString().slice(0, 10)
}

const formatDisplayDate = (date) =>
  new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(date))

const startOfWeek = (date) => {
  const d = new Date(date)
  const day = (d.getDay() + 6) % 7
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

  for (let i = 0; i < 42; i += 1) {
    const d = addDays(start, i)
    cells.push({
      date: d,
      dateStr: formatDate(d),
      day: d.getDate(),
      isOtherMonth: d.getMonth() !== month,
      isToday: formatDate(d) === formatDate(new Date()),
    })
  }

  return cells
})

const weekRange = computed(() => {
  const start = startOfWeek(currentDate.value)
  return Array.from({ length: 7 }, (_, index) => {
    const d = addDays(start, index)
    return { date: d, dateStr: formatDate(d), label: weekDays[index] }
  })
})

const title = computed(() => {
  if (viewMode.value === 'month') {
    return currentDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  }

  if (viewMode.value === 'week') {
    const start = weekRange.value[0]?.date
    const end = weekRange.value[6]?.date
    if (!start || !end) return 'Semaine'
    return `${formatDisplayDate(start)} → ${formatDisplayDate(end)}`
  }

  return formatDisplayDate(currentDate.value)
})

const viewLabel = computed(() => {
  if (viewMode.value === 'month') return 'Vue mois'
  if (viewMode.value === 'week') return 'Vue semaine'
  return 'Vue jour'
})

const eventCounts = computed(() => {
  const base = { total: events.value.length, conge: 0, absence: 0, ferie: 0, rh: 0 }
  events.value.forEach((event) => {
    if (event.type === 'conge') base.conge += 1
    else if (event.type === 'absence') base.absence += 1
    else if (event.type === 'ferie') base.ferie += 1
    else base.rh += 1
  })
  return base
})

const metricCards = computed(() => [
  {
    label: 'Événements chargés',
    value: eventCounts.value.total,
    caption: 'Volume actuellement récupéré depuis l’API',
    tag: 'All',
  },
  {
    label: 'Congés',
    value: eventCounts.value.conge,
    caption: 'Congés validés ou remontés',
    tag: 'Leave',
  },
  {
    label: 'Absences',
    value: eventCounts.value.absence,
    caption: 'Absences détectées sur la période',
    tag: 'Absence',
  },
  {
    label: 'Jours fériés',
    value: eventCounts.value.ferie,
    caption: 'Dates légales intégrées à la vue',
    tag: 'Legal',
  },
])

const upcomingEvents = computed(() =>
  [...events.value]
    .filter((event) => String(event.date_fin || '') >= formatDate(new Date()))
    .sort((left, right) => String(left.date_debut || '').localeCompare(String(right.date_debut || '')))
    .slice(0, 5),
)

const overviewCards = computed(() => [
  {
    label: 'Type dominant',
    value:
      [
        { label: 'Congés', value: eventCounts.value.conge },
        { label: 'Absences', value: eventCounts.value.absence },
        { label: 'Fériés', value: eventCounts.value.ferie },
        { label: 'RH', value: eventCounts.value.rh },
      ].sort((left, right) => right.value - left.value)[0]?.label || 'Aucun',
    copy: 'Catégorie la plus visible dans la période chargée.',
    tag: 'Focus',
  },
  {
    label: 'Éléments à venir',
    value: upcomingEvents.value.length,
    copy: 'Prochains événements encore à traiter ou surveiller.',
    tag: 'Next',
  },
  {
    label: 'Vue active',
    value: viewLabel.value.replace('Vue ', ''),
    copy: 'Mode de lecture actuellement sélectionné.',
    tag: 'Mode',
  },
  {
    label: 'Filtres actifs',
    value: [filter.value.type, filter.value.matricule, filter.value.nom].filter(Boolean).length,
    copy: 'Nombre de filtres actuellement appliqués.',
    tag: 'Filters',
  },
])

const isDateBetween = (date, start, end) => {
  const d = new Date(date)
  const s = new Date(start)
  const e = new Date(end)

  d.setHours(0, 0, 0, 0)
  s.setHours(0, 0, 0, 0)
  e.setHours(0, 0, 0, 0)

  return d >= s && d <= e
}

const eventsByDate = (dateStr) =>
  events.value.filter(
    (event) =>
      isDateBetween(dateStr, event.date_debut, event.date_fin) &&
      (!filter.value.type || event.type === filter.value.type),
  )

const eventKey = (event) =>
  `${event.id || 'evt'}-${event.type || 'type'}-${event.date_debut || ''}-${event.date_fin || ''}`

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

const nextRange = () => {
  const d = new Date(currentDate.value)
  if (viewMode.value === 'month') d.setMonth(d.getMonth() + 1)
  else if (viewMode.value === 'week') d.setDate(d.getDate() + 7)
  else d.setDate(d.getDate() + 1)
  currentDate.value = d
}

const resetFilters = () => {
  filter.value = { type: '', matricule: '', nom: '' }
  fetchEvents()
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

const formatType = (type) => {
  if (type === 'conge') return 'Congé'
  if (type === 'absence') return 'Absence'
  if (type === 'ferie') return 'Férié'
  return 'RH'
}

onMounted(fetchEvents)
</script>

<style scoped>
.filters-grid {
  grid-template-columns: 1fr;
}

.calendar-toolbar,
.legend-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
}

.toolbar-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.btn.active {
  border-color: rgba(79, 70, 229, 0.28);
  background: rgba(79, 70, 229, 0.12);
}

.legend-list {
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.68);
}

body[data-theme='dark'] .legend-list {
  background: rgba(15, 23, 42, 0.72);
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 0.86rem;
  font-weight: 600;
}

.legend {
  width: 12px;
  height: 12px;
  border-radius: 999px;
}

.legend-conge {
  background: #10b981;
}

.legend-absence {
  background: #3b82f6;
}

.legend-ferie {
  background: #94a3b8;
}

.legend-rh {
  background: #f59e0b;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 10px;
}

.calendar-header {
  padding: 10px 0;
  color: var(--muted);
  text-align: center;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.calendar-cell {
  min-height: 140px;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .calendar-cell {
  background: rgba(15, 23, 42, 0.72);
}

.calendar-cell.today {
  border-color: rgba(79, 70, 229, 0.28);
  box-shadow: 0 18px 32px rgba(79, 70, 229, 0.12);
}

.calendar-cell.muted {
  opacity: 0.5;
}

.cell-top {
  display: flex;
  justify-content: flex-end;
  font-weight: 700;
  color: var(--text);
}

.cell-events,
.agenda-list {
  display: grid;
  gap: 8px;
  margin-top: 10px;
}

.event-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  padding: 7px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 700;
  line-height: 1.45;
}

.badge-conge {
  background: rgba(16, 185, 129, 0.14);
  color: #047857;
}

.badge-absence {
  background: rgba(59, 130, 246, 0.14);
  color: #2563eb;
}

.badge-ferie {
  background: rgba(148, 163, 184, 0.16);
  color: #475569;
}

.badge-rh {
  background: rgba(245, 158, 11, 0.16);
  color: #d97706;
}

.agenda-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.agenda-card,
.day-card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .agenda-card,
body[data-theme='dark'] .day-card {
  background: rgba(15, 23, 42, 0.72);
}

.agenda-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.agenda-head h3,
.upcoming-item p,
.day-copy p {
  margin: 0;
}

.agenda-label {
  margin: 0 0 4px;
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.agenda-head h3 {
  font-size: 1rem;
  font-weight: 800;
}

.agenda-pill {
  justify-content: space-between;
}

.day-row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.day-row:last-child {
  border-bottom: none;
}

.day-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  white-space: nowrap;
}

.day-copy {
  min-width: 0;
}

.day-copy span {
  color: var(--muted);
  font-size: 0.84rem;
}

.upcoming-list {
  display: grid;
  gap: 12px;
}

.upcoming-item {
  display: grid;
  gap: 8px;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .upcoming-item {
  background: rgba(15, 23, 42, 0.72);
}

.upcoming-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.upcoming-date,
.upcoming-item span {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 600;
}

.compact {
  padding-top: 0;
  padding-bottom: 0;
}

@media (max-width: 1080px) {
  .calendar-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .agenda-grid,
  .calendar-grid {
    grid-template-columns: 1fr;
  }

  .day-row {
    grid-template-columns: 1fr;
    align-items: stretch;
  }
}
</style>
