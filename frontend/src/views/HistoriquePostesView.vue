<template>
  <div class="historiques-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Career mobility</p>
        <h1>Historique des postes</h1>
        <p class="hero-subtitle">
          Suivez les mobilités internes pour comprendre les transitions de postes, les mouvements
          entre départements et les motifs d’évolution.
        </p>

        <div class="hero-pills">
          <span class="pill">Mobilités internes</span>
          <span class="pill">Trajectoires RH</span>
          <span class="pill">Suivi des mouvements</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/historiques/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouvelle mobilité</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total mouvements:
              <strong>{{ formatInteger(pagination.total || historiques.length) }}</strong>
            </p>
            <p class="hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <template v-if="loading && !historiques.length">
      <div class="card loading-card">
        <p class="loading-title">Chargement des mobilités…</p>
        <p class="muted">Les mouvements internes sont en cours de synchronisation.</p>
      </div>
    </template>

    <template v-else>
      <section class="card section-card controls-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Directory controls</p>
            <h2>Recherche et filtres</h2>
          </div>
          <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters" :disabled="!hasFilters">
            Réinitialiser
          </button>
        </div>

        <p class="section-copy">
          Combinez les filtres par collaborateur, poste, département et motif pour retrouver vite un
          mouvement précis.
        </p>

        <div class="controls-grid">
          <label class="field-card search-card">
            <span class="field-label">Employé</span>
            <select class="select" v-model="filterEmploye" @change="debouncedFetchHistorique">
              <option value="">Tous les employés</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Matricule</span>
            <input class="input" placeholder="EMP-001" v-model="filters.matricule" list="historique-postes-matricules" />
          </label>

          <label class="field-card">
            <span class="field-label">Nom</span>
            <input class="input" placeholder="Nom ou prénom" v-model="filters.nom" />
          </label>

          <label class="field-card">
            <span class="field-label">Poste</span>
            <input class="input" placeholder="Fonction" v-model="filters.poste" list="historique-postes-postes" />
          </label>

          <label class="field-card">
            <span class="field-label">Département</span>
            <input class="input" placeholder="Structure" v-model="filters.departement" list="historique-postes-departements" />
          </label>

          <label class="field-card">
            <span class="field-label">Motif</span>
            <input class="input" placeholder="Changement, promotion..." v-model="filters.motif" />
          </label>

          <label class="field-card">
            <span class="field-label">Entre (début)</span>
            <input class="input" type="date" v-model="filters.from" />
          </label>

          <label class="field-card">
            <span class="field-label">Et (fin)</span>
            <input class="input" type="date" v-model="filters.to" />
          </label>
        </div>

        <datalist id="historique-postes-matricules">
          <option v-for="m in optionsMatricules" :key="m" :value="m" />
        </datalist>
        <datalist id="historique-postes-postes">
          <option v-for="p in optionsPostes" :key="p" :value="p" />
        </datalist>
        <datalist id="historique-postes-departements">
          <option v-for="d in optionsDepartements" :key="d" :value="d" />
        </datalist>
      </section>

      <section class="content-grid">
        <article class="card section-card table-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Mobility log</p>
              <h2>Mouvements enregistrés</h2>
            </div>
            <span class="section-chip">{{ formatInteger(historiquesFiltres.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Chronologie des changements de poste pour analyser les trajectoires individuelles et les
            dynamiques d’organisation.
          </p>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('date')">
                      Date
                      <span>{{ sortLabel('date') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('employe')">
                      Employé
                      <span>{{ sortLabel('employe') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('poste')">
                      Poste
                      <span>{{ sortLabel('poste') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('departement')">
                      Département
                      <span>{{ sortLabel('departement') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('motif')">
                      Motif
                      <span>{{ sortLabel('motif') }}</span>
                    </button>
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="h in historiquesFiltres" :key="h.id">
                  <td>{{ formatDate(h.date_changement) || '—' }}</td>
                  <td>
                    <div v-if="h.employe" class="employee-cell">
                      <div class="employee-avatar">{{ initials(h.employe) }}</div>
                      <div class="employee-copy">
                        <p>{{ employeName(h.employe) }}</p>
                        <span>{{ h.employe.matricule || 'Sans matricule' }}</span>
                      </div>
                    </div>
                    <span v-else>—</span>
                  </td>
                  <td>{{ h.poste?.nom || '—' }}</td>
                  <td>{{ h.departement?.nom || '—' }}</td>
                  <td class="history-reason">{{ h.motif || '—' }}</td>
                </tr>

                <tr v-if="!historiquesFiltres.length">
                  <td colspan="5" class="empty-state">
                    <p>Aucun mouvement ne correspond à la sélection actuelle.</p>
                    <span>Essayez d’ajuster les filtres ou d’ajouter une nouvelle mobilité.</span>
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
              <button class="btn btn-secondary btn-sm" type="button" :disabled="loading || pagination.page <= 1" @click="prevPage">
                Précédent
              </button>
              <button
                class="btn btn-secondary btn-sm"
                type="button"
                :disabled="loading || pagination.page >= pagination.last_page"
                @click="nextPage"
              >
                Suivant
              </button>
            </div>
          </div>
        </article>

      </section>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import { RouterLink } from 'vue-router'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatDateValue } from '../utils/formatters'

const historiques = ref([])
const employes = ref([])
const filterEmploye = ref('')
const filters = ref({ matricule: '', nom: '', poste: '', departement: '', motif: '', from: '', to: '' })
const loading = ref(false)
const lastRefreshedAt = ref(null)
const sortKey = ref('date')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const hasFilters = computed(() =>
  Boolean(
    filterEmploye.value ||
    filters.value.matricule ||
    filters.value.nom ||
    filters.value.poste ||
    filters.value.departement ||
    filters.value.motif ||
    filters.value.from ||
    filters.value.to,
  ),
)

const optionsMatricules = computed(() => {
  const fromEmployees = employes.value.map((item) => item.matricule)
  const fromHistorique = historiques.value.map((item) => item.employe?.matricule)
  return [...new Set([...fromEmployees, ...fromHistorique].filter(Boolean))]
})

const optionsPostes = computed(() =>
  [...new Set(historiques.value.map((item) => item.poste?.nom).filter(Boolean))],
)

const optionsDepartements = computed(() =>
  [...new Set(historiques.value.map((item) => item.departement?.nom).filter(Boolean))],
)

const fetchHistorique = async () => {
  loading.value = true
  const params = buildHistoriqueParams()
  try {
    const { data } = await api.get('/v1/historiques-postes', { params })
    historiques.value = data.data || []
    if (data.meta) {
      pagination.value = { page: data.meta.current_page, last_page: data.meta.last_page, total: data.meta.total }
    } else if (data.current_page !== undefined) {
      pagination.value = { page: data.current_page, last_page: data.last_page, total: data.total }
    }
    lastRefreshedAt.value = new Date()
  } finally {
    loading.value = false
  }
}

const buildHistoriqueParams = () => ({
  page: pagination.value.page,
  ...(filterEmploye.value ? { employe_id: filterEmploye.value } : {}),
  ...(filters.value.matricule ? { matricule: filters.value.matricule } : {}),
  ...(filters.value.nom ? { nom: filters.value.nom } : {}),
  ...(filters.value.poste ? { poste: filters.value.poste } : {}),
  ...(filters.value.departement ? { departement: filters.value.departement } : {}),
  ...(filters.value.motif ? { motif: filters.value.motif } : {}),
  ...(filters.value.from ? { from: filters.value.from } : {}),
  ...(filters.value.to ? { to: filters.value.to } : {}),
})

const debouncedFetchHistorique = debounce(fetchHistorique, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const historiquesFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  const inDateRange = (value) => {
    if (!value) return !f.from && !f.to
    const normalized = String(value).slice(0, 10)
    if (f.from && normalized < f.from) return false
    if (f.to && normalized > f.to) return false
    return true
  }

  let list = historiques.value.filter((h) =>
    toStr(h.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(h.poste?.nom).includes(toStr(f.poste)) &&
    toStr(h.departement?.nom).includes(toStr(f.departement)) &&
    toStr(h.motif).includes(toStr(f.motif)) &&
    inDateRange(h.date_changement)
  )
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = getVal(a, key)
    const vb = getVal(b, key)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getVal = (h, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'employe': return `${toStr(h.employe?.nom)} ${toStr(h.employe?.prenom)}`
    case 'poste': return toStr(h.poste?.nom)
    case 'departement': return toStr(h.departement?.nom)
    case 'motif': return toStr(h.motif)
    case 'date':
    default:
      return toStr(h.date_changement)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => {
  filterEmploye.value = ''
  filters.value = { matricule: '', nom: '', poste: '', departement: '', motif: '', from: '', to: '' }
  sortKey.value = 'date'
  sortDir.value = 'desc'
}

const formatInteger = (value) =>
  new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)

const formatDate = (value) => formatDateValue(value)

const employeName = (employe) =>
  `${employe?.nom || ''} ${employe?.prenom || ''}`.trim() || 'Employé non renseigné'

const initials = (employe) =>
  employeName(employe)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'RH'

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(lastRefreshedAt.value)
})

const withReason = computed(() => historiques.value.filter((item) => Boolean(String(item.motif || '').trim())).length)
const withDepartment = computed(() => historiques.value.filter((item) => Boolean(item.departement?.nom)).length)
const thisYearMoves = computed(() => {
  const year = String(new Date().getFullYear())
  return historiques.value.filter((item) => String(item.date_changement || '').startsWith(year)).length
})

const metricCards = computed(() => [
  {
    label: 'Mouvements retournés',
    value: formatInteger(pagination.value.total || historiques.value.length),
    caption: 'Total correspondant aux filtres, toutes pages incluses',
    tag: 'Mobility',
  },
  {
    label: 'Motifs renseignés',
    value: formatInteger(withReason.value),
    caption: `${formatInteger(Math.max(historiques.value.length - withReason.value, 0))} sans motif`,
    tag: 'Quality',
  },
  {
    label: 'Mouvements cette année',
    value: formatInteger(thisYearMoves.value),
    caption: 'Transitions datées sur l’année civile en cours',
    tag: 'Year',
  },
  {
    label: 'Rattachement département',
    value: `${formatInteger(historiques.value.length ? Math.round((withDepartment.value / historiques.value.length) * 100) : 0)}%`,
    caption: 'Part des lignes avec département renseigné',
    tag: 'Coverage',
  },
])

const topPoste = computed(() => {
  const counter = new Map()
  historiques.value.forEach((item) => {
    const key = item.poste?.nom || ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const topDepartement = computed(() => {
  const counter = new Map()
  historiques.value.forEach((item) => {
    const key = item.departement?.nom || ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const overviewCards = computed(() => [
  {
    label: 'Poste le plus fréquent',
    value: topPoste.value,
    copy: 'Fonction la plus observée dans les mouvements affichés',
    tag: 'Role',
  },
  {
    label: 'Département dominant',
    value: topDepartement.value,
    copy: 'Structure la plus impactée par les mobilités',
    tag: 'Dept',
  },
])

const notes = computed(() => [
  `${formatInteger(historiquesFiltres.value.length)} mouvements visibles sur cette page.`,
  `${formatInteger(withReason.value)} lignes avec motif renseigné.`,
  `${formatInteger(thisYearMoves.value)} mobilités enregistrées cette année.`,
])

const refreshData = async () => {
  await fetchHistorique()
}

const nextPage = () => {
  if (!loading.value && pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchHistorique()
  }
}
const prevPage = () => {
  if (!loading.value && pagination.value.page > 1) {
    pagination.value.page--
    fetchHistorique()
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchHistorique()
})

watch(filters, () => {
  pagination.value.page = 1
  debouncedFetchHistorique()
}, { deep: true })
</script>

<style scoped>
.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.metric-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  padding: 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
}

.metric-chip,
.section-chip,
.overview-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.76rem;
  font-weight: 700;
}

.metric-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.metric-value {
  margin: 10px 0 8px;
  font-size: 1.82rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.metric-caption {
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.loading-card {
  min-height: 180px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.loading-title,
.overview-value,
.empty-state p {
  margin: 0;
}

.loading-title {
  font-size: 1.1rem;
  font-weight: 800;
}

.section-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.section-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.section-heading.compact {
  margin-bottom: 2px;
}

.section-heading h2 {
  font-size: 1.48rem;
}

.section-copy {
  margin: 0;
  color: var(--muted);
  font-size: 0.95rem;
  line-height: 1.65;
}

.controls-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.field-card {
  display: grid;
  gap: 8px;
}

.search-card {
  grid-column: span 1;
}

.field-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: start;
}

.table-shell {
  overflow: auto;
}

.sort-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 0;
  padding: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-transform: inherit;
  cursor: pointer;
}

.sort-button span {
  color: var(--brand-600);
  font-size: 0.72rem;
}

.history-reason {
  color: var(--muted);
}

.table-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.table-meta {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
}

.insights-card {
  position: sticky;
  top: 18px;
}

.summary-intro {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.6;
}

.overview-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.overview-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(248, 250, 252, 0.82);
}

body[data-theme='dark'] .overview-card {
  background: rgba(15, 23, 42, 0.46);
}

.overview-label {
  color: var(--muted);
  font-size: 0.82rem;
}

.overview-value {
  font-size: 1.22rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.overview-copy {
  color: var(--muted);
  font-size: 0.84rem;
  line-height: 1.5;
}

.notes-card {
  padding: 18px 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(248, 250, 252, 0.84);
}

body[data-theme='dark'] .notes-card {
  background: rgba(15, 23, 42, 0.56);
}

.notes-card h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.notes-card ul {
  margin: 14px 0 0;
  padding-left: 18px;
  color: var(--muted);
  display: grid;
  gap: 10px;
}

.empty-state {
  padding: 26px 14px;
  text-align: center;
}

.empty-state p {
  font-weight: 700;
}

@media (max-width: 1200px) {
  .metric-grid,
  .controls-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .insights-card {
    position: static;
  }
}

@media (max-width: 900px) {
  .controls-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .action-row,
  .section-heading,
  .table-footer {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
