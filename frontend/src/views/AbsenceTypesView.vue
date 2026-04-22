<template>
  <div class="absence-types-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Leave taxonomy</p>
        <h1>Types d'absence</h1>
        <p class="hero-subtitle">
          Gérez le catalogue des congés et absences pour un traitement homogène des droits,
          limites et règles de cumul.
        </p>
        <div class="hero-pills">
          <span class="pill">Congés payés</span>
          <span class="pill">Absences exceptionnelles</span>
          <span class="pill">Politique RH</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="fetchTypes" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
            <RouterLink class="btn" to="/absences-types/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total types:
              <strong>{{ formatInteger(pagination.total || types.length) }}</strong>
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
        Filtrez les types par libellé, mode de rémunération et volume de jours pour cibler rapidement
        la bonne règle d'absence.
      </p>

      <div class="controls-grid">
        <label class="field-card">
          <span class="field-label">Recherche</span>
          <input class="input" placeholder="Rechercher un type" v-model="search" @input="fetchTypes" />
        </label>
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" placeholder="Nom" v-model="filters.nom" />
        </label>
        <label class="field-card">
          <span class="field-label">Payant</span>
          <input class="input" placeholder="oui / non" v-model="filters.payant" />
        </label>
        <label class="field-card">
          <span class="field-label">Jours annuels</span>
          <input class="input" placeholder="Nombre de jours" v-model="filters.jours" />
        </label>
      </div>
    </section>

    <section class="content-grid">
      <article class="card section-card table-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Leave types</p>
            <h2>Catalogue des types</h2>
          </div>
          <span class="section-chip">{{ formatInteger(typesFiltres.length) }} visibles</span>
        </div>

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
                  <button class="sort-button" type="button" @click="setSort('payant')">
                    Payant
                    <span>{{ sortLabel('payant') }}</span>
                  </button>
                </th>
                <th>
                  <button class="sort-button" type="button" @click="setSort('jours')">
                    Jours
                    <span>{{ sortLabel('jours') }}</span>
                  </button>
                </th>
                <th>Fréquence</th>
                <th>Limite</th>
                <th>Cumulable</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in typesFiltres" :key="t.id">
                <td class="type-name">{{ t.libelle }}</td>
                <td>
                  <span v-if="t.paye" class="chip">Payant</span>
                  <span v-else class="chip muted-chip">Non payant</span>
                </td>
                <td>{{ t.jours_forfait ?? '—' }}</td>
                <td>{{ frequence(t) }}</td>
                <td>
                  <div class="cell-stack">
                    <div v-if="t.limite">
                      Max {{ t.limite }}
                      <span v-if="t.limite_frequence"> / {{ t.limite_frequence.libelle || t.limite_frequence.code }}</span>
                    </div>
                    <div v-else>—</div>
                  </div>
                </td>
                <td>
                  <div class="cell-stack">
                    <div>{{ t.cumulable ? 'Oui' : 'Non' }}</div>
                    <div v-if="t.cumulable_duree">Durée: {{ t.cumulable_duree }} ({{ cumulableFreq(t) }})</div>
                  </div>
                </td>
                <td class="type-desc">{{ t.description || '—' }}</td>
              </tr>
              <tr v-if="!typesFiltres.length">
                <td colspan="7" class="empty-state">
                  <p>Aucun type d'absence ne correspond à la sélection actuelle.</p>
                  <span>Affinez les filtres ou ajoutez un nouveau type.</span>
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
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const types = ref([])
const search = ref('')
const filters = ref({ nom: '', payant: '', jours: '' })
const loading = ref(false)
const lastRefreshedAt = ref(null)
const sortKey = ref('libelle')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const hasFilters = computed(() => Boolean(search.value || filters.value.nom || filters.value.payant || filters.value.jours))

const fetchTypes = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/types-conges', { params: { search: search.value, page: pagination.value.page }, paramsSerializer: { indexes: null } })
    types.value = data.data || []
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

onMounted(async () => {
  await fetchTypes()
})

const typesFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = types.value.filter((t) =>
    toStr(t.libelle).includes(toStr(f.nom)) &&
    (toStr(t.paye ? 'oui' : 'non').includes(toStr(f.payant))) &&
    (toStr(t.jours_forfait).includes(toStr(f.jours)) || toStr(frequence(t)).includes(toStr(f.jours)))
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

const getVal = (t, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'payant': return toStr(t.paye ? 'oui' : 'non')
    case 'jours': return Number(t.jours_forfait) || 0
    case 'nom':
    default:
      return toStr(t.libelle)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filters.value = { nom: '', payant: '', jours: '' } }

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

const payantsCount = computed(() => types.value.filter((t) => Boolean(t.paye)).length)
const cumulablesCount = computed(() => types.value.filter((t) => Boolean(t.cumulable)).length)
const withLimitCount = computed(() => types.value.filter((t) => Number(t.limite) > 0).length)

const metricCards = computed(() => [
  {
    label: 'Types visibles',
    value: formatInteger(typesFiltres.value.length),
    caption: 'Résultats après recherche et filtres',
    tag: 'Types',
  },
  {
    label: 'Types payants',
    value: formatInteger(payantsCount.value),
    caption: `${formatInteger(Math.max(types.value.length - payantsCount.value, 0))} non payants`,
    tag: 'Paid',
  },
  {
    label: 'Cumulables',
    value: formatInteger(cumulablesCount.value),
    caption: 'Types autorisant le report de droits',
    tag: 'Carry',
  },
  {
    label: 'Avec limite',
    value: formatInteger(withLimitCount.value),
    caption: 'Règles avec plafond configuré',
    tag: 'Limit',
  },
])

const frequence = (t) => {
  if (t.frequence?.libelle) return `${t.frequence.libelle} (${t.frequence.code})`
  const nom = (t.libelle || '').toLowerCase()
  if (nom.includes('mariage')) return '3 j — 1 fois/événement'
  if (nom.includes('décès') || nom.includes('deces')) return '3 j — par décès'
  if (nom.includes('naissance')) return '2-5 j — par naissance'
  if (nom.includes('matern')) return '12-16 sem — grossesse'
  if (nom.includes('patern')) return '2-5 j — par naissance'
  if (nom.includes('sabbatique')) return 'Longue durée, manager'
  if (nom.includes('sans solde')) return 'Selon validation'
  if (t.jours_forfait) return `${t.jours_forfait} j`
  return 'Selon politique (mois/événement)'
}
const cumulableFreq = (t) => {
  if (t.cumulable_frequence_id && t.cumulable_frequence) return `${t.cumulable_frequence.libelle || ''}`.trim() || '—'
  if (t.cumulable_frequence_id && t.frequences) {
    const found = (t.frequences || []).find((f) => f.id === t.cumulable_frequence_id)
    if (found) return found.libelle
  }
  if (t.frequence?.libelle) return t.frequence.libelle
  return '—'
}
const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchTypes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchTypes()
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
  grid-template-columns: repeat(4, minmax(0, 1fr));
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
  grid-template-columns: 1fr;
  gap: 18px;
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

.type-name {
  font-weight: 700;
}

.type-desc,
.cell-stack {
  color: var(--muted);
  font-size: 0.84rem;
}

.muted-chip {
  background: rgba(148, 163, 184, 0.12);
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
  /* Hero handled globally (shared hero-band). */
}

@media (max-width: 680px) {
  .metric-grid,
  .controls-grid {
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
