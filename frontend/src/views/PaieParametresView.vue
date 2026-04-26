<template>
  <div class="payroll-settings-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll settings</p>
        <h1>Paramètres paie</h1>
        <p class="hero-subtitle">
          Configurez les contributions sociales, les compléments de paie et le barème IRSA avec un
          niveau de lisibilité adapté à un outil RH moderne.
        </p>

        <div class="hero-pills">
          <span class="pill">CNAPS / OSTIE</span>
          <span class="pill">IRSA progressif</span>
          <span class="pill">Heures supplémentaires</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div v-if="status.text" class="status-banner" :class="status.type">
            <span class="status-dot"></span>
            <span>{{ status.text }}</span>
          </div>

          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="reload" :disabled="loading || saving">
              <AppIcon name="refresh" :size="18" />
              <span>Reload</span>
            </button>
            <button class="btn" type="button" @click="save" :disabled="loading || saving || !hasChanges">
              <AppIcon name="save" :size="18" />
              <span>{{ saving ? 'Saving...' : 'Save changes' }}</span>
            </button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="metric-card">
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div v-if="loading" class="card loading-card">
      <p class="loading-title">Chargement des paramètres de paie…</p>
      <p class="muted">Les données réglementaires et le barème IRSA sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="content-grid">
        <article class="card section-card contributions-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Social contributions</p>
              <h2>Cotisations et variables paie</h2>
            </div>
            <span class="section-chip">2-column structured form</span>
          </div>

          <div class="field-sections">
            <section v-for="group in fieldGroups" :key="group.title" class="field-group">
              <div class="field-group-head">
                <h3>{{ group.title }}</h3>
                <p>{{ group.description }}</p>
              </div>

              <div class="fields-grid">
                <label v-for="field in group.fields" :key="field.key" class="field-card">
                  <span class="field-topline">
                    <span class="field-label">{{ field.label }}</span>
                    <span class="field-badge">{{ field.badge }}</span>
                  </span>
                  <span class="field-helper">{{ field.helper }}</span>

                  <div class="input-shell" :class="{ suffix: Boolean(resolveFieldSuffix(field)) }">
                    <input
                      v-model.number="form[field.key]"
                      class="input field-input"
                      type="number"
                      min="0"
                      :step="field.step"
                    />
                    <span v-if="resolveFieldSuffix(field)" class="input-suffix">{{ resolveFieldSuffix(field) }}</span>
                  </div>
                </label>
              </div>
            </section>
          </div>
        </article>

      </section>

      <section class="card section-card table-card">
        <div class="table-header">
          <div>
            <p class="section-kicker">IRSA tax brackets</p>
            <h2>Barème progressif IRSA</h2>
            <p class="table-copy">
              Modifiez les tranches directement dans le tableau. Les champs vides en plafond max sont
              interprétés comme “illimité”.
            </p>
          </div>
          <button class="btn btn-secondary add-inline" type="button" @click="appendEmptyTranche">
            <AppIcon name="plus" :size="18" />
            <span>Add bracket</span>
          </button>
        </div>

        <div class="table-shell">
          <table class="table irsa-table">
            <thead>
              <tr>
                <th>Min salary</th>
                <th>Max salary</th>
                <th>Rate</th>
                <th>Scope</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tranche, index) in tranches" :key="tranche.id ?? tranche.uid" class="tranche-row">
                <td>
                  <input v-model.number="tranche.min_base" class="input table-input" type="number" min="0" step="0.01" />
                </td>
                <td>
                  <input
                    v-model="tranche.max_base"
                    class="input table-input"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="Unlimited"
                  />
                </td>
                <td>
                  <div class="input-shell suffix compact">
                    <input v-model.number="tranche.taux" class="input table-input" type="number" min="0" step="0.01" />
                    <span class="input-suffix">%</span>
                  </div>
                </td>
                <td>
                  <span class="scope-pill" :class="{ open: isUnlimited(tranche.max_base) }">
                    {{ isUnlimited(tranche.max_base) ? 'Open ended' : 'Capped' }}
                  </span>
                </td>
                <td class="actions-col">
                  <button class="btn-icon danger" type="button" @click="removeTranche(index)">
                    <AppIcon name="trash" :size="16" />
                  </button>
                </td>
              </tr>

              <tr v-if="!tranches.length">
                <td colspan="5" class="empty-state">
                  <p>Aucune tranche IRSA n’est configurée.</p>
                  <span>Ajoutez une première ligne pour construire le barème progressif.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="new-row-card">
          <div class="new-row-copy">
            <p class="new-row-title">Ajouter une tranche</p>
            <p class="new-row-subtitle">Préparez un nouvel intervalle puis ajoutez-le au tableau.</p>
          </div>

          <div class="new-row-grid">
            <div class="input-shell">
              <input v-model.number="newTranche.min_base" class="input" type="number" min="0" step="0.01" placeholder="Minimum" />
            </div>
            <div class="input-shell">
              <input v-model="newTranche.max_base" class="input" type="number" min="0" step="0.01" placeholder="Maximum" />
            </div>
            <div class="input-shell suffix">
              <input v-model.number="newTranche.taux" class="input" type="number" min="0" step="0.01" placeholder="Taux" />
              <span class="input-suffix">%</span>
            </div>
            <button class="btn" type="button" @click="addTranche">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </button>
          </div>
        </div>
      </section>
    </template>

    <transition name="savebar">
      <div v-if="hasChanges" class="savebar">
        <div class="savebar-copy">
          <span class="savebar-chip">Unsaved changes</span>
          <div>
            <p class="savebar-title">{{ pendingSummary }}</p>
            <p class="savebar-subtitle">Revoyez vos ajustements puis appliquez-les en une seule action.</p>
          </div>
        </div>

        <div class="savebar-actions">
          <button class="btn btn-secondary" type="button" @click="reload" :disabled="saving">
            Discard
          </button>
          <button class="btn" type="button" @click="save" :disabled="saving">
            <AppIcon name="save" :size="18" />
            <span>{{ saving ? 'Saving...' : 'Save all settings' }}</span>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatMoneyAmount } from '../utils/formatters'
