<template>
  <div class="rh-page contrat-detail-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Fiche contrat</p>
        <h1>Contrat #{{ contrat?.id || route.params.id }}</h1>
        <p class="hero-subtitle">Vue synthétique du contrat et de son historique.</p>

        <div class="hero-pills">
          <span class="pill">{{ contrat?.type_contrat || 'Type non défini' }}</span>
          <span v-if="contrat?.numero" class="pill">N° {{ contrat.numero }}</span>
          <span class="pill" :class="contrat?.renouvelable ? 'pill-green' : 'pill-red'">
            {{ contrat?.renouvelable ? 'Renouvelable' : 'Non renouvelable' }}
          </span>
          <span v-if="contrat?.statut" class="pill">{{ contrat.statut }}</span>
          <span v-if="joursRestants !== null && joursRestants <= 30" class="pill pill-red">
            Échéance dans {{ joursRestants }} j
          </span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="router.back()">Retour</button>
            <button class="btn" type="button" @click="telechargerPdf" :disabled="!contrat?.id">PDF contrat</button>
          </div>

          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/contrats-historiques">Historique</RouterLink>
            <RouterLink
              v-if="contrat?.employe?.id"
              class="btn btn-secondary"
              :to="{ name: 'employe-detail', params: { id: contrat.employe.id } }"
            >
              Employé
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Employé:
              <strong>{{ employeLabel }}</strong>
            </p>
            <p class="hero-meta">
              Période:
              <strong>{{ periodeContratLabel }}</strong>
            </p>
            <p class="hero-meta">
              Salaire:
              <strong>{{ formatSalaire(contrat?.salaire_base) }}</strong>
            </p>
          </div>

          <div v-if="deadlineBanner" class="status-banner" :class="deadlineBanner.tone">
            <span class="status-dot" />
            <span>{{ deadlineBanner.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <template v-if="contrat">
      <section class="content-grid">
        <div class="main-column">
          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Overview</p>
                <h2>Aperçu</h2>
              </div>
              <span class="section-chip">{{ contrat.type_contrat || 'Contrat' }}</span>
            </div>

            <p class="section-copy">Informations opérationnelles principales (employé, rémunération, périodes).</p>

            <div class="overview-grid">
              <div class="overview-card">
                <p class="overview-label">Employé</p>
                <p class="overview-value overview-value--wrap">{{ employeLabel }}</p>
                <p class="overview-copy overview-value--wrap">{{ employeOrganisationLabel }}</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Salaire de base</p>
                <p class="overview-value">{{ formatSalaire(contrat.salaire_base) }}</p>
                <p class="overview-copy">Montant brut de référence</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Durée du contrat</p>
                <p class="overview-value">{{ dureeContrat }}</p>
                <p class="overview-copy overview-value--wrap">{{ periodeContratLabel }}</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Période d'essai</p>
                <p class="overview-value overview-value--wrap">{{ periodeEssai }}</p>
                <p class="overview-copy overview-value--wrap">{{ periodeEssaiLabel }}</p>
              </div>
            </div>
          </article>

          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Details</p>
                <h2>Informations contractuelles</h2>
              </div>
              <span class="section-chip">{{ contrat.numero || `#${contrat.id}` }}</span>
            </div>

            <div class="overview-grid">
              <div class="overview-card">
                <p class="overview-label">Numéro</p>
                <p class="overview-value overview-value--wrap">{{ contrat.numero || '—' }}</p>
                <p class="overview-copy">Identifiant interne</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Statut</p>
                <p class="overview-value">{{ contrat.statut || '—' }}</p>
                <p class="overview-copy">Cycle de vie du contrat</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Renouvelable</p>
                <p class="overview-value">{{ contrat.renouvelable ? 'Oui' : 'Non' }}</p>
                <p class="overview-copy">Règle de renouvellement</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Dates</p>
                <p class="overview-value overview-value--wrap">{{ periodeContratLabel }}</p>
                <p class="overview-copy">Début → Fin</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Essai</p>
                <p class="overview-value overview-value--wrap">{{ periodeEssaiLabel }}</p>
                <p class="overview-copy">Période d'essai (début → fin)</p>
              </div>

              <div class="overview-card">
                <p class="overview-label">Échéance</p>
                <p class="overview-value">{{ joursRestantsLabel }}</p>
                <p class="overview-copy">Temps restant avant fin</p>
              </div>
            </div>
          </article>

          <article class="card section-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">History</p>
                <h2>Évolutions du contrat</h2>
              </div>
              <RouterLink class="btn btn-secondary btn-sm" to="/contrats-historiques">Voir tout</RouterLink>
            </div>

            <p class="section-copy">Suivi des modifications rattachées à ce contrat.</p>

            <div class="table-shell">
              <table class="table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Numéro</th>
                    <th>Type</th>
                    <th>Durée</th>
                    <th>Contrat</th>
                    <th>Période d'essai</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in histContrats" :key="h.id">
                    <td>{{ h.id }}</td>
                    <td>{{ h.numero || '—' }}</td>
                    <td>{{ h.type_contrat || '—' }}</td>
                    <td>{{ duree(h) }}</td>
                    <td>{{ formatDate(h.date_debut) || '—' }} → {{ formatDate(h.date_fin) || '—' }}</td>
                    <td>{{ formatDate(h.periode_essai_debut) || '—' }} → {{ formatDate(h.periode_essai_fin) || '—' }}</td>
                    <td><span class="chip">{{ h.statut || '—' }}</span></td>
                  </tr>
                  <tr v-if="!histContrats.length">
                    <td colspan="7" class="muted">Aucun historique</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>

        <aside class="sidebar-column">
          <article class="card section-card side-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Insights</p>
                <h2>Synthèse</h2>
              </div>
            </div>

            <div class="chip-list">
              <span class="pill">{{ contrat.type_contrat || 'Contrat' }}</span>
              <span class="pill" :class="contrat.renouvelable ? 'pill-green' : 'pill-red'">
                {{ contrat.renouvelable ? 'Renouvelable' : 'Non renouvelable' }}
              </span>
              <span v-if="contrat.statut" class="pill">{{ contrat.statut }}</span>
            </div>

            <div class="overview-grid contrat-sidebar-grid">
              <div class="overview-card">
                <p class="overview-label">Employé</p>
                <p class="overview-value overview-value--wrap">{{ contrat.employe?.matricule || '—' }}</p>
                <p class="overview-copy overview-value--wrap">{{ contrat.employe?.nom }} {{ contrat.employe?.prenom }}</p>
              </div>
              <div class="overview-card">
                <p class="overview-label">Échéance</p>
                <p class="overview-value">{{ joursRestantsLabel }}</p>
                <p class="overview-copy overview-value--wrap">{{ formatDate(contrat.date_fin) || '—' }}</p>
              </div>
            </div>

            <div class="action-row">
              <RouterLink class="btn btn-secondary btn-sm" to="/contrats">Liste contrats</RouterLink>
              <RouterLink class="btn btn-secondary btn-sm" to="/contrats-historiques">Historique</RouterLink>
            </div>
          </article>
        </aside>
      </section>
    </template>

    <div v-else class="card loading-card">
      <p class="loading-title">Chargement du contrat…</p>
      <p class="muted">Les informations contractuelles sont en cours de synchronisation.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { parseISO, intervalToDuration, formatDuration, differenceInCalendarDays } from 'date-fns'
import { fr } from 'date-fns/locale'
import { formatMoneyAmount } from '../utils/formatters'

const route = useRoute()
const router = useRouter()
const contrat = ref(null)
const histContrats = ref([])

const fetchContrat = async () => {
  const { data } = await api.get(`/v1/contrats/${route.params.id}`)
  contrat.value = data
  // Charger l'historique uniquement pour ce contrat
  if (data?.id) {
    const hist = await api.get('/v1/contrats-historiques', { params: { contrat_id: data.id } })
    histContrats.value = hist.data?.data || []
  }
}

const formatDate = (d) => (d ? String(d).split('T')[0] : '')

const formatSalaire = (amount) => {
  return formatMoneyAmount(amount)
}

const joursRestants = computed(() => {
  const fin = contrat.value?.date_fin
  if (!fin) return null
  const end = parseISO(fin)
  if (isNaN(end)) return null
  const diff = differenceInCalendarDays(end, new Date())
  return diff >= 0 ? diff : null
})

const joursRestantsLabel = computed(() => {
  if (joursRestants.value === null) return '—'
  if (joursRestants.value === 0) return "Aujourd'hui"
  return `${joursRestants.value} j`
})

const dureeContrat = computed(() => {
  const start = contrat.value?.date_debut
  const end = contrat.value?.date_fin
  if (!start && !end) return '—'
  if (start && !end) return 'En cours'
  if (!start || !end) return '—'
  const s = parseISO(start)
  const e = parseISO(end)
  if (isNaN(s) || isNaN(e) || e <= s) return '—'
  const d = intervalToDuration({ start: s, end: e })
  return formatDuration(d, { locale: fr, format: ['years', 'months', 'days'] }) || '—'
})

const periodeEssai = computed(() => {
  const s = contrat.value?.periode_essai_debut
  const e = contrat.value?.periode_essai_fin
  if (!s && !e) return '—'
  if (s && e) {
    const start = parseISO(s)
    const end = parseISO(e)
    if (!isNaN(start) && !isNaN(end) && end > start) {
      const d = intervalToDuration({ start, end })
      return formatDuration(d, { locale: fr, format: ['months', 'days'] }) || `${formatDate(s)} → ${formatDate(e)}`
    }
  }
  return `${formatDate(s) || '—'} → ${formatDate(e) || '—'}`
})

const periodeEssaiLabel = computed(() => {
  if (!contrat.value) return '—'
  return `${formatDate(contrat.value.periode_essai_debut) || '—'} → ${formatDate(contrat.value.periode_essai_fin) || '—'}`
})

const employeLabel = computed(() => {
  const e = contrat.value?.employe
  if (!e) return 'Employé non renseigné'
  const matricule = e.matricule ? `${e.matricule} — ` : ''
  return `${matricule}${e.nom || ''} ${e.prenom || ''}`.trim() || 'Employé non renseigné'
})

const employeOrganisationLabel = computed(() => {
  const e = contrat.value?.employe
  if (!e) return '—'
  const poste = e.poste?.nom || 'Poste non renseigné'
  const departement = e.departement?.nom || 'Département non renseigné'
  return `${poste} • ${departement}`
})

const periodeContratLabel = computed(() => {
  if (!contrat.value) return '—'
  return `${formatDate(contrat.value.date_debut) || '—'} → ${formatDate(contrat.value.date_fin) || '—'}`
})

const deadlineBanner = computed(() => {
  if (joursRestants.value === null) return null
  if (joursRestants.value <= 7) return { tone: 'danger', label: `Échéance imminente (${joursRestants.value} j)` }
  if (joursRestants.value <= 30) return { tone: 'warning', label: `Échéance proche (${joursRestants.value} j)` }
  return null
})

const telechargerPdf = async () => {
  if (!contrat.value?.id) return
  try {
    const { data, headers } = await api.get(`/v1/contrats/${contrat.value.id}/pdf`, {
      responseType: 'blob'
    })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `contrat_${contrat.value.id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    // ignore
  }
}

const duree = (row) => {
  const start = row.periode_essai_debut || row.date_debut
  const end = row.periode_essai_fin || row.date_fin
  if (!start || !end) return '—'
  const s = parseISO(start)
  const e = parseISO(end)
  if (isNaN(s) || isNaN(e) || e <= s) return '—'
  const d = intervalToDuration({ start: s, end: e })
  return formatDuration(d, { locale: fr, format: ['years', 'months', 'days'] }) || '—'
}

onMounted(fetchContrat)
</script>

<style scoped>
.overview-value--wrap {
  word-break: break-word;
  overflow-wrap: anywhere;
  max-width: 100%;
}

.contrat-sidebar-grid {
  grid-template-columns: 1fr;
}
</style>
