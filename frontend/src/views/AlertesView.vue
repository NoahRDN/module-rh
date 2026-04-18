<template>
  <div class="rh-page alertes-page">
    <section class="rh-hero">
      <div class="rh-hero-copy">
        <p class="rh-hero-kicker">Continuous monitoring</p>
        <h1>Alertes automatiques</h1>
        <p class="rh-hero-subtitle">
          Surveillez les demandes de congé, les absences répétées, les fins de contrat et les soldes à
          traiter dans une vue plus cohérente avec le reste du module RH.
        </p>

        <div class="rh-hero-pills">
          <span class="pill">{{ stats.total }} alertes</span>
          <span class="pill green">{{ stats.conges }} congés</span>
          <span class="pill">{{ stats.contrats }} contrats</span>
          <span class="pill red">{{ stats.critiques }} critiques</span>
        </div>
      </div>

      <div class="rh-hero-actions">
        <div class="rh-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="fetchAlertes">
              <AppIcon name="refresh" :size="18" />
              <span>Actualiser</span>
            </button>
            <RouterLink class="btn" to="/alerte-settings">
              <AppIcon name="settings" :size="18" />
              <span>Configurer</span>
            </RouterLink>
          </div>

          <div class="rh-hero-meta-list">
            <p class="rh-hero-meta">
              Alertes critiques:
              <strong>{{ stats.critiques }}</strong>
            </p>
            <p class="rh-hero-meta">
              Catégories actives:
              <strong>{{ activeSections }}</strong>
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
      <div class="main-column">
        <section class="alerts-grid">
          <article v-for="section in alertSections" :key="section.title" class="card rh-section-card alert-card">
            <div class="rh-section-heading">
              <div>
                <p class="rh-section-kicker">{{ section.kicker }}</p>
                <h2>{{ section.title }}</h2>
              </div>
              <span class="rh-section-chip">{{ section.items.length }}</span>
            </div>

            <p class="rh-section-copy">{{ section.copy }}</p>

            <div class="timeline" v-if="section.items.length">
              <div v-for="item in section.items" :key="section.key + (item.demande_id || item.contrat_id || item.employe_id || item.type)" class="timeline-item">
                <div class="bullet" :class="levelClass(item.level)"></div>
                <div class="timeline-copy">
                  <div class="timeline-top">
                    <span class="badge" v-if="item.employe?.matricule">{{ item.employe.matricule }}</span>
                    <span class="message">{{ item.message }}</span>
                  </div>

                  <div class="meta">
                    <span v-if="item.demande_id">Demande #{{ item.demande_id }}</span>
                    <span v-if="item.contrat_id">Contrat #{{ item.contrat_id }}</span>
                    <span v-if="item.date_fin">Fin le {{ item.date_fin }}</span>
                    <span v-if="item.solde">Solde {{ item.solde }} jours</span>
                    <span class="type-tag">{{ formatType(item.type) }}</span>
                  </div>

                  <RouterLink
                    v-if="item.demande_id"
                    class="link"
                    :to="{ name: 'demandes-conges', query: { focus: item.demande_id } }"
                  >
                    Ouvrir la demande
                  </RouterLink>

                  <RouterLink
                    v-else-if="item.contrat_id"
                    class="link"
                    :to="{ name: 'contrat-detail', params: { id: item.contrat_id } }"
                  >
                    Ouvrir le contrat
                  </RouterLink>
                </div>
              </div>
            </div>

            <div v-else class="rh-empty-state compact">
              <p>Aucune alerte</p>
              <span>Cette catégorie ne remonte aucun signal pour le moment.</span>
            </div>
          </article>
        </section>
      </div>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Overview</p>
            <h2>Résumé surveillance</h2>
          </div>
        </div>

        <p class="rh-summary-intro">
          Vue rapide des volumes et des alertes prioritaires avant d’entrer dans le détail.
        </p>

        <div class="rh-overview-grid">
          <article v-for="card in overviewCards" :key="card.label" class="rh-overview-card">
            <span class="rh-overview-chip">{{ card.tag }}</span>
            <p class="rh-overview-label">{{ card.label }}</p>
            <p class="rh-overview-value">{{ card.value }}</p>
            <p class="rh-overview-copy">{{ card.copy }}</p>
          </article>
        </div>

        <div class="priority-list" v-if="priorityAlerts.length">
          <div v-for="(item, index) in priorityAlerts" :key="index" class="priority-item" :class="levelClass(item.level)">
            <div class="priority-top">
              <span class="priority-pill" :class="levelClass(item.level)">{{ item.level || 'warning' }}</span>
              <span class="priority-type">{{ formatType(item.type) }}</span>
            </div>
            <p class="priority-message">{{ item.message }}</p>
          </div>
        </div>

        <div class="rh-notes-card">
          <h3>Repères rapides</h3>
          <ul>
            <li>Les alertes critiques doivent être traitées avant les alertes informatives.</li>
            <li>Le centre d’alertes et la page de configuration sont maintenant cohérents visuellement.</li>
            <li>Les liens rapides renvoient directement vers les écrans métier utiles.</li>
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

const alertes = ref([])

const fetchAlertes = async () => {
  const { data } = await api.get('/v1/alertes')
  alertes.value = data.data || []
}

const alertesFiltrees = (types) => alertes.value.filter((item) => types.includes(item.type))

