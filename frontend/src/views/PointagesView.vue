<template>
  <div class="rh-page pointages-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Attendance tracking</p>
        <h1>Pointages</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Centralisez les entrées, sorties, retards, sources de pointage et absences justifiées dans
          une vue plus ordonnée et plus cohérente avec le reste du produit.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Entrées / sorties</span>
          <span class="pill">Retards</span>
          <span class="pill">Sources de pointage</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="fetchPointages" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
            <RouterLink class="btn" to="/pointages/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </RouterLink>
          </div>

          <div class="rh-hero-meta-list hero-meta-list">
            <p class="rh-hero-meta hero-meta">
              Lignes visibles:
              <strong>{{ formatInteger(pointagesFiltres.length) }}</strong>
            </p>
            <p class="rh-hero-meta hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
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

    <section class="rh-content-grid">
      <article class="card rh-section-card">
        <div class="rh-section-heading">
          <div>
            <p class="rh-section-kicker">Attendance list</p>
            <h2>Recherche et liste des pointages</h2>
          </div>
          <button class="btn btn-secondary btn-sm" @click="resetFilters" :disabled="!hasFilters">
            Réinitialiser
          </button>
        </div>

        <p class="rh-section-copy">
          Combinez les filtres serveur et les filtres locaux pour isoler rapidement un collaborateur,
          une source ou un type de pointage.
        </p>

        <div class="rh-controls-grid">
          <label class="rh-field-card">
            <span class="rh-field-label">Employé</span>
            <select class="select" v-model="filters.employe_id" @change="debouncedFetchPointages">
              <option value="">Tous les employés</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Date de début</span>
            <input class="input" type="date" v-model="filters.from" @change="debouncedFetchPointages" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Date de fin</span>
            <input class="input" type="date" v-model="filters.to" @change="debouncedFetchPointages" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Matricule</span>
            <input class="input" placeholder="EMP-001" v-model="filtersLocal.matricule" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Nom</span>
            <input class="input" placeholder="Nom ou prénom" v-model="filtersLocal.nom" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Type</span>
            <input class="input" placeholder="Entrée, sortie, retard..." v-model="filtersLocal.type" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Source</span>
            <input class="input" placeholder="Badge, manuel..." v-model="filtersLocal.source" />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Horodatage</span>
            <input class="input" placeholder="2026-04" v-model="filtersLocal.date" />
          </label>
        </div>

        <div class="rh-table-shell">
          <table class="table pointage-table">
            <thead>
              <tr>
                <th>
                  <button class="sort-button" @click="setSort('employe')">
                    Employé
                    <span>{{ sortLabel('employe') }}</span>
                  </button>
                </th>
                <th>
                  <button class="sort-button" @click="setSort('type')">
                    Type
                    <span>{{ sortLabel('type') }}</span>
                  </button>
                </th>
                <th>
                  <button class="sort-button" @click="setSort('date')">
                    Date / heure
                    <span>{{ sortLabel('date') }}</span>
                  </button>
                </th>
                <th>
                  <button class="sort-button" @click="setSort('source')">
                    Source
                    <span>{{ sortLabel('source') }}</span>
                  </button>
                </th>
                <th>Absence</th>
                <th>Commentaire</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="p in pointagesFiltres" :key="p.id">
                <td>
                  <div class="employee-cell">
                    <div class="employee-avatar">{{ initials(p) }}</div>
                    <div class="employee-copy">
                      <p>{{ p.employe?.nom || 'Employé' }} {{ p.employe?.prenom || '' }}</p>
                      <span>{{ p.employe?.matricule || 'Sans matricule' }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="chip">{{ p.type || '—' }}</span>
                </td>
                <td>{{ formatDateTime(p.pointe_a) }}</td>
                <td>{{ p.source || '—' }}</td>
                <td>
                  <span v-if="p.absence_justifiee" class="pill green">Justifiée</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="comment-cell">{{ p.commentaire || '—' }}</td>
              </tr>

              <tr v-if="!pointagesFiltres.length">
                <td colspan="6" class="rh-empty-state">
                  <p>Aucun pointage</p>
                  <span>Ajustez les filtres ou rechargez les données.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="table-footer">
          <p class="table-meta">
            Page <strong>{{ pagination.page }}</strong> sur <strong>{{ pagination.last_page }}</strong>
            · {{ formatInteger(pagination.total) }} lignes
          </p>

          <div class="table-actions">
            <button class="btn btn-secondary btn-sm" :disabled="pagination.page <= 1" @click="prevPage">
              Précédent
            </button>
            <button class="btn btn-secondary btn-sm" :disabled="pagination.page >= pagination.last_page" @click="nextPage">
              Suivant
            </button>
          </div>
        </div>
      </article>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Overview</p>
            <h2>Résumé opérationnel</h2>
          </div>
        </div>

        <p class="rh-summary-intro">
          Quelques repères rapides pour voir si la période contient surtout des retards, des absences
          justifiées ou des pointages normaux.
        </p>

        <div class="rh-overview-grid">
          <article v-for="card in overviewCards" :key="card.label" class="rh-overview-card">
            <span class="rh-overview-chip">{{ card.tag }}</span>
            <p class="rh-overview-label">{{ card.label }}</p>
            <p class="rh-overview-value">{{ card.value }}</p>
            <p class="rh-overview-copy">{{ card.copy }}</p>
          </article>
        </div>

        <div class="source-list" v-if="sourceSummary.length">
          <div v-for="item in sourceSummary" :key="item.label" class="source-item">
            <span class="source-name">{{ item.label }}</span>
            <span class="chip">{{ item.value }}</span>
          </div>
        </div>

        <div class="rh-notes-card">
          <h3>Repères rapides</h3>
          <ul>
            <li>Les dates de début et de fin déclenchent un rechargement côté API.</li>
            <li>Les autres filtres affinent la vue localement sans relancer la requête.</li>
            <li>La pagination conserve les mêmes critères pour la période chargée.</li>
          </ul>
        </div>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import AppIcon from '../components/ui/AppIcon.vue'

const pointages = ref([])
const employes = ref([])
const loading = ref(false)
const lastRefreshedAt = ref(null)

const filters = ref({
  employe_id: '',
  from: '',
  to: '',
})

const filtersLocal = ref({ matricule: '', nom: '', type: '', source: '', date: '' })
const sortKey = ref('date')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchPointages = async () => {
  loading.value = true
  try {
    const params = { ...filters.value, page: pagination.value.page }
    const { data } = await api.get('/v1/pointages', { params })
    pointages.value = data.data || []
    if (data.meta) {
      pagination.value = {
        page: data.meta.current_page,
        last_page: data.meta.last_page,
        total: data.meta.total,
      }
    } else if (data.current_page !== undefined) {
      pagination.value = {
        page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    }
    lastRefreshedAt.value = new Date()
  } finally {
    loading.value = false
  }
}

const debouncedFetchPointages = debounce(fetchPointages, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

const pointagesFiltres = computed(() => {
  const f = filtersLocal.value
  const toStr = (value) => String(value || '').toLowerCase()
  let list = pointages.value.filter(
    (item) =>
      toStr(item.employe?.matricule).includes(toStr(f.matricule)) &&
      `${toStr(item.employe?.nom)} ${toStr(item.employe?.prenom)}`.includes(toStr(f.nom)) &&
      toStr(item.type).includes(toStr(f.type)) &&
      toStr(item.source).includes(toStr(f.source)) &&
      toStr(item.pointe_a).includes(toStr(f.date)),
  )

  list = [...list].sort((left, right) => {
    const valueA = getVal(left, sortKey.value)
    const valueB = getVal(right, sortKey.value)
    if (valueA < valueB) return sortDir.value === 'asc' ? -1 : 1
    if (valueA > valueB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })

  return list
})

const hasFilters = computed(
  () =>
    Object.values(filters.value).some(Boolean) ||
    Object.values(filtersLocal.value).some((value) => Boolean(String(value || '').trim())),
)

const withJustified = computed(() => pointages.value.filter((item) => Boolean(item.absence_justifiee)).length)
const withLate = computed(() =>
  pointages.value.filter((item) => String(item.type || '').toLowerCase().includes('retard')).length,
)

const sourceSummary = computed(() => {
  const counts = new Map()
  pointagesFiltres.value.forEach((item) => {
    const key = item.source || 'Non renseignée'
    counts.set(key, (counts.get(key) || 0) + 1)
  })
  return [...counts.entries()]
    .map(([label, value]) => ({ label, value }))
    .sort((left, right) => right.value - left.value)
    .slice(0, 5)
})

const metricCards = computed(() => [
  {
    label: 'Pointages visibles',
    value: formatInteger(pointagesFiltres.value.length),
    caption: 'Résultats après filtres',
    tag: 'Rows',
  },
  {
    label: 'Absences justifiées',
    value: formatInteger(withJustified.value),
    caption: 'Entrées marquées justifiées',
    tag: 'Justified',
  },
  {
    label: 'Retards détectés',
    value: formatInteger(withLate.value),
    caption: 'Pointages de type retard',
    tag: 'Late',
  },
  {
    label: 'Dernière synchro',
    value: lastSyncedLabel.value,
    caption: 'Date de rafraîchissement',
    tag: 'Sync',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Employés filtrés',
    value: new Set(pointagesFiltres.value.map((item) => item.employe?.id).filter(Boolean)).size,
    copy: 'Nombre de collaborateurs présents dans la vue courante.',
    tag: 'People',
  },
  {
    label: 'Source dominante',
    value: sourceSummary.value[0]?.label || '—',
    copy: 'Canal le plus fréquent dans les résultats visibles.',
    tag: 'Source',
  },
  {
    label: 'Filtres actifs',
    value: hasFilters.value ? 'Oui' : 'Non',
    copy: hasFilters.value ? 'La liste est restreinte par des critères.' : 'Vue complète de la période chargée.',
    tag: 'State',
  },
  {
    label: 'Plage chargée',
    value: filters.value.from || filters.value.to ? 'Personnalisée' : 'Libre',
    copy: 'Lecture rapide de la période interrogée côté API.',
    tag: 'Range',
  },
])

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(lastRefreshedAt.value)
})

const getVal = (item, key) => {
  const toStr = (value) => String(value || '').toLowerCase()
  switch (key) {
    case 'employe':
      return `${toStr(item.employe?.nom)} ${toStr(item.employe?.prenom)}`
    case 'type':
      return toStr(item.type)
    case 'source':
      return toStr(item.source)
    case 'date':
    default:
      return toStr(item.pointe_a)
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

const resetFilters = () => {
  filters.value = { employe_id: '', from: '', to: '' }
  filtersLocal.value = { matricule: '', nom: '', type: '', source: '', date: '' }
  pagination.value.page = 1
  fetchPointages()
}

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page += 1
    fetchPointages()
  }
}

const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page -= 1
    fetchPointages()
  }
}

const formatInteger = (value) =>
  new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)

const formatDateTime = (value) => {
  if (!value) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

const initials = (item) => {
  const nom = item.employe?.nom?.[0] || 'E'
  const prenom = item.employe?.prenom?.[0] || 'P'
  return `${nom}${prenom}`.toUpperCase()
}

onMounted(async () => {
  await fetchEmployes()
  await fetchPointages()
})
</script>

<style scoped>
.sort-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0;
  border: none;
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.employee-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(79, 70, 229, 0.12);
  color: var(--brand-600);
  font-weight: 800;
}

.employee-copy p,
.table-meta,
.source-name {
  margin: 0;
}

.employee-copy p {
  font-weight: 700;
}

.employee-copy span {
  color: var(--muted);
  font-size: 0.84rem;
}

.comment-cell {
  max-width: 260px;
  white-space: normal;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.table-meta {
  color: var(--muted);
  font-size: 0.88rem;
}

.source-list {
  display: grid;
  gap: 10px;
}

.source-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .source-item {
  background: rgba(15, 23, 42, 0.72);
}

.source-name {
  font-weight: 700;
  color: var(--text);
}

@media (max-width: 880px) {
  .table-footer {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