import { getStoredCurrency } from '../utils/currency'

const createDefaultForm = () => ({
  cnaps_plafond: 0,
  cnaps_taux_employe: 0,
  cnaps_taux_employeur: 0,
  ostie_taux_employe: 0,
  ostie_taux_employeur: 0,
  irsa_base: 0,
  irsa_taux: 0,
  hs_taux: 0,
  prime_transport: 0,
  prime_presence: 0,
})

const createDraftTranche = (overrides = {}) => ({
  id: overrides.id ?? null,
  uid: overrides.uid ?? `draft-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
  min_base: overrides.min_base ?? '',
  max_base: overrides.max_base ?? '',
  taux: overrides.taux ?? '',
})

const fieldGroups = [
  {
    title: 'Cotisations sociales',
    description: 'Taux salariés, employeurs et plafond réglementaire.',
    fields: [
      {
        key: 'cnaps_plafond',
        label: 'Plafond CNAPS',
        helper: 'Plafond mensuel brut pris en compte pour la cotisation.',
        suffix: 'currency',
        badge: 'Cap',
        step: '1',
      },
      {
        key: 'cnaps_taux_employe',
        label: 'CNAPS employé',
        helper: 'Part retenue sur le salaire du collaborateur.',
        suffix: '%',
        badge: 'Employee',
        step: '0.01',
      },
      {
        key: 'cnaps_taux_employeur',
        label: 'CNAPS employeur',
        helper: 'Charge portée par l’entreprise.',
        suffix: '%',
        badge: 'Employer',
        step: '0.01',
      },
      {
        key: 'ostie_taux_employe',
        label: 'OSTIE employé',
        helper: 'Part assurance santé imputée au salarié.',
        suffix: '%',
        badge: 'Employee',
        step: '0.01',
      },
      {
        key: 'ostie_taux_employeur',
        label: 'OSTIE employeur',
        helper: 'Part assurance santé imputée à l’entreprise.',
        suffix: '%',
        badge: 'Employer',
        step: '0.01',
      },
    ],
  },
  {
    title: 'Seuils et compléments',
    description: 'Paramètres complémentaires influençant le calcul du net.',
    fields: [
      {
        key: 'irsa_base',
        label: 'Base IRSA',
        helper: 'Base ou seuil de référence utilisé par la configuration fiscale.',
        suffix: 'currency',
        badge: 'Tax',
        step: '1',
      },
      {
        key: 'irsa_taux',
        label: 'Taux IRSA global',
        helper: 'Taux de référence utilisé par certaines simulations.',
        suffix: '%',
        badge: 'Tax',
        step: '0.01',
      },
      {
        key: 'hs_taux',
        label: 'Coefficient heures sup',
        helper: 'Multiplicateur appliqué aux heures supplémentaires.',
        suffix: 'x',
        badge: 'Hours',
        step: '0.01',
      },
      {
        key: 'prime_transport',
        label: 'Prime transport',
        helper: 'Montant mensuel par défaut hors base brute.',
        suffix: 'currency',
        badge: 'Bonus',
        step: '1',
      },
      {
        key: 'prime_presence',
        label: 'Prime présence',
        helper: 'Prime fixe versée selon les règles internes.',
        suffix: 'currency',
        badge: 'Bonus',
        step: '1',
      },
    ],
  },
]

const loading = ref(false)
const saving = ref(false)
const id = ref(null)
const form = ref(createDefaultForm())
const tranches = ref([])
const newTranche = ref(createDraftTranche())
const deletedTrancheIds = ref([])
const lastSyncedAt = ref(null)
const status = ref({ type: '', text: '' })
const initialFormSnapshot = ref('')
const initialTranchesSnapshot = ref('')
const currencyUnit = ref(getStoredCurrency())

const resolveFieldSuffix = (field) => (field.suffix === 'currency' ? currencyUnit.value : field.suffix)

const normalizeNumber = (value, fallback = 0) => {
  if (value === '' || value === null || value === undefined) return fallback
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : fallback
}

const normalizeNullableNumber = (value) => {
  if (value === '' || value === null || value === undefined) return null
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

const normalizeFormState = (source) => ({
  cnaps_plafond: normalizeNumber(source.cnaps_plafond),
  cnaps_taux_employe: normalizeNumber(source.cnaps_taux_employe),
  cnaps_taux_employeur: normalizeNumber(source.cnaps_taux_employeur),
  ostie_taux_employe: normalizeNumber(source.ostie_taux_employe),
  ostie_taux_employeur: normalizeNumber(source.ostie_taux_employeur),
  irsa_base: normalizeNumber(source.irsa_base),
  irsa_taux: normalizeNumber(source.irsa_taux),
  hs_taux: normalizeNumber(source.hs_taux),
  prime_transport: normalizeNumber(source.prime_transport),
  prime_presence: normalizeNumber(source.prime_presence),
})

const sortTranches = (rows) =>
  [...rows].sort((left, right) => normalizeNumber(left.min_base) - normalizeNumber(right.min_base))

const normalizeTrancheState = (source) => ({
  id: source.id ?? null,
  uid: source.uid ?? null,
  min_base: normalizeNumber(source.min_base),
  max_base: normalizeNullableNumber(source.max_base),
  taux: normalizeNumber(source.taux),
})

const formSnapshot = (source) => JSON.stringify(normalizeFormState(source))
const trancheSnapshot = (source) =>
  JSON.stringify(sortTranches(source).map((row) => normalizeTrancheState(row)))

const normalizedForm = computed(() => normalizeFormState(form.value))
const employeeContributionRate = computed(
  () => normalizedForm.value.cnaps_taux_employe + normalizedForm.value.ostie_taux_employe,
)
const employerContributionRate = computed(
  () => normalizedForm.value.cnaps_taux_employeur + normalizedForm.value.ostie_taux_employeur,
)
const totalPrimes = computed(
  () => normalizedForm.value.prime_transport + normalizedForm.value.prime_presence,
)

const metrics = computed(() => [
  {
    label: 'Plafond CNAPS',
    value: formatCurrency(normalizedForm.value.cnaps_plafond),
    caption: 'Plafond mensuel réglementaire',
  },
  {
    label: 'Charge salariale',
    value: formatPercent(employeeContributionRate.value),
    caption: 'CNAPS + OSTIE côté salarié',
  },
  {
    label: 'Charge employeur',
    value: formatPercent(employerContributionRate.value),
    caption: 'CNAPS + OSTIE côté entreprise',
  },
  {
    label: 'Tranches IRSA',
    value: `${tranches.value.length}`,
    caption: 'Lignes actives dans le barème',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Charge salariale',
    value: formatPercent(employeeContributionRate.value),
    copy: 'CNAPS et OSTIE retenus côté collaborateur.',
    tag: 'Salarié',
  },
  {
    label: 'Charge employeur',
    value: formatPercent(employerContributionRate.value),
    copy: 'Part patronale appliquée sur le brut soumis.',
    tag: 'Entreprise',
  },
  {
    label: 'Primes fixes',
    value: formatCurrency(totalPrimes.value),
    copy: 'Transport et présence intégrés au paramétrage.',
    tag: 'Primes',
  },
  {
    label: 'Base IRSA',
    value: formatCurrency(normalizedForm.value.irsa_base),
    copy: 'Seuil fiscal de référence pour vos simulations.',
    tag: 'Fiscalité',
  },
])

const hasChanges = computed(
  () =>
    formSnapshot(form.value) !== initialFormSnapshot.value ||
    trancheSnapshot(tranches.value) !== initialTranchesSnapshot.value ||
    deletedTrancheIds.value.length > 0,
)

const pendingSummary = computed(() => {
  const parts = []
  if (formSnapshot(form.value) !== initialFormSnapshot.value) parts.push('paramètres paie modifiés')

  const currentRows = sortTranches(tranches.value).length
  const initialRows = initialTranchesSnapshot.value ? JSON.parse(initialTranchesSnapshot.value).length : 0
  if (currentRows !== initialRows || trancheSnapshot(tranches.value) !== initialTranchesSnapshot.value) {
    parts.push('barème IRSA ajusté')
  }

  if (deletedTrancheIds.value.length) {
    parts.push(`${deletedTrancheIds.value.length} suppression(s) en attente`)
  }

  return parts.length ? parts.join(' • ') : 'Aucune modification détectée'
})

const lastSyncedLabel = computed(() => {
  if (!lastSyncedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastSyncedAt.value)
})

function setSnapshots() {
  initialFormSnapshot.value = formSnapshot(form.value)
  initialTranchesSnapshot.value = trancheSnapshot(tranches.value)
}

function setStatus(type, text) {
  status.value = { type, text }
}

function formatCurrency(value) {
  return formatMoneyAmount(normalizeNumber(value))
}

function formatPercent(value) {
  return `${new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(normalizeNumber(value))}%`
}

function isUnlimited(value) {
  return value === '' || value === null || value === undefined
}

async function load(showMessage = false) {
  loading.value = true

  try {
    const [{ data: parametres }, { data: irsaRows }] = await Promise.all([
      api.get('/v1/paie-parametres'),
      api.get('/v1/irsa-tranches'),
    ])

    if (parametres) {
      id.value = parametres.id
      form.value = {
        ...createDefaultForm(),
        ...parametres,
      }
    }

    tranches.value = sortTranches((irsaRows || []).map((row) => createDraftTranche(row)))
    deletedTrancheIds.value = []
    newTranche.value = createDraftTranche()
    lastSyncedAt.value = new Date()
    setSnapshots()

    if (showMessage) {
      setStatus('info', 'Les paramètres de paie ont été rechargés.')
    }
  } catch (error) {
    setStatus('error', error.response?.data?.message || 'Erreur lors du chargement des paramètres.')
  } finally {
    loading.value = false
  }
}

async function reload() {
  await load(true)
}

function validateTranches(rows) {
  return rows.every((row) => {
    const minBase = normalizeNullableNumber(row.min_base)
    const taux = normalizeNullableNumber(row.taux)
    const maxBase = normalizeNullableNumber(row.max_base)

    if (minBase === null || taux === null) return false
    if (maxBase !== null && maxBase < minBase) return false
    return true
  })
}

async function save() {
  if (!id.value) {
    setStatus('error', 'Impossible de sauvegarder: aucun paramètre paie trouvé.')
    return
  }

  if (!validateTranches(tranches.value)) {
    setStatus('error', 'Vérifiez les tranches IRSA: min et taux sont requis, max doit être supérieur au min.')
    return
  }

  saving.value = true

  try {
    await api.put(`/v1/paie-parametres/${id.value}`, normalizedForm.value)

    const deleteCalls = deletedTrancheIds.value.map((rowId) => api.delete(`/v1/irsa-tranches/${rowId}`))
    const upsertCalls = sortTranches(tranches.value).map((row) => {
      const payload = normalizeTrancheState(row)
      const body = {
        min_base: payload.min_base,
        max_base: payload.max_base,
        taux: payload.taux,
      }

      return row.id
        ? api.put(`/v1/irsa-tranches/${row.id}`, body)
        : api.post('/v1/irsa-tranches', body)
    })

    await Promise.all([...deleteCalls, ...upsertCalls])
    await load(false)
    setStatus('success', 'Paramètres de paie enregistrés avec succès.')
  } catch (error) {
    setStatus('error', error.response?.data?.message || 'Erreur lors de la sauvegarde des paramètres.')
  } finally {
    saving.value = false
  }
}

function addTranche() {
  const draft = {
    ...newTranche.value,
    min_base: newTranche.value.min_base === '' ? '' : normalizeNumber(newTranche.value.min_base),
    taux: newTranche.value.taux === '' ? '' : normalizeNumber(newTranche.value.taux),
  }

  const minBase = normalizeNullableNumber(draft.min_base)
  const maxBase = normalizeNullableNumber(draft.max_base)
  const taux = normalizeNullableNumber(draft.taux)

  if (minBase === null || taux === null) {
    setStatus('error', 'Le minimum et le taux sont requis pour ajouter une tranche.')
    return
  }

  if (maxBase !== null && maxBase < minBase) {
    setStatus('error', 'Le plafond max doit être supérieur ou égal au minimum.')
    return
  }

  tranches.value = sortTranches([
    ...tranches.value,
    createDraftTranche({
      min_base: minBase,
      max_base: maxBase,
      taux,
    }),
  ])

  newTranche.value = createDraftTranche()
  setStatus('info', 'Nouvelle tranche ajoutée au brouillon.')
}

function appendEmptyTranche() {
  tranches.value = [
    ...tranches.value,
    createDraftTranche(),
  ]
  setStatus('info', 'Nouvelle ligne ajoutée au tableau IRSA.')
}

function removeTranche(index) {
  const target = tranches.value[index]
  if (!target) return

  if (target.id) {
    deletedTrancheIds.value = [...deletedTrancheIds.value, target.id]
  }

  tranches.value = tranches.value.filter((_, currentIndex) => currentIndex !== index)
  setStatus('info', 'Suppression marquée. Elle sera appliquée lors de la sauvegarde.')
}

onMounted(() => {
  currencyUnit.value = getStoredCurrency()
  load()
})
</script>

<style scoped>
.payroll-settings-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 110px;
}

.hero-kicker,
.section-kicker,
.metric-label,
.field-helper,
.hero-meta,
.table-copy,
.new-row-subtitle,
.savebar-subtitle {
  margin: 0;
}

.status-banner {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.78);
  color: var(--text);
  font-weight: 600;
}

body[data-theme='dark'] .status-banner {
  background: rgba(15, 23, 42, 0.78);
}

.status-banner.success {
  border-color: rgba(18, 183, 106, 0.18);
  background: var(--success-100);
  color: var(--success-500);
}

.status-banner.error {
  border-color: rgba(240, 68, 56, 0.18);
  background: var(--danger-100);
  color: var(--danger-500);
}

.status-banner.info {
  border-color: rgba(79, 70, 229, 0.16);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: currentColor;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.metric-card {
  padding: 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
}

.metric-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.metric-value {
  margin: 14px 0 8px;
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.metric-caption {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
}

.section-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: start;
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
  font-size: 1.55rem;
}

.section-chip,
.field-badge,
.scope-pill,
.savebar-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.78);
  color: var(--muted);
  font-size: 0.78rem;
  font-weight: 700;
}

body[data-theme='dark'] .section-chip,
body[data-theme='dark'] .field-badge,
body[data-theme='dark'] .scope-pill,
body[data-theme='dark'] .savebar-chip {
  background: rgba(15, 23, 42, 0.74);
}

.field-sections {
  display: grid;
  gap: 18px;
}

.field-group {
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(248, 250, 252, 0.8);
}

body[data-theme='dark'] .field-group {
  background: rgba(15, 23, 42, 0.38);
}

.field-group-head {
  margin-bottom: 16px;
}

.field-group-head h3 {
  margin: 0;
  font-size: 1.02rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.field-group-head p {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 0.92rem;
}

.fields-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.field-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: var(--panel-solid);
}

body[data-theme='dark'] .field-card {
  background: rgba(15, 23, 42, 0.78);
}

.field-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.field-label {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.field-helper {
  color: var(--muted);
  font-size: 0.84rem;
  line-height: 1.5;
}

.input-shell {
  position: relative;
}

.input-shell.suffix .input {
  padding-right: 58px;
}

.input-shell.compact .input {
  min-height: 42px;
}

.input-suffix {
  position: absolute;
  top: 50%;
  right: 14px;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.field-input {
  font-weight: 600;
}

.insights-card {
  position: sticky;
  top: 18px;
  gap: 16px;
}

.summary-intro,
.overview-label,
.overview-value,
.overview-copy {
  margin: 0;
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

.overview-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.74rem;
  font-weight: 700;
  border: 1px solid rgba(79, 70, 229, 0.12);
}

.overview-label {
  color: var(--muted);
  font-size: 0.82rem;
}

.overview-value {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.overview-copy {
  color: var(--muted);
  font-size: 0.84rem;
  line-height: 1.5;
}

.notes-card {
  padding: 18px;
  border-radius: 22px;
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(255, 255, 255, 0)),
    rgba(248, 250, 252, 0.84);
  border: 1px solid rgba(79, 70, 229, 0.12);
}

body[data-theme='dark'] .notes-card {
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.14), rgba(15, 23, 42, 0)),
    rgba(15, 23, 42, 0.56);
}

.notes-card h3 {
  margin: 0 0 12px;
  font-size: 1rem;
  font-weight: 800;
}

.new-row-title,
.loading-title,
.savebar-title {
  margin: 0;
}

.notes-card ul {
  margin: 0;
  padding-left: 18px;
  color: var(--muted);
  line-height: 1.65;
}

.table-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.table-copy {
  margin-top: 10px;
  color: var(--muted);
  max-width: 760px;
  line-height: 1.65;
}

.add-inline {
  align-self: center;
  white-space: nowrap;
}

.table-shell {
  overflow: auto;
}

.irsa-table {
  min-width: 860px;
}

.tranche-row td {
  vertical-align: middle;
}

.table-input {
  min-height: 42px;
}

.scope-pill {
  color: var(--gray-700);
}

.scope-pill.open {
  color: var(--brand-600);
  background: rgba(79, 70, 229, 0.1);
  border-color: rgba(79, 70, 229, 0.16);
}

.actions-col {
  width: 92px;
  text-align: right;
}

.empty-state {
  padding: 34px 20px;
  text-align: center;
}

.empty-state p {
  margin: 0;
  font-weight: 700;
}

.empty-state span {
  display: inline-block;
  margin-top: 6px;
  color: var(--muted);
  font-size: 0.9rem;
}

.new-row-card {
  display: grid;
  gap: 16px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(248, 250, 252, 0.82);
}

body[data-theme='dark'] .new-row-card {
  background: rgba(15, 23, 42, 0.38);
}

.new-row-subtitle {
  margin-top: 6px;
  color: var(--muted);
}

.new-row-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.loading-card {
  align-items: flex-start;
  justify-content: center;
  min-height: 180px;
}

.loading-title {
  font-size: 1.1rem;
  font-weight: 800;
}

.savebar {
  position: fixed;
  left: 50%;
  bottom: 24px;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  width: min(760px, calc(100vw - 32px));
  padding: 16px 18px;
  border: 1px solid rgba(79, 70, 229, 0.16);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  backdrop-filter: blur(22px);
  transform: translateX(-50%);
}

body[data-theme='dark'] .savebar {
  background: rgba(15, 23, 42, 0.94);
}

.savebar-copy,
.savebar-actions {
  display: flex;
  align-items: center;
}

.savebar-copy {
  gap: 14px;
}

.savebar-chip {
  color: var(--brand-600);
  background: rgba(79, 70, 229, 0.1);
  border-color: rgba(79, 70, 229, 0.14);
}

.savebar-title {
  font-size: 0.98rem;
  font-weight: 800;
}

.savebar-subtitle {
  margin-top: 4px;
  color: var(--muted);
  font-size: 0.84rem;
}

.savebar-actions {
  gap: 10px;
  flex-shrink: 0;
}

.savebar-enter-active,
.savebar-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.savebar-enter-from,
.savebar-leave-to {
  opacity: 0;
  transform: translate(-50%, 10px);
}

@media (max-width: 1200px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .insights-card {
    position: static;
  }
}

@media (max-width: 860px) {
  .fields-grid,
  .new-row-grid,
  .overview-grid {
    grid-template-columns: 1fr;
  }

  .section-heading,
  .table-header {
    flex-direction: column;
  }

  .add-inline {
    align-self: stretch;
  }

  .savebar {
    flex-direction: column;
    align-items: stretch;
  }
}

@media (max-width: 640px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .savebar-copy {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
