<template>
  <div class="history-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Contract history</p>
        <h1>Historique des contrats</h1>
        <p class="hero-subtitle">
          Suivez les versions, renouvellements et jalons contractuels dans une vue plus lisible, plus
          structurée et plus proche du niveau de finition des autres écrans RH.
        </p>

        <div class="hero-pills">
          <span class="pill">Versions</span>
          <span class="pill">Chronologie</span>
          <span class="pill">Audit contractuel</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink to="/contrats" class="btn">
              <AppIcon name="file" :size="18" />
              <span>Contrats actifs</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total historique:
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

    <div v-if="loading && !historiques.length" class="card loading-card">
      <p class="loading-title">Chargement de l’historique contractuel…</p>
      <p class="muted">Les versions de contrats et renouvellements sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="highlights-grid">
        <article class="card section-card spotlight-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Latest snapshot</p>
              <h2>Dernière version enregistrée</h2>
            </div>
            <span class="section-chip">Temps réel</span>
          </div>

          <template v-if="latestHistory">
            <div class="spotlight-head">
              <div class="spotlight-avatar">{{ initials(latestHistory.employe) }}</div>
              <div class="spotlight-copy">
                <p class="spotlight-title">{{ employeName(latestHistory.employe) }}</p>
                <p class="spotlight-subtitle">
                  {{ latestHistory.numero || 'Numéro non renseigné' }} · {{ latestHistory.type_contrat || 'Type non défini' }}
                </p>
              </div>
            </div>

            <div class="spotlight-grid">
              <div class="spotlight-cell">
                <span class="spotlight-label">Contrat</span>
                <strong>{{ formatPeriod(latestHistory.date_debut, latestHistory.date_fin) }}</strong>
              </div>
              <div class="spotlight-cell">
                <span class="spotlight-label">Essai</span>
                <strong>{{ formatPeriod(latestHistory.periode_essai_debut, latestHistory.periode_essai_fin) }}</strong>
              </div>
              <div class="spotlight-cell full">
                <span class="spotlight-label">Structure</span>
                <strong>
                  {{ latestHistory.employe?.departement?.nom || 'Département non défini' }}
                  ·
                  {{ latestHistory.employe?.poste?.nom || 'Poste non défini' }}
                </strong>
              </div>
            </div>
          </template>

          <div v-else class="empty-state compact">
            <p>Aucune version chargée</p>
            <span>La synthèse se remplira dès qu’un historique sera disponible.</span>
          </div>
        </article>

        <article class="card section-card pulse-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Reading aids</p>
              <h2>Signaux de lecture</h2>
            </div>
          </div>

          <div class="signal-grid">
            <article class="signal-card signal-brand">
              <span class="signal-badge">Type</span>
              <p class="signal-label">Contrat dominant</p>
              <p class="signal-value">{{ dominantType?.label || 'Non disponible' }}</p>
              <p class="signal-copy">
                {{
                  dominantType
                    ? `${formatInteger(dominantType.count)} version(s) visibles sur la sélection.`
                    : 'Aucun type clairement représenté.'
                }}
              </p>
            </article>

            <article class="signal-card signal-neutral">
              <span class="signal-badge">Trials</span>
              <p class="signal-label">Périodes d’essai</p>
              <p class="signal-value">{{ formatInteger(trialCount) }}</p>
              <p class="signal-copy">Versions avec essai renseigné sur la page affichée.</p>
            </article>

            <article class="signal-card signal-warm">
              <span class="signal-badge">Renewal</span>
              <p class="signal-label">Échéances proches</p>
              <p class="signal-value">{{ formatInteger(endingSoonCount) }}</p>
              <p class="signal-copy">Contrats avec fin prévue dans les 90 prochains jours.</p>
            </article>
          </div>
        </article>
      </section>

      <section class="card section-card filters-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">History filters</p>
            <h2>Recherche et tri</h2>
          </div>
          <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters" :disabled="!hasFilters">
            Réinitialiser
          </button>
        </div>

        <p class="section-copy">
          Filtrez l’historique par contrat, employé, type ou structure pour isoler rapidement une
          version précise sans perdre le contexte global.
        </p>

        <div class="filter-grid">
          <label class="field-card search-card">
            <span class="field-label">Numéro</span>
            <input v-model="filters.numero" class="input" placeholder="Numéro de contrat" />
          </label>

          <label class="field-card">
            <span class="field-label">Matricule</span>
            <input v-model="filters.matricule" class="input" placeholder="Matricule employé" list="contrats-historique-matricules" />
          </label>

          <label class="field-card">
            <span class="field-label">Nom</span>
            <input v-model="filters.nom" class="input" placeholder="Nom ou prénom" />
          </label>

          <label class="field-card">
            <span class="field-label">Type</span>
            <input v-model="filters.type" class="input" placeholder="CDD, CDI, ..." list="contrats-historique-types" />
          </label>

          <label class="field-card">
            <span class="field-label">Département</span>
            <input v-model="filters.departement" class="input" placeholder="Structure" list="contrats-historique-departements" />
          </label>

          <label class="field-card">
            <span class="field-label">Poste</span>
            <input v-model="filters.poste" class="input" placeholder="Fonction" list="contrats-historique-postes" />
          </label>

          <label class="field-card">
            <span class="field-label">Entre (début)</span>
            <input v-model="filters.from" class="input" type="date" />
          </label>

          <label class="field-card">
            <span class="field-label">Et (fin)</span>
            <input v-model="filters.to" class="input" type="date" />
          </label>
        </div>

        <datalist id="contrats-historique-matricules">
          <option v-for="matricule in optionsMatricules" :key="matricule" :value="matricule" />
        </datalist>
        <datalist id="contrats-historique-types">
          <option v-for="type in optionsTypes" :key="type" :value="type" />
        </datalist>
        <datalist id="contrats-historique-departements">
          <option v-for="departement in optionsDepartements" :key="departement" :value="departement" />
        </datalist>
        <datalist id="contrats-historique-postes">
          <option v-for="poste in optionsPostes" :key="poste" :value="poste" />
        </datalist>
      </section>

      <section>
        <div class="main-column">
          <article class="card section-card table-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Contract versions</p>
                <h2>Versions et renouvellements</h2>
              </div>
              <span class="section-chip">{{ formatInteger(historiquesFiltres.length) }} visibles</span>
            </div>

            <p class="section-copy">
              La chronologie contractuelle reste lisible grâce à une hiérarchie plus nette entre
              collaborateur, période, type de contrat et date de création.
            </p>

            <div class="table-shell">
              <table class="table history-table">
                <thead>
                  <tr>
                    <th>
                      <button class="sort-button" type="button" @click="setSort('contrat')">
                        Contrat
                        <span>{{ sortLabel('contrat') }}</span>
                      </button>
                    </th>
                    <th class="numero-col">
                      <button class="sort-button" type="button" @click="setSort('numero')">
                        Numéro
                        <span>{{ sortLabel('numero') }}</span>
                      </button>
                    </th>
                    <th>
                      <button class="sort-button" type="button" @click="setSort('employe')">
                        Employé
                        <span>{{ sortLabel('employe') }}</span>
                      </button>
                    </th>
                    <th>
                      <button class="sort-button" type="button" @click="setSort('type')">
                        Type
                        <span>{{ sortLabel('type') }}</span>
                      </button>
                    </th>
                    <th class="periode-col">
                      <button class="sort-button" type="button" @click="setSort('contrat_dates')">
                        Période
                        <span>{{ sortLabel('contrat_dates') }}</span>
                      </button>
                    </th>
                    <th class="essai-col">Essai</th>
                    <th class="creation-col">
                      <button class="sort-button" type="button" @click="setSort('created_at')">
                        Création
                        <span>{{ sortLabel('created_at') }}</span>
                      </button>
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="item in historiquesFiltres" :key="item.id">
                    <td>
                      <div class="contract-badge">
                        <span class="contract-id">#{{ item.contrat_id }}</span>
                        <span class="contract-meta">{{ endingLabel(item) }}</span>
                      </div>
                    </td>
                    <td class="numero-col">
                      <span class="chip soft">{{ item.numero || '—' }}</span>
                    </td>
                    <td class="employee-col">
                      <div class="employee-cell">
                        <div class="employee-avatar">{{ initials(item.employe) }}</div>
                        <div class="employee-copy">
                          <span class="employee-name">{{ employeName(item.employe) }}</span>
                          <span class="employee-meta">
                            {{ item.employe?.matricule || 'Sans matricule' }}
                          </span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="pill type-pill">{{ item.type_contrat || '—' }}</span>
                    </td>
                    <td class="periode-col">
                      <div class="period-block">
                        <span class="period-main">{{ formatPeriod(item.date_debut, item.date_fin) }}</span>
                        <span class="period-sub">{{ periodMeta(item.date_debut, item.date_fin) }}</span>
                      </div>
                    </td>
                    <td class="essai-col">
                      <span class="chip soft">{{ formatPeriod(item.periode_essai_debut, item.periode_essai_fin) }}</span>
                    </td>
                    <td class="muted creation-col">
                      {{ formatDisplayDate(item.created_at) || '—' }}
                    </td>
                  </tr>

                  <tr v-if="!historiquesFiltres.length">
                    <td colspan="7" class="empty-state">
                      <p>Aucun historique ne correspond à la sélection actuelle.</p>
                      <span>Réduisez les filtres ou rechargez une autre page de résultats.</span>
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
        </div>

      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const historiques = ref([])
