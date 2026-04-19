<template>
  <div class="soldes-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Leave balances</p>
        <h1>Soldes de congés</h1>
        <p class="hero-subtitle">
          Suivez les droits acquis, consommations et soldes par collaborateur pour anticiper les
          disponibilités et les régularisations.
        </p>
        <div class="hero-pills">
          <span class="pill">Acquis et utilisé</span>
          <span class="pill">Simulation date</span>
          <span class="pill">Suivi contractuel</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="fetchSoldes" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total lignes:
              <strong>{{ formatInteger(pagination.total || soldes.length) }}</strong>
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
          <p class="section-kicker">Settings</p>
          <h2>Règles et filtres</h2>
        </div>
        <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters" :disabled="!hasFilters">
          Réinitialiser
        </button>
      </div>

      <div class="chip-row">
        <span class="chip" :class="deductionClasses.leave">
          Prélèvement solde congé : {{ deductionText(deductionSettings.deduct_from_leave_balance) }}
        </span>
        <span class="chip" :class="deductionClasses.salary">
          Prélèvement salaire : {{ deductionText(deductionSettings.deduct_from_salary) }}
        </span>
      </div>

      <div class="controls-grid">
        <label class="field-card">
          <span class="field-label">Employé</span>
          <select class="select" v-model="filterEmploye" @change="debouncedFetchSoldes">
            <option value="">Tous les employés</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
        </label>
        <label class="field-card">
          <span class="field-label">Matricule</span>
          <input class="input" placeholder="Matricule" v-model="filters.matricule" />
        </label>
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" placeholder="Nom" v-model="filters.nom" />
        </label>
        <label class="field-card">
          <span class="field-label">Type</span>
          <input class="input" placeholder="Type" v-model="filters.type" />
        </label>
        <label class="field-card">
          <span class="field-label">Département</span>
          <input class="input" placeholder="Département" v-model="filters.departement" />
        </label>
        <label class="field-card">
          <span class="field-label">Simulation date actuelle</span>
          <input class="input" type="date" v-model="filters.simulation_date" />
        </label>
      </div>

      <p v-if="message" class="error-inline">{{ message }}</p>
    </section>

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Balances</p>
          <h2>Soldes par employé et type</h2>
        </div>
        <span class="section-chip">{{ formatInteger(soldesFiltres.length) }} visibles</span>
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
              <th>Contrat</th>
              <th>Premier acquis</th>
              <th>Expiration max</th>
              <th>Acquis période</th>
              <th>Utilisé période</th>
              <th>
                <button class="sort-button" type="button" @click="setSort('solde_actuel')">
                  Solde période
                  <span>{{ sortLabel('solde_actuel') }}</span>
                </button>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in soldesFiltres" :key="s.id">
              <td>{{ s.employe ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}` : `${s.employe_matricule || ''} ${s.employe_nom || ''} ${s.employe_prenom || ''}` }}</td>
              <td>{{ s.type_conge?.libelle || s.type_conge_libelle || '—' }}</td>
              <td>{{ contratLabel(s) }}</td>
              <td>{{ s.premier_acquis || '—' }}</td>
              <td>{{ expirationMax(s) }}</td>
              <td>{{ s.acquis_periode ?? s.total_acquis ?? '—' }}</td>
              <td>{{ s.utilise_periode ?? s.total_utilise ?? '—' }}</td>
              <td>{{ s.solde_periode ?? s.solde_actuel }}</td>
              <td>
                <RouterLink class="btn btn-secondary btn-xs" :to="`/soldes-conges/${s.id}`">Détails</RouterLink>
              </td>
            </tr>
            <tr v-if="!soldesFiltres.length">
              <td colspan="9" class="empty-state">
                <p>Aucun solde ne correspond à la sélection actuelle.</p>
                <span>Ajustez les filtres ou la date de simulation.</span>
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
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import AppIcon from '../components/ui/AppIcon.vue'

const soldes = ref([])
const employes = ref([])
const types = ref([])
const deductionSettings = ref({ deduct_from_leave_balance: true, deduct_from_salary: true })
const deductionClasses = computed(() => ({
  leave: deductionSettings.value.deduct_from_leave_balance ? 'chip-on' : 'chip-off',
  salary: deductionSettings.value.deduct_from_salary ? 'chip-on' : 'chip-off'
}))
const deductionText = (val) => (val ? 'Activé' : 'Désactivé')
const filterEmploye = ref('')
const message = ref('')
const loading = ref(false)
const lastRefreshedAt = ref(null)
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const filters = ref({ matricule: '', nom: '', type: '', departement: '', from: '', to: '', simulation_date: '' })
const sortKey = ref('employe')
const sortDir = ref('asc')

const hasFilters = computed(() =>
  Boolean(
    filterEmploye.value ||
    filters.value.matricule ||
    filters.value.nom ||
    filters.value.type ||
    filters.value.departement ||
    filters.value.simulation_date,
  ),
)

const form = ref({
  employe_id: '',
  type_conge_id: '',
  solde_actuel: '',
  solde_annuel: ''
})

const periode = ref({
  employe_id: '',
  type_conge_id: '',
  from: '',
  to: ''
})
const soldePeriode = ref({ solde: null, from: null, to: null })
const messagePeriode = ref('')

const congesRange = ref({ from: '', to: '', employe_id: '' })
const demandesRange = ref([])

const fetchSoldes = async () => {
  loading.value = true
  message.value = ''

  // Interdire le futur
  if (filters.value.simulation_date) {
    const sim = new Date(filters.value.simulation_date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    if (sim > today) {
      message.value = 'Simulation interdite dans le futur. Choisissez une date au passé ou aujourd\'hui.'
      loading.value = false
      return
    }
  }

  const params = {
    page: pagination.value.page,
    employe_id: filterEmploye.value || undefined,
    from: filters.value.from || undefined,
    to: filters.value.to || undefined,
    simulation_date: filters.value.simulation_date || undefined,
    matricule: filters.value.matricule || undefined,
    nom: filters.value.nom || undefined,
    type: filters.value.type || undefined,
    departement: filters.value.departement || undefined
  }
  const { data } = await api.get('/v1/soldes-conges', { params })
  soldes.value = data.data || []
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
  loading.value = false
}

const debouncedFetchSoldes = debounce(fetchSoldes, 300)

const fetchRefs = async () => {
  const [emps, tps, worktime] = await Promise.all([
    api.get('/v1/employes', { params: { active_only: true } }),
    api.get('/v1/types-conges'),
    api.get('/v1/worktime').catch(() => ({ data: {} }))
  ])
  employes.value = emps.data.data || []
  types.value = tps.data.data || []
  if (worktime?.data) {
    deductionSettings.value = {
      deduct_from_leave_balance: worktime.data.deduct_from_leave_balance ?? true,
      deduct_from_salary: worktime.data.deduct_from_salary ?? true
    }
  }
}

const saveSolde = async () => {
  message.value = 'Lecture seule : le solde est calculé automatiquement.'
}

onMounted(async () => {
  await Promise.all([fetchRefs(), fetchSoldes()])
})

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchSoldes()
  }
}

const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchSoldes()
  }
}

const soldesFiltres = computed(() => {
  let list = soldes.value
  console.log(soldes)
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

const getVal = (s, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'type': return toStr(s.type_conge?.libelle)
    case 'solde_actuel': return Number(s.solde_actuel) || 0
    case 'solde_annuel': return Number(s.solde_annuel) || 0
    case 'employe':
    default:
      return `${toStr(s.employe?.nom)} ${toStr(s.employe?.prenom)}`
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

const expirationMax = (s) => {
  const defaultVal = s.expire_first || s.expire_le || '—'
  const contratType = (s.contrat_type || s.contratType || '').toLowerCase()
  const contratFin = s.contrat_fin || s.contratFin
  const acquisFirst = s.acquis_first || s.acquisFirst
  console.log("contrat type")
  console.log(contratType);
  if (contratType !== 'cdd' || !contratFin || !acquisFirst) {
    return defaultVal
  }

  const contratFinDate = new Date(contratFin)
  const acquisDate = new Date(acquisFirst)
  if (isNaN(contratFinDate.getTime()) || isNaN(acquisDate.getTime())) {
    return defaultVal
  }

  const diffYears = Math.abs(contratFinDate - acquisDate) / (365.25 * 24 * 60 * 60 * 1000)
  if (diffYears <= 3) {
    return contratFin
  }
  return defaultVal
}

const contratLabel = (s) => {
  const type = s.contrat_type || s.contratType
  const fin = s.contrat_fin || s.contratFin
  if (!type && !fin) return '—'
  if (type && fin) return `${type} — fin ${fin}`
  if (type) return type
  return fin
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')

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

const activeBalances = computed(() => soldes.value.filter((s) => Number(s.solde_periode ?? s.solde_actuel ?? 0) > 0).length)
const negativeBalances = computed(() => soldes.value.filter((s) => Number(s.solde_periode ?? s.solde_actuel ?? 0) < 0).length)
const withContractInfo = computed(() => soldes.value.filter((s) => Boolean(s.contrat_type || s.contratType)).length)

const metricCards = computed(() => [
  {
    label: 'Soldes visibles',
    value: formatInteger(soldesFiltres.value.length),
    caption: 'Résultats après application des filtres',
    tag: 'Balance',
  },
  {
    label: 'Solde positif',
    value: formatInteger(activeBalances.value),
    caption: 'Comptes avec jours disponibles',
    tag: 'Positive',
  },
  {
    label: 'Solde négatif',
    value: formatInteger(negativeBalances.value),
    caption: 'Comptes à régulariser',
    tag: 'Alert',
  },
  {
    label: 'Avec contrat',
    value: formatInteger(withContractInfo.value),
    caption: 'Lignes enrichies de données contractuelles',
    tag: 'Contract',
  },
])
const resetFilters = () => {
  filters.value = { matricule: '', nom: '', type: '', departement: '', from: '', to: '', simulation_date: '' }
  pagination.value.page = 1
  debouncedFetchSoldes()
}

const router = useRouter()
const goToDetail = (s) => {
  if (!s?.id) return
  router.push(`/soldes-conges/${s.id}`)
}

// Rafraîchir quand la plage de dates change
watch(
  () => [filters.value.from, filters.value.to, filters.value.simulation_date, filters.value.matricule, filters.value.nom, filters.value.type, filters.value.departement],
  () => {
    pagination.value.page = 1
    debouncedFetchSoldes()
  }
)

const calculerPeriode = async () => {
  messagePeriode.value = ''
  soldePeriode.value = { solde: null, from: null, to: null }
  if (!periode.value.employe_id) {
    messagePeriode.value = 'Sélectionne un employé'
    return
  }
  try {
    const params = { ...periode.value }
    const { data } = await api.get('/v1/soldes-conges/periode', { params })
    soldePeriode.value = data
  } catch (e) {
    messagePeriode.value = e.response?.data?.message || 'Erreur lors du calcul'
  }
}

const fetchCongesRange = async () => {
  try {
    const params = { ...congesRange.value }
    const { data } = await api.get('/v1/demandes-conges', { params })
    demandesRange.value = data.data || data || []
  } catch (e) {
    demandesRange.value = []
  }
}
</script>

<style scoped>
.soldes-page {
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
    linear-gradient(135deg, rgba(79, 70, 229, 0.18), rgba(15, 23, 42, 0)),
    rgba(15, 23, 42, 0.88);
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
  max-width: 380px;
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
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.action-row > * {
  flex: 0 0 auto;
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

.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
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

.error-inline {
  margin: 0;
  color: #dc2626;
  font-size: 0.9rem;
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

.chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}
.chip-on { background: rgba(34,197,94,0.12); color: #166534; }
.chip-off { background: rgba(248,113,113,0.15); color: #b91c1c; }

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
