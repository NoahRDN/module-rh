<template>
  <div class="contrats-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Contract management</p>
        <h1>Contrats</h1>
        <p class="hero-subtitle">
          Pilotez les contrats actifs et leurs renouvellements avec une vue claire sur les échéances,
          statuts et rattachements organisationnels.
        </p>

        <div class="hero-pills">
          <span class="pill">Contrats actifs</span>
          <span class="pill">Renouvellements</span>
          <span class="pill">Traçabilité RH</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refreshData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>

            <RouterLink class="btn btn-secondary" to="/contrats-historiques">
              <AppIcon name="history" :size="18" />
              <span>Historique</span>
            </RouterLink>
          </div>

          <div class="action-row">
            <RouterLink class="btn" to="/contrats/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouveau contrat</span>
            </RouterLink>
          </div>

          <label class="field-card">
            <span class="field-label">Employé</span>
            <select class="select" v-model="filterEmploye" @change="debouncedFetchContrats">
              <option value="">Tous les employés</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Total contrats:
              <strong>{{ formatInteger(pagination.total || contrats.length) }}</strong>
            </p>
            <p class="hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <p class="banner-success" v-if="banner">{{ banner }}</p>

    <section class="metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <template v-if="loading && !contrats.length">
      <div class="card loading-card">
        <p class="loading-title">Chargement des contrats…</p>
        <p class="muted">Les données contractuelles sont en cours de synchronisation.</p>
      </div>
    </template>

    <template v-else>
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
          Combinez les filtres pour retrouver rapidement un contrat par numéro, collaborateur, type,
          poste ou département.
        </p>

        <div class="controls-grid">
          <label class="field-card">
            <span class="field-label">Numéro</span>
            <input class="input" placeholder="CTR-2026-001" v-model="filters.numero" />
          </label>

          <label class="field-card">
            <span class="field-label">Matricule</span>
            <input class="input" placeholder="EMP-001" v-model="filters.matricule" />
          </label>

          <label class="field-card">
            <span class="field-label">Nom</span>
            <input class="input" placeholder="Nom ou prénom" v-model="filters.nom" />
          </label>

          <label class="field-card">
            <span class="field-label">Type</span>
            <input class="input" placeholder="CDI, CDD..." v-model="filters.type" />
          </label>

          <label class="field-card">
            <span class="field-label">Statut</span>
            <select class="select" v-model="filters.statut">
              <option value="">Tous</option>
              <option value="en_cours">En cours</option>
              <option value="termine">Terminé</option>
              <option value="suspendu">Suspendu</option>
              <option value="brouillon">Brouillon</option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Département</span>
            <input class="input" placeholder="Structure" v-model="filters.departement" />
          </label>

          <label class="field-card">
            <span class="field-label">Poste</span>
            <input class="input" placeholder="Fonction" v-model="filters.poste" />
          </label>
        </div>
      </section>

      <section class="content-grid">
        <article class="card section-card table-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Contract list</p>
              <h2>Contrats en cours et suivis</h2>
            </div>
            <span class="section-chip">{{ formatInteger(contratsFiltres.length) }} visibles</span>
          </div>

          <p class="section-copy">
            Suivez l’état des contrats, les dates clés et les actions de gestion dans un seul tableau.
          </p>

          <div class="table-shell">
            <table class="table contrats-table">
              <thead>
                <tr>
                  <th class="contract-col">
                    <button class="sort-button" type="button" @click="setSort('id')">
                      Contrat
                      <span>{{ sortLabel('id') }}</span>
                    </button>
                  </th>
                  <th class="numero-col">
                    <button class="sort-button" type="button" @click="setSort('numero')">
                      Numéro
                      <span>{{ sortLabel('numero') }}</span>
                    </button>
                  </th>
                  <th class="employee-col">
                    <button class="sort-button" type="button" @click="setSort('nom')">
                      Employé
                      <span>{{ sortLabel('nom') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('type')">
                      Type
                      <span>{{ sortLabel('type') }}</span>
                    </button>
                  </th>
                  <th class="periode-col">
                    <button class="sort-button" type="button" @click="setSort('date_debut')">
                      Période
                      <span>{{ sortLabel('date_debut') }}</span>
                    </button>
                  </th>
                  <th class="essai-col">Essai</th>
                  <th class="renewal-col">
                    <button class="sort-button" type="button" @click="setSort('renouvellement')">
                      Renouvellement
                      <span>{{ sortLabel('renouvellement') }}</span>
                    </button>
                  </th>
                  <th class="status-col">
                    <button class="sort-button" type="button" @click="setSort('statut')">
                      Statut
                      <span>{{ sortLabel('statut') }}</span>
                    </button>
                  </th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="c in contratsFiltres" :key="c.id">
                  <td class="contract-col">
                    <div class="contract-badge">
                      <span class="contract-id">#{{ c.id }}</span>
                      <span class="contract-meta">{{ contractMetaLabel(c) }}</span>
                    </div>
                  </td>
                  <td class="numero-col">
                    <span class="chip soft number-chip">{{ c.numero || '—' }}</span>
                  </td>
                  <td class="employee-col">
                    <div class="employee-cell">
                      <div class="employee-avatar">{{ initials(c.employe) }}</div>
                      <div class="employee-copy">
                        <span class="employee-name">{{ employeName(c.employe) }}</span>
                        <span class="employee-meta">
                          {{ c.employe?.matricule || 'Sans matricule' }}
                          <template v-if="c.employe?.departement?.nom">
                            · {{ c.employe.departement.nom }}
                          </template>
                        </span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="pill type-pill">{{ c.type_contrat || '—' }}</span>
                  </td>
                  <td class="periode-col">
                    <div class="period-block">
                      <span class="period-main">{{ formatRange(c.date_debut, c.date_fin) }}</span>
                      <span class="period-sub">{{ contractDurationLabel(c.date_debut, c.date_fin) }}</span>
                    </div>
                  </td>
                  <td class="essai-col">
                    <span class="chip soft trial-chip">
                      {{ formatRange(c.periode_essai_debut, c.periode_essai_fin) }}
                    </span>
                  </td>
                  <td class="renewal-col">
                    <div class="renewal-block">
                      <span class="renewal-main">{{ formatDate(currentEnd(c)) || '—' }}</span>
                      <span class="renewal-sub">{{ renewalMeta(c) }}</span>
                    </div>
                  </td>
                  <td class="status-col">
                    <div class="status-stack">
                      <span class="chip status-chip" :class="c.statut === 'en_cours' ? 'status-active' : 'status-muted'">
                        {{ statutLabel(c.statut) }}
                      </span>
                      <span class="chip soft renewable-chip" :class="c.renouvelable ? 'renewable-yes' : 'renewable-no'">
                        {{ c.renouvelable ? 'Renouvelable' : 'Non renouvelable' }}
                      </span>
                    </div>
                  </td>
                  <td class="actions-col">
                    <div class="actions-stack">
                    <div class="row-actions primary-actions">
                      <button class="btn btn-secondary btn-xs" @click="ouvrirCloture(c)">Clore</button>
                      <span
                        class="action-wrap"
                        :title="!c.renouvelable ? 'Ce contrat n’est pas renouvelable' : ''"
                      >
                        <button
                          class="btn btn-secondary btn-xs"
                          :disabled="!c.renouvelable"
                          @click="ouvrirRenouv(c)"
                        >
                          Renouveler
                        </button>
                      </span>
                      <RouterLink class="btn btn-secondary btn-xs" :to="`/contrats/${c.id}`">Fiche</RouterLink>
                      <button class="btn btn-secondary btn-xs" @click="telechargerPdf(c.id)">PDF</button>
                    </div>

                    <div v-if="renouvellementId === c.id" class="inline-panel">
                      <p class="inline-title">Prolongation</p>
                      <label class="field-card compact">
                        <span class="field-label">Cible</span>
                        <select class="select" v-model="renouvellementCible">
                          <option value="contrat">Contrat</option>
                          <option value="essai">Période d'essai</option>
                        </select>
                      </label>
                      <div class="duration-grid">
                        <label class="field-card compact">
                          <span class="field-label">Jours</span>
                          <input class="input" type="number" min="0" v-model.number="renouvellement.duree_jours" />
                        </label>
                        <label class="field-card compact">
                          <span class="field-label">Mois</span>
                          <input class="input" type="number" min="0" v-model.number="renouvellement.duree_mois" />
                        </label>
                        <label class="field-card compact">
                          <span class="field-label">Années</span>
                          <input class="input" type="number" min="0" v-model.number="renouvellement.duree_ans" />
                        </label>
                      </div>
                      <div class="row-actions inline-actions">
                        <button class="btn btn-secondary btn-xs" @click="confirmerRenouv(c)">Confirmer</button>
                        <button class="btn btn-secondary btn-xs" @click="annulerRenouv">Annuler</button>
                      </div>
                      <p class="error-inline" v-if="message">{{ message }}</p>
                    </div>

                    <div v-if="clotureId === c.id" class="inline-panel danger">
                      <p class="inline-title">Clôturer ce contrat</p>
                      <label class="field-card compact">
                        <span class="field-label">Date de fin</span>
                        <input class="input" type="date" v-model="clotureDate" />
                      </label>
                      <div class="row-actions inline-actions">
                        <button class="btn btn-secondary btn-xs" @click="confirmerCloture(c)">Clore</button>
                        <button class="btn btn-secondary btn-xs" @click="annulerCloture">Annuler</button>
                      </div>
                      <p class="error-inline" v-if="messageCloture">{{ messageCloture }}</p>
                    </div>
                    </div>
                  </td>
                </tr>

                <tr v-if="!contratsFiltres.length">
                  <td colspan="9" class="empty-state">
                    <p>Aucun contrat ne correspond à la sélection actuelle.</p>
                    <span>Ajustez les filtres ou créez un nouveau contrat.</span>
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

        <aside class="card section-card insights-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Résumé contrats</h2>
            </div>
          </div>

          <p class="summary-intro">
            Lecture synthétique pour suivre la santé contractuelle et anticiper les actions à venir.
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
import { onMounted, ref, computed } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'
import { parseISO, intervalToDuration, formatDuration } from 'date-fns'
import { fr } from 'date-fns/locale'
import AppIcon from '../components/ui/AppIcon.vue'

const contrats = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')
const banner = ref('')
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']
const loading = ref(false)
const lastRefreshedAt = ref(null)
const renouvellementId = ref(null)
const renouvellement = ref({ duree_jours: 0, duree_mois: 0, duree_ans: 0 })
const renouvellementCible = ref('contrat')
const renouvellementEssaiDebut = ref('')
const filters = ref({ numero: '', matricule: '', nom: '', type: '', statut: '', departement: '', poste: '' })
const sortKey = ref('id')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const clotureId = ref(null)
const clotureDate = ref('')
const messageCloture = ref('')
const statutId = ref(null)
const nouveauStatut = ref('en_cours')
const messageStatut = ref('')

const hasFilters = computed(() =>
  Boolean(
    filterEmploye.value ||
    filters.value.numero ||
    filters.value.matricule ||
    filters.value.nom ||
    filters.value.type ||
    filters.value.statut ||
    filters.value.departement ||
    filters.value.poste,
  ),
)

const formatDate = (d) => {
  if (!d) return ''
  return String(d).split('T')[0]
}

const currentEnd = (c) => c.periode_essai_fin || c.date_fin

const formatRange = (start, end) => {
  const startValue = formatDate(start) || '—'
  const endValue = formatDate(end) || '—'
  return `${startValue} -> ${endValue}`
}

const employeName = (employe) => {
  if (!employe) return 'Employé non renseigné'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || 'Employé non renseigné'
}

const initials = (employe) => {
  const label = employeName(employe)
  return label
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'RH'
}

const isExpiringSoonDate = (value, days = 30) => {
  if (!value) return false
  const now = new Date()
  const threshold = new Date(now)
  threshold.setDate(threshold.getDate() + days)
  const endDate = new Date(value)
  if (Number.isNaN(endDate.getTime())) return false
  return endDate >= now && endDate <= threshold
}

const contractDurationLabel = (start, end) => {
  if (!start && !end) return 'Période non renseignée'
  if (start && !end) return 'Contrat sans fin définie'
  if (!start || !end) return 'Période partielle'

  const s = parseISO(start)
  const e = parseISO(end)
  if (isNaN(s) || isNaN(e) || e <= s) return 'Période renseignée'

  const duration = intervalToDuration({ start: s, end: e })
  return formatDuration(duration, { locale: fr }) || 'Période renseignée'
}

const statutLabel = (value) => {
  const labels = {
    en_cours: 'En cours',
    termine: 'Terminé',
    suspendu: 'Suspendu',
    brouillon: 'Brouillon',
  }

  return labels[value] || value || '—'
}

const contractMetaLabel = (c) => {
  if (c.statut && c.statut !== 'en_cours') return statutLabel(c.statut)
  if (isExpiringSoonDate(currentEnd(c))) return 'Échéance proche'
  if (!c.date_fin) return 'Sans fin définie'
  return 'Suivi actif'
}

const renewalMeta = (c) => {
  const end = currentEnd(c)
  if (!end) return 'Sans échéance'
  if (isExpiringSoonDate(end)) return 'Échéance proche'
  return c.renouvelable ? 'Action possible' : 'Échéance fixée'
}

const ouvrirRenouv = (c) => {
  if (!c?.renouvelable) return
  renouvellementId.value = c.id
  renouvellement.value = { duree_jours: 0, duree_mois: 0, duree_ans: 0 }
  renouvellementCible.value = 'contrat'
  renouvellementEssaiDebut.value = ''
  message.value = ''
}

const annulerRenouv = () => {
  renouvellementId.value = null
  renouvellement.value = { duree_jours: 0, duree_mois: 0, duree_ans: 0 }
  message.value = ''
}

const confirmerRenouv = async (c) => {
  const d = renouvellement.value
  if (![d.duree_jours, d.duree_mois, d.duree_ans].some((v) => v && v > 0)) {
    message.value = 'Indique une durée (jours, mois ou années)'
    return
  }
  const target = renouvellementCible.value
  let payload = {}
  if (target === 'contrat') {
    payload = prolongerContrat(c, d)
  } else {
    payload = prolongerEssai(c, d, renouvellementEssaiDebut.value)
  }
  if (!payload.date_debut || !payload.date_fin) {
    message.value = 'Impossible de calculer les nouvelles dates'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: payload.date_debut,
      date_fin: payload.date_fin,
      periode_essai_debut: payload.periode_essai_debut || null,
      periode_essai_fin: payload.periode_essai_fin || null,
      renouvelable: true,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero
    })
    banner.value = 'Contrat renouvelé'
    message.value = ''
    annulerRenouv()
    await fetchContrats()
  } catch (e) {
    message.value = 'Erreur lors du renouvellement'
  }
}

