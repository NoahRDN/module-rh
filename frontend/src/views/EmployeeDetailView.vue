<template>
  <div class="rh-page employee-detail-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <div class="employee-title">
          <img v-if="employe.photo" class="employee-avatar employee-photo" :src="employe.photo" alt="Employé" />
          <div v-else class="employee-avatar employee-avatar-fallback">{{ initials(employe) }}</div>
          <div>
            <p class="hero-kicker">Fiche employé</p>
            <h1>{{ fullName }}</h1>
            <p class="hero-subtitle">
              {{ posteLabel }} • {{ departementLabel }}
            </p>
          </div>
        </div>

        <div class="hero-pills">
          <span class="pill">Matricule {{ employe.matricule || '—' }}</span>
          <span class="pill">Catégorie {{ categorieLabel }}</span>
          <span class="pill">{{ isActif ? 'Actif' : 'Inactif' }}</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="router.back()">Retour</button>
            <button class="btn" type="button" @click="telechargerPdf" :disabled="!employe.id">PDF fiche</button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Email: <strong>{{ employe.email || '—' }}</strong>
            </p>
            <p class="hero-meta">
              Embauche: <strong>{{ formatDate(employe.date_embauche) || '—' }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

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
              <p class="section-kicker">Contract</p>
              <h2>Contrat actuel</h2>
            </div>
            <RouterLink class="btn btn-secondary btn-sm" to="/contrats">Voir tous</RouterLink>
          </div>

          <p class="section-copy">
            Détail du contrat actuellement rattaché à cet employé.
          </p>

          <div v-if="contratActuel" class="overview-grid">
            <div class="overview-card">
              <p class="overview-label">Numéro</p>
              <p class="overview-value">{{ contratActuel.numero || '—' }}</p>
              <p class="overview-copy">Identifiant contrat</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Type</p>
              <p class="overview-value">{{ contratActuel.type_contrat || '—' }}</p>
              <p class="overview-copy">Nature du contrat</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Période</p>
              <p class="overview-value overview-value--wrap">
                {{ formatDate(contratActuel.date_debut) || '—' }} → {{ formatDate(contratActuel.date_fin) || '—' }}
              </p>
              <p class="overview-copy">Dates de validité</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Période d'essai</p>
              <p class="overview-value overview-value--wrap">
                {{ formatDate(contratActuel.periode_essai_debut) || '—' }} → {{ formatDate(contratActuel.periode_essai_fin) || '—' }}
              </p>
              <p class="overview-copy">Essai</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Salaire</p>
              <p class="overview-value">{{ formatMoney(contratActuel.salaire_base) }}</p>
              <p class="overview-copy">Salaire de base</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Taux horaire</p>
              <p class="overview-value">{{ formatMoney(tauxHoraireContrat) }}</p>
              <p class="overview-copy">Sur {{ formatNumber(heuresMensuellesContrat) }} h imposées</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Taux journalier</p>
              <p class="overview-value">{{ formatMoney(tauxJournalierContrat) }}</p>
              <p class="overview-copy">Sur {{ formatNumber(joursOuvresContrat) }} jours ouvrés</p>
            </div>
          </div>

          <div v-else class="empty-state">
            <p>Aucun contrat associé</p>
            <span>Créez ou rattachez un contrat depuis la gestion des contrats.</span>
          </div>
        </article>

        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Mobility</p>
              <h2>Historique des postes</h2>
            </div>
            <RouterLink class="btn btn-secondary btn-sm" to="/historiques">Voir</RouterLink>
          </div>

          <p class="section-copy">
            Liste des mobilités enregistrées pour retracer les changements de poste et de département.
          </p>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>Poste</th>
                  <th>Département</th>
                  <th>Date</th>
                  <th>Motif</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="h in histPostes" :key="h.id">
                  <td>{{ h.poste?.nom || '—' }}</td>
                  <td>{{ h.departement?.nom || '—' }}</td>
                  <td>{{ formatDate(h.date_changement) || '—' }}</td>
                  <td>{{ h.motif || '—' }}</td>
                </tr>
                <tr v-if="!histPostes.length">
                  <td colspan="4" class="muted">Aucun historique</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Contracts</p>
              <h2>Historique des contrats</h2>
            </div>
            <RouterLink class="btn btn-secondary btn-sm" to="/contrats-historiques">Voir</RouterLink>
          </div>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>Numéro</th>
                  <th>Type</th>
                  <th>Période</th>
                  <th>Période d'essai</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="h in histContrats" :key="h.id">
                  <td>{{ h.numero || '—' }}</td>
                  <td>{{ h.type_contrat || '—' }}</td>
                  <td>{{ formatDate(h.date_debut) || '—' }} → {{ formatDate(h.date_fin) || '—' }}</td>
                  <td>
                    {{ formatDate(h.periode_essai_debut) || '—' }} → {{ formatDate(h.periode_essai_fin) || '—' }}
                  </td>
                </tr>
                <tr v-if="!histContrats.length">
                  <td colspan="4" class="muted">Aucun historique</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Employee files</p>
              <h2>Documents</h2>
            </div>
            <RouterLink
              class="btn btn-secondary btn-sm"
              :to="{ name: 'document-create', query: { employe_id: employe.id, return: route.fullPath } }"
            >
              Ajouter
            </RouterLink>
          </div>

          <p class="section-copy">
            Pièces justificatives rattachées à l’employé avec type, date d’importation, expiration éventuelle et accès direct à l’aperçu.
          </p>

          <div class="table-shell">
            <table class="table documents-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Importation</th>
                  <th>Expiration</th>
                  <th>Lot</th>
                  <th>Aperçu</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="group in documentGroups" :key="group.key">
                  <td>
                    <div class="document-group-cell">
                      <span class="document-group-title">{{ group.type_document || '—' }}</span>
                      <div class="document-group-files">
                        <span
                          v-for="doc in group.documents.slice(0, 4)"
                          :key="doc.id"
                          class="document-file-chip"
                          :title="documentFileName(doc)"
                        >
                          {{ documentFileName(doc) }}
                        </span>
                        <span v-if="group.documents.length > 4" class="document-file-chip document-file-chip-more">
                          +{{ group.documents.length - 4 }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td>{{ formatDate(group.date_importation) || '—' }}</td>
                  <td>{{ formatDate(group.date_expiration) || '—' }}</td>
                  <td>
                    <span class="chip">{{ group.documents.length }} fichier{{ group.documents.length > 1 ? 's' : '' }}</span>
                  </td>
                  <td>
                    <button
                      class="btn btn-secondary btn-xs"
                      type="button"
                      @click="openDocumentGroupPreview(group)"
                      :disabled="!group.previewableDocuments.length"
                    >
                      {{ group.previewableDocuments.length ? 'Aperçu' : 'Indisponible' }}
                    </button>
                  </td>
                  <td class="actions-col">
                    <div class="inline-actions">
                      <button
                        class="btn btn-secondary btn-xs"
                        type="button"
                        @click="downloadDocumentGroup(group)"
                        :disabled="downloadLoadingId === `group-${group.key}`"
                      >
                        {{ downloadLoadingId === `group-${group.key}` ? '...' : group.documents.length > 1 ? 'Télécharger tout' : 'Télécharger' }}
                      </button>
                      <button class="btn btn-secondary btn-xs" type="button" @click="editDocumentGroup(group)">
                        Modifier
                      </button>
                      <button
                        class="btn btn-secondary btn-xs btn-danger-soft"
                        type="button"
                        @click="removeDocumentGroup(group)"
                        :disabled="deleteLoadingId === `group-${group.key}`"
                      >
                        {{ deleteLoadingId === `group-${group.key}` ? '...' : 'Supprimer' }}
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!documentGroups.length">
                  <td colspan="6" class="empty-state">
                    <p>Aucun document enregistré pour cet employé.</p>
                    <span>Ajoutez une pièce justificative pour démarrer le suivi documentaire.</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Leave requests</p>
              <h2>Demandes de congés</h2>
            </div>
          </div>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Début</th>
                  <th>Fin</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in demandes" :key="d.id">
                  <td>{{ d.type_conge?.libelle || d.type?.label || '—' }}</td>
                  <td>{{ formatDate(d.date_debut) || '—' }}</td>
                  <td>{{ formatDate(d.date_fin) || '—' }}</td>
                  <td><span class="chip">{{ d.statut || '—' }}</span></td>
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
        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Attendance</p>
              <h2>Pointages</h2>
            </div>
          </div>

          <div class="toolbar pointage-toolbar">
            <select class="select" v-model="pointageMode">
              <option value="week">Semaine</option>
              <option value="month">Mois</option>
              <option value="year">Année</option>
            </select>
            <input
              v-if="pointageMode === 'week' || pointageMode === 'month'"
              class="input"
              type="month"
              v-model="pointageMonth"
            />
            <input v-else class="input" type="number" min="2000" max="2100" v-model="pointageYear" />
            <button class="btn btn-secondary btn-sm" type="button" @click="loadPointages">Actualiser</button>
          </div>

          <div v-if="!isActif" class="empty-state">
            <p>Employé inactif</p>
            <span>Les pointages ne sont pas affichés.</span>
          </div>

          <template v-else>
            <div v-if="pointageMode === 'week'" class="overview-grid pointage-grid">
              <div v-for="w in pointagesSynth" :key="w.label" class="overview-card">
                <p class="overview-label">Semaine</p>
                <p class="overview-value overview-value--wrap">{{ w.label }}</p>
                <p class="overview-copy">
                  Heures: {{ w.heures_travaillees }} • HS: {{ w.heures_supplementaires }} • Retards: {{ w.retard_minutes }} min
                </p>
                <p class="overview-copy">Absences: {{ w.absences }} • Dimanches: {{ w.dimanches }}</p>
              </div>
              <p v-if="!pointagesSynth.length" class="muted">Aucune donnée</p>
            </div>

            <div v-if="pointageMode === 'month'" class="table-shell">
              <table class="table">
                <thead>
                  <tr>
                    <th>Jour</th>
                    <th>Heures</th>
                    <th>HS</th>
                    <th>Retard</th>
                    <th>Absence</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in pointagesDetails" :key="d.jour">
                    <td>{{ d.jour }}</td>
                    <td>{{ d.heures_travaillees }}</td>
                    <td>{{ d.heures_supplementaires }}</td>
                    <td>{{ d.retard_minutes }} min</td>
                    <td>{{ d.absent ? 1 : 0 }}</td>
                  </tr>
                  <tr v-if="!pointagesDetails.length">
                    <td colspan="5" class="muted">Aucune donnée</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="pointageMode === 'year'" class="table-shell">
              <table class="table">
                <thead>
                  <tr>
                    <th>Mois</th>
                    <th>Heures</th>
                    <th>HS</th>
                    <th>Retards</th>
                    <th>Absences</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in pointagesSynth" :key="m.label">
                    <td>{{ m.label }}</td>
                    <td>{{ m.heures_travaillees }}</td>
                    <td>{{ m.heures_supplementaires }}</td>
                    <td>{{ m.retard_minutes }} min</td>
                    <td>{{ m.absences }}</td>
                  </tr>
                  <tr v-if="!pointagesSynth.length">
                    <td colspan="5" class="muted">Aucune donnée</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </article>

        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Leave balance</p>
              <h2>Soldes de congé</h2>
            </div>
            <RouterLink class="btn btn-secondary btn-sm" to="/soldes-conges">Voir tout</RouterLink>
          </div>

          <div class="table-shell">
            <table class="table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Acquis</th>
                  <th>Utilisé</th>
                  <th>Solde</th>
                  <th>Expiration</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in soldes" :key="s.type_conge_id || s.id">
                  <td>{{ s.type_conge?.libelle || s.type_conge_libelle || '—' }}</td>
                  <td>{{ s.total_acquis ?? s.acquis_periode ?? 0 }}</td>
                  <td>{{ s.total_utilise ?? s.utilise_periode ?? 0 }}</td>
                  <td>{{ s.solde_actuel ?? s.solde_periode ?? 0 }}</td>
                  <td>{{ s.expire_first || '—' }}</td>
                </tr>
                <tr v-if="!soldes.length">
                  <td colspan="5" class="muted">Aucun solde</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </aside>
    </section>

    <div v-if="selectedDocument" class="preview-overlay" @click.self="closeDocumentPreview">
      <article class="preview-modal card">
        <div class="preview-head">
          <div>
            <p class="section-kicker">Document preview</p>
            <h2>{{ selectedDocument.type_document || 'Document' }}</h2>
            <p class="preview-subtitle">
              Importé le {{ formatDate(selectedDocument.date_importation || selectedDocument.created_at) || '—' }}
              <span v-if="selectedDocument.date_expiration">
                • Expire le {{ formatDate(selectedDocument.date_expiration) }}
              </span>
            </p>
          </div>

          <div class="preview-head-actions">
            <span class="preview-counter">{{ previewIndex + 1 }} / {{ previewDocuments.length }}</span>
            <button class="btn btn-secondary btn-sm" type="button" @click="goToPreviousPreview" :disabled="previewDocuments.length < 2">
              ←
            </button>
            <button class="btn btn-secondary btn-sm" type="button" @click="goToNextPreview" :disabled="previewDocuments.length < 2">
              →
            </button>
            <button
              class="btn btn-secondary btn-sm"
              type="button"
              @click="downloadDocument(selectedDocument)"
              :disabled="downloadLoadingId === `doc-${selectedDocument.id}`"
            >
              Télécharger
            </button>
            <button class="btn btn-secondary btn-sm" type="button" @click="closeDocumentPreview">Fermer</button>
          </div>
        </div>

        <div class="preview-stage">
          <div v-if="previewLoading" class="preview-fallback">
            <p>Chargement de l’aperçu…</p>
          </div>
          <div v-else-if="previewError" class="preview-fallback">
            <p>{{ previewError }}</p>
            <button class="btn" type="button" @click="downloadDocument(selectedDocument)">Télécharger le fichier</button>
          </div>
          <img
            v-else-if="resolvePreviewType(selectedDocument) === 'image' && previewObjectUrl"
            class="preview-image"
            :src="previewObjectUrl"
            :alt="selectedDocument.type_document || 'Document'"
          />
          <iframe
            v-else-if="resolvePreviewType(selectedDocument) === 'pdf' && previewObjectUrl"
            class="preview-frame"
            :src="previewObjectUrl"
            title="Aperçu du document"
          ></iframe>
          <div v-else class="preview-fallback">
            <p>Aperçu intégré non disponible pour ce format.</p>
            <button class="btn" type="button" @click="downloadDocument(selectedDocument)">Télécharger le fichier</button>
          </div>
        </div>

        <div v-if="previewDocuments.length > 1" class="preview-strip">
          <button
            v-for="(doc, index) in previewDocuments"
            :key="doc.id"
            class="preview-strip-item"
            :class="{ active: index === previewIndex }"
            type="button"
            @click="setPreviewIndex(index)"
          >
            <span class="preview-strip-type">{{ doc.type_document || 'Document' }}</span>
            <span class="preview-strip-name">{{ documentFileName(doc) }}</span>
          </button>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import { formatDateValue, formatMoneyAmount } from '../utils/formatters'

const route = useRoute()
const router = useRouter()
const employe = ref({})
const contratActuel = ref(null)
const histPostes = ref([])
const histContrats = ref([])
const soldes = ref([])
const demandes = ref([])
const pointageMode = ref('week')
const pointageMonth = ref(new Date().toISOString().slice(0, 7))
const pointageYear = ref(new Date().getFullYear())
const pointagesSynth = ref([])
const pointagesDetails = ref([])
const previewDocuments = ref([])
const previewIndex = ref(-1)
const previewObjectUrl = ref('')
const previewLoading = ref(false)
const previewError = ref('')
const downloadLoadingId = ref(null)
const deleteLoadingId = ref(null)
let previewRequestToken = 0

const isActif = computed(() => !!employe.value?.actif)
const fullName = computed(() => {
  const name = `${employe.value?.nom || ''} ${employe.value?.prenom || ''}`.trim()
  return name || 'Fiche employé'
})
const posteLabel = computed(() => employe.value?.poste?.nom || 'Poste N/A')
const departementLabel = computed(() => employe.value?.departement?.nom || 'Département N/A')
const categorieLabel = computed(() => employe.value?.poste?.categorie || '—')
const documentsEmploye = computed(() =>
  [...(employe.value?.documents || [])].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0)),
)
const selectedDocument = computed(() => previewDocuments.value[previewIndex.value] || null)
const joursOuvresContrat = computed(() => Number(contratActuel.value?.jours_ouvres || 0))
const heuresMensuellesContrat = computed(() => Number(contratActuel.value?.heures_mensuelles_requises || 0))
const tauxHoraireContrat = computed(() => Number(contratActuel.value?.taux_horaire || 0))
const tauxJournalierContrat = computed(() => Number(contratActuel.value?.taux_journalier || 0))
const metrics = computed(() => [
  {
    tag: 'Profil',
    label: 'Statut employé',
    value: isActif.value ? 'Actif' : 'Inactif',
    caption: `${posteLabel.value} • ${departementLabel.value}`,
  },
  {
    tag: 'Contrat',
    label: 'Contrat courant',
    value: contratActuel.value?.type_contrat || 'Aucun',
    caption: contratActuel.value?.numero ? `N° ${contratActuel.value.numero}` : 'Aucun contrat rattaché',
  },
  {
    tag: 'Docs',
    label: 'Documents',
    value: String(documentGroups.value.length),
    caption: `${documentsEmploye.value.length} fichier(s) au total`,
  },
  {
    tag: 'Congés',
    label: 'Demandes',
    value: String(demandes.value.length),
    caption: `${soldes.value.length} type(s) de solde disponible(s)`,
  },
])

