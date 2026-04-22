<template>
  <div class="categories-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Role taxonomy</p>
        <h1>Catégories de postes</h1>
        <p class="hero-subtitle">
          Harmonisez les familles de postes pour améliorer le tri des fonctions, la lecture des
          effectifs et la cohérence des rapports RH.
        </p>

        <div class="hero-pills">
          <span class="pill">Taxonomie RH</span>
          <span class="pill">Référentiel métiers</span>
          <span class="pill">Niveaux de fonction</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/categories-postes/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouvelle</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total catégories:
              <strong>{{ formatInteger(categories.length) }}</strong>
            </p>
            <p class="hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
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

    <section class="card section-card controls-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Directory controls</p>
          <h2>Recherche et tri</h2>
        </div>
        <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters" :disabled="!hasFilters">
          Réinitialiser
        </button>
      </div>

      <p class="section-copy">
        Filtrez rapidement les catégories pour retrouver une famille précise ou préparer une mise à
        jour de structure.
      </p>

      <div class="controls-grid">
        <label class="field-card search-card">
          <span class="field-label">Recherche</span>
          <input v-model="search" class="input" placeholder="Nom, code ou description" />
        </label>

        <label class="field-card">
          <span class="field-label">Tri</span>
          <select v-model="sortKey" class="select">
            <option value="nom">Nom</option>
            <option value="code">Code</option>
            <option value="description">Description</option>
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
    </section>

    <section class="content-grid">
      <article class="card section-card table-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Category list</p>
            <h2>Catégories existantes</h2>
          </div>
          <span class="section-chip">{{ formatInteger(filteredCategories.length) }} visibles</span>
        </div>

        <p class="section-copy">
          Liste consolidée des catégories utilisées dans les postes pour garantir un référentiel propre
          et homogène.
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
                  <button class="sort-button" type="button" @click="setSort('code')">
                    Code
                    <span>{{ sortLabel('code') }}</span>
                  </button>
                </th>
                <th>
                  <button class="sort-button" type="button" @click="setSort('description')">
                    Description
                    <span>{{ sortLabel('description') }}</span>
                  </button>
                </th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="cat in filteredCategories" :key="cat.id">
                <td class="category-name"><span class="chip">{{ cat.nom }}</span></td>
                <td class="category-code">{{ cat.code || '—' }}</td>
                <td class="category-description">{{ cat.description || '—' }}</td>
                <td class="actions-col">
                  <div class="row-actions">
                    <RouterLink class="btn btn-secondary btn-xs" :to="`/categories-postes/${cat.id}/modifier`">
                      <AppIcon name="file" :size="15" />
                      <span>Modifier</span>
                    </RouterLink>
                    <button
                      class="btn btn-danger btn-xs"
                      type="button"
                      @click="remove(cat)"
                      :disabled="loadingDelete === cat.id"
                    >
                      <AppIcon name="trash" :size="15" />
                      <span>{{ loadingDelete === cat.id ? 'Suppression...' : 'Supprimer' }}</span>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!filteredCategories.length">
                <td colspan="4" class="empty-state">
                  <p>Aucune catégorie ne correspond à la sélection actuelle.</p>
                  <span>Ajustez la recherche ou créez une nouvelle catégorie.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { RouterLink } from 'vue-router'

const categories = ref([])
const loading = ref(false)
const loadingDelete = ref(null)
const message = ref('')
const messageType = ref('info')
const search = ref('')
const sortKey = ref('nom')
const sortDir = ref('asc')
const lastRefreshedAt = ref(null)

const hasFilters = computed(() => Boolean(search.value.trim()))

const filteredCategories = computed(() => {
  const term = search.value.trim().toLowerCase()
  let list = categories.value

  if (term) {
    list = list.filter((item) =>
      `${item.nom || ''} ${item.code || ''} ${item.description || ''}`.toLowerCase().includes(term),
    )
  }

  const accessor =
    sortKey.value === 'code'
      ? (item) => (item.code || '').toLowerCase()
      : sortKey.value === 'description'
        ? (item) => (item.description || '').toLowerCase()
        : (item) => (item.nom || '').toLowerCase()

  return [...list].sort((left, right) => {
    const valueA = accessor(left)
    const valueB = accessor(right)
    if (valueA < valueB) return sortDir.value === 'asc' ? -1 : 1
    if (valueA > valueB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })
})

const codedCount = computed(() => categories.value.filter((item) => Boolean(String(item.code || '').trim())).length)
const describedCount = computed(() =>
  categories.value.filter((item) => Boolean(String(item.description || '').trim())).length,
)

const descriptionCoverage = computed(() => {
  if (!categories.value.length) return 0
  return Math.round((describedCount.value / categories.value.length) * 100)
})

const metricCards = computed(() => [
  {
    label: 'Catégories',
    value: formatInteger(categories.value.length),
    caption: 'Familles de postes actuellement disponibles',
    tag: 'Taxonomy',
  },
  {
    label: 'Codes renseignés',
    value: formatInteger(codedCount.value),
    caption: `${formatInteger(Math.max(categories.value.length - codedCount.value, 0))} sans code`,
    tag: 'Quality',
  },
  {
    label: 'Couverture description',
    value: `${formatInteger(descriptionCoverage.value)}%`,
    caption: 'Part des catégories avec description complète',
    tag: 'Coverage',
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

const resetFilters = () => {
  search.value = ''
  sortKey.value = 'nom'
  sortDir.value = 'asc'
}

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    return
  }
  sortKey.value = key
  sortDir.value = 'asc'
}

const sortLabel = (key) => {
  if (sortKey.value !== key) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}

const formatInteger = (value) =>
  new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)

const fetchCategories = async () => {
  const { data } = await api.get('/v1/categories-postes')
  categories.value = data || []
  lastRefreshedAt.value = new Date()
}

const refreshData = async () => {
  loading.value = true
  try {
    await fetchCategories()
    message.value = ''
    messageType.value = 'info'
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors du chargement'
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

const remove = async (cat) => {
  if (!confirm(`Supprimer la catégorie "${cat.nom}" ?`)) return
  loadingDelete.value = cat.id
  try {
    await api.delete(`/v1/categories-postes/${cat.id}`)
    await fetchCategories()
    message.value = 'Catégorie supprimée.'
    messageType.value = 'success'
  } catch (e) {
    message.value = e.response?.data?.message || 'Suppression impossible'
    messageType.value = 'danger'
  } finally {
    loadingDelete.value = null
  }
}

onMounted(fetchCategories)
</script>

<style scoped>
.categories-page {
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
  grid-template-columns: repeat(3, minmax(0, 1fr));
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
.section-chip {
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

.metric-value,
.empty-state p {
  margin: 0;
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

.category-name {
  width: 28%;
}

.category-code,
.category-description {
  color: var(--muted);
}

.actions-col {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.row-actions {
  display: inline-flex;
  justify-content: flex-end;
  gap: 8px;
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
}

@media (max-width: 900px) {
  .hero {
    padding: 22px;
  }

  .hero-actions {
    min-width: 100%;
    max-width: none;
  }

  .controls-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .action-row,
  .section-heading {
    flex-direction: column;
    align-items: stretch;
  }

  .row-actions {
    display: grid;
    width: 100%;
  }

  .actions-col {
    width: auto;
  }
}
</style>
