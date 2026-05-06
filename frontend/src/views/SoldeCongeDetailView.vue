<template>
  <div class="rh-page solde-detail-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Leave balance detail</p>
        <h1>Détail solde de congé</h1>
        <p class="hero-subtitle">
          Vue détaillée d’un solde de congé avec l’employé concerné, le contrat de référence et l’historique
          des demandes filtrées sur ce type.
        </p>

        <div v-if="solde" class="hero-pills">
          <span class="pill">{{ employeLabel }}</span>
          <span class="pill">{{ solde.type_conge?.libelle || solde.type_conge_libelle || 'Type inconnu' }}</span>
          <span class="pill">{{ formatNumber(solde.solde_actuel) }} jour(s)</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="router.back()">Retour</button>
            <RouterLink class="btn btn-secondary" to="/soldes-conges">Voir tous</RouterLink>
          </div>

          <div class="hero-meta-list" v-if="solde">
            <p class="hero-meta">Employé: <strong>{{ employeLabel }}</strong></p>
            <p class="hero-meta">Contrat: <strong>{{ contratLabel }}</strong></p>
            <p class="hero-meta">Expiration max: <strong>{{ expirationMax }}</strong></p>
          </div>

          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
          </div>
        </div>
      </div>
    </section>

    <div v-if="loading" class="card section-card">
      <p class="loading-title">Chargement du solde…</p>
      <p class="muted">Récupération des informations détaillées.</p>
    </div>

    <div v-else-if="!solde" class="card section-card empty-state">
      <p>Aucune donnée</p>
      <span>Ce solde de congé est introuvable ou n’est plus disponible.</span>
    </div>

    <template v-else>
      <section class="metric-grid">
        <article v-for="metric in metrics" :key="metric.label" class="metric-card">
          <span class="metric-chip">{{ metric.tag }}</span>
          <p class="metric-label">{{ metric.label }}</p>
          <p class="metric-value">{{ metric.value }}</p>
          <p class="metric-caption">{{ metric.caption }}</p>
        </article>
      </section>

      <section class="content-grid">
        <div class="main-column">
          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Balance</p>
                <h2>Résumé du solde</h2>
              </div>
            </div>

            <div class="overview-grid">
              <div class="overview-card">
                <p class="overview-label">Employé</p>
                <p class="overview-value overview-value--wrap">{{ employeLabel }}</p>
                <p class="overview-copy">Collaborateur rattaché à ce solde</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Type de congé</p>
                <p class="overview-value">{{ solde.type_conge?.libelle || solde.type_conge_libelle || '—' }}</p>
                <p class="overview-copy">Nature du droit suivi</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Contrat</p>
                <p class="overview-value overview-value--wrap">{{ contratLabel }}</p>
                <p class="overview-copy">Référence contractuelle utile au calcul</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Premier acquis</p>
                <p class="overview-value">{{ formatDate(solde.premier_acquis || solde.acquis_first) || '—' }}</p>
                <p class="overview-copy">Date du premier droit crédité</p>
              </div>
            </div>
          </article>

          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Requests</p>
                <h2>Historique des demandes</h2>
              </div>
            </div>

            <p class="section-copy">
              Historique des demandes filtré sur l’employé concerné et le type de congé associé à ce solde.
            </p>

            <div class="filters-grid demand-filters">
              <label class="field-card">
                <span class="field-label">Date début</span>
                <input class="input" type="date" v-model="filters.from" />
              </label>

              <label class="field-card">
                <span class="field-label">Date fin</span>
                <input class="input" type="date" v-model="filters.to" />
              </label>

              <div class="field-card demand-filter-actions">
                <span class="field-label">Actions</span>
                <div class="action-row">
                  <button class="btn btn-secondary btn-sm" type="button" @click="resetFilters">Réinitialiser</button>
                </div>
              </div>
            </div>

            <div class="table-shell">
              <table class="table">
                <thead>
                  <tr>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Statut</th>
                    <th>Motif</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in demandes" :key="d.id">
                    <td>{{ formatDate(d.date_debut) || '—' }}</td>
                    <td>{{ formatDate(d.date_fin) || '—' }}</td>
                    <td><span class="chip">{{ d.statut || '—' }}</span></td>
                    <td>{{ d.motif || '—' }}</td>
                  </tr>
                  <tr v-if="!demandes.length">
                    <td colspan="4" class="muted">Aucune demande</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>

        <aside class="sidebar-column">
          <article class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Snapshot</p>
                <h2>Repères rapides</h2>
              </div>
            </div>

            <div class="overview-grid side-grid">
              <div class="overview-card">
                <p class="overview-label">Solde actuel</p>
                <p class="overview-value accent">{{ formatNumber(solde.solde_actuel) }}</p>
                <p class="overview-copy">Droits disponibles actuellement</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Expiration max</p>
                <p class="overview-value">{{ expirationMax }}</p>
                <p class="overview-copy">Date plafond d’utilisation</p>
              </div>
            </div>
          </article>
        </aside>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { formatDateValue } from '../utils/formatters'

