<template>
  <div class="documents-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Employee files</p>
        <h1>Documents RH</h1>
        <p class="hero-subtitle">
          Centralisez les pièces RH pour simplifier la consultation, sécuriser le suivi des
          expirations et fluidifier les opérations administratives.
        </p>

        <div class="hero-pills">
          <span class="pill">Archivage RH</span>
          <span class="pill">Conformité</span>
          <span class="pill">Suivi expirations</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/documents/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Uploader</span>
            </RouterLink>
          </div>

          <label class="field-card">
            <span class="field-label">Employé</span>
            <select class="select" v-model="filterEmploye" @change="debouncedFetchDocs">
              <option value="">Tous les employés</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total documents:
              <strong>{{ formatInteger(pagination.total || docs.length) }}</strong>
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

    <template v-if="loading && !docs.length">
      <div class="card loading-card">
        <p class="loading-title">Chargement des documents…</p>
        <p class="muted">Les fichiers RH sont en cours de synchronisation.</p>
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
          Filtrez par collaborateur, type, nom de fichier ou date d’expiration pour retrouver vite le
          bon document.
        </p>

        <div class="controls-grid">
          <label class="field-card">
            <span class="field-label">Matricule</span>
            <input class="input" placeholder="EMP-001" v-model="filters.matricule" />
          </label>

          <label class="field-card">
            <span class="field-label">Nom</span>
            <input class="input" placeholder="Nom ou prénom" v-model="filters.nom" />
          </label>

          <label class="field-card">
            <span class="field-label">Type</span>
            <input class="input" placeholder="Contrat, CIN..." v-model="filters.type" />
          </label>

          <label class="field-card">
            <span class="field-label">Fichier</span>
            <input class="input" placeholder="Nom du fichier" v-model="filters.fichier" />
          </label>

          <label class="field-card">
            <span class="field-label">Expiration</span>
            <input class="input" placeholder="YYYY-MM-DD" v-model="filters.date_expiration" />
          </label>
        </div>
      </section>

      <section class="content-grid">
        <article class="card section-card table-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Document list</p>
              <h2>Pièces et justificatifs</h2>
            </div>
            <span class="section-chip">{{ formatInteger(docsFiltres.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Tableau centralisé des documents rattachés aux employés avec accès direct aux fichiers.
          </p>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
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
                  <th>
                    <button class="sort-button" type="button" @click="setSort('fichier')">
                      Nom de fichier
                      <span>{{ sortLabel('fichier') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('expiration')">
                      Expiration
                      <span>{{ sortLabel('expiration') }}</span>
                    </button>
                  </th>
                  <th class="actions-col">Fichier</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="d in docsFiltres" :key="d.id">
                  <td>{{ d.employe ? `${d.employe.matricule} - ${d.employe.nom} ${d.employe.prenom || ''}` : '—' }}</td>
                  <td>{{ d.type_document || '—' }}</td>
                  <td>{{ fileName(d.fichier) }}</td>
                  <td>{{ formatDate(d.date_expiration) || '—' }}</td>
                  <td class="actions-col">
                    <a class="btn btn-secondary btn-xs" :href="documentUrl(d)" target="_blank" rel="noopener">Ouvrir</a>
                  </td>
                </tr>

                <tr v-if="!docsFiltres.length">
                  <td colspan="5" class="empty-state">
                    <p>Aucun document ne correspond à la sélection actuelle.</p>
                    <span>Ajustez les filtres ou importez une nouvelle pièce.</span>
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
              <button class="btn btn-secondary btn-sm" :disabled="pagination.page <= 1" @click="prevPage">Précédent</button>
              <button class="btn btn-secondary btn-sm" :disabled="pagination.page >= pagination.last_page" @click="nextPage">Suivant</button>
            </div>
          </div>
        </article>

        <aside class="card section-card insights-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Résumé documents</h2>
            </div>
          </div>

          <p class="summary-intro">
            Lecture rapide des volumes, de la qualité des métadonnées et des dates de validité.
          </p>

          <div class="overview-grid">
            <article v-for="card in overviewCards" :key="card.label" class="overview-card">
              <span class="overview-chip">{{ card.tag }}</span>
              <p class="overview-label">{{ card.label }}</p>
              <p class="overview-value">{{ card.value }}</p>
              <p class="overview-copy">{{ card.copy }}</p>
            </article>
          </div>

          <div class="notes-card">
            <h3>Repères rapides</h3>
            <ul>
              <li v-for="note in notes" :key="note">{{ note }}</li>
            </ul>
          </div>
        </aside>
      </section>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api, { resolveBackendAssetUrl } from '../services/api'
import { debounce } from '../utils/debounce'
import { RouterLink } from 'vue-router'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatDateValue } from '../utils/formatters'

const docs = ref([])
const employes = ref([])
const filterEmploye = ref('')
const filters = ref({ matricule: '', nom: '', type: '', fichier: '', date_expiration: '' })
const loading = ref(false)
const lastRefreshedAt = ref(null)
const sortKey = ref('employe')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const hasFilters = computed(() =>
  Boolean(
    filterEmploye.value ||
    filters.value.matricule ||
    filters.value.nom ||
    filters.value.type ||
    filters.value.fichier ||
    filters.value.date_expiration,
  ),
)

const fetchDocs = async () => {
  loading.value = true
  try {
    const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
    const { data } = await api.get('/v1/documents', { params })
    docs.value = data.data || data || []
    if (data.meta) {
      pagination.value = {
        page: data.meta.current_page,
        last_page: data.meta.last_page,
        total: data.meta.total
      }
    } else if (data.current_page !== undefined) {
      pagination.value = {
        page: data.current_page,
        last_page: data.last_page,
        total: data.total
      }
    }
    lastRefreshedAt.value = new Date()
  } finally {
    loading.value = false
  }
}

const debouncedFetchDocs = debounce(fetchDocs, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

onMounted(async () => {
  await Promise.all([fetchEmployes(), fetchDocs()])
})

const fileName = (path) => {
  if (!path) return '—'
  const parts = String(path).split(/[\\/]/)
  return parts[parts.length - 1] || path
}

const formatDate = (d) => {
  return formatDateValue(d)
}

const documentUrl = (doc) => resolveBackendAssetUrl(doc?.url)

const docsFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = docs.value.filter((d) =>
    toStr(d.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(d.type_document).includes(toStr(f.type)) &&
    toStr(fileName(d.fichier)).includes(toStr(f.fichier)) &&
    toStr(formatDate(d.date_expiration)).includes(toStr(f.date_expiration))
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

const getVal = (d, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'type': return toStr(d.type_document)
    case 'fichier': return toStr(fileName(d.fichier))
    case 'expiration': return toStr(formatDate(d.date_expiration))
    case 'employe':
    default:
      return `${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => {
  filterEmploye.value = ''
  filters.value = { matricule: '', nom: '', type: '', fichier: '', date_expiration: '' }
  sortKey.value = 'employe'
  sortDir.value = 'asc'
}

const formatInteger = (value) =>
  new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(lastRefreshedAt.value)
})

const withExpirationCount = computed(() => docs.value.filter((d) => Boolean(d.date_expiration)).length)
const expiredCount = computed(() => {
  const now = new Date()
  return docs.value.filter((d) => {
    if (!d.date_expiration) return false
    const date = new Date(d.date_expiration)
    return !Number.isNaN(date.getTime()) && date < now
  }).length
})
const expiringSoonCount = computed(() => {
  const now = new Date()
  const threshold = new Date(now)
  threshold.setDate(threshold.getDate() + 30)
  return docs.value.filter((d) => {
    if (!d.date_expiration) return false
    const date = new Date(d.date_expiration)
    return !Number.isNaN(date.getTime()) && date >= now && date <= threshold
  }).length
})

const metricCards = computed(() => [
  {
    label: 'Documents visibles',
    value: formatInteger(docsFiltres.value.length),
    caption: 'Résultats sur la page courante après filtres',
    tag: 'Files',
  },
  {
    label: 'Avec expiration',
    value: formatInteger(withExpirationCount.value),
    caption: `${formatInteger(Math.max(docs.value.length - withExpirationCount.value, 0))} sans date limite`,
    tag: 'Metadata',
  },
  {
    label: 'Expirés',
    value: formatInteger(expiredCount.value),
    caption: 'Documents dont la date est déjà dépassée',
    tag: 'Expired',
  },
  {
    label: 'Échéance 30 jours',
    value: formatInteger(expiringSoonCount.value),
    caption: 'Documents expirant prochainement',
    tag: 'Alert',
  },
])

const topType = computed(() => {
  const counter = new Map()
  docs.value.forEach((item) => {
    const key = item.type_document || ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const topEmploye = computed(() => {
  const counter = new Map()
  docs.value.forEach((item) => {
    const key = item.employe ? `${item.employe.matricule || ''} ${item.employe.nom || ''}`.trim() : ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const overviewCards = computed(() => [
  {
    label: 'Type dominant',
    value: topType.value,
    copy: 'Type documentaire le plus représenté actuellement',
    tag: 'Type',
  },
  {
    label: 'Employé le plus documenté',
    value: topEmploye.value,
    copy: 'Collaborateur avec le plus de pièces rattachées',
    tag: 'Employee',
  },
])

const notes = computed(() => [
  `${formatInteger(docsFiltres.value.length)} documents visibles sur cette page.`,
  `${formatInteger(expiringSoonCount.value)} documents expirent sous 30 jours.`,
  `${formatInteger(expiredCount.value)} documents sont déjà expirés.`,
])

const refreshData = async () => {
  await fetchDocs()
}
const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchDocs()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchDocs()
  }
}
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

.field-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(320px, 0.9fr);
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

.actions-col {
  width: 1%;
  white-space: nowrap;
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
  grid-template-columns: repeat(2, minmax(0, 1fr));
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
  .controls-grid,
  .overview-grid {
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
