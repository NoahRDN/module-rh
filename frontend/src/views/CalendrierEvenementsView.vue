<template>
  <div class="rh-page calendrier-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Company calendar</p>
        <h1>Calendrier entreprise</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Visualisez les congés, absences, jours fériés et événements RH dans une interface plus
          structurée, alignée avec le tableau de bord et les autres vues métier.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Vue mois</span>
          <span class="pill">Vue semaine</span>
          <span class="pill">Vue jour</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
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

          <button type="button" class="btn create-event-btn" @click="openCreateModal">
            <AppIcon name="plus" :size="18" />
            <span>Nouvel événement RH</span>
          </button>
        </div>
      </div>
    </section>

    <p v-if="statusMessage" class="calendar-status-banner success">
      {{ statusMessage }}
    </p>
    <p v-if="errorMessage" class="calendar-status-banner danger">
      {{ errorMessage }}
    </p>

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
            :class="{ today: cell.isToday, muted: cell.isOtherMonth, clickable: eventsByDate(cell.dateStr).length }"
            @click="openDateDetails(cell.dateStr)"
          >
            <div class="cell-top">
              <span>{{ cell.day }}</span>
            </div>

            <div class="cell-events">
              <div
                v-for="evt in monthPreviewEvents(cell.dateStr)"
                :key="eventKey(evt)"
                class="event-pill event-pill-compact"
                :class="badgeClass(evt.type)"
              >
                <span class="event-pill-main">{{ monthEventLabel(evt) }}</span>
              </div>

              <div v-if="hiddenEventCount(cell.dateStr)" class="more-events-pill">
                +{{ hiddenEventCount(cell.dateStr) }} autre<span v-if="hiddenEventCount(cell.dateStr) > 1">s</span>
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
              <button
                v-for="evt in eventsByDate(day.dateStr)"
                :key="eventKey(evt)"
                type="button"
                class="event-pill agenda-pill event-button"
                :class="badgeClass(evt.type)"
                @click="openDateDetails(day.dateStr)"
              >
                <span>{{ formatType(evt.type) }}</span>
                <span>{{ compactEventLabel(evt) }}</span>
              </button>
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
            <button
              v-for="evt in eventsByDate(formatDate(currentDate))"
              :key="eventKey(evt)"
              type="button"
              class="day-row event-detail-trigger"
              @click="openDateDetails(formatDate(currentDate))"
            >
              <div class="day-badge" :class="badgeClass(evt.type)">{{ formatType(evt.type) }}</div>
              <div class="day-copy">
                <p>{{ compactEventLabel(evt) }}</p>
                <span>{{ formatDisplayDate(evt.date_debut) }} → {{ formatDisplayDate(evt.date_fin) || '—' }}</span>
              </div>
              <span class="chip">{{ evt.employe?.matricule || 'Global' }}</span>
            </button>
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
              <span class="upcoming-date">{{ formatDisplayDate(evt.date_debut) }}</span>
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

    <div v-if="selectedDate" class="event-modal-backdrop" @click.self="closeEventDetails">
      <article class="event-modal card">
        <div class="event-modal-head">
          <div>
            <p class="rh-section-kicker">Day details</p>
            <h2>{{ selectedDateLabel }}</h2>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" @click="closeEventDetails">Fermer</button>
        </div>

        <div class="event-modal-summary">
          <span class="chip">{{ selectedDateEvents.length }} événement<span v-if="selectedDateEvents.length > 1">s</span></span>
        </div>

        <div class="event-date-list">
          <article v-for="evt in selectedDateEvents" :key="eventKey(evt)" class="event-date-card">
            <div class="event-date-top">
              <span class="day-badge" :class="badgeClass(evt.type)">{{ formatType(evt.type) }}</span>
              <span class="chip">{{ evt.employe?.matricule || 'Global' }}</span>
            </div>

            <h3>{{ eventEmployeeName(evt) }}</h3>
            <p>{{ evt.meta?.type_conge_libelle || evt.description || detailLine(evt) }}</p>

            <div class="event-date-meta">
              <span>{{ formatDisplayDate(evt.date_debut) }} → {{ formatDisplayDate(evt.date_fin) || '—' }}</span>
              <span>{{ detailLine(evt) }}</span>
            </div>

            <div v-if="isEditableRhEvent(evt)" class="event-date-actions">
              <button type="button" class="btn btn-secondary btn-sm" @click="openEditModal(evt)">Modifier</button>
              <button type="button" class="btn btn-secondary btn-sm danger-action" @click="deleteRhEvent(evt)">
                Supprimer
              </button>
            </div>
          </article>
        </div>
      </article>
    </div>

    <div v-if="showCreateModal" class="event-modal-backdrop" @click.self="closeCreateModal">
      <article class="event-modal card create-event-modal">
        <div class="event-modal-head">
          <div>
            <p class="rh-section-kicker">Create event</p>
            <h2>{{ isEditingEvent ? 'Modifier l’événement RH' : 'Nouvel événement RH' }}</h2>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" @click="closeCreateModal">Fermer</button>
        </div>

        <form class="create-event-form" @submit.prevent="submitRhEvent">
          <label class="rh-field-card">
            <span class="rh-field-label">Employé</span>
            <select v-model="rhEventForm.employe_id" class="select" :disabled="loadingEmployes">
              <option value="">Événement global</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </label>

          <label class="rh-field-card full">
            <span class="rh-field-label">Description</span>
            <input
              v-model="rhEventForm.description"
              class="input"
              placeholder="Ex: Entretien annuel, réunion RH, session onboarding"
              required
            />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Date de début</span>
            <input v-model="rhEventForm.date_debut" class="input" type="date" required />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Date de fin</span>
            <input v-model="rhEventForm.date_fin" class="input" type="date" required />
          </label>

          <p v-if="createModalError" class="modal-message danger">{{ createModalError }}</p>

          <div class="modal-actions">
            <button type="submit" class="btn" :disabled="savingEvent">
              <AppIcon name="save" :size="18" />
              <span>
                {{
                  savingEvent
                    ? isEditingEvent
                      ? 'Mise à jour...'
                      : 'Création...'
                    : isEditingEvent
                      ? 'Enregistrer les changements'
                      : 'Créer l’événement'
                }}
              </span>
            </button>
            <button type="button" class="btn btn-secondary" @click="closeCreateModal">Annuler</button>
          </div>
        </form>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import AppIcon from '../components/ui/AppIcon.vue'

