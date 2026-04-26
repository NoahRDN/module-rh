<template>
  <div class="rh-page paie-detail-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll detail</p>
        <h1>Fiche de paie {{ paie?.mois || '' }}</h1>
        <p class="hero-subtitle">
          Détail complet du bulletin : collaborateur, contrat, rémunération, retenues et présence.
        </p>

        <div class="hero-pills">
          <span class="pill">{{ employeeName }}</span>
          <span class="pill" :class="statusPillClass">{{ statutLabel }}</span>
          <span class="pill">{{ formatMoney(paie?.net_a_payer) }}</span>
          <span v-if="coutReelEntreprise" class="pill">{{ formatMoney(coutReelEntreprise) }} coût entreprise</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="router.back()">Retour</button>
            <button
              v-if="['non_paye', 'paye'].includes(statutCode)"
              class="btn"
              type="button"
              :disabled="loading"
              @click="downloadPdf"
            >
              <AppIcon name="download" :size="18" />
              <span>PDF</span>
            </button>
            <button
              v-if="statutCode === 'paye'"
              class="btn btn-secondary"
              type="button"
              :disabled="loading"
              @click="downloadReceipt"
            >
              Reçu paiement
            </button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Matricule: <strong>{{ paie?.employe?.matricule || '—' }}</strong>
            </p>
            <p class="hero-meta">
              Demande validation: <strong>{{ formatDateTime(paie?.demande_validation_le) || '—' }}</strong>
            </p>
            <p class="hero-meta">
              Validation paie: <strong>{{ formatDateTime(paie?.valide_le) || '—' }}</strong>
            </p>
            <p class="hero-meta">
              Caisse: <strong>{{ mouvementPaiement?.caisse?.nom || '—' }}</strong>
            </p>
            <p class="hero-meta">
              Paiement validé: <strong>{{ formatDateTime(mouvementPaiement?.valide_le) || formatDate(paie?.paye_le) || '—' }}</strong>
            </p>
          </div>

          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
          </div>
        </div>
      </div>
    </section>

    <div v-if="loading && !paie" class="card loading-card">
      <p class="loading-title">Chargement de la fiche…</p>
      <p class="muted">Récupération des éléments de paie.</p>
    </div>

    <template v-else-if="paie">
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
                <p class="section-kicker">Compensation</p>
                <h2>Rémunération</h2>
              </div>
              <span class="section-chip">{{ paie.mois }}</span>
            </div>

            <div class="overview-grid">
              <div class="overview-card">
                <p class="overview-label">Salaire de base</p>
                <p class="overview-value">{{ formatMoney(paie.salaire_base) }}</p>
                <p class="overview-copy">Base contractuelle utilisée</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Taux horaire</p>
                <p class="overview-value">{{ formatMoney(tauxHoraire) }}</p>
                <p class="overview-copy">Référence 173,33 h/mois</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Taux journalier</p>
                <p class="overview-value">{{ formatMoney(tauxJournalier) }}</p>
                <p class="overview-copy">Référence 30 jours/mois</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Heures supp.</p>
                <p class="overview-value">{{ formatHours(paie.heures_supplementaires) }}</p>
                <p class="overview-copy">Montant : {{ formatMoney(paie.montant_hs) }}</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Total brut</p>
                <p class="overview-value">{{ formatMoney(paie.total_brut) }}</p>
                <p class="overview-copy">Salaire + primes + majorations</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Net à payer</p>
                <p class="overview-value accent">{{ formatMoney(paie.net_a_payer) }}</p>
                <p class="overview-copy">Montant final collaborateur</p>
              </div>
              <div v-if="coutReelEntreprise" class="overview-card">
                <p class="overview-label">Coût réel entreprise</p>
                <p class="overview-value accent">{{ formatMoney(coutReelEntreprise) }}</p>
                <p class="overview-copy">Brut + cotisations patronales + remboursements</p>
              </div>
            </div>
          </article>

          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Timekeeping</p>
                <h2>Temps, absences et retards</h2>
              </div>
            </div>

            <div class="overview-grid">
              <div class="overview-card">
                <p class="overview-label">Heures travaillées</p>
                <p class="overview-value">{{ formatHours(resume.heures_travaillees) }}</p>
                <p class="overview-copy">Total mensuel pointé</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Absences</p>
                <p class="overview-value">{{ formatInteger(resume.absences) }}</p>
                <p class="overview-copy">{{ formatInteger(resume.absences_justifiees) }} justifiée(s)</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Retards</p>
                <p class="overview-value">{{ formatInteger(resume.retard_minutes) }} min</p>
                <p class="overview-copy">Retards cumulés du mois</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Jours non travaillés</p>
                <p class="overview-value">{{ formatInteger(resume.weekends + resume.jours_feries) }}</p>
                <p class="overview-copy">Weekends et jours fériés</p>
              </div>
            </div>
          </article>

          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Daily details</p>
                <h2>Détail journalier</h2>
              </div>
            </div>

            <div class="table-shell">
              <table class="table">
                <thead>
                  <tr>
                    <th>Jour</th>
                    <th>Heures</th>
                    <th>HS</th>
                    <th>Retard</th>
                    <th>Absence</th>
                    <th>Info</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in details" :key="detail.id">
                    <td>{{ formatDate(detail.jour) }}</td>
                    <td>{{ formatHours(detail.heures_travaillees) }}</td>
                    <td>{{ formatHours(detail.heures_supplementaires) }}</td>
                    <td>{{ formatInteger(detail.retard_minutes) }} min</td>
                    <td>{{ detail.absent ? 'Oui' : 'Non' }}</td>
                    <td>{{ dayInfo(detail) }}</td>
                  </tr>
                  <tr v-if="!details.length">
                    <td colspan="6" class="muted">Aucun détail journalier enregistré.</td>
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
                <p class="section-kicker">Profile</p>
                <h2>Collaborateur</h2>
              </div>
            </div>

            <div class="overview-grid side-grid">
              <div class="overview-card">
                <p class="overview-label">Employé</p>
                <p class="overview-value overview-value--wrap">{{ employeeName }}</p>
                <p class="overview-copy">{{ paie.employe?.matricule || '—' }}</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Poste</p>
                <p class="overview-value overview-value--wrap">{{ paie.employe?.poste?.nom || '—' }}</p>
                <p class="overview-copy">{{ paie.employe?.departement?.nom || 'Département non renseigné' }}</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Contrat</p>
                <p class="overview-value overview-value--wrap">{{ contrat?.numero || '—' }}</p>
                <p class="overview-copy">{{ formatDate(contrat?.date_debut) || '—' }} → {{ formatDate(contrat?.date_fin) || '—' }}</p>
              </div>
            </div>
          </article>

          <article class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Deductions</p>
                <h2>Retenues</h2>
              </div>
            </div>

            <div class="amount-list">
              <div v-for="retenue in retenues" :key="retenue.label" class="amount-row">
                <span>{{ retenue.label }}</span>
                <strong>{{ formatMoney(retenue.montant) }}</strong>
              </div>
              <div class="amount-row total">
                <span>Total retenues</span>
                <strong>{{ formatMoney(paie.total_retenues) }}</strong>
              </div>
            </div>
          </article>

          <article v-if="hasEmployerCharges" class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Employer costs</p>
                <h2>Charges patronales</h2>
              </div>
            </div>

            <div class="amount-list">
              <div class="amount-row">
                <span>CNAPS employeur</span>
                <strong>{{ formatMoney(chargesPatronales.cnaps) }}</strong>
              </div>
              <div class="amount-row">
                <span>OSTIE employeur</span>
                <strong>{{ formatMoney(chargesPatronales.ostie) }}</strong>
              </div>
              <div class="amount-row total">
                <span>Total charges patronales</span>
                <strong>{{ formatMoney(chargesPatronales.total) }}</strong>
              </div>
            </div>
          </article>

          <article v-if="hasPrevisionBreakdown" class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Forecast method</p>
                <h2>Méthode de prévision</h2>
              </div>
            </div>

            <div class="amount-list">
              <div class="amount-row">
                <span>Salaire brut</span>
                <strong>{{ formatMoney(previsionBreakdown.salaire_brut) }}</strong>
              </div>
              <div class="amount-row">
                <span>Charges salariales</span>
                <strong>-{{ formatMoney(previsionBreakdown.charges_salariales) }}</strong>
              </div>
              <div class="amount-row">
                <span>Net salaire</span>
                <strong>{{ formatMoney(previsionBreakdown.net_salaire) }}</strong>
              </div>
              <div class="amount-row">
                <span>Indemnités à payer</span>
                <strong>{{ formatMoney(previsionBreakdown.indemnites_a_payer) }}</strong>
              </div>
              <div class="amount-row">
                <span>Cotisations patronales</span>
                <strong>{{ formatMoney(previsionBreakdown.cotisations_patronales) }}</strong>
              </div>
              <div class="amount-row">
                <span>Net à payer employé</span>
                <strong>{{ formatMoney(previsionBreakdown.net_a_payer_employe) }}</strong>
              </div>
              <div class="amount-row total">
                <span>Coût réel entreprise</span>
                <strong>{{ formatMoney(previsionBreakdown.cout_reel_entreprise) }}</strong>
              </div>
            </div>
          </article>

          <article v-if="hasCotisations" class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Reversements</p>
                <h2>Cotisations à reverser</h2>
              </div>
            </div>

            <div class="amount-list">
              <div class="amount-row">
                <span>CNAPS total</span>
                <strong>{{ formatMoney(cotisationsAReverser.cnaps) }}</strong>
              </div>
              <div class="amount-row">
                <span>OSTIE total</span>
                <strong>{{ formatMoney(cotisationsAReverser.ostie) }}</strong>
              </div>
              <div class="amount-row">
                <span>IRSA</span>
                <strong>{{ formatMoney(cotisationsAReverser.irsa) }}</strong>
              </div>
              <div class="amount-row total">
                <span>Total à reverser</span>
                <strong>{{ formatMoney(cotisationsAReverser.total) }}</strong>
              </div>
            </div>
          </article>

          <article class="card section-card side-card">
            <div class="section-heading compact">
              <div>
                <p class="section-kicker">Additional compensation</p>
                <h2>Indemnités et primes</h2>
              </div>
            </div>

            <div class="amount-list">
              <div v-for="prime in primes" :key="prime.label" class="amount-row">
                <span>
                  {{ prime.label }}
                  <small class="muted">
                    ({{ prime.nature === 'indemnite' ? 'Indemnité' : 'Prime' }} • {{ prime.is_taxable === false ? 'Non imposable' : 'Imposable' }})
                  </small>
                </span>
                <strong>{{ formatMoney(prime.montant) }}</strong>
              </div>
              <div v-if="!primes.length" class="amount-row">
                <span>Aucun élément additionnel</span>
                <strong>{{ formatMoney(0) }}</strong>
              </div>
            </div>
          </article>
        </aside>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatDateValue, formatMoneyAmount } from '../utils/formatters'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const error = ref('')