const route = useRoute()
const router = useRouter()
const solde = ref(null)
const demandes = ref([])
const loading = ref(false)
const error = ref('')
const filters = ref({ from: '', to: '' })

const employeLabel = computed(() => {
  if (!solde.value) return ''
  const s = solde.value
  return s.employe
    ? `${s.employe.matricule} - ${s.employe.nom} ${s.employe.prenom}`
    : `${s.employe_matricule || ''} ${s.employe_nom || ''} ${s.employe_prenom || ''}`.trim()
})

const contratLabel = computed(() => {
  const s = solde.value
  if (!s) return '—'
  const type = s.contrat_type || s.contratType
  const fin = s.contrat_fin || s.contratFin
  if (type && fin) return `${type} — fin ${formatDate(fin)}`
  if (type) return type
  if (fin) return formatDate(fin)
  return '—'
})

const expirationMax = computed(() => {
  const s = solde.value
  if (!s) return '—'
  const defaultVal = s.expire_first || s.expire_le || '—'
  const contratType = (s.contrat_type || s.contratType || '').toLowerCase()
  const contratFin = s.contrat_fin || s.contratFin
  const acquisFirst = s.acquis_first || s.acquisFirst

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
    return formatDate(contratFin)
  }

  return formatDate(defaultVal) || defaultVal
})

const metrics = computed(() => [
  {
    tag: 'Solde',
    label: 'Solde actuel',
    value: formatNumber(solde.value?.solde_actuel),
    caption: 'Jours actuellement disponibles',
  },
  {
    tag: 'Type',
    label: 'Type de congé',
    value: solde.value?.type_conge?.libelle || solde.value?.type_conge_libelle || '—',
    caption: 'Droit suivi dans cette fiche',
  },
  {
    tag: 'Acquis',
    label: 'Premier acquis',
    value: formatDate(solde.value?.premier_acquis || solde.value?.acquis_first) || '—',
    caption: 'Date de premier crédit enregistré',
  },
  {
    tag: 'Hist.',
    label: 'Demandes liées',
    value: String(demandes.value.length),
    caption: 'Demandes retrouvées sur ce type',
  },
])

const formatDate = (value) => formatDateValue(value)
const formatNumber = (value) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0))

const fetchSolde = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/v1/soldes-conges/${route.params.id}`)
    solde.value = data
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

const fetchDemandes = async () => {
  if (!solde.value) return

  const params = {
    employe_id: solde.value.employe_id,
    type_id: solde.value.type_conge_id,
    from: filters.value.from || undefined,
    to: filters.value.to || undefined,
  }

  try {
    const { data } = await api.get('/v1/demandes-conges', { params })
    demandes.value = data.data || data || []
  } catch (e) {
    demandes.value = []
  }
}

const resetFilters = () => {
  filters.value = { from: '', to: '' }
}

watch(filters, fetchDemandes, { deep: true })

onMounted(async () => {
  await fetchSolde()
  await fetchDemandes()
})
</script>

<style scoped>
.solde-detail-page .hero-meta strong,
.overview-value--wrap {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.demand-filters {
  margin-bottom: 18px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.demand-filter-actions .action-row {
  align-items: flex-end;
}

.side-grid {
  grid-template-columns: 1fr;
}

.accent {
  color: var(--brand-600);
}

@media (max-width: 920px) {
  .demand-filters {
    grid-template-columns: 1fr;
  }
}
</style>
