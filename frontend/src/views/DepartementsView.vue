<template>
  <div class="departements-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Organization structure</p>
        <h1>Départements</h1>
        <p class="hero-subtitle">
          Structurez les pôles de l’entreprise avec une vue claire sur les entités actives, leur
          description et la couverture organisationnelle globale.
        </p>

        <div class="hero-pills">
          <span class="pill">Structure RH</span>
          <span class="pill">Référentiel interne</span>
          <span class="pill">Navigation rapide</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn" to="/departements/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total structure:
              <strong>{{ formatInteger(departements.length) }}</strong>
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

    <template v-if="loading && !departements.length">
      <div class="card loading-card">
        <p class="loading-title">Chargement des départements…</p>
        <p class="muted">Le référentiel organisationnel est en cours de synchronisation.</p>
      </div>
    </template>

    <template v-else>
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
          Filtrez rapidement les entités pour retrouver une description, valider une nomenclature ou
          préparer une mise à jour structurelle.
        </p>

        <div class="controls-grid">
          <label class="field-card search-card">
            <span class="field-label">Recherche</span>
            <input
              v-model="search"
              class="input"
              placeholder="Nom ou description"
            />
          </label>

          <label class="field-card">
            <span class="field-label">Tri</span>
            <select v-model="sortKey" class="select">
              <option value="nom">Nom</option>
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
              <p class="section-kicker">Department list</p>
              <h2>Structure de l’entreprise</h2>
            </div>
            <span class="section-chip">{{ formatInteger(filteredDepartements.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Liste centralisée des départements pour maintenir un référentiel cohérent utilisé dans tout
            le module RH.
          </p>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Description</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="dep in filteredDepartements" :key="dep.id">
                  <td class="dept-name">
                    <span class="chip">{{ dep.nom }}</span>
                  </td>
                  <td class="dept-description">
                    {{ dep.description || 'Aucune description renseignée.' }}
                  </td>
                </tr>

                <tr v-if="!filteredDepartements.length">
                  <td colspan="2" class="empty-state">
                    <p>Aucun département ne correspond à la sélection actuelle.</p>
                    <span>Ajustez la recherche ou créez un nouveau département.</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <aside class="card section-card insights-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Résumé structure</h2>
            </div>
          </div>

          <p class="summary-intro">
            Indicateurs rapides pour vérifier la qualité du référentiel et garder une structure lisible.
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
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const departements = ref([])
const loading = ref(false)
const search = ref('')
const sortKey = ref('nom')
const sortDir = ref('asc')
const lastRefreshedAt = ref(null)

const hasFilters = computed(() => Boolean(search.value))

const filteredDepartements = computed(() => {
  const term = search.value.trim().toLowerCase()
  let list = departements.value

  if (term) {
    list = list.filter((item) =>
      `${item.nom || ''} ${item.description || ''}`.toLowerCase().includes(term),
    )
  }

  const accessor = sortKey.value === 'description'
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

const describedCount = computed(() =>
  departements.value.filter((item) => Boolean(String(item.description || '').trim())).length,
)

const descriptionCoverage = computed(() => {
  if (!departements.value.length) return 0
  return Math.round((describedCount.value / departements.value.length) * 100)
})

const nameLongest = computed(() => {
  if (!departements.value.length) return null
  return [...departements.value]
    .map((item) => item.nom || '')
    .sort((left, right) => right.length - left.length)[0]
})

const metricCards = computed(() => [
  {
    label: 'Départements',
    value: formatInteger(departements.value.length),
    caption: 'Entités de structure actuellement référencées',
    tag: 'Structure',
  },
  {
    label: 'Descriptions complètes',
    value: formatInteger(describedCount.value),
    caption: `${formatInteger(Math.max(departements.value.length - describedCount.value, 0))} sans description`,
    tag: 'Quality',
  },
  {
    label: 'Couverture descriptive',
    value: `${formatInteger(descriptionCoverage.value)}%`,
    caption: 'Qualité globale de documentation des entités',
    tag: 'Coverage',
  },
  {
    label: 'Résultats visibles',
    value: formatInteger(filteredDepartements.value.length),
    caption: 'Nombre affiché selon la recherche active',
    tag: 'View',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Départements visibles',
    value: formatInteger(filteredDepartements.value.length),
    copy: 'Total actuellement affiché dans la table.',
    tag: 'List',
  },
  {
    label: 'Descriptions renseignées',
    value: formatInteger(describedCount.value),
    copy: 'Entités avec une description utile pour le pilotage.',
    tag: 'Docs',
  },
  {
    label: 'Nom le plus long',
    value: nameLongest.value || 'Non disponible',
    copy: 'Point de contrôle rapide pour la cohérence de nomenclature.',
    tag: 'Naming',
  },
  {
    label: 'Filtre actif',
    value: search.value ? 'Oui' : 'Non',
    copy: search.value ? 'La vue est actuellement filtrée.' : 'Aucune recherche active.',
    tag: 'State',
  },
])

const notes = computed(() => [
  search.value
    ? 'La recherche est active et restreint la vue affichée.'
    : 'Aucun filtre actif, la vue montre le référentiel complet.',
  descriptionCoverage.value < 70
    ? 'La couverture des descriptions est faible; documenter les départements manquants.'
    : 'La couverture descriptive est globalement satisfaisante.',
  nameLongest.value
    ? `Nom le plus long actuel: "${nameLongest.value}".`
    : 'Aucun nom de département disponible pour le moment.',
])

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const fetchDepartements = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/departements')
    departements.value = data.data || []
    lastRefreshedAt.value = new Date()
  } finally {
    loading.value = false
  }
}

const refreshData = async () => {
  await fetchDepartements()
}

const resetFilters = () => {
  search.value = ''
}

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

onMounted(fetchDepartements)
</script>

<style scoped>
.departements-page {
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
  grid-template-columns: minmax(0, 1.8fr) minmax(320px, 0.9fr);
  gap: 18px;
  align-items: start;
}

.table-shell {
  overflow: auto;
}

.dept-name {
  width: 28%;
}

.dept-description {
  color: var(--muted);
  line-height: 1.55;
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
  .section-heading {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