const paie = ref(null)
const contrat = ref(null)
const statut = ref({})
const resume = ref({})
const retenues = ref([])
const primes = ref([])
const mouvementPaiement = ref(null)
const chargesPatronales = ref({})
const cotisationsAReverser = ref({})
const previsionBreakdown = ref({})

const details = computed(() => paie.value?.details || [])
const employeeName = computed(() => {
  const employe = paie.value?.employe
  if (!employe) return '—'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || '—'
})
const statutCode = computed(() => statut.value?.code || '')
const statutLabel = computed(() => statut.value?.label || '—')
const hasEmployerCharges = computed(() => Number(chargesPatronales.value?.total || 0) > 0)
const hasCotisations = computed(() => Number(cotisationsAReverser.value?.total || 0) > 0)
const coutReelEntreprise = computed(() => Number(previsionBreakdown.value?.cout_reel_entreprise || 0))
const hasPrevisionBreakdown = computed(() => coutReelEntreprise.value > 0)
const tauxHoraire = computed(() => paie.value?.taux_horaire ?? (Number(paie.value?.salaire_base || 0) / 173.33))
const tauxJournalier = computed(() => paie.value?.taux_journalier ?? (Number(paie.value?.salaire_base || 0) / 30))
const statusPillClass = computed(() => ({
  'pill-green': statutCode.value === 'paye',
  'pill-red': statutCode.value === 'non_paye',
  'pill-yellow': ['en_attente_validation', 'paiement_en_validation'].includes(statutCode.value),
}))