const loading = ref(false)
const lastRefreshedAt = ref(null)

const filters = ref({
  numero: '',
  matricule: '',
  nom: '',
  type: '',
  departement: '',
  poste: '',
  from: '',
  to: '',
})

const sortKey = ref('created_at')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const hasFilters = computed(() =>
  Object.values(filters.value).some((value) => Boolean(String(value || '').trim())),
)

const optionsMatricules = computed(() =>
  [...new Set(historiques.value.map((item) => item.employe?.matricule).filter(Boolean))],
)

const optionsTypes = computed(() =>
  [...new Set(historiques.value.map((item) => item.type_contrat).filter(Boolean))],
)

const optionsDepartements = computed(() =>
  [...new Set(historiques.value.map((item) => item.employe?.departement?.nom).filter(Boolean))],
)

const optionsPostes = computed(() =>
  [...new Set(historiques.value.map((item) => item.employe?.poste?.nom).filter(Boolean))],
)

const historiquesFiltres = computed(() => {
  const f = filters.value
  const toStr = (value) => String(value || '').toLowerCase()
  const inPeriodRange = (startValue, endValue) => {
    if (!f.from && !f.to) return true

    const start = startValue ? String(startValue).slice(0, 10) : ''
    const end = endValue ? String(endValue).slice(0, 10) : ''

    if (!start && !end) return false

    const periodStart = start || end
    const periodEnd = end || start || '9999-12-31'
    const filterStart = f.from || '0000-01-01'
    const filterEnd = f.to || '9999-12-31'

    if (periodEnd < filterStart) return false
    if (periodStart > filterEnd) return false
    return true
  }

  let list = historiques.value.filter((item) => (
    toStr(item.numero).includes(toStr(f.numero)) &&
    toStr(item.employe?.matricule).includes(toStr(f.matricule)) &&
    `${toStr(item.employe?.nom)} ${toStr(item.employe?.prenom)}`.includes(toStr(f.nom)) &&
    toStr(item.type_contrat).includes(toStr(f.type)) &&
    toStr(item.employe?.departement?.nom).includes(toStr(f.departement)) &&
    toStr(item.employe?.poste?.nom).includes(toStr(f.poste)) &&
    inPeriodRange(item.date_debut, item.date_fin)
  ))

  list = [...list].sort((left, right) => {
    const valueA = getVal(left, sortKey.value)
    const valueB = getVal(right, sortKey.value)
    if (valueA < valueB) return sortDir.value === 'asc' ? -1 : 1
    if (valueA > valueB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })

  return list
})

const latestHistory = computed(() => historiquesFiltres.value[0] || null)

const uniqueEmployees = computed(() =>
  new Set(historiquesFiltres.value.map((item) => item.employe_id).filter(Boolean)).size,
)

const trialCount = computed(() =>
  historiquesFiltres.value.filter((item) => item.periode_essai_debut || item.periode_essai_fin).length,
)

const currentTypes = computed(() =>
  new Set(historiquesFiltres.value.map((item) => item.type_contrat).filter(Boolean)).size,
)

const dominantType = computed(() => findDominant(historiquesFiltres.value, (item) => item.type_contrat))

const endingSoonCount = computed(() =>
  historiquesFiltres.value.filter((item) => isEndingSoon(item.date_fin)).length,
)

const recentEntries = computed(() => historiquesFiltres.value.slice(0, 5))

const activeFiltersCount = computed(() =>
  Object.values(filters.value).filter((value) => Boolean(String(value || '').trim())).length,
)

const metricCards = computed(() => [
  {
    label: 'Historiques',
    value: formatInteger(pagination.value.total || historiques.value.length),
    caption: 'Total correspondant aux filtres, toutes pages incluses',
    tag: 'Versions',
  },
  {
    label: 'Employés visibles',
    value: formatInteger(uniqueEmployees.value),
    caption: 'Profils représentés dans la page courante',
    tag: 'People',
  },
  {
    label: 'Types visibles',
    value: formatInteger(currentTypes.value),
    caption: 'Diversité des types de contrats chargés',
    tag: 'Types',
  },
  {
    label: 'Périodes d’essai',
    value: formatInteger(trialCount.value),
    caption: 'Historique avec période d’essai renseignée',
    tag: 'Trials',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Résultats visibles',
    value: formatInteger(historiquesFiltres.value.length),
    copy: 'Nombre de versions affichées après filtrage.',
    tag: 'View',
  },
  {
    label: 'Filtres actifs',
    value: formatInteger(activeFiltersCount.value),
    copy: activeFiltersCount.value ? 'La vue est resserrée par plusieurs filtres.' : 'Aucun filtre manuel actif.',
    tag: 'Filters',
  },
  {
    label: 'Page courante',
    value: `${pagination.value.page}/${pagination.value.last_page}`,
    copy: 'Position actuelle dans la pagination des historiques.',
    tag: 'Page',
  },
  {
    label: 'Ordre actif',
    value: `${sortKey.value} ${sortDir.value}`,
    copy: 'Tri appliqué sur la table principale.',
    tag: 'Sort',
  },
])

const notes = computed(() => [
  activeFiltersCount.value
    ? `${formatInteger(activeFiltersCount.value)} filtre(s) resserrent actuellement la lecture de l’historique.`
    : 'Aucun filtre actif, la page reflète la vue complète chargée.',
  dominantType.value
    ? `${dominantType.value.label} est le type de contrat le plus représenté sur cette sélection.`
    : 'Aucun type dominant ne se dégage sur la page actuelle.',
  endingSoonCount.value
    ? `${formatInteger(endingSoonCount.value)} contrat(s) arrivent à échéance dans les 90 prochains jours.`
    : 'Aucune échéance proche n’est remontée sur la page actuelle.',
])

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const fetchHistoriques = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/contrats-historiques', { params: buildHistoriqueContratParams() })
    historiques.value = data.data || []

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

const buildHistoriqueContratParams = () => ({
  page: pagination.value.page,
  ...(filters.value.numero ? { numero: filters.value.numero } : {}),
  ...(filters.value.matricule ? { matricule: filters.value.matricule } : {}),
  ...(filters.value.nom ? { nom: filters.value.nom } : {}),
  ...(filters.value.type ? { type: filters.value.type } : {}),
  ...(filters.value.departement ? { departement: filters.value.departement } : {}),
  ...(filters.value.poste ? { poste: filters.value.poste } : {}),
  ...(filters.value.from ? { from: filters.value.from } : {}),
  ...(filters.value.to ? { to: filters.value.to } : {}),
})

const refreshData = async () => {
  await fetchHistoriques()
}

const parseDate = (date) => {
  if (!date) return null
  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const formatDisplayDate = (date) => {
  const parsed = parseDate(date)
  if (!parsed) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
  }).format(parsed)
}

