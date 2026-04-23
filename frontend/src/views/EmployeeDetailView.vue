<template>
  <div class="rh-page employee-detail-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <div class="employee-title">
          <img class="employee-avatar" :src="photoUrl(employe)" alt="Employé" />
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

    <section class="content-grid">
      <div class="main-column">
        <article class="card section-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Informations personnelles</h2>
            </div>
            <span class="section-chip">{{ fullName }}</span>
          </div>

          <div class="overview-grid">
            <div class="overview-card">
              <p class="overview-label">Email</p>
              <p class="overview-value overview-value--wrap">{{ employe.email || '—' }}</p>
              <p class="overview-copy">Contact principal</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Téléphone</p>
              <p class="overview-value">{{ employe.telephone || '—' }}</p>
              <p class="overview-copy">Numéro de contact</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Adresse</p>
              <p class="overview-value overview-value--wrap">{{ employe.adresse || '—' }}</p>
              <p class="overview-copy">Coordonnées</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Date de naissance</p>
              <p class="overview-value">{{ formatDate(employe.date_naissance) || '—' }}</p>
              <p class="overview-copy">Identité</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Date d'embauche</p>
              <p class="overview-value">{{ formatDate(employe.date_embauche) || '—' }}</p>
              <p class="overview-copy">Entrée dans l’entreprise</p>
            </div>
            <div class="overview-card">
              <p class="overview-label">Catégorie</p>
              <p class="overview-value">{{ categorieLabel }}</p>
              <p class="overview-copy">Référentiel poste</p>
            </div>
          </div>
        </article>

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
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import { formatMoneyAmount } from '../utils/formatters'

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
const isActif = computed(() => !!employe.value?.actif)
const fullName = computed(() => {
  const name = `${employe.value?.nom || ''} ${employe.value?.prenom || ''}`.trim()
  return name || 'Fiche employé'
})
const posteLabel = computed(() => employe.value?.poste?.nom || 'Poste N/A')
const departementLabel = computed(() => employe.value?.departement?.nom || 'Département N/A')
const categorieLabel = computed(() => employe.value?.poste?.categorie || '—')

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
      params: { employe_id: route.params.id, mois: pointageMonth.value }
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
    // année : agrégation mensuelle
    const results = []
    for (let m = 1; m <= 12; m++) {
      const moisStr = `${pointageYear.value}-${String(m).padStart(2, '0')}`
      try {
        const { data } = await api.get('/v1/pointages/releve-paie', { params: { employe_id: route.params.id, mois: moisStr } })
        const tot = data.totaux || {}
        results.push({
          label: moisStr,
          heures_travaillees: tot.heures_travaillees || 0,
          heures_supplementaires: tot.heures_supplementaires || 0,
          retard_minutes: tot.retard_minutes || 0,
          absences: tot.absences || 0,
        })
      } catch (e) {
        // ignore
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
      weeks[label] = { label, heures_travaillees: 0, heures_supplementaires: 0, retard_minutes: 0, absences: 0, dimanches: 0 }
    }
    weeks[label].heures_travaillees += d.heures_travaillees || 0
    weeks[label].heures_supplementaires += d.heures_supplementaires || 0
    weeks[label].retard_minutes += d.retard_minutes || 0
    weeks[label].absences += d.absent ? 1 : 0
    if (new Date(d.jour).getDay() === 0) weeks[label].dimanches += 1
  })
  return Object.values(weeks)
}

const weekLabel = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diffToMonday = (day === 0 ? -6 : 1 - day)
  const monday = new Date(d)
  monday.setDate(d.getDate() + diffToMonday)
  const end = new Date(monday)
  end.setDate(monday.getDate() + 6)
  const fmt = (dt) => dt.toISOString().slice(0, 10)
  return `${fmt(monday)} → ${fmt(end)}`
}

const photoUrl = (emp) => {
  if (emp?.photo) return emp.photo
  const initials = `${emp?.nom?.[0] || ''}${emp?.prenom?.[0] || ''}` || 'EMP'
  return generateAvatar(initials)
}

function generateAvatar(initials) {
  const bg = "#0f172a";
  const fg = "#ffffff";

  const svg = `
  <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128">
    <rect width="100%" height="100%" fill="${bg}"/>
    <text x="50%" y="50%"
          dominant-baseline="middle"
          text-anchor="middle"
          font-size="48"
          font-family="Arial, sans-serif"
          fill="${fg}">
      ${initials}
    </text>
  </svg>
  `;

  return "data:image/svg+xml;base64," + btoa(svg);
}


const formatDate = (d) => (d ? String(d).split('T')[0] : '')
const formatMoney = (value) => {
  return formatMoneyAmount(value, { unit: 'Ar' })
}

const telechargerPdf = async () => {
  if (!employe.value?.id) return
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
.employee-title {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.employee-avatar {
  width: 74px;
  height: 74px;
  border-radius: 22px;
  overflow: hidden;
  border: 1px solid var(--border);
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.76);
  object-fit: cover;
}

.overview-value--wrap {
  word-break: break-word;
  overflow-wrap: anywhere;
  max-width: 100%;
  min-width: 0;
}

.pointage-toolbar .input,
.pointage-toolbar .select {
  max-width: 220px;
}

.pointage-grid {
  grid-template-columns: 1fr;
}

@media (max-width: 680px) {
  .employee-title {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