const fetchEmploye = async () => {
  const { data } = await api.get(`/v1/employes/${route.params.id}`)
  employe.value = data
}

const fetchContratActuel = async () => {
  const { data } = await api.get('/v1/contrats', { params: { employe_id: route.params.id } })
  const items = data.data || []
  contratActuel.value = items[0] || null
}

const fetchHistPostes = async () => {
  const { data } = await api.get('/v1/historiques-postes', { params: { employe_id: route.params.id } })
  histPostes.value = data.data || []
}

const fetchHistContrats = async () => {
  const { data } = await api.get('/v1/contrats-historiques', { params: { employe_id: route.params.id } })
  histContrats.value = data.data || []
}

const fetchSoldes = async () => {
  try {
    const { data } = await api.get('/v1/soldes-conges', { params: { employe_id: route.params.id } })
    soldes.value = data.data || []
  } catch (e) {
    soldes.value = []
  }
}

const fetchDemandes = async () => {
  try {
    const { data } = await api.get('/v1/demandes-conges', { params: { employe_id: route.params.id } })
    demandes.value = data.data || []
  } catch (e) {
    demandes.value = []
  }
}

const loadPointages = async () => {
  pointagesSynth.value = []
  pointagesDetails.value = []
  if (pointageMode.value === 'week' || pointageMode.value === 'month') {
    const { data } = await api.get('/v1/pointages/releve-paie', {
      params: { employe_id: route.params.id, mois: pointageMonth.value },
    })
    if (pointageMode.value === 'week') {
      pointagesSynth.value = groupByWeek(data.details || [])
    } else {
      pointagesDetails.value = (data.details || []).map((d) => ({
        ...d,
        jour: d.jour,
      }))
    }
  } else {
    const results = []
    for (let m = 1; m <= 12; m += 1) {
      const moisStr = `${pointageYear.value}-${String(m).padStart(2, '0')}`
      try {
        const { data } = await api.get('/v1/pointages/releve-paie', {
          params: { employe_id: route.params.id, mois: moisStr },
        })
        const tot = data.totaux || {}
        results.push({
          label: moisStr,
          heures_travaillees: tot.heures_travaillees || 0,
          heures_supplementaires: tot.heures_supplementaires || 0,
          retard_minutes: tot.retard_minutes || 0,
          absences: tot.absences || 0,
        })
      } catch (e) {
        // ignore les mois sans données
      }
    }
    pointagesSynth.value = results
  }
}