const formatPeriod = (start, end) => {
  const startValue = formatDisplayDate(start) || '—'
  const endValue = formatDisplayDate(end) || '—'
  return `${startValue} -> ${endValue}`
}

const periodMeta = (start, end) => {
  if (!start && !end) return 'Période non renseignée'
  if (start && !end) return 'Contrat sans fin définie'

  const startDate = parseDate(start)
  const endDate = parseDate(end)
  if (!startDate || !endDate) return 'Période renseignée'

  const diff = Math.max(1, Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)))
  return `${formatInteger(diff)} jour(s)`
}

const employeName = (employe) => {
  if (!employe) return 'Employé non renseigné'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || 'Employé non renseigné'
}

const initials = (employe) => {
  const label = employeName(employe)
  return label
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'RH'
}

const isEndingSoon = (date) => {
  const parsed = parseDate(date)
  if (!parsed) return false

  const now = new Date()
  const limit = new Date()
  limit.setDate(limit.getDate() + 90)

  return parsed >= now && parsed <= limit
}

const endingLabel = (item) => {
  if (isEndingSoon(item.date_fin)) return 'Échéance proche'
  if (item.date_fin) return 'Version close'
  return 'Sans fin définie'
}

const findDominant = (list, getter) => {
  const counts = new Map()

  for (const item of list) {
    const key = getter(item)
    if (!key) continue
    counts.set(key, (counts.get(key) || 0) + 1)
  }

  let best = null

  for (const [label, count] of counts.entries()) {
    if (!best || count > best.count) best = { label, count }
  }

  return best
}