const metrics = computed(() => [
  {
    tag: 'Gross',
    label: 'Total brut',
    value: formatMoney(paie.value?.total_brut),
    caption: 'Base + primes + majorations',
  },
  {
    tag: 'Deductions',
    label: 'Retenues',
    value: formatMoney(paie.value?.total_retenues),
    caption: 'CNAPS, OSTIE, IRSA',
  },
  {
    tag: 'Net',
    label: 'Net à payer',
    value: formatMoney(paie.value?.net_a_payer),
    caption: 'Montant final',
  },
  {
    tag: 'Hours',
    label: 'Heures travaillées',
    value: formatHours(resume.value?.heures_travaillees),
    caption: 'Total pointage mensuel',
  },
])

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatMoney = (amount) => formatMoneyAmount(amount)
const formatHours = (value) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0))} h`

const formatDate = (value) => formatDateValue(value)

const formatDateTime = (value) => {
  if (!value) return ''
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
}

const dayInfo = (detail) => {
  const labels = []
  if (detail.absence_justifiee) labels.push('Absence justifiée')
  if (detail.ferie) labels.push('Férié')
  if (detail.weekend) labels.push('Weekend')
  if (detail.present_partiel) labels.push('Présence partielle')
  return labels.join(' · ') || '—'
}

const fetchDetail = async () => {
  loading.value = true
  error.value = ''
  try {
    const request = route.name === 'paie-prevision'
      ? api.get('/v1/paies/prevision', { params: { employe_id: route.params.employeId, mois: route.params.mois } })
      : api.get(`/v1/paies/${route.params.id}`)
    const { data } = await request
    paie.value = data.paie
    contrat.value = data.contrat
    statut.value = data.statut || {}
    resume.value = data.resume || {}
    retenues.value = data.retenues || []
    primes.value = data.primes || []
    mouvementPaiement.value = data.mouvement_paiement || null
    chargesPatronales.value = data.charges_patronales || {}
    cotisationsAReverser.value = data.cotisations_a_reverser || {}
    previsionBreakdown.value = data.prevision_breakdown || {}
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement fiche de paie'
  } finally {
    loading.value = false
  }
}

const downloadPdf = async () => {
  if (!paie.value?.id) return
  loading.value = true
  error.value = ''
  try {
    const { data, headers } = await api.get(`/v1/paies/${paie.value.id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bulletin_paie_${paie.value.employe_id}_${paie.value.mois}.pdf`)
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur téléchargement PDF'
  } finally {
    loading.value = false
  }
}

const downloadReceipt = async () => {
  if (!paie.value?.id) return
  loading.value = true
  error.value = ''
  try {
    const { data, headers } = await api.get(`/v1/paies/${paie.value.id}/recu-paiement`, { responseType: 'blob' })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `recu_paiement_${paie.value.employe_id}_${paie.value.mois}.pdf`)
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur téléchargement reçu'
  } finally {
    loading.value = false
  }
}

onMounted(fetchDetail)
</script>

<style scoped>
.paie-detail-page .content-grid {
  align-items: start;
}

.paie-detail-page .side-card {
  position: static;
}

.paie-detail-page .sidebar-column {
  align-content: start;
}

.accent {
  color: var(--brand-600);
}

.pill-yellow {
  border-color: rgba(245, 158, 11, 0.22);
  background: rgba(245, 158, 11, 0.12);
  color: var(--warning-500);
}

.side-grid {
  grid-template-columns: 1fr;
}

.amount-list {
  display: grid;
  gap: 10px;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.amount-row:last-child {
  border-bottom: 0;
}

.amount-row span {
  color: var(--muted);
}

.amount-row.total {
  font-weight: 800;
}
</style>
