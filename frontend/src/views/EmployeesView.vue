<template>
  <div class="employees-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Employee directory</p>
        <h1>Employés</h1>
        <p class="hero-subtitle">
          Gérez l’annuaire RH, les postes, les départements et le statut des collaborateurs dans une
          vue plus structurée, plus lisible et plus cohérente avec le reste du produit.
        </p>

        <div class="hero-pills">
          <span class="pill">Annuaire unifié</span>
          <span class="pill">Recherche rapide</span>
          <span class="pill">Filtres multicritères</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/employes/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouvel employé</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total annuaire:
              <strong>{{ formatInteger(pagination.total || employes.length) }}</strong>
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

    <div v-if="loading && !employes.length" class="card loading-card">
      <p class="loading-title">Chargement de l’annuaire RH…</p>
      <p class="muted">Les collaborateurs, statuts et informations de structure sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="card section-card filters-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Directory controls</p>
            <h2>Recherche et filtres</h2>
          </div>
          <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters" :disabled="!hasAnyFilters">
            Réinitialiser
          </button>
        </div>

        <p class="section-copy">
          Combinez la recherche globale avec les filtres métier pour isoler rapidement un profil, une
          équipe ou une catégorie de poste.
        </p>

        <div class="filter-grid">
          <label class="filter-card search-card">
            <span class="field-label">Recherche globale</span>
            <div class="search-wrap">
              <input
                v-model="search"
                class="input"
                placeholder="Nom, prénom, matricule ou poste"
                @input="handleSearch"
                @focus="searchFocus = true"
                @blur="handleSearchBlur"
              />

              <div v-if="searchFocus && searchSuggestions.length" class="search-suggestions">
                <div class="suggestion-header">
                  <span>Suggestions</span>
                  <span class="badge">{{ searchSuggestions.length }}</span>
                </div>

                <button
                  v-for="emp in searchSuggestions"
                  :key="emp.id"
                  class="suggestion-row"
                  type="button"
                  @mousedown.prevent="applySuggestion(emp)"
                >
                  <span class="suggestion-avatar">{{ initials(emp) }}</span>
                  <span class="suggestion-copy">
                    <span class="suggestion-name">{{ emp.nom }} {{ emp.prenom }}</span>
                    <span class="suggestion-meta">
                      {{ emp.matricule || 'Sans matricule' }} · {{ emp.poste?.nom || 'Poste non défini' }}
                    </span>
                  </span>
                  <span class="pill">{{ emp.departement?.nom || 'Département' }}</span>
                </button>
              </div>
            </div>
          </label>

          <label class="filter-card">
            <span class="field-label">Matricule</span>
            <input v-model="filters.matricule" class="input" placeholder="EMP-001" list="matricules-list" />
          </label>

          <label class="filter-card">
            <span class="field-label">Nom / prénom</span>
            <input v-model="filters.nom" class="input" placeholder="Recherche nominative" />
          </label>

          <label class="filter-card">
            <span class="field-label">Email</span>
            <input v-model="filters.email" class="input" placeholder="adresse@email.test" />
          </label>

          <label class="filter-card">
            <span class="field-label">Poste</span>
            <input v-model="filters.poste" class="input" placeholder="Fonction" list="postes-list" />
          </label>

          <label class="filter-card">
            <span class="field-label">Département</span>
            <input v-model="filters.departement" class="input" placeholder="Structure" list="departements-list" />
          </label>

          <label class="filter-card">
            <span class="field-label">Catégorie</span>
            <input v-model="filters.categorie" class="input" placeholder="Famille ou niveau" list="categories-list" />
          </label>
        </div>

        <datalist id="matricules-list">
          <option v-for="m in optionsMatricules" :key="m" :value="m" />
        </datalist>
        <datalist id="postes-list">
          <option v-for="p in optionsPostes" :key="p" :value="p" />
        </datalist>
        <datalist id="departements-list">
          <option v-for="d in optionsDepartements" :key="d" :value="d" />
        </datalist>
        <datalist id="categories-list">
          <option v-for="c in optionsCategories" :key="c" :value="c" />
        </datalist>
      </section>

      <section class="content-grid">
        <article class="card section-card table-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Employee list</p>
              <h2>Annuaire des collaborateurs</h2>
            </div>
            <span class="section-chip">{{ formatInteger(filteredEmployes.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Le tableau centralise les informations essentielles pour passer rapidement du repérage à la
            consultation détaillée d’un collaborateur.
          </p>

          <div class="table-shell">
            <table class="table employee-table">
              <thead>
                <tr>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('nom')">
                      Collaborateur
                      <span>{{ sortLabel('nom') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('matricule')">
                      Matricule
                      <span>{{ sortLabel('matricule') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('poste')">
                      Poste
                      <span>{{ sortLabel('poste') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('categorie')">
                      Catégorie
                      <span>{{ sortLabel('categorie') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('departement')">
                      Département
                      <span>{{ sortLabel('departement') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('actif')">
                      Statut
                      <span>{{ sortLabel('actif') }}</span>
                    </button>
                  </th>
                  <th class="actions-col">Fiche</th>
                </tr>
              </thead>

              <tbody v-if="loading">
                <tr v-for="n in 5" :key="n" class="loading-row">
                  <td>
                    <div class="employee-cell skeleton-line">
                      <div class="employee-avatar skeleton-circle"></div>
                      <div class="employee-main">
                        <span class="skeleton-bar wide"></span>
                        <span class="skeleton-bar short"></span>
                      </div>
                    </div>
                  </td>
                  <td><span class="skeleton-bar short"></span></td>
                  <td><span class="skeleton-bar medium"></span></td>
                  <td><span class="skeleton-bar medium"></span></td>
                  <td><span class="skeleton-bar medium"></span></td>
                  <td><span class="skeleton-bar short"></span></td>
                  <td class="actions-col"><span class="skeleton-bar short"></span></td>
                </tr>
              </tbody>

              <tbody v-else>
                <tr v-for="emp in filteredEmployes" :key="emp.id">
                  <td>
                    <div class="employee-cell">
                      <img v-if="emp.photo" :src="emp.photo" alt="photo" class="employee-avatar employee-photo" />
                      <div v-else class="employee-avatar employee-avatar-fallback">{{ initials(emp) }}</div>
                      <div class="employee-main">
                        <p class="employee-name">{{ emp.nom }} {{ emp.prenom }}</p>
                        <span class="employee-sub">{{ emp.email || 'Email non renseigné' }}</span>
                      </div>
                    </div>
                  </td>
                  <td>{{ emp.matricule || '—' }}</td>
                  <td>
                    <span class="chip">{{ emp.poste?.nom || '—' }}</span>
                  </td>
                  <td>
                    <span class="chip">{{ emp.poste?.categorie || '—' }}</span>
                  </td>
                  <td>{{ emp.departement?.nom || '—' }}</td>
                  <td>
                    <span class="pill" :class="emp.actif ? 'green' : 'red'">
                      {{ emp.actif ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="actions-col">
                    <RouterLink :to="`/employes/${emp.id}`" class="btn btn-secondary btn-sm">
                      Voir
                    </RouterLink>
                  </td>
                </tr>

                <tr v-if="!filteredEmployes.length">
                  <td colspan="7" class="empty-state">
                    <p>Aucun employé ne correspond à la sélection actuelle.</p>
                    <span>Réduisez les filtres ou relancez une recherche plus large.</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="table-footer">
            <p class="table-meta">
              Page <strong>{{ pagination.page }}</strong> sur <strong>{{ pagination.last_page }}</strong>
              · {{ formatInteger(pagination.total) }} collaborateurs dans l’annuaire
            </p>

            <div class="table-actions">
              <button class="btn btn-secondary btn-sm" type="button" :disabled="pagination.page <= 1" @click="prevPage">
                Précédent
              </button>
              <button
                class="btn btn-secondary btn-sm"
                type="button"
                :disabled="pagination.page >= pagination.last_page"
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const employes = ref([])
const search = ref('')
const loading = ref(false)
const searchFocus = ref(false)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const lastRefreshedAt = ref(null)

const filters = ref({
  matricule: '',
  nom: '',
  email: '',
  poste: '',
  departement: '',
  categorie: '',
})

const sortKey = ref('matricule')
const sortDir = ref('asc')
let searchTimer = null

const optionsMatricules = computed(() => [...new Set(employes.value.map((e) => e.matricule).filter(Boolean))])
const optionsPostes = computed(() => [...new Set(employes.value.map((e) => e.poste?.nom).filter(Boolean))])
const optionsDepartements = computed(() => [...new Set(employes.value.map((e) => e.departement?.nom).filter(Boolean))])
const optionsCategories = computed(() => [...new Set(employes.value.map((e) => e.poste?.categorie).filter(Boolean))])

const searchSuggestions = computed(() => {
  if (!search.value) return []
  const term = search.value.toLowerCase()
  return employes.value
    .filter((emp) =>
      (`${emp.nom || ''} ${emp.prenom || ''}`.toLowerCase().includes(term)) ||
      (emp.matricule || '').toLowerCase().includes(term) ||
      (emp.poste?.nom || '').toLowerCase().includes(term),
    )
    .slice(0, 6)
})

const filteredEmployes = computed(() => {
  const toSearch = (value) => String(value || '').toLowerCase()
  const f = filters.value

  let list = employes.value.filter((emp) => (
    toSearch(emp.matricule).includes(toSearch(f.matricule)) &&
    `${toSearch(emp.nom)} ${toSearch(emp.prenom)}`.includes(toSearch(f.nom)) &&
    toSearch(emp.email).includes(toSearch(f.email)) &&
    toSearch(emp.poste?.nom).includes(toSearch(f.poste)) &&
    toSearch(emp.poste?.categorie).includes(toSearch(f.categorie)) &&
    toSearch(emp.departement?.nom).includes(toSearch(f.departement))
  ))

  list = [...list].sort((left, right) => {
    const valA = getSortVal(left, sortKey.value)
    const valB = getSortVal(right, sortKey.value)

    if (valA < valB) return sortDir.value === 'asc' ? -1 : 1
    if (valA > valB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })

  return list
})

const activeVisibleCount = computed(() => filteredEmployes.value.filter((emp) => emp.actif).length)
const departmentCount = computed(() => countDistinct(filteredEmployes.value, (emp) => emp.departement?.nom))
const positionCount = computed(() => countDistinct(filteredEmployes.value, (emp) => emp.poste?.nom))
const activeFiltersCount = computed(() => [
  search.value,
  filters.value.matricule,
  filters.value.nom,
  filters.value.email,
  filters.value.poste,
  filters.value.departement,
  filters.value.categorie,
].filter(Boolean).length)

const dominantDepartment = computed(() => findDominant(filteredEmployes.value, (emp) => emp.departement?.nom))
const dominantCategory = computed(() => findDominant(filteredEmployes.value, (emp) => emp.poste?.categorie))

const hasAnyFilters = computed(() => activeFiltersCount.value > 0)

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const metricCards = computed(() => [
  {
    label: 'Collaborateurs',
    value: formatInteger(pagination.value.total || employes.value.length),
    caption: `${formatInteger(filteredEmployes.value.length)} visibles sur la page courante`,
    tag: 'Directory',
  },
  {
    label: 'Actifs visibles',
    value: formatInteger(activeVisibleCount.value),
    caption: `${formatInteger(Math.max(filteredEmployes.value.length - activeVisibleCount.value, 0))} inactifs sur la sélection`,
    tag: 'Status',
  },
  {
    label: 'Départements',
    value: formatInteger(departmentCount.value),
    caption: 'Couverture structurelle de la sélection affichée',
    tag: 'Structure',
  },
  {
    label: 'Postes visibles',
    value: formatInteger(positionCount.value),
    caption: 'Fonctions représentées dans la page courante',
    tag: 'Roles',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Sélection visible',
    value: formatInteger(filteredEmployes.value.length),
    copy: 'Nombre de collaborateurs affichés après tri et filtres.',
    tag: 'Page',
  },
  {
    label: 'Filtres actifs',
    value: formatInteger(activeFiltersCount.value),
    copy: activeFiltersCount.value ? 'La recherche courante resserre la vue affichée.' : 'Aucun filtre manuel actif.',
    tag: 'Filters',
  },
  {
    label: 'Département dominant',
    value: dominantDepartment.value?.label || 'Non disponible',
    copy: dominantDepartment.value ? `${formatInteger(dominantDepartment.value.count)} profils représentés.` : 'Aucune structure dominante.',
    tag: 'Teams',
  },
  {
    label: 'Catégorie dominante',
    value: dominantCategory.value?.label || 'Non disponible',
    copy: dominantCategory.value ? `${formatInteger(dominantCategory.value.count)} profils sur la page.` : 'Aucune catégorie dominante.',
    tag: 'Families',
  },
])

const employeeNotes = computed(() => [
  activeFiltersCount.value
    ? `${formatInteger(activeFiltersCount.value)} filtre(s) ou recherche(s) affinent actuellement l’annuaire.`
    : 'Aucun filtre n’est actif, la page montre la vue la plus large disponible.',
  dominantDepartment.value
    ? `${dominantDepartment.value.label} est le département le plus représenté sur cette page.`
    : 'La répartition par département n’est pas encore suffisamment marquée.',
  filteredEmployes.value.length
    ? `${formatInteger(activeVisibleCount.value)} collaborateur(s) actifs restent visibles après filtrage.`
    : 'La sélection actuelle ne renvoie aucun collaborateur visible.',
])

const fetchEmployes = async () => {
  loading.value = true

  try {
    const { data } = await api.get('/v1/employes', {
      params: {
        search: search.value,
        page: pagination.value.page,
      },
    })

    employes.value = data.data || []

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

const refreshData = async () => {
  await fetchEmployes()
}

const handleSearch = () => {
  clearTimeout(searchTimer)
  pagination.value.page = 1
  searchTimer = setTimeout(fetchEmployes, 300)
}

const handleSearchBlur = () => {
  window.setTimeout(() => {
    searchFocus.value = false
  }, 150)
}

const applySuggestion = async (emp) => {
  search.value = emp.matricule || `${emp.nom} ${emp.prenom}`.trim()
  searchFocus.value = false
  pagination.value.page = 1
  await fetchEmployes()
}

const initials = (emp) => `${emp?.nom?.[0] || ''}${emp?.prenom?.[0] || ''}`.trim() || 'RH'

const getSortVal = (emp, key) => {
  switch (key) {
    case 'nom':
      return `${emp.nom || ''} ${emp.prenom || ''}`.toLowerCase()
    case 'email':
      return (emp.email || '').toLowerCase()
    case 'poste':
      return (emp.poste?.nom || '').toLowerCase()
    case 'categorie':
      return (emp.poste?.categorie || '').toLowerCase()
    case 'departement':
      return (emp.departement?.nom || '').toLowerCase()
    case 'actif':
      return emp.actif ? 1 : 0
    case 'matricule':
    default:
      return (emp.matricule || '').toLowerCase()
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

const sortLabel = (key) => {
  if (sortKey.value !== key) return ''
  return sortDir.value === 'asc' ? '▲' : '▼'
}

const resetFilters = async () => {
  filters.value = {
    matricule: '',
    nom: '',
    email: '',
    poste: '',
    departement: '',
    categorie: '',
  }
  search.value = ''
  pagination.value.page = 1
  await fetchEmployes()
}

const nextPage = async () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page += 1
    await fetchEmployes()
  }
}

const prevPage = async () => {
  if (pagination.value.page > 1) {
    pagination.value.page -= 1
    await fetchEmployes()
  }
}

const countDistinct = (list, accessor) => new Set(list.map(accessor).filter(Boolean)).size

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
  await fetchEmployes()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
})
</script>

<style scoped>
.employees-page {
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

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.filter-card {
  display: grid;
  gap: 8px;
}

.search-card {
  grid-column: span 2;
}

.field-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.search-wrap {
  position: relative;
}

.search-suggestions {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  display: grid;
  gap: 0;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: var(--panel-solid);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  z-index: 20;
}

body[data-theme='dark'] .search-suggestions {
  background: rgba(15, 23, 42, 0.96);
}

.suggestion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 700;
}

.suggestion-row {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 14px;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  cursor: pointer;
  transition: background 0.18s ease;
}

.suggestion-row:hover {
  background: rgba(79, 70, 229, 0.06);
}

.suggestion-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  flex: none;
  border-radius: 12px;
  background: var(--brand-500);
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 800;
}

.suggestion-copy {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.suggestion-name {
  font-weight: 700;
}

.suggestion-meta {
  color: var(--muted);
  font-size: 0.8rem;
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

.employee-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 220px;
}

.employee-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  flex: none;
  border-radius: 16px;
  border: 1px solid var(--border);
}

.employee-photo {
  object-fit: cover;
}

.employee-avatar-fallback {
  background: var(--brand-500);
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 800;
}

body[data-theme='dark'] .employee-avatar-fallback {
  background: var(--brand-500);
  color: #ffffff;
}

.employee-main {
  display: grid;
  gap: 4px;
  min-width: 0;
}

.employee-name {
  margin: 0;
  font-weight: 700;
  line-height: 1.3;
}

.employee-sub {
  color: var(--muted);
  font-size: 0.82rem;
  line-height: 1.35;
}

.actions-col {
  text-align: right;
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

.loading-row {
  animation: pulse 1.4s ease-in-out infinite;
}

.skeleton-line {
  min-width: 220px;
}

.skeleton-circle {
  background: rgba(148, 163, 184, 0.22);
}

.skeleton-bar {
  display: block;
  height: 12px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.22);
}

.skeleton-bar.short {
  width: 82px;
}

.skeleton-bar.medium {
  width: 118px;
}

.skeleton-bar.wide {
  width: 160px;
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

@keyframes pulse {
  0%,
  100% {
    opacity: 0.65;
  }
  50% {
    opacity: 1;
  }
}

@media (max-width: 1200px) {
  .metric-grid,
  .filter-grid {
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

  .filter-grid,
  .overview-grid {
    grid-template-columns: 1fr;
  }

  .search-card {
    grid-column: span 1;
  }
}

@media (max-width: 680px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .action-row,
  .table-footer,
  .section-heading {
    flex-direction: column;
    align-items: stretch;
  }

  .employee-cell {
    min-width: 180px;
  }
}
</style>
