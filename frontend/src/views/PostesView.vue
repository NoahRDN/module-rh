<template>
  <div class="postes-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Roles and functions</p>
        <h1>Postes</h1>
        <p class="hero-subtitle">
          Pilotez le référentiel des fonctions avec leur rattachement départemental et leur catégorie
          afin de garder une structure RH cohérente et exploitable.
        </p>

        <div class="hero-pills">
          <span class="pill">Référentiel fonctions</span>
          <span class="pill">Structure par département</span>
          <span class="pill">Catégories RH</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/postes/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total postes:
              <strong>{{ formatInteger(pagination.total || postes.length) }}</strong>
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

    <div v-if="loading && !postes.length" class="card loading-card">
      <p class="loading-title">Chargement des postes…</p>
      <p class="muted">Les fonctions et leurs rattachements sont en cours de synchronisation.</p>
    </div>

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
          Filtrez les fonctions par nom, catégorie ou département pour retrouver rapidement les postes à
          mettre à jour.
        </p>

        <div class="controls-grid">
          <label class="field-card search-card">
            <span class="field-label">Nom du poste</span>
            <input v-model="filters.nom" class="input" placeholder="Ex: Responsable Paie" />
          </label>

          <label class="field-card">
            <span class="field-label">Département</span>
            <select v-model="filterDep" class="select" @change="onDepartmentFilterChange">
              <option value="">Tous les départements</option>
              <option v-for="dep in departements" :key="dep.id" :value="String(dep.id)">{{ dep.nom }}</option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Catégorie</span>
            <input v-model="filters.categorie" class="input" placeholder="Cadres, TAM, ..." list="cat-list" />
          </label>

          <label class="field-card">
            <span class="field-label">Tri</span>
            <select v-model="sortKey" class="select">
              <option value="nom">Nom</option>
              <option value="departement">Département</option>
              <option value="categorie">Catégorie</option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Ordre</span>
            <select v-model="sortDir" class="select">
              <option value="asc">Croissant</option>
              <option value="desc">Décroissant</option>
            </select>
          </label>
        </div>

        <datalist id="cat-list">
          <option v-for="c in categories" :key="c" :value="c" />
        </datalist>
      </section>

      <section class="content-grid">
        <article class="card section-card table-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Role list</p>
              <h2>Fonctions et rattachements</h2>
            </div>
            <span class="section-chip">{{ formatInteger(postesFiltrees.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Vue consolidée des postes pour garantir la cohérence entre fonction, catégorie et structure
            départementale.
          </p>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('nom')">
                      Nom
                      <span>{{ sortLabel('nom') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('departement')">
                      Département
                      <span>{{ sortLabel('departement') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('categorie')">
                      Catégorie
                      <span>{{ sortLabel('categorie') }}</span>
                    </button>
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="poste in postesFiltrees" :key="poste.id">
                  <td class="role-name">
                    <span class="chip">{{ poste.nom }}</span>
                  </td>
                  <td class="role-dept">{{ poste.departement?.nom || '—' }}</td>
                  <td>
                    <span class="pill">{{ poste.categorie || '—' }}</span>
                  </td>
                </tr>

                <tr v-if="!postesFiltrees.length">
                  <td colspan="3" class="empty-state">
                    <p>Aucun poste ne correspond à la sélection actuelle.</p>
                    <span>Essayez un autre filtre ou ajoutez un nouveau poste.</span>
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
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const departements = ref([])
const postes = ref([])
const categories = ref([])
const defaultCategories = ['Ouvriers', 'Employés', 'TAM', 'Cadres', 'Dirigeants']
const filterDep = ref('')
const loading = ref(false)
const lastRefreshedAt = ref(null)

const filters = ref({
  nom: '',
  categorie: '',
})

const sortKey = ref('nom')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const hasFilters = computed(() => Boolean(filters.value.nom || filters.value.categorie || filterDep.value))

const postesFiltrees = computed(() => {
  const toSearch = (value) => String(value || '').toLowerCase()
  const nameFilter = toSearch(filters.value.nom)
  const categoryFilter = toSearch(filters.value.categorie)

  let list = postes.value.filter((item) =>
    toSearch(item.nom).includes(nameFilter) &&
    toSearch(item.categorie).includes(categoryFilter),
  )

  list = [...list].sort((left, right) => {
    const valueA = getSortValue(left, sortKey.value)
    const valueB = getSortValue(right, sortKey.value)

    if (valueA < valueB) return sortDir.value === 'asc' ? -1 : 1
    if (valueA > valueB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })

  return list
})

const departmentsVisible = computed(() =>
  new Set(postesFiltrees.value.map((item) => item.departement?.nom).filter(Boolean)).size,
)

const categoriesVisible = computed(() =>
  new Set(postesFiltrees.value.map((item) => item.categorie).filter(Boolean)).size,
)

const uncategorizedCount = computed(() =>
  postesFiltrees.value.filter((item) => !String(item.categorie || '').trim()).length,
)

const dominantCategory = computed(() => findDominant(postesFiltrees.value, (item) => item.categorie))
const dominantDepartment = computed(() => findDominant(postesFiltrees.value, (item) => item.departement?.nom))

const metricCards = computed(() => [
  {
    label: 'Postes',
    value: formatInteger(pagination.value.total || postes.value.length),
    caption: 'Fonctions référencées dans la structure RH',
    tag: 'Roles',
  },
  {
    label: 'Départements visibles',
    value: formatInteger(departmentsVisible.value),
    caption: 'Répartition départementale sur la vue courante',
    tag: 'Structure',
  },
  {
    label: 'Catégories visibles',
    value: formatInteger(categoriesVisible.value),
    caption: `${formatInteger(uncategorizedCount.value)} poste(s) sans catégorie`,
    tag: 'Categories',
  },
  {
    label: 'Résultats visibles',
    value: formatInteger(postesFiltrees.value.length),
    caption: 'Nombre affiché après filtres actifs',
    tag: 'View',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Postes affichés',
    value: formatInteger(postesFiltrees.value.length),
    copy: 'Volume de fonctions visible sur la sélection actuelle.',
    tag: 'List',
  },
  {
    label: 'Catégorie dominante',
    value: dominantCategory.value?.label || 'Non disponible',
    copy: dominantCategory.value ? `${formatInteger(dominantCategory.value.count)} poste(s)` : 'Aucune catégorie dominante.',
    tag: 'Families',
  },
  {
    label: 'Département dominant',
    value: dominantDepartment.value?.label || 'Non disponible',
    copy: dominantDepartment.value ? `${formatInteger(dominantDepartment.value.count)} poste(s)` : 'Aucune dominance détectée.',
    tag: 'Teams',
  },
  {
    label: 'Filtrage actif',
    value: hasFilters.value ? 'Oui' : 'Non',
    copy: hasFilters.value ? 'La vue est restreinte par des filtres.' : 'Vue globale sans restriction.',
    tag: 'State',
  },
])

const notes = computed(() => [
  hasFilters.value
    ? 'Un ou plusieurs filtres sont actifs sur la vue actuelle.'
    : 'Aucun filtre actif, la vue couvre l’ensemble des postes chargés.',
  dominantCategory.value
    ? `La catégorie "${dominantCategory.value.label}" est la plus représentée.`
    : 'Aucune catégorie dominante n’est détectée pour le moment.',
  uncategorizedCount.value > 0
    ? `${formatInteger(uncategorizedCount.value)} poste(s) sans catégorie méritent une revue.`
    : 'Tous les postes visibles possèdent une catégorie renseignée.',
])

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const fetchPostes = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.value.page,
      ...(filterDep.value ? { departement_id: filterDep.value } : {}),
      ...(filters.value.nom ? { nom: filters.value.nom } : {}),
      ...(filters.value.categorie ? { categorie: filters.value.categorie } : {}),
    }
    const { data } = await api.get('/v1/postes', { params })

    postes.value = data.data || []

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

const fetchDepartements = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || []
}

const fetchCategories = async () => {
  try {
    const { data } = await api.get('/v1/categories-postes')
    const payload = data || []
    categories.value = payload.length ? payload.map((item) => item.nom) : defaultCategories
  } catch {
    categories.value = defaultCategories
  }
}

const refreshData = async () => {
  await fetchPostes()
}

const onDepartmentFilterChange = async () => {
  pagination.value.page = 1
  await fetchPostes()
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

const getSortValue = (item, key) => {
  const toSearch = (value) => String(value || '').toLowerCase()

  if (key === 'departement') return toSearch(item.departement?.nom)
  if (key === 'categorie') return toSearch(item.categorie)
  return toSearch(item.nom)
}

const resetFilters = () => {
  filters.value = { nom: '', categorie: '' }
  filterDep.value = ''
  sortKey.value = 'nom'
  sortDir.value = 'asc'
  pagination.value.page = 1
  fetchPostes()
}

const nextPage = () => {
  if (!loading.value && pagination.value.page < pagination.value.last_page) {
    pagination.value.page += 1
    fetchPostes()
  }
}

const prevPage = () => {
  if (!loading.value && pagination.value.page > 1) {
    pagination.value.page -= 1
    fetchPostes()
  }
}

const findDominant = (list, accessor) => {
  const counts = new Map()

  list.forEach((item) => {
    const value = accessor(item)
    if (!value) return
    counts.set(value, (counts.get(value) || 0) + 1)
  })

  let dominant = null
  counts.forEach((count, label) => {
    if (!dominant || count > dominant.count) {
      dominant = { label, count }
    }
  })

  return dominant
}

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

onMounted(async () => {
  await fetchDepartements()
  await fetchCategories()
  await fetchPostes()
})

watch(filters, () => {
  pagination.value.page = 1
  fetchPostes()
}, { deep: true })
</script>

<style scoped>
.postes-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 24px;
}

.hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 18px;
  padding: 28px;
  border: 1px solid rgba(79, 70, 229, 0.14);
  border-radius: 30px;
  background:var(--purple-100);
  box-shadow: var(--shadow-lg);
}

body[data-theme='dark'] .hero {
  background:
    var(--hero-band-bg);
}

.hero-copy {
  max-width: 760px;
}

.hero-kicker,
.section-kicker,
.metric-label,
.metric-caption,
.hero-meta,
.summary-intro,
.overview-label,
.overview-copy,
.empty-state span {
  margin: 0;
}

.hero-kicker,
.section-kicker {
  color: var(--brand-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.hero h1,
.section-heading h2 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero h1 {
  font-size: clamp(2rem, 3vw, 2.9rem);
}

.hero-subtitle {
  margin: 12px 0 0;
  max-width: 700px;
  color: var(--muted);
  font-size: 1rem;
  line-height: 1.7;
}

.hero-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.hero-actions {
  display: flex;
  min-width: 320px;
  max-width: 360px;
  flex-direction: column;
  gap: 12px;
}

.filters-panel {
  display: grid;
  gap: 14px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .filters-panel {
  background: rgba(15, 23, 42, 0.72);
}

.action-row {
  display: flex;
  gap: 10px;
}

.action-row > * {
  flex: 1;
}

.hero-meta-list {
  display: grid;
  gap: 6px;
}

.hero-meta {
  color: var(--muted);
  font-size: 0.85rem;
}

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

.role-name {
  width: 34%;
}

.role-dept {
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
  .hero {
    padding: 22px;
  }

  .hero-actions {
    min-width: 100%;
    max-width: none;
  }

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