const groupByWeek = (list) => {
  const weeks = {}
  list.forEach((d) => {
    const date = new Date(d.jour)
    const label = weekLabel(date)
    if (!weeks[label]) {
      weeks[label] = {
        label,
        heures_travaillees: 0,
        heures_supplementaires: 0,
        retard_minutes: 0,
        absences: 0,
        dimanches: 0,
      }
    }
    weeks[label].heures_travaillees += d.heures_travaillees || 0
    weeks[label].heures_supplementaires += d.heures_supplementaires || 0
    weeks[label].retard_minutes += d.retard_minutes || 0
    weeks[label].absences += d.absent ? 1 : 0
    if (new Date(d.jour).getDay() === 0) {
      weeks[label].dimanches += 1
    }
  })
  return Object.values(weeks)
}

const weekLabel = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diffToMonday = day === 0 ? -6 : 1 - day
  const monday = new Date(d)
  monday.setDate(d.getDate() + diffToMonday)
  const end = new Date(monday)
  end.setDate(monday.getDate() + 6)
  const fmt = (dt) => dt.toISOString().slice(0, 10)
  return `${fmt(monday)} → ${fmt(end)}`
}

const initials = (emp) => `${emp?.nom?.[0] || ''}${emp?.prenom?.[0] || ''}`.trim() || 'RH'