const pad2 = (value) => String(value).padStart(2, '0')

const normalizeDate = (value) => {
  if (!value) return ''

  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (/^\d{4}-\d{2}-\d{2}/.test(trimmed)) return trimmed.slice(0, 10)
    const parsed = new Date(trimmed)
    if (Number.isNaN(parsed.getTime())) return ''
    return `${parsed.getFullYear()}-${pad2(parsed.getMonth() + 1)}-${pad2(parsed.getDate())}`
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`
}

const toInputDate = (value = new Date()) => normalizeDate(value) || normalizeDate(new Date())

const events = ref([])
const employes = ref([])
const filter = ref({ type: '', matricule: '', nom: '' })
const viewMode = ref('month')
const currentDate = ref(new Date())
const selectedDate = ref('')
const showCreateModal = ref(false)
const loadingEmployes = ref(false)
const savingEvent = ref(false)
const editingEventId = ref(null)
const statusMessage = ref('')
const errorMessage = ref('')
const createModalError = ref('')
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const rhEventForm = ref(defaultRhEventForm())

const fetchEvents = async () => {
  errorMessage.value = ''
  const params = { ...visibleRange.value }
  if (filter.value.type) params.type = filter.value.type
  if (filter.value.matricule) params.matricule = filter.value.matricule
  if (filter.value.nom) params.nom = filter.value.nom
  try {
    const { data } = await api.get('/v1/calendrier-evenements', { params })
    events.value = data.data || []
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Impossible de charger le calendrier.'
  }
}

const debouncedFetchEvents = debounce(fetchEvents, 300)

const formatDate = (date) => normalizeDate(date)

const formatDisplayDate = (date) =>
  new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(`${formatDate(date)}T00:00:00`))

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

function defaultRhEventForm(date = null) {
  const baseDate = date || toInputDate()
  return {
    employe_id: '',
    description: '',
    date_debut: baseDate,
    date_fin: baseDate,
  }
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

const visibleRange = computed(() => {
  if (viewMode.value === 'month') {
    return {
      from: monthCells.value[0]?.dateStr || formatDate(currentDate.value),
      to: monthCells.value[monthCells.value.length - 1]?.dateStr || formatDate(currentDate.value),
    }
  }

  if (viewMode.value === 'week') {
    return {
      from: weekRange.value[0]?.dateStr || formatDate(currentDate.value),
      to: weekRange.value[weekRange.value.length - 1]?.dateStr || formatDate(currentDate.value),
    }
  }

  const dateStr = formatDate(currentDate.value)
  return { from: dateStr, to: dateStr }
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
  const d = normalizeDate(date)
  const s = normalizeDate(start)
  const e = normalizeDate(end)
  if (!d || !s || !e) return false
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

const monthPreviewEvents = (dateStr) => eventsByDate(dateStr).slice(0, 2)

const hiddenEventCount = (dateStr) => Math.max(eventsByDate(dateStr).length - 2, 0)

const selectedDateEvents = computed(() => (selectedDate.value ? eventsByDate(selectedDate.value) : []))

const selectedDateLabel = computed(() => (selectedDate.value ? formatDisplayDate(selectedDate.value) : ''))
const isEditingEvent = computed(() => editingEventId.value !== null)

const monthEventLabel = (event) => event.employe?.matricule || 'Global'

const compactEventLabel = (event) => {
  if (event.type === 'ferie') return 'Férié'
  return event.employe?.matricule || 'Global'
}

const eventEmployeeName = (event) => {
  if (!event?.employe) return 'Événement global'
  return `${event.employe.nom || ''} ${event.employe.prenom || ''}`.trim() || 'Employé non renseigné'
}

const detailLine = (event) => {
  if (event.type === 'conge') return event.meta?.type_conge_libelle || 'Congé'
  if (event.type === 'absence') return 'Absence'
  if (event.type === 'ferie') return 'Jour férié'
  return 'Événement RH'
}

const openDateDetails = (dateStr) => {
  if (!eventsByDate(dateStr).length) return
  selectedDate.value = dateStr
  showCreateModal.value = false
}

const closeEventDetails = () => {
  selectedDate.value = ''
}

const isEditableRhEvent = (event) => event?.type === 'rh' && typeof event?.id !== 'undefined'

const fetchEmployes = async () => {
  if (employes.value.length || loadingEmployes.value) return

  loadingEmployes.value = true
  try {
    const { data } = await api.get('/v1/employes', {
      params: { active_only: true, all: 1, sort: 'nom' },
    })
    employes.value = data.data || data || []
  } catch (error) {
    createModalError.value = error.response?.data?.message || 'Impossible de charger la liste des employés.'
  } finally {
    loadingEmployes.value = false
  }
}

const openCreateModal = async () => {
  const initialDate = selectedDate.value || formatDate(currentDate.value)
  selectedDate.value = ''
  editingEventId.value = null
  createModalError.value = ''
  statusMessage.value = ''
  errorMessage.value = ''
  rhEventForm.value = defaultRhEventForm(initialDate)
  showCreateModal.value = true
  await fetchEmployes()
}

const closeCreateModal = () => {
  showCreateModal.value = false
  editingEventId.value = null
  createModalError.value = ''
  rhEventForm.value = defaultRhEventForm()
}

const openEditModal = async (event) => {
  if (!isEditableRhEvent(event)) return

  editingEventId.value = event.id
  createModalError.value = ''
  statusMessage.value = ''
  errorMessage.value = ''
  rhEventForm.value = {
    employe_id: event.employe_id || '',
    description: event.description || '',
    date_debut: toInputDate(event.date_debut),
    date_fin: toInputDate(event.date_fin),
  }
  selectedDate.value = ''
  showCreateModal.value = true
  await fetchEmployes()
}

const submitRhEvent = async () => {
  createModalError.value = ''
  statusMessage.value = ''
  errorMessage.value = ''

  if (!rhEventForm.value.description.trim()) {
    createModalError.value = 'La description est requise.'
    return
  }

  if (rhEventForm.value.date_fin < rhEventForm.value.date_debut) {
    createModalError.value = 'La date de fin doit être postérieure ou égale à la date de début.'
    return
  }

  savingEvent.value = true

  try {
    const payload = {
      employe_id: rhEventForm.value.employe_id || null,
      date_debut: rhEventForm.value.date_debut,
      date_fin: rhEventForm.value.date_fin,
      description: rhEventForm.value.description.trim(),
    }

    if (isEditingEvent.value) {
      await api.put(`/v1/calendrier-evenements/${editingEventId.value}`, payload)
      statusMessage.value = 'Événement RH mis à jour.'
    } else {
      await api.post('/v1/calendrier-evenements', {
        type: 'rh',
        ...payload,
      })
      statusMessage.value = 'Événement RH ajouté au calendrier.'
    }

    showCreateModal.value = false
    await fetchEvents()
    openDateDetails(rhEventForm.value.date_debut)
    editingEventId.value = null
    rhEventForm.value = defaultRhEventForm()
  } catch (error) {
    createModalError.value =
      error.response?.data?.message ||
      (isEditingEvent.value
        ? 'Impossible de modifier l’événement RH.'
        : 'Impossible de créer l’événement RH.')
  } finally {
    savingEvent.value = false
  }
}

const deleteRhEvent = async (event) => {
  if (!isEditableRhEvent(event)) return
  if (!window.confirm('Supprimer cet événement RH ?')) return

  statusMessage.value = ''
  errorMessage.value = ''

  try {
    await api.delete(`/v1/calendrier-evenements/${event.id}`)
    const previousDate = selectedDate.value || formatDate(event.date_debut)
    await fetchEvents()

    if (eventsByDate(previousDate).length) {
      selectedDate.value = previousDate
    } else {
      selectedDate.value = ''
    }

    statusMessage.value = 'Événement RH supprimé.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Impossible de supprimer l’événement RH.'
  }
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

watch(
  () => [viewMode.value, formatDate(currentDate.value)],
  () => {
    fetchEvents()
  },
)

onMounted(fetchEvents)
</script>

<style scoped>
.calendar-status-banner {
  margin: 0;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 0.92rem;
  font-weight: 700;
}

.calendar-status-banner.success {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
}

.calendar-status-banner.danger {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
}

.event-date-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.danger-action {
  border-color: rgba(239, 68, 68, 0.18);
  color: #dc2626;
}

.danger-action:hover {
  border-color: rgba(239, 68, 68, 0.28);
  background: rgba(239, 68, 68, 0.08);
}

.filters-grid {
  grid-template-columns: 1fr;
}

.create-event-btn {
  width: 100%;
  justify-content: center;
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
  display: flex;
  flex-direction: column;
  min-height: 140px;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.76);
  overflow: hidden;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

body[data-theme='dark'] .calendar-cell {
  background: rgba(15, 23, 42, 0.72);
}

.calendar-cell.clickable {
  cursor: pointer;
}

.calendar-cell.clickable:hover {
  border-color: rgba(79, 70, 229, 0.22);
  box-shadow: 0 12px 24px rgba(79, 70, 229, 0.08);
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
  align-content: start;
}

.cell-events {
  min-width: 0;
  overflow: hidden;
}

.event-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  width: 100%;
  padding: 7px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 700;
  line-height: 1.45;
}

.event-pill-compact {
  white-space: nowrap;
}

.event-button {
  border: 0;
  text-align: left;
  cursor: pointer;
}

.event-pill-type {
  flex: none;
}

.event-pill-main {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.more-events-pill {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  max-width: 100%;
  padding: 5px 8px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
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

.event-detail-trigger {
  width: 100%;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
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

.event-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(6px);
}

.event-modal {
  width: min(680px, 100%);
  display: grid;
  gap: 18px;
  padding: 24px;
  border-radius: 28px;
  box-shadow: var(--shadow-lg);
}

.create-event-modal {
  width: min(720px, 100%);
}

.event-modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.event-modal-head h2 {
  margin: 8px 0 0;
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.event-modal-summary {
  display: flex;
  justify-content: flex-start;
}

.create-event-form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.create-event-form .full {
  grid-column: 1 / -1;
}

.modal-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  grid-column: 1 / -1;
}

.modal-message {
  margin: 0;
  grid-column: 1 / -1;
  font-size: 0.88rem;
  font-weight: 700;
}

.modal-message.danger {
  color: #b91c1c;
}

.event-date-list {
  display: grid;
  gap: 14px;
}

.event-date-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(248, 250, 252, 0.82);
}

body[data-theme='dark'] .event-date-card {
  background: rgba(15, 23, 42, 0.68);
}

.event-date-top,
.event-date-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.event-date-card h3,
.event-date-card p {
  margin: 0;
}

.event-date-card h3 {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.event-date-card p,
.event-date-meta span {
  color: var(--muted);
  font-size: 0.88rem;
  line-height: 1.55;
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

  .create-event-form {
    grid-template-columns: 1fr;
  }
}
</style>
