<template>
  <div class="demandes-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Leave workflow</p>
        <h1>Demandes de congés</h1>
        <p class="hero-subtitle">
          Pilotez le workflow manager vers RH, suivez les statuts et traitez rapidement les demandes
          en attente.
        </p>
        <div class="hero-pills">
          <span class="pill">Validation manager</span>
          <span class="pill">Contrôle RH</span>
          <span class="pill">Suivi des périodes</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="fetchDemandes" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
            <RouterLink class="btn" to="/demandes-conges/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouvelle</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total demandes:
              <strong>{{ formatInteger(pagination.total || demandes.length) }}</strong>
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

      <div class="controls-grid">
        <label class="field-card">
          <span class="field-label">Employé</span>
          <select class="select" v-model="filterEmploye" @change="debouncedFetchDemandes">
            <option value="">Tous les employés</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </label>
        <label class="field-card">
          <span class="field-label">Matricule</span>
          <input class="input" placeholder="EMP-001" v-model="filters.matricule" list="demandes-matricules" />
        </label>
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" placeholder="Nom ou prénom" v-model="filters.nom" />
        </label>
        <label class="field-card">
          <span class="field-label">Type</span>
          <input class="input" placeholder="Type de congé" v-model="filters.type" list="demandes-types" />
        </label>
        <label class="field-card">
          <span class="field-label">Statut</span>
          <input class="input" placeholder="en_attente, rh_valide..." v-model="filters.statut" list="demandes-statuts" />
        </label>
        <label class="field-card">
          <span class="field-label">Date début</span>
          <input class="input" placeholder="YYYY-MM-DD" v-model="filters.date_debut" />
        </label>
        <label class="field-card">
          <span class="field-label">Date fin</span>
          <input class="input" placeholder="YYYY-MM-DD" v-model="filters.date_fin" />
        </label>
      </div>

      <datalist id="demandes-matricules">
        <option v-for="matricule in optionsMatricules" :key="matricule" :value="matricule" />
      </datalist>
      <datalist id="demandes-types">
        <option v-for="type in optionsTypes" :key="type" :value="type" />
      </datalist>
      <datalist id="demandes-statuts">
        <option v-for="statut in optionsStatuts" :key="statut" :value="statut" />
      </datalist>
    </section>

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Requests</p>
          <h2>Demandes en cours</h2>
        </div>
        <span class="section-chip">{{ formatInteger(demandesFiltrees.length) }} visibles</span>
      </div>

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
                <button class="sort-button" type="button" @click="setSort('debut')">
                  Période
                  <span>{{ sortLabel('debut') }}</span>
                </button>
              </th>
              <th>
                <button class="sort-button" type="button" @click="setSort('statut')">
                  Statut
                  <span>{{ sortLabel('statut') }}</span>
                </button>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in demandesFiltrees" :key="d.id">
              <td>
                <p class="emp-main">{{ d.employe?.matricule || '—' }}</p>
                <p class="emp-sub">{{ d.employe ? `${d.employe.nom} ${d.employe.prenom}` : '—' }}</p>
              </td>
              <td>{{ d.type_conge?.libelle || d.type?.nom || '—' }}</td>
              <td class="period">{{ formatDate(d.date_debut) || '—' }} → {{ formatDate(d.date_fin) || '—' }}</td>
              <td>
                <span class="status-badge" :class="badgeClass(d.statut)">{{ d.statut }}</span>
              </td>
              <td>
                <div class="row-actions" v-if="canAct(d)">
                  <button class="btn btn-secondary btn-xs" @click="approveManager(d.id)">Valider</button>
                  <button class="btn btn-danger btn-xs" @click="reject(d.id)">Refuser</button>
                </div>
                <span v-else class="emp-sub">—</span>
              </td>
            </tr>
            <tr v-if="!demandesFiltrees.length">
              <td colspan="5" class="empty-state">
                <p>Aucune demande ne correspond à la sélection actuelle.</p>
                <span>Essayez d’ajuster les filtres.</span>
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
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatDateValue } from '../utils/formatters'