const formatDate = (d) => formatDateValue(d)
const formatMoney = (value) => formatMoneyAmount(value)
const formatNumber = (value) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0))

const revokePreviewObjectUrl = () => {
  if (previewObjectUrl.value) {
    window.URL.revokeObjectURL(previewObjectUrl.value)
    previewObjectUrl.value = ''
  }
}

const fetchDocumentBlob = async (doc) => {
  if (!doc?.id) {
    throw new Error('Document introuvable.')
  }

  return api.get(`/v1/documents/${doc.id}/download`, { responseType: 'blob' })
}

const telechargerPdf = async () => {
  if (!employe.value?.id) {
    return
  }
  try {
    const { data, headers } = await api.get(`/v1/employes/${employe.value.id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `employe_${employe.value.matricule || employe.value.id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    // ignore
  }
}

const documentFileName = (doc) => doc?.nom_fichier || String(doc?.fichier || '').split(/[\\/]/).pop() || 'document'
const resolvePreviewType = (doc) => {
  if (!doc) {
    return 'file'
  }
  if (doc.preview_type) {
    return doc.preview_type
  }
  const extension = String(doc.extension || documentFileName(doc).split('.').pop() || '').toLowerCase()
  if (extension === 'pdf') {
    return 'pdf'
  }
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
    return 'image'
  }
  return 'file'
}

const canPreviewDocument = (doc) => ['image', 'pdf'].includes(resolvePreviewType(doc))

const buildDocumentGroupKey = (doc) => {
  if (doc?.group_uuid) {
    return `uuid:${doc.group_uuid}`
  }

  const createdAt = String(doc?.created_at || doc?.date_importation || '').split('.')[0]
  return [
    doc?.employe_id || route.params.id || '',
    doc?.type_document || '',
    formatDate(doc?.date_expiration) || '',
    createdAt,
  ].join('::')
}

const documentGroups = computed(() => {
  const groups = new Map()

  documentsEmploye.value.forEach((doc) => {
    const key = buildDocumentGroupKey(doc)

    if (!groups.has(key)) {
      groups.set(key, {
        key,
        type_document: doc.type_document || '',
        date_importation: doc.date_importation || doc.created_at || '',
        date_expiration: doc.date_expiration || '',
        documents: [],
      })
    }

    groups.get(key).documents.push(doc)
  })

  return [...groups.values()]
    .map((group) => {
      const documents = [...group.documents].sort((a, b) => {
        const dateDiff = new Date(b.created_at || 0) - new Date(a.created_at || 0)
        if (dateDiff !== 0) {
          return dateDiff
        }
        return Number(b.id || 0) - Number(a.id || 0)
      })

      return {
        ...group,
        documents,
        primaryDocument: documents[0] || null,
        previewableDocuments: documents.filter(canPreviewDocument),
      }
    })
    .sort((a, b) => {
      const dateDiff = new Date(b.primaryDocument?.created_at || 0) - new Date(a.primaryDocument?.created_at || 0)
      if (dateDiff !== 0) {
        return dateDiff
      }
      return Number(b.primaryDocument?.id || 0) - Number(a.primaryDocument?.id || 0)
    })
})

const openDocumentPreview = (documents, startIndex = 0) => {
  if (!documents?.length) {
    return
  }

  previewDocuments.value = documents
  previewIndex.value = Math.max(0, Math.min(startIndex, documents.length - 1))
}

const openDocumentGroupPreview = (group) => {
  if (!group?.previewableDocuments?.length) {
    return
  }

  openDocumentPreview(group.previewableDocuments, 0)
}

const setPreviewIndex = (index) => {
  if (index >= 0 && index < previewDocuments.value.length) {
    previewIndex.value = index
  }
}

const closeDocumentPreview = () => {
  previewDocuments.value = []
  previewIndex.value = -1
}

const goToPreviousPreview = () => {
  if (!previewDocuments.value.length) {
    return
  }
  previewIndex.value = (previewIndex.value - 1 + previewDocuments.value.length) % previewDocuments.value.length
}

const goToNextPreview = () => {
  if (!previewDocuments.value.length) {
    return
  }
  previewIndex.value = (previewIndex.value + 1) % previewDocuments.value.length
}

const triggerBlobDownload = (blob, filename) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

const downloadDocument = async (doc) => {
  const loadingKey = `doc-${doc?.id}`
  if (!doc?.id || downloadLoadingId.value === loadingKey) {
    return
  }

  downloadLoadingId.value = loadingKey
  try {
    const response = await fetchDocumentBlob(doc)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    triggerBlobDownload(blob, documentFileName(doc))
  } finally {
    downloadLoadingId.value = null
  }
}

const downloadDocumentGroup = async (group) => {
  if (!group?.documents?.length) {
    return
  }

  if (group.documents.length === 1) {
    await downloadDocument(group.documents[0])
    return
  }

  const loadingKey = `group-${group.key}`
  if (downloadLoadingId.value === loadingKey) {
    return
  }

  downloadLoadingId.value = loadingKey
  try {
    for (const doc of group.documents) {
      const response = await fetchDocumentBlob(doc)
      const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
      triggerBlobDownload(blob, documentFileName(doc))
      await new Promise((resolve) => window.setTimeout(resolve, 120))
    }
  } finally {
    downloadLoadingId.value = null
  }
}

const editDocument = (doc) => {
  if (!doc?.id) {
    return
  }

  router.push({
    name: 'document-edit',
    params: { id: doc.id },
    query: { return: route.fullPath },
  })
}

const editDocumentGroup = (group) => {
  if (!group?.primaryDocument?.id) {
    return
  }

  router.push({
    name: 'document-edit',
    params: { id: group.primaryDocument.id },
    query: {
      return: route.fullPath,
      group_key: group.key,
      group_uuid: group.primaryDocument.group_uuid || '',
    },
  })
}

const removeDocument = async (doc) => {
  const loadingKey = `doc-${doc?.id}`
  if (!doc?.id || deleteLoadingId.value === loadingKey) {
    return
  }
  const confirmed = window.confirm(`Supprimer le document "${documentFileName(doc)}" ?`)
  if (!confirmed) {
    return
  }

  deleteLoadingId.value = loadingKey
  try {
    await api.delete(`/v1/documents/${doc.id}`)
    if (selectedDocument.value?.id === doc.id) {
      closeDocumentPreview()
    }
    await fetchEmploye()
  } finally {
    deleteLoadingId.value = null
  }
}

const removeDocumentGroup = async (group) => {
  if (!group?.documents?.length) {
    return
  }

  if (group.documents.length === 1) {
    await removeDocument(group.documents[0])
    return
  }

  const loadingKey = `group-${group.key}`
  if (deleteLoadingId.value === loadingKey) {
    return
  }

  const confirmed = window.confirm(
    `Supprimer le groupe "${group.type_document || 'Document'}" et ses ${group.documents.length} fichiers ?`,
  )

  if (!confirmed) {
    return
  }

  deleteLoadingId.value = loadingKey
  try {
    for (const doc of group.documents) {
      await api.delete(`/v1/documents/${doc.id}`)
    }

    const removedIds = new Set(group.documents.map((doc) => doc.id))
    if (previewDocuments.value.some((doc) => removedIds.has(doc.id))) {
      closeDocumentPreview()
    }

    await fetchEmploye()
  } finally {
    deleteLoadingId.value = null
  }
}

watch(
  () => selectedDocument.value?.id,
  async (documentId) => {
    previewRequestToken += 1
    const requestToken = previewRequestToken

    revokePreviewObjectUrl()
    previewError.value = ''

    if (!documentId || !selectedDocument.value || !canPreviewDocument(selectedDocument.value)) {
      previewLoading.value = false
      return
    }

    previewLoading.value = true

    try {
      const response = await fetchDocumentBlob(selectedDocument.value)

      if (requestToken !== previewRequestToken) {
        return
      }

      const blob = response.data instanceof Blob
        ? response.data
        : new Blob([response.data], { type: response.headers?.['content-type'] || 'application/octet-stream' })

      previewObjectUrl.value = window.URL.createObjectURL(blob)
    } catch (error) {
      if (requestToken !== previewRequestToken) {
        return
      }

      previewError.value = 'Impossible de charger l’aperçu de ce document.'
    } finally {
      if (requestToken === previewRequestToken) {
        previewLoading.value = false
      }
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  previewRequestToken += 1
  revokePreviewObjectUrl()
})

onMounted(async () => {
  await Promise.all([
    fetchEmploye(),
    fetchContratActuel(),
    fetchHistPostes(),
    fetchHistContrats(),
    fetchSoldes(),
    fetchDemandes(),
  ])
  await loadPointages()
})
</script>

<style scoped>
.employee-detail-page .hero-copy,
.employee-detail-page .hero-actions,
.employee-detail-page .filters-panel,
.employee-detail-page .main-column,
.employee-detail-page .sidebar-column {
  min-width: 0;
}

.employee-detail-page .hero-meta strong {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.employee-detail-page .sidebar-column {
  width: 100%;
  max-width: 100%;
}

.employee-title {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.employee-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 74px;
  height: 74px;
  border-radius: 22px;
  border: 1px solid var(--border);
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.76);
}

.employee-photo {
  overflow: hidden;
  object-fit: cover;
}

.employee-avatar-fallback {
  background: var(--brand-500);
  color: #ffffff;
  font-size: 1.45rem;
  font-weight: 800;
}

.overview-value--wrap,
.document-name-cell {
  word-break: break-word;
  overflow-wrap: anywhere;
  max-width: 100%;
  min-width: 0;
}

.documents-table .actions-col {
  width: 1%;
  white-space: nowrap;
}

.document-group-cell {
  display: grid;
  gap: 10px;
}

.document-group-title {
  font-weight: 700;
  color: var(--text);
}

.document-group-files {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.document-file-chip {
  display: inline-flex;
  align-items: center;
  max-width: min(280px, 100%);
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.06);
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.document-file-chip-more {
  color: var(--brand-600);
  border-color: rgba(79, 70, 229, 0.18);
  background: rgba(79, 70, 229, 0.08);
}

.inline-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.btn-danger-soft {
  color: #b42318;
      border-color: rgba(180, 35, 24, 0.2);
      background: rgba(180, 35, 24, 0.08);
}

.preview-overlay {
  position: fixed;
  inset: 0;
  z-index: 120;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(2, 6, 23, 0.76);
  backdrop-filter: blur(8px);
}

.preview-modal {
  width: min(1120px, 100%);
  max-height: calc(100vh - 48px);
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 22px;
  overflow: hidden;
}

.preview-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.preview-head h2 {
  margin: 6px 0 0;
}

.preview-subtitle {
  margin: 8px 0 0;
  color: var(--muted);
}

.preview-head-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.preview-counter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 68px;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.08);
  color: var(--text);
  font-weight: 700;
}

.preview-stage {
  min-height: 420px;
  border-radius: 28px;
  border: 1px solid var(--border);
  background:
    radial-gradient(circle at top left, rgba(59, 130, 246, 0.14), transparent 42%),
    linear-gradient(180deg, rgba(15, 23, 42, 0.06), rgba(15, 23, 42, 0.02));
  overflow: hidden;
}

.preview-image,
.preview-frame {
  width: 100%;
  height: min(68vh, 760px);
  border: 0;
  display: block;
  background: white;
}

.preview-image {
  object-fit: contain;
}

.preview-fallback {
  min-height: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: var(--muted);
}

.preview-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  overflow: auto;
  padding-bottom: 2px;
}

.preview-strip-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.66);
  text-align: left;
  cursor: pointer;
  transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.preview-strip-item.active {
  border-color: rgba(37, 99, 235, 0.4);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.16);
  transform: translateY(-2px);
}

.preview-strip-type {
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--brand-600);
}

.preview-strip-name {
  color: var(--text);
  font-weight: 600;
  word-break: break-word;
}

@media (max-width: 900px) {
  .preview-overlay {
    padding: 14px;
  }

  .preview-modal {
    padding: 18px;
  }

  .preview-head {
    flex-direction: column;
  }

  .preview-head-actions {
    justify-content: flex-start;
  }
}

@media (max-width: 680px) {
  .employee-title {
    flex-direction: column;
    align-items: flex-start;
  }

  .preview-image,
  .preview-frame {
    height: 52vh;
  }
}

.pointage-toolbar .input,
.pointage-toolbar .select {
  max-width: 220px;
}

.pointage-grid {
  grid-template-columns: 1fr;
}

.sidebar-column > .section-card,
.pointage-grid .overview-card {
  width: 100%;
}
</style>