const getVal = (item, key) => {
  const toStr = (value) => String(value || '').toLowerCase()

  switch (key) {
    case 'numero':
      return toStr(item.numero)
    case 'employe':
      return `${toStr(item.employe?.nom)} ${toStr(item.employe?.prenom)}`
    case 'type':
      return toStr(item.type_contrat)
    case 'contrat_dates':
      return toStr(item.date_debut)
    case 'contrat':
      return Number(item.contrat_id) || 0
    case 'created_at':
    default:
      return toStr(item.created_at)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')

const resetFilters = () => {
  filters.value = { numero: '', matricule: '', nom: '', type: '', departement: '', poste: '', from: '', to: '' }
  pagination.value.page = 1
  fetchHistoriques()
}

const nextPage = () => {
  if (!loading.value && pagination.value.page < pagination.value.last_page) {
    pagination.value.page += 1
    fetchHistoriques()
  }
}

const prevPage = () => {
  if (!loading.value && pagination.value.page > 1) {
    pagination.value.page -= 1
    fetchHistoriques()
  }
}

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

onMounted(fetchHistoriques)

watch(filters, () => {
  pagination.value.page = 1
  fetchHistoriques()
}, { deep: true })
</script>

<style scoped>
.employee-name,
.employee-meta,
.signal-label,
.signal-copy,
.spotlight-label,
.spotlight-title,
.spotlight-subtitle,
.period-sub,
.activity-title,
.activity-subtitle,
.activity-meta {
  margin: 0;
}

.highlights-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
  gap: 16px;
}

.signal-value {
  margin: 10px 0 8px;
  font-size: 1.82rem;
  font-weight: 800;
  letter-spacing: 0;
}

.spotlight-card,
.pulse-card {
  min-height: 100%;
}

.spotlight-head {
  display: flex;
  align-items: center;
  gap: 14px;
}

.spotlight-avatar,
.employee-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: var(--brand-500);
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 800;
}

