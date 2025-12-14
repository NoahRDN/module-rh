<template>
  <div class="profile-shell">
    <div class="flex items-center gap-2 mb-2">
      <button class="back-btn" @click="router.back()">← Retour</button>
      <button class="btn btn-secondary text-sm" @click="telechargerPdf">PDF fiche</button>
    </div>
    <div class="card header-card">
      <div class="profile-top">
        <div class="avatar">
          <img :src="photoUrl(employe)" alt="user" />
        </div>
        <div class="profile-main">
          <h4>{{ employe.nom }} {{ employe.prenom }}</h4>
          <div class="meta">
            <p>{{ employe.poste?.nom || 'Poste N/A' }}</p>
            <span class="divider"></span>
            <p>{{ employe.departement?.nom || 'Département N/A' }}</p>
          </div>
        </div>
        <div class="badge">
          Matricule : {{ employe.matricule }}
        </div>
      </div>
    </div>

    <div class="card info-card">
      <h3>Informations personnelles</h3>
      <div class="info-grid">
        <div class="info-item fit-email">
          <p class="label">Email</p>
          <p class="value">{{ employe.email || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Téléphone</p>
          <p class="value">{{ employe.telephone || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Adresse</p>
          <p class="value">{{ employe.adresse || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Date de naissance</p>
          <p class="value">{{ employe.date_naissance || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Date d'embauche</p>
          <p class="value">{{ employe.date_embauche || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Catégorie</p>
          <p class="value">{{ employe.poste?.categorie || '—' }}</p>
        </div>
      </div>
    </div>

    <div class="grid gap-3">
      <div class="card">
        <div class="flex items-center justify-between mb-2">
          <h3>Contrat actuel</h3>
          <RouterLink class="text-sm underline" to="/contrats">Voir tous</RouterLink>
        </div>
        <div v-if="contratActuel" class="info-grid">
          <div class="info-item"><p class="label">Numéro</p><p class="value">{{ contratActuel.numero || '—' }}</p></div>
          <div class="info-item"><p class="label">Type</p><p class="value">{{ contratActuel.type_contrat }}</p></div>
          <div class="info-item"><p class="label">Contrat</p><p class="value">{{ formatDate(contratActuel.date_debut) }} → {{ formatDate(contratActuel.date_fin) || '—' }}</p></div>
          <div class="info-item"><p class="label">Période d'essai</p><p class="value">{{ formatDate(contratActuel.periode_essai_debut) || '—' }} → {{ formatDate(contratActuel.periode_essai_fin) || '—' }}</p></div>
          <div class="info-item"><p class="label">Salaire</p><p class="value">{{ contratActuel.salaire_base }} Ar</p></div>
        </div>
        <p v-else class="muted text-sm">Aucun contrat associé</p>
      </div>

      <div class="card">
        <div class="flex items-center justify-between mb-2">
          <h3>Historique des postes</h3>
          <RouterLink class="text-sm underline" to="/historiques">Voir</RouterLink>
        </div>
        <table class="table text-sm">
          <thead><tr><th>Poste</th><th>Département</th><th>Date</th><th>Motif</th></tr></thead>
          <tbody>
            <tr v-for="h in histPostes" :key="h.id">
              <td>{{ h.poste?.nom || '—' }}</td>
              <td>{{ h.departement?.nom || '—' }}</td>
              <td>{{ formatDate(h.date_changement) }}</td>
              <td>{{ h.motif || '—' }}</td>
            </tr>
            <tr v-if="!histPostes.length"><td colspan="4" class="muted">Aucun historique</td></tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="flex items-center justify-between mb-2">
          <h3>Historique des contrats</h3>
          <RouterLink class="text-sm underline" to="/contrats-historiques">Voir</RouterLink>
        </div>
        <table class="table text-sm">
          <thead><tr><th>Numéro</th><th>Type</th><th>Contrat</th><th>Période d'essai</th></tr></thead>
          <tbody>
            <tr v-for="h in histContrats" :key="h.id">
              <td>{{ h.numero || '—' }}</td>
              <td>{{ h.type_contrat }}</td>
              <td>{{ formatDate(h.date_debut) }} → {{ formatDate(h.date_fin) || '—' }}</td>
              <td>{{ formatDate(h.periode_essai_debut) || '—' }} → {{ formatDate(h.periode_essai_fin) || '—' }}</td>
            </tr>
            <tr v-if="!histContrats.length"><td colspan="4" class="muted">Aucun historique</td></tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="flex items-center justify-between mb-2">
          <h3>Pointages</h3>
          <div class="flex items-center gap-2">
            <select class="select" v-model="pointageMode">
              <option value="week">Semaine</option>
              <option value="month">Mois</option>
              <option value="year">Année</option>
            </select>
            <input v-if="pointageMode==='week' || pointageMode==='month'" class="input w-32" type="month" v-model="pointageMonth" />
            <input v-else class="input w-24" type="number" min="2000" max="2100" v-model="pointageYear" />
            <button class="btn btn-secondary btn-xs" @click="loadPointages">Actualiser</button>
          </div>
        </div>
        <div v-if="!isActif" class="muted text-sm">Employé inactif : pointages non affichés.</div>
        <template v-else>
          <div v-if="pointageMode==='week'" class="grid gap-2 md:grid-cols-2">
            <div v-for="w in pointagesSynth" :key="w.label" class="info-item">
              <p class="font-semibold">Semaine {{ w.label }}</p>
              <p class="text-sm">Heures : {{ w.heures_travaillees }} | HS : {{ w.heures_supplementaires }}</p>
              <p class="text-sm">Retards : {{ w.retard_minutes }} min | Absences : {{ w.absences }}</p>
              <p class="text-sm">Dimanches : {{ w.dimanches }}</p>
            </div>
            <p v-if="!pointagesSynth.length" class="muted text-sm">Aucune donnée</p>
          </div>
          <div v-if="pointageMode==='month'" class="grid gap-2 md:grid-cols-2">
            <div v-for="d in pointagesDetails" :key="d.jour" class="info-item">
              <p class="font-semibold">{{ d.jour }}</p>
              <p class="text-sm">Heures : {{ d.heures_travaillees }} | HS : {{ d.heures_supplementaires }}</p>
              <p class="text-sm">Retard : {{ d.retard_minutes }} min | Absences : {{ d.absent ? 1 : 0 }}</p>
            </div>
          </div>
          <div v-if="pointageMode==='year'" class="grid gap-2 md:grid-cols-3">
            <div v-for="m in pointagesSynth" :key="m.label" class="info-item">
              <p class="font-semibold">{{ m.label }}</p>
              <p class="text-sm">Heures : {{ m.heures_travaillees }} | HS : {{ m.heures_supplementaires }}</p>
              <p class="text-sm">Retards : {{ m.retard_minutes }} min | Absences : {{ m.absences }}</p>
            </div>
          </div>
        </template>
      </div>

      <div class="card">
        <div class="flex items-center justify-between mb-2">
          <h3>Soldes de congé</h3>
          <RouterLink class="text-sm underline" to="/soldes-conges">Voir tout</RouterLink>
        </div>
        <table class="table text-sm">
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
            <tr v-if="!soldes.length"><td colspan="5" class="muted">Aucun solde</td></tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h3>Historique des demandes de congés</h3>
        <table class="table text-sm">
          <thead><tr><th>Type</th><th>Début</th><th>Fin</th><th>Statut</th></tr></thead>
          <tbody>
            <tr v-for="d in demandes" :key="d.id">
              <td>{{ d.type?.nom || d.type?.label || '—' }}</td>
              <td>{{ formatDate(d.date_debut) }}</td>
              <td>{{ formatDate(d.date_fin) }}</td>
              <td><span class="chip">{{ d.statut }}</span></td>
            </tr>
            <tr v-if="!demandes.length"><td colspan="4" class="muted">Aucune demande</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

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
const placeholder = 'https://via.placeholder.com/120?text=EMP'
const isActif = computed(() => !!employe.value?.actif)

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
    console.log("Soldes fetched:", JSON.parse(JSON.stringify(soldes.value)))
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
  return `https://ui-avatars.com/api/?background=0f172a&color=fff&name=${encodeURIComponent(initials)}`
}

const formatDate = (d) => (d ? String(d).split('T')[0] : '')

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
.profile-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px;
  color: var(--text);
  box-shadow: var(--card-shadow);
}

.header-card {
  padding: 20px;
}

.profile-top {
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid var(--border);
  flex-shrink: 0;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-main h4 {
  margin: 0 0 6px;
  font-size: 20px;
}

.meta {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 14px;
}

.divider {
  width: 1px;
  height: 14px;
  background: var(--border);
}

.badge {
  margin-left: auto;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(34, 197, 94, 0.12);
  color: var(--accent);
  font-weight: 600;
  font-size: 12px;
  border: 1px solid var(--border);
}

.info-card h3 {
  margin: 0 0 12px;
  font-size: 18px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.info-item {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--bg-soft);
}

.info-item.fit-email {
  width: fit-content;
  max-width: 100%;
}

.info-item.fit-email .value {
  word-break: break-all;
}

.label {
  margin: 0 0 4px;
  color: var(--muted);
  font-size: 13px;
}

.value {
  margin: 0;
  font-weight: 600;
  color: var(--text);
}

.back-btn {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text);
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  margin-bottom: 10px;
  width: 10%;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.04);
}
</style>
