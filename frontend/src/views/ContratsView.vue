<template>
  <div class="contrats-page">
    <section class="hero">
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
                  <th>
                    <button class="sort-button" type="button" @click="setSort('id')">
                      ID
                      <span>{{ sortLabel('id') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('numero')">
                      Numéro
                      <span>{{ sortLabel('numero') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('matricule')">
                      Matricule
                      <span>{{ sortLabel('matricule') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('nom')">
                      Collaborateur
                      <span>{{ sortLabel('nom') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('type')">
                      Type
                      <span>{{ sortLabel('type') }}</span>
                    </button>
                  </th>
                  <th>Durée</th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('date_debut')">
                      Dates contrat
                      <span>{{ sortLabel('date_debut') }}</span>
                    </button>
                  </th>
                  <th>Période d'essai</th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('renouvellement')">
                      Renouvellement
                      <span>{{ sortLabel('renouvellement') }}</span>
                    </button>
                  </th>
                  <th>Renouvelable</th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('statut')">
                      Statut
                      <span>{{ sortLabel('statut') }}</span>
                    </button>
                  </th>
                  <th>
                    <button class="sort-button" type="button" @click="setSort('departement')">
                      Département
                      <span>{{ sortLabel('departement') }}</span>
                    </button>
                  </th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="c in contratsFiltres" :key="c.id">
                  <td>{{ c.id }}</td>
                  <td>{{ c.numero || '—' }}</td>
                  <td>{{ c.employe?.matricule || '—' }}</td>
                  <td>{{ c.employe ? `${c.employe.nom} ${c.employe.prenom}` : '—' }}</td>
                  <td>{{ c.type_contrat || '—' }}</td>
                  <td>{{ duree(c) }}</td>
                  <td>
                    <div>Début : {{ formatDate(c.date_debut) || '—' }}</div>
                    <div>Fin : {{ formatDate(c.date_fin) || '—' }}</div>
                  </td>
                  <td>
                    <div>Début : {{ formatDate(c.periode_essai_debut) || '—' }}</div>
                    <div>Fin : {{ formatDate(c.periode_essai_fin) || '—' }}</div>
                  </td>
                  <td>{{ formatDate(currentEnd(c)) || '—' }}</td>
                  <td>
                    <span class="chip" :class="c.renouvelable ? '' : 'muted'">{{ c.renouvelable ? 'Oui' : 'Non' }}</span>
                  </td>
                  <td>
                    <span class="chip" :class="c.statut === 'en_cours' ? '' : 'muted'">{{ c.statut || '—' }}</span>
                  </td>
                  <td>{{ c.employe?.departement?.nom || '—' }}</td>
                  <td class="actions-col">
                    <div class="row-actions">
                      <button class="btn btn-secondary btn-xs" @click="ouvrirCloture(c)">Clore</button>
                      <button class="btn btn-secondary btn-xs" @click="ouvrirRenouv(c)">Renouveler</button>
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
                      <div class="row-actions">
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
                      <div class="row-actions">
                        <button class="btn btn-secondary btn-xs" @click="confirmerCloture(c)">Clore</button>
                        <button class="btn btn-secondary btn-xs" @click="annulerCloture">Annuler</button>
                      </div>
                      <p class="error-inline" v-if="messageCloture">{{ messageCloture }}</p>
                    </div>
                  </td>
                </tr>

                <tr v-if="!contratsFiltres.length">
                  <td colspan="13" class="empty-state">
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
const filters = ref({ numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' })
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
    filters.value.departement ||
    filters.value.poste,
  ),
)

const formatDate = (d) => {
  if (!d) return ''
  return String(d).split('T')[0]
}

const currentEnd = (c) => c.periode_essai_fin || c.date_fin

const ouvrirRenouv = (c) => {
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
  filters.value = { numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' }
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
  const now = new Date()
  const threshold = new Date(now)
  threshold.setDate(threshold.getDate() + 30)

  return contrats.value.filter((c) => {
    const end = currentEnd(c)
    if (!end) return false
    const endDate = new Date(end)
    if (Number.isNaN(endDate.getTime())) return false
    return endDate >= now && endDate <= threshold
  }).length
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
.contrats-page {
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
  max-width: 420px;
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

.banner-success {
  margin: 0;
  color: #16a34a;
  font-size: 0.9rem;
  font-weight: 700;
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

.field-card.compact {
  gap: 4px;
}

.field-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(320px, 0.9fr);
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

.contrats-table td {
  vertical-align: top;
}

.actions-col {
  width: 1%;
  white-space: nowrap;
}

.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.inline-panel {
  margin-top: 10px;
  display: grid;
  gap: 10px;
  padding: 12px;
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
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.error-inline {
  margin: 0;
  color: #dc2626;
  font-size: 0.78rem;
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

@media (max-width: 1300px) {
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
  .overview-grid,
  .duration-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .metric-grid {
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