.spotlight-copy {
  display: grid;
  gap: 4px;
}

.spotlight-title,
.activity-title {
  font-size: 1.02rem;
  font-weight: 800;
  letter-spacing: 0;
}

.spotlight-subtitle,
.activity-subtitle,
.activity-meta,
.period-sub {
  color: var(--muted);
  font-size: 0.88rem;
  line-height: 1.55;
}

.spotlight-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.spotlight-cell {
  display: grid;
  gap: 6px;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(248, 250, 252, 0.82);
}

body[data-theme='dark'] .spotlight-cell {
  background: rgba(15, 23, 42, 0.5);
}

.spotlight-cell.full {
  grid-column: 1 / -1;
}

.spotlight-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.spotlight-cell strong {
  font-size: 0.97rem;
  letter-spacing: 0;
}

.signal-grid {
  display: grid;
  gap: 12px;
}

.signal-card {
  display: grid;
  gap: 10px;
  padding: 16px 18px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
}

.signal-card.signal-brand {
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(20, 184, 166, 0.03)),
    var(--hero-band-bg),
    var(--panel);
}

.signal-card.signal-neutral {
  background:
    linear-gradient(135deg, rgba(148, 163, 184, 0.14), rgba(255, 255, 255, 0.03)),
    var(--hero-band-bg),
    var(--panel);
}