function duree(c) {
  const start = c.periode_essai_debut || c.date_debut
  const end   = c.periode_essai_fin   || c.date_fin
  if (!start || !end) return '—'

  const s = parseISO(start)
  const e = parseISO(end)
  if (isNaN(s) || isNaN(e) || e <= s) return '—'

  const d = intervalToDuration({ start: s, end: e })
  // Exemple: "6 mois" / "1 an 2 mois" / "10 jours"
  return formatDuration(d, { locale: fr })
}


const fetchContrats = async () => {
  loading.value = true
  try {
    const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
    const { data } = await api.get('/v1/contrats', { params })
    contrats.value = data.data || []
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
  } finally {
    loading.value = false
  }
}

const debouncedFetchContrats = debounce(fetchContrats, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const telechargerPdf = async (id) => {
  if (!id) return
  try {
    const { data, headers } = await api.get(`/v1/contrats/${id}/pdf`, {
      responseType: 'blob'
    })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `contrat_${id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    banner.value = 'Impossible de générer le PDF'
  }
}

const addDuration = (start, d) => {
  const date = new Date(start)
  if (d.duree_ans) date.setFullYear(date.getFullYear() + Number(d.duree_ans))
  if (d.duree_mois) date.setMonth(date.getMonth() + Number(d.duree_mois))
  if (d.duree_jours) date.setDate(date.getDate() + Number(d.duree_jours))
  return date.toISOString().slice(0, 10)
}

const prolongerContrat = (c, d) => {
  const start = formatDate(c.date_fin || c.periode_essai_fin || c.date_debut)
  const end = start ? addDuration(start, d) : ''
  return { date_debut: start, date_fin: end, periode_essai_debut: null, periode_essai_fin: null }
}

const prolongerEssai = (c, d, essaiStartOverride) => {
  const essaiStart = essaiStartOverride || formatDate(c.periode_essai_fin || c.periode_essai_debut || c.date_debut)
  const essaiEnd = essaiStart ? addDuration(essaiStart, d) : ''
  return {
    date_debut: formatDate(c.date_debut),
    date_fin: formatDate(c.date_fin),
    periode_essai_debut: essaiStart,
    periode_essai_fin: essaiEnd
  }
}

const ouvrirCloture = (c) => {
  clotureId.value = c.id
  clotureDate.value = formatDate(c.date_fin) || formatDate(new Date())
  messageCloture.value = ''
}

const annulerCloture = () => {
  clotureId.value = null
  clotureDate.value = ''
  messageCloture.value = ''
}

const confirmerCloture = async (c) => {
  if (!clotureDate.value) {
    messageCloture.value = 'Date de fin requise'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: formatDate(c.date_debut),
      date_fin: clotureDate.value,
      periode_essai_debut: formatDate(c.periode_essai_debut),
      periode_essai_fin: formatDate(c.periode_essai_fin),
      renouvelable: c.renouvelable,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero,
      statut: 'termine'
    })
    banner.value = 'Contrat clôturé'
    annulerCloture()
    await fetchContrats()
  } catch (e) {
    messageCloture.value = 'Erreur lors de la clôture'
  }
}

const ouvrirStatut = (c) => {
  statutId.value = c.id
  nouveauStatut.value = c.statut || 'en_cours'
  messageStatut.value = ''
}

const annulerStatut = () => {
  statutId.value = null
  nouveauStatut.value = 'en_cours'
  messageStatut.value = ''
}

const confirmerStatut = async (c) => {
  if (!nouveauStatut.value) {
    messageStatut.value = 'Choisis un statut'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: formatDate(c.date_debut),
      date_fin: formatDate(c.date_fin),
      periode_essai_debut: formatDate(c.periode_essai_debut),
      periode_essai_fin: formatDate(c.periode_essai_fin),
      renouvelable: c.renouvelable,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero,
      statut: nouveauStatut.value
    })
    banner.value = 'Statut mis à jour'
    annulerStatut()
    await fetchContrats()
  } catch (e) {
    messageStatut.value = 'Erreur mise à jour statut'
  }
}

const contratsFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = contrats.value.filter((c) => {
    return (
      toStr(c.numero).includes(toStr(f.numero)) &&
      toStr(c.employe?.matricule).includes(toStr(f.matricule)) &&
      (`${toStr(c.employe?.nom)} ${toStr(c.employe?.prenom)}`).includes(toStr(f.nom)) &&
      toStr(c.type_contrat).includes(toStr(f.type)) &&
      (!f.statut || toStr(c.statut) === toStr(f.statut)) &&
      toStr(c.employe?.departement?.nom).includes(toStr(f.departement)) &&
      toStr(c.employe?.poste?.nom).includes(toStr(f.poste))
    )
  })
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = getSortVal(a, key)
    const vb = getSortVal(b, key)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getSortVal = (c, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'numero':
      return toStr(c.numero)
    case 'matricule':
      return toStr(c.employe?.matricule)
    case 'nom':
      return `${toStr(c.employe?.nom)} ${toStr(c.employe?.prenom)}`
    case 'type':
      return toStr(c.type_contrat)
    case 'date_debut':
      return toStr(c.date_debut)
    case 'renouvellement':
      return toStr(currentEnd(c))
    case 'departement':
      return toStr(c.employe?.departement?.nom)
    case 'statut':
      return toStr(c.statut)
    case 'poste':
      return toStr(c.employe?.poste?.nom)
    case 'id':
    default:
      return Number(c.id) || 0
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

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')

const resetFilters = () => {
  filterEmploye.value = ''
  filters.value = { numero: '', matricule: '', nom: '', type: '', statut: '', departement: '', poste: '' }
  sortKey.value = 'id'
  sortDir.value = 'asc'
}

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

const actifsCount = computed(() => contrats.value.filter((c) => c.statut === 'en_cours').length)
const renouvelablesCount = computed(() => contrats.value.filter((c) => Boolean(c.renouvelable)).length)
const cddCount = computed(() =>
  contrats.value.filter((c) => String(c.type_contrat || '').toLowerCase().includes('cdd')).length,
)
const expiringSoonCount = computed(() => {
  return contrats.value.filter((c) => isExpiringSoonDate(currentEnd(c))).length
})

const metricCards = computed(() => [
  {
    label: 'Contrats actifs',
    value: formatInteger(actifsCount.value),
    caption: `${formatInteger(Math.max(contrats.value.length - actifsCount.value, 0))} hors statut en cours`,
    tag: 'Active',
  },
  {
    label: 'Renouvelables',
    value: formatInteger(renouvelablesCount.value),
    caption: 'Contrats autorisant une prolongation',
    tag: 'Renew',
  },
  {
    label: 'CDD',
    value: formatInteger(cddCount.value),
    caption: 'Contrats à durée déterminée visibles',
    tag: 'Type',
  },
  {
    label: 'Échéance 30 jours',
    value: formatInteger(expiringSoonCount.value),
    caption: 'Contrats arrivant bientôt à terme',
    tag: 'Alert',
  },
])

const topDepartement = computed(() => {
  const counter = new Map()
  contrats.value.forEach((item) => {
    const key = item.employe?.departement?.nom || ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const topType = computed(() => {
  const counter = new Map()
  contrats.value.forEach((item) => {
    const key = item.type_contrat || ''
    if (!key) return
    counter.set(key, (counter.get(key) || 0) + 1)
  })
  return [...counter.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Non défini'
})

const overviewCards = computed(() => [
  {
    label: 'Type dominant',
    value: topType.value,
    copy: 'Type de contrat le plus représenté actuellement',
    tag: 'Type',
  },
  {
    label: 'Département dominant',
    value: topDepartement.value,
    copy: 'Structure concentrant le plus de contrats',
    tag: 'Dept',
  },
])

const notes = computed(() => [
  `${formatInteger(contratsFiltres.value.length)} contrats visibles sur la page.`,
  `${formatInteger(expiringSoonCount.value)} contrats expirent sous 30 jours.`,
  `${formatInteger(renouvelablesCount.value)} contrats sont renouvelables.`,
])

const refreshData = async () => {
  await fetchContrats()
}

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchContrats()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchContrats()
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchContrats()
})
</script>

<style scoped>
.banner-success {
  margin: 0;
  color: #16a34a;
  font-size: 0.9rem;
  font-weight: 700;
}

.field-card.compact {
  gap: 4px;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(320px, 0.9fr);
  gap: 18px;
  align-items: start;
}

.table-card {
  min-width: 0;
  overflow: hidden;
}

.table-shell {
  max-width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 4px;
}

.contrats-table {
  min-width: 1680px;
}

.contrats-table th,
.contrats-table td {
  vertical-align: top;
}

.contrats-table tbody tr:hover {
  background: rgba(79, 70, 229, 0.04);
}

body[data-theme='dark'] .contrats-table tbody tr:hover {
  background: rgba(79, 70, 229, 0.08);
}

.contract-col {
  min-width: 96px;
}

.numero-col {
  min-width: 190px;
}

.employee-col {
  min-width: 250px;
}

.periode-col {
  min-width: 250px;
}

.essai-col {
  min-width: 200px;
}

.renewal-col {
  min-width: 155px;
}

.status-col {
  min-width: 170px;
}

.contract-badge {
  display: grid;
  gap: 4px;
}

.contract-id {
  font-weight: 800;
  letter-spacing: 0;
}

.contract-meta,
.employee-meta,
.period-sub,
.renewal-sub {
  color: var(--muted);
  font-size: 0.8rem;
  line-height: 1.5;
}

.number-chip,
.trial-chip {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.employee-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.16), rgba(14, 165, 233, 0.18));
  color: var(--brand-700);
  font-size: 0.92rem;
  font-weight: 800;
  flex: none;
}

.employee-copy {
  display: grid;
  gap: 4px;
}

.employee-name {
  margin: 0;
  font-weight: 700;
}

.type-pill,
.number-chip,
.trial-chip {
  background: rgba(79, 70, 229, 0.08);
}

.period-block,
.renewal-block,
.status-stack {
  display: grid;
  gap: 4px;
}

.period-main,
.renewal-main {
  font-weight: 700;
}

.status-stack .chip {
  width: fit-content;
}

.status-chip.status-active {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
}

.status-chip.status-muted {
  background: rgba(148, 163, 184, 0.16);
  color: var(--muted);
}

.renewable-chip.renewable-yes {
  background: rgba(79, 70, 229, 0.1);
}

.renewable-chip.renewable-no {
  background: rgba(148, 163, 184, 0.14);
  color: var(--muted);
}

.actions-col {
  width: 340px;
  min-width: 340px;
  white-space: normal;
}

.actions-stack {
  display: grid;
  gap: 10px;
  min-width: 312px;
}

.row-actions {
  display: flex;
  gap: 8px;
}

.primary-actions,
.inline-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.primary-actions > *,
.inline-actions > * {
  width: 100%;
  justify-content: center;
}

.action-wrap {
  display: inline-flex;
  width: 100%;
}

.action-wrap > .btn {
  width: 100%;
  justify-content: center;
}

.inline-panel {
  display: grid;
  gap: 12px;
  width: min(100%, 312px);
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: rgba(248, 250, 252, 0.84);
}

body[data-theme='dark'] .inline-panel {
  background: rgba(15, 23, 42, 0.52);
}

.inline-panel.danger {
  border-color: rgba(239, 68, 68, 0.26);
  background: rgba(254, 242, 242, 0.84);
}

body[data-theme='dark'] .inline-panel.danger {
  background: rgba(127, 29, 29, 0.2);
}

.inline-title {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 800;
}

.duration-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(72px, 1fr));
  gap: 10px;
}

.duration-grid .field-label {
  white-space: nowrap;
}

.duration-grid .input,
.inline-panel .input,
.inline-panel .select {
  min-width: 0;
  width: 100%;
}

.error-inline {
  margin: 0;
  color: #dc2626;
  font-size: 0.78rem;
}

@media (max-width: 1300px) {
  .controls-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

}

@media (max-width: 900px) {
  .duration-grid {
    grid-template-columns: 1fr;
  }

  .actions-col {
    width: 300px;
    min-width: 300px;
  }

  .actions-stack,
  .inline-panel {
    min-width: 272px;
    width: min(100%, 272px);
  }
}

</style>