const stats = computed(() => {
  const total = alertes.value.length
  const conges = alertesFiltrees(['conge_en_attente', 'conge_proche']).length
  const absences = alertesFiltrees(['absences_maladie', 'absences_exceptionnelles']).length
  const contrats = alertesFiltrees(['fin_contrat']).length
  const soldes = alertesFiltrees(['conges_non_pris']).length
  const critiques = alertes.value.filter((item) => item.level === 'danger').length
  return { total, conges, absences, contrats, soldes, critiques }
})

const alertSections = computed(() => [
  {
    key: 'conges',
    kicker: 'Congés',
    title: 'En attente / Proches',
    copy: 'Demandes à traiter et départs imminents visibles dans un seul bloc.',
    items: alertesFiltrees(['conge_en_attente', 'conge_proche']),
  },
  {
    key: 'absences',
    kicker: 'Absences',
    title: 'Absences répétées',
    copy: 'Suivi rapide des récurrences et des signaux comportementaux.',
    items: alertesFiltrees(['absences_maladie', 'absences_exceptionnelles']),
  },
  {
    key: 'contrats',
    kicker: 'Contrats',
    title: 'Fin de contrat proche',
    copy: 'Échéances à anticiper pour éviter les ruptures non préparées.',
    items: alertesFiltrees(['fin_contrat']),
  },
  {
    key: 'soldes',
    kicker: 'Soldes',
    title: 'Congés non pris',
    copy: 'Repérage des soldes à arbitrer avant clôture de période.',
    items: alertesFiltrees(['conges_non_pris']),
  },
])

const activeSections = computed(() => alertSections.value.filter((section) => section.items.length).length)

const priorityAlerts = computed(() =>
  [...alertes.value]
    .sort((left, right) => {
      const score = { danger: 0, warning: 1, info: 2 }
      return (score[left.level] ?? 3) - (score[right.level] ?? 3)
    })
    .slice(0, 4),
)

const metricCards = computed(() => [
  {
    label: 'Alertes totales',
    value: stats.value.total,
    caption: 'Volume global actuellement détecté',
    tag: 'All',
  },
  {
    label: 'Congés & demandes',
    value: stats.value.conges,
    caption: 'Demandes en attente ou départs proches',
    tag: 'Leave',
  },
  {
    label: 'Absences',
    value: stats.value.absences,
    caption: 'Maladie ou absences exceptionnelles',
    tag: 'Absence',
  },
  {
    label: 'Critiques',
    value: stats.value.critiques,
    caption: 'Alertes de niveau danger',
    tag: 'Priority',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Contrats proches',
    value: stats.value.contrats,
    copy: 'Échéances contractuelles à surveiller.',
    tag: 'Contracts',
  },
  {
    label: 'Soldes à traiter',
    value: stats.value.soldes,
    copy: 'Congés non pris signalés par le moteur.',
    tag: 'Balances',
  },
  {
    label: 'Sections actives',
    value: activeSections.value,
    copy: 'Catégories qui contiennent actuellement des alertes.',
    tag: 'Sections',
  },
  {
    label: 'Priorité dominante',
    value: stats.value.critiques ? 'Critique' : stats.value.total ? 'Surveillance' : 'Stable',
    copy: 'Lecture rapide du niveau de tension RH.',
    tag: 'State',
  },
])

const levelClass = (level) => {
  switch (level) {
    case 'danger':
      return 'danger'
    case 'info':
      return 'info'
    case 'warning':
    default:
      return 'warning'
  }
}

const formatType = (type) => {
  const types = {
    fin_contrat: 'Contrat',
    conges_non_pris: 'Congés',
    absences_maladie: 'Maladie',
    absences_exceptionnelles: 'Absences',
    conge_en_attente: 'Demande',
    conge_proche: 'Congé urgent',
  }
  return types[type] || type
}

onMounted(fetchAlertes)
</script>

<style scoped>
.main-column {
  min-width: 0;
}

.alerts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.timeline-item {
  display: grid;
  grid-template-columns: 18px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
  padding: 14px 0;
  border-bottom: 1px solid var(--border);
}

.timeline-item:last-child {
  border-bottom: none;
}

.bullet {
  width: 12px;
  height: 12px;
  margin-top: 5px;
  border-radius: 999px;
  background: rgba(247, 144, 9, 0.95);
  box-shadow: 0 0 0 6px rgba(247, 144, 9, 0.16);
}

.bullet.danger {
  background: rgba(240, 68, 56, 0.95);
  box-shadow: 0 0 0 6px rgba(240, 68, 56, 0.16);
}

.bullet.info {
  background: rgba(59, 130, 246, 0.95);
  box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.16);
}

.timeline-copy {
  min-width: 0;
}

.timeline-top {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.message {
  font-weight: 700;
  color: var(--text);
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
  color: var(--muted);
  font-size: 0.82rem;
}

.type-tag {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(79, 70, 229, 0.08);
  border: 1px solid rgba(79, 70, 229, 0.12);
  color: var(--brand-600);
  font-weight: 600;
}

.link {
  display: inline-flex;
  margin-top: 10px;
  color: var(--brand-600);
  font-size: 0.86rem;
  font-weight: 600;
}

.priority-list {
  display: grid;
  gap: 12px;
}

.priority-item {
  display: grid;
  gap: 8px;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .priority-item {
  background: rgba(15, 23, 42, 0.72);
}

.priority-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.priority-pill {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 700;
  text-transform: uppercase;
}

.priority-pill.danger {
  background: var(--danger-100);
  color: var(--danger-500);
}

.priority-pill.warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.priority-pill.info {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.priority-type {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 600;
}

.priority-message {
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.55;
  color: var(--text);
}

.compact {
  padding-top: 0;
  padding-bottom: 0;
}

@media (max-width: 960px) {
  .alerts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