.signal-card.signal-warm {
  background:
    linear-gradient(135deg, rgba(245, 158, 11, 0.16), rgba(255, 255, 255, 0.03)),
    var(--hero-band-bg),
    var(--panel);
}

.signal-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.signal-copy {
  color: var(--muted);
  font-size: 0.88rem;
  line-height: 1.55;
}

.sidebar-column {
  position: relative;
  z-index: 1;
}

.table-shell {
  max-width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 4px;
}

.table-card {
  min-width: 0;
  overflow: hidden;
}

.history-table {
  min-width: 1180px;
}

.history-table th,
.history-table td {
  vertical-align: top;
}

.history-table tbody tr:hover {
  background: rgba(79, 70, 229, 0.04);
}

body[data-theme='dark'] .history-table tbody tr:hover {
  background: rgba(79, 70, 229, 0.08);
}

.contract-badge {
  display: grid;
  gap: 4px;
  min-width: 90px;
}

.contract-id {
  font-weight: 800;
  letter-spacing: 0;
}

.contract-meta {
  color: var(--muted);
  font-size: 0.8rem;
}

.employee-col {
  min-width: 240px;
}

.numero-col {
  min-width: 190px;
}

.periode-col {
  min-width: 230px;
}

.essai-col {
  min-width: 190px;
}

.creation-col {
  min-width: 140px;
  white-space: nowrap;
}

.employee-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-copy {
  display: grid;
  gap: 4px;
}

.employee-name {
  font-weight: 700;
}

.employee-meta {
  color: var(--muted);
  font-size: 0.8rem;
}

.type-pill,
.chip.soft {
  background: rgba(79, 70, 229, 0.08);
}

.numero-col .chip.soft,
.essai-col .chip.soft {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.period-block {
  display: grid;
  gap: 4px;
  min-width: 190px;
}

.period-main {
  font-weight: 700;
}

.activity-list {
  position: relative;
  display: grid;
  gap: 14px;
}

.activity-item {
  display: grid;
  grid-template-columns: 10px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.activity-dot {
  width: 10px;
  height: 10px;
  margin-top: 6px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--brand-500), #14b8a6);
  box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.08);
}

.activity-copy {
  display: grid;
  gap: 4px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
}

.activity-item:last-child .activity-copy {
  padding-bottom: 0;
  border-bottom: 0;
}

.notes-card ul {
  margin: 0;
  padding-left: 18px;
  color: var(--muted);
  display: grid;
  gap: 10px;
}

.empty-state.compact {
  padding: 14px 0 2px;
  text-align: left;
}

@media (max-width: 1280px) {
  .highlights-grid,
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .spotlight-grid {
    grid-template-columns: 1fr;
  }

  .spotlight-cell.full {
    grid-column: auto;
  }
}

@media (max-width: 680px) {
  .employee-cell {
    align-items: flex-start;
  }
}
</style>