const demandes = ref([])
const employes = ref([])
const filterEmploye = ref('')
const loading = ref(false)
const lastRefreshedAt = ref(null)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const filters = ref({ matricule: '', nom: '', type: '', statut: '', date_debut: '', date_fin: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')

const hasFilters = computed(() =>
  Boolean(
    filterEmploye.value ||
    filters.value.matricule ||
    filters.value.nom ||
    filters.value.type ||
    filters.value.statut ||
    filters.value.date_debut ||
    filters.value.date_fin,
  ),
)

const optionsMatricules = computed(() => {
  const fromEmployees = employes.value.map((item) => item.matricule)
  const fromDemandes = demandes.value.map((item) => item.employe?.matricule)
  return [...new Set([...fromEmployees, ...fromDemandes].filter(Boolean))]
})

const optionsTypes = computed(() =>
  [...new Set(demandes.value.map((item) => item.type_conge?.libelle || item.type?.nom).filter(Boolean))],
)

const optionsStatuts = computed(() =>
  [...new Set(demandes.value.map((item) => item.statut).filter(Boolean))],
)

const badgeClass = (statut) => {
  switch (statut) {
    case 'rh_valide':
      return 'bg-green-50 text-green-600'
    case 'manager_valide':
    case 'en_attente':
      return 'bg-blue-50 text-blue-600'
    case 'rejete':
      return 'bg-red-50 text-red-600'
    default:
      return 'bg-slate-100 text-slate-600'
  }
}

const fetchDemandes = async () => {
  loading.value = true
  try {
    const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
    const { data } = await api.get('/v1/demandes-conges', { params })
    demandes.value = data.data || []
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

const debouncedFetchDemandes = debounce(fetchDemandes, 300)

const fetchRefs = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

const approveManager = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/manager-approve`)
  await fetchDemandes()
}

const reject = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/reject`)
  await fetchDemandes()
}

const demandesFiltrees = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = demandes.value.filter((d) =>
    toStr(d.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(d.employe?.nom)} ${toStr(d.employe?.prenom)}`).includes(toStr(f.nom)) &&
    (toStr(d.type_conge?.libelle).includes(toStr(f.type)) || toStr(d.type?.nom).includes(toStr(f.type))) &&
    toStr(d.statut).includes(toStr(f.statut)) &&
    toStr(d.date_debut).includes(toStr(f.date_debut)) &&
    toStr(d.date_fin).includes(toStr(f.date_fin))
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
    case 'type': return toStr(d.type_conge?.libelle || d.type?.nom)
    case 'debut': return toStr(d.date_debut)
    case 'statut': return toStr(d.statut)
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
  filters.value = { matricule: '', nom: '', type: '', statut: '', date_debut: '', date_fin: '' }
}

const canAct = (demande) => demande.statut === 'en_attente' || demande.statut === 'manager_valide'
const formatDate = (value) => formatDateValue(value)

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

const pendingCount = computed(() => demandes.value.filter((d) => d.statut === 'en_attente').length)
const managerValidatedCount = computed(() => demandes.value.filter((d) => d.statut === 'manager_valide').length)
const rejectedCount = computed(() => demandes.value.filter((d) => d.statut === 'rejete').length)

const metricCards = computed(() => [
  {
    label: 'Demandes visibles',
    value: formatInteger(demandesFiltrees.value.length),
    caption: 'Résultats de la page après filtres',
    tag: 'Flow',
  },
  {
    label: 'En attente',
    value: formatInteger(pendingCount.value),
    caption: 'Demandes à traiter côté manager',
    tag: 'Pending',
  },
  {
    label: 'Validées manager',
    value: formatInteger(managerValidatedCount.value),
    caption: 'En attente de finalisation RH',
    tag: 'Review',
  },
  {
    label: 'Rejetées',
    value: formatInteger(rejectedCount.value),
    caption: 'Demandes refusées sur la période affichée',
    tag: 'Rejected',
  },
])

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchDemandes()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchDemandes()
  }
}

onMounted(async () => {
  await fetchRefs()
  await fetchDemandes()
})
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

.emp-main {
  margin: 0;
  font-weight: 700;
}

.emp-sub,
.period {
  margin: 0;
  color: var(--muted);
  font-size: 0.84rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 700;
}

.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
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
