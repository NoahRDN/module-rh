<template>
  <div class="contrat-page">
    <div class="hero-card">
      <div class="hero-main">
        <p class="eyebrow">Contrat #{{ contrat?.id || route.params.id }}</p>
        <h1>Fiche contrat</h1>
        <p class="subtitle">Vue synthétique du contrat et de son historique</p>
        <div class="chips">
          <span class="pill pill-blue">{{ contrat?.type_contrat || 'Type non défini' }}</span>
          <span class="pill" v-if="contrat?.numero">N° {{ contrat?.numero }}</span>
          <span class="pill" :class="contrat?.renouvelable ? 'pill-green' : 'pill-red'">
            {{ contrat?.renouvelable ? 'Renouvelable' : 'Non renouvelable' }}
          </span>
          <span class="pill" v-if="contrat?.statut">{{ contrat?.statut }}</span>
          <span class="pill pill-red" v-if="joursRestants !== null && joursRestants <= 30">
            Échéance dans {{ joursRestants }} j
          </span>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn btn-secondary" @click="telechargerPdf" :disabled="!contrat">Télécharger le PDF</button>
        <RouterLink class="btn btn-secondary" to="/contrats-historiques">Historique</RouterLink>
        <RouterLink
          v-if="contrat?.employe?.id"
          class="btn btn-secondary"
          :to="{ name: 'employe-detail', params: { id: contrat.employe.id } }"
        >
          Ouvrir l'employé
        </RouterLink>
        <button class="btn btn-secondary" @click="router.back()">← Retour</button>
      </div>
    </div>

    <div class="summary-grid">
      <div class="summary-card">
        <p class="label">Employé</p>
        <p class="value">
          {{ contrat?.employe?.matricule }} — {{ contrat?.employe?.nom }} {{ contrat?.employe?.prenom }}
        </p>
        <p class="muted text-xs">
          {{ contrat?.employe?.poste?.nom || 'Poste non renseigné' }} ·
          {{ contrat?.employe?.departement?.nom || 'Département non renseigné' }}
        </p>
      </div>
      <div class="summary-card">
        <p class="label">Salaire de base</p>
        <p class="value">{{ formatSalaire(contrat?.salaire_base) }}</p>
        <p class="muted text-xs">Montant brut de référence</p>
      </div>
      <div class="summary-card">
        <p class="label">Durée du contrat</p>
        <p class="value">{{ dureeContrat }}</p>
        <p class="muted text-xs">{{ formatDate(contrat?.date_debut) }} → {{ formatDate(contrat?.date_fin) || '—' }}</p>
      </div>
      <div class="summary-card">
        <p class="label">Période d'essai</p>
        <p class="value">{{ periodeEssai }}</p>
        <p class="muted text-xs">
          {{ formatDate(contrat?.periode_essai_debut) || '—' }} → {{ formatDate(contrat?.periode_essai_fin) || '—' }}
        </p>
      </div>
    </div>

    <div class="card glass">
      <div class="section-header">
        <div>
          <p class="eyebrow">Détails</p>
          <h3>Informations clés</h3>
        </div>
      </div>
      <div class="details-grid">
        <div class="detail">
          <p class="label">Numéro</p>
          <p class="value">{{ contrat?.numero || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Type</p>
          <p class="value">{{ contrat?.type_contrat || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Statut</p>
          <p class="value">{{ contrat?.statut || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Renouvelable</p>
          <p class="value">{{ contrat?.renouvelable ? 'Oui' : 'Non' }}</p>
        </div>
        <div class="detail">
          <p class="label">Début</p>
          <p class="value">{{ formatDate(contrat?.date_debut) || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Fin</p>
          <p class="value">{{ formatDate(contrat?.date_fin) || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Essai — début</p>
          <p class="value">{{ formatDate(contrat?.periode_essai_debut) || '—' }}</p>
        </div>
        <div class="detail">
          <p class="label">Essai — fin</p>
          <p class="value">{{ formatDate(contrat?.periode_essai_fin) || '—' }}</p>
        </div>
      </div>
    </div>

    <div class="card glass">
      <div class="section-header">
        <div>
          <p class="eyebrow">Historique</p>
          <h3>Évolutions du contrat</h3>
        </div>
        <RouterLink class="link" to="/contrats-historiques">Voir tout</RouterLink>
      </div>
      <div class="table-scroll">
        <table class="table text-sm">
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
              <td>{{ h.type_contrat }}</td>
              <td>{{ duree(h) }}</td>
              <td>{{ formatDate(h.date_debut) }} → {{ formatDate(h.date_fin) || '—' }}</td>
              <td>{{ formatDate(h.periode_essai_debut) || '—' }} → {{ formatDate(h.periode_essai_fin) || '—' }}</td>
              <td>{{ h.statut || '—' }}</td>
            </tr>
            <tr v-if="!histContrats.length">
              <td colspan="7" class="muted">Aucun historique</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { parseISO, intervalToDuration, formatDuration, differenceInCalendarDays } from 'date-fns'
import { fr } from 'date-fns/locale'

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
  if (amount === null || amount === undefined || amount === '') return '—'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', maximumFractionDigits: 0 }).format(amount)
}

const joursRestants = computed(() => {
  const fin = contrat.value?.date_fin
  if (!fin) return null
  const end = parseISO(fin)
  if (isNaN(end)) return null
  const diff = differenceInCalendarDays(end, new Date())
  return diff >= 0 ? diff : null
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
.contrat-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.hero-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 18px 20px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(70, 95, 255, 0.12), rgba(34, 197, 94, 0.08)), var(--panel);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
}

.hero-main h1 {
  margin: 4px 0;
}

.subtitle {
  margin: 0;
  color: var(--muted);
}

.eyebrow {
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--brand-500);
  margin: 0;
}

.link {
  color: var(--brand-500);
  font-weight: 600;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 12px;
}

.summary-card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
  box-shadow: var(--shadow-sm);
}

.label {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
  margin: 0 0 4px;
}

.value {
  margin: 0;
  font-weight: 700;
}

.card.glass {
  border: 1px solid var(--border);
  background: var(--panel);
  box-shadow: var(--shadow-sm);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.detail {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0));
}

.table-scroll {
  overflow: auto;
}

@media (max-width: 768px) {
  .hero-card {
    padding: 16px;
  }
}
</style>
