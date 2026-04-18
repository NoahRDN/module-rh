<template>
  <div class="rh-page feries-page">
    <section class="rh-hero">
      <div class="rh-hero-copy">
        <p class="rh-hero-kicker">Legal calendar</p>
        <h1>Jours fériés</h1>
        <p class="rh-hero-subtitle">
          Maintenez le calendrier légal utilisé par les absences, la présence et les calculs de paie
          dans une vue alignée avec le reste du module RH.
        </p>

        <div class="rh-hero-pills">
          <span class="pill">Calendrier légal</span>
          <span class="pill">Base paie</span>
          <span class="pill">Référentiel présence</span>
        </div>
      </div>

      <div class="rh-hero-actions">
        <div class="rh-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="fetchFeries" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
            <RouterLink class="btn" to="/jours-feries/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Ajouter</span>
            </RouterLink>
          </div>

          <div class="rh-hero-meta-list">
            <p class="rh-hero-meta">
              Total calendrier:
              <strong>{{ formatInteger(feries.length) }}</strong>
            </p>
            <p class="rh-hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="rh-metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="rh-metric-card">
        <span class="rh-metric-chip">{{ metric.tag }}</span>
        <p class="rh-metric-label">{{ metric.label }}</p>
        <p class="rh-metric-value">{{ metric.value }}</p>
        <p class="rh-metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <section class="rh-content-grid">
      <article class="card rh-section-card">
        <div class="rh-section-heading">
          <div>
            <p class="rh-section-kicker">Holiday list</p>
            <h2>Calendrier férié</h2>
          </div>
          <span class="rh-section-chip">{{ formatInteger(feries.length) }} lignes</span>
        </div>

        <p class="rh-section-copy">
          Le tableau centralise les jours fériés pris en compte par les modules temps, absence et paie.
        </p>

        <div class="rh-table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Date</th>
                <th>Récurrent</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="f in orderedFeries" :key="f.id">
                <td>
                  <span class="chip">{{ f.nom }}</span>
                </td>
                <td>{{ formatDate(f.date) }}</td>
                <td>
                  <span class="pill" :class="f.recurrent ? 'green' : ''">
                    {{ f.recurrent ? 'Oui' : 'Non' }}
                  </span>
                </td>
                <td class="actions-col">
                  <button class="btn btn-danger btn-xs" @click="remove(f)">Supprimer</button>
                </td>
              </tr>

              <tr v-if="!orderedFeries.length">
                <td colspan="4" class="rh-empty-state">
                  <p>Aucun jour férié enregistré.</p>
                  <span>Ajoutez une date pour compléter le calendrier légal.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Overview</p>
            <h2>Résumé calendrier</h2>
          </div>
        </div>

        <p class="rh-summary-intro">
          Quelques repères rapides pour vérifier si le référentiel est prêt pour les modules
          opérationnels.
        </p>

        <div class="rh-overview-grid">
          <article v-for="card in overviewCards" :key="card.label" class="rh-overview-card">
            <span class="rh-overview-chip">{{ card.tag }}</span>
            <p class="rh-overview-label">{{ card.label }}</p>
            <p class="rh-overview-value">{{ card.value }}</p>
            <p class="rh-overview-copy">{{ card.copy }}</p>
          </article>
        </div>

        <div class="rh-notes-card">
          <h3>Repères rapides</h3>
          <ul>
            <li>Les jours récurrents évitent la recréation manuelle chaque année.</li>
            <li>Le calendrier alimente directement les règles temps et paie.</li>
            <li>Supprimez uniquement les dates réellement obsolètes du référentiel.</li>
          </ul>
        </div>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const feries = ref([])
const loading = ref(false)
const lastRefreshedAt = ref(null)

const orderedFeries = computed(() =>
  [...feries.value].sort((left, right) => String(left.date || '').localeCompare(String(right.date || ''))),
)

const recurrentCount = computed(() => feries.value.filter((item) => Boolean(item.recurrent)).length)
const upcomingHoliday = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return orderedFeries.value.find((item) => String(item.date || '') >= today) || null
})

const metricCards = computed(() => [
  {
    label: 'Jours fériés',
    value: formatInteger(feries.value.length),
    caption: 'Dates actuellement enregistrées',
    tag: 'Calendar',
  },
  {
    label: 'Dates récurrentes',
    value: formatInteger(recurrentCount.value),
    caption: 'Réutilisées automatiquement selon l’année',
    tag: 'Recurring',
  },
  {
    label: 'Prochaine date',
    value: upcomingHoliday.value ? formatDate(upcomingHoliday.value.date) : '—',
    caption: upcomingHoliday.value ? upcomingHoliday.value.nom : 'Aucune date à venir',
    tag: 'Next',
  },
  {
    label: 'Couverture',
    value: feries.value.length ? 'Active' : 'Vide',
    caption: feries.value.length ? 'Référentiel disponible pour les calculs' : 'Aucune date enregistrée',
    tag: 'State',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Total visible',
    value: formatInteger(orderedFeries.value.length),
    copy: 'Nombre de lignes actuellement affichées.',
    tag: 'List',
  },
  {
    label: 'Référence suivante',
    value: upcomingHoliday.value?.nom || 'Non définie',
    copy: upcomingHoliday.value ? formatDate(upcomingHoliday.value.date) : 'Aucune date future détectée.',
    tag: 'Next',
  },
  {
    label: 'Mode annuel',
    value: `${formatInteger(recurrentCount.value)}/${formatInteger(feries.value.length)}`,
    copy: 'Part des dates récurrentes dans le référentiel.',
    tag: 'Ratio',
  },
  {
    label: 'Synchro',
    value: lastSyncedLabel.value,
    copy: 'Dernière récupération depuis l’API.',
    tag: 'Sync',
  },
])

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const fetchFeries = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/jours-feries')
    feries.value = data.data || []
    lastRefreshedAt.value = new Date()
  } finally {
    loading.value = false
  }
}

const remove = async (f) => {
  if (!confirm(`Supprimer ${f.nom} ?`)) return
  try {
    await api.delete(`/v1/jours-feries/${f.id}`)
    await fetchFeries()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchFeries)

const formatInteger = (value) =>
  new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)

const formatDate = (value) => {
  if (!value) return '—'
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value))
}
</script>

<style scoped>
.actions-col {
  width: 110px;
}
</style>
