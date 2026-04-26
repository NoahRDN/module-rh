<template>
  <div class="rh-page alerte-settings-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Monitoring rules</p>
        <h1>Paramètres d'alertes</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Ajustez les seuils, les fenêtres d’analyse et les niveaux de criticité avec la même
          structure visuelle que les autres pages de pilotage RH.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Règles actives</span>
          <span class="pill">Seuils métier</span>
          <span class="pill">Criticité</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" @click="loadSettings">
              <AppIcon name="refresh" :size="18" />
              <span>Recharger règles</span>
            </button>
            <button class="btn" @click="loadAlertes">
              <AppIcon name="bell" :size="18" />
              <span>Rafraîchir alertes</span>
            </button>
          </div>

          <div class="rh-hero-meta-list hero-meta-list">
            <p class="rh-hero-meta hero-meta">
              Règles actives:
              <strong>{{ activeCount }}</strong>
            </p>
            <p class="rh-hero-meta hero-meta">
              Alertes remontées:
              <strong>{{ alertes.length }}</strong>
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
            <p class="rh-section-kicker">Rule catalog</p>
            <h2>Configuration des règles</h2>
          </div>
          <span class="rh-section-chip">{{ settings.length }} règles</span>
        </div>

        <p class="rh-section-copy">
          Activez, désactivez et ajustez les paramètres de chaque alerte sans quitter l’écran de
          configuration.
        </p>

        <div class="settings-list">
          <article v-for="setting in settings" :key="setting.id" class="setting-card" :class="{ inactive: !setting.actif }">
            <div class="setting-header">
              <div class="setting-info">
                <label class="switch">
                  <input type="checkbox" v-model="setting.actif" @change="updateSetting(setting)" />
                  <span class="slider"></span>
                </label>

                <div class="setting-copy">
                  <div class="setting-top">
                    <h3>{{ setting.libelle }}</h3>
                    <span class="niveau-badge" :class="'niveau-' + setting.niveau">{{ setting.niveau }}</span>
                  </div>
                  <p>{{ setting.description }}</p>
                </div>
              </div>
            </div>

            <div class="setting-params" v-if="setting.actif">
              <label class="rh-field-card" v-if="setting.seuil_jours !== null">
                <span class="rh-field-label">Seuil en jours</span>
                <input class="input" type="number" v-model.number="setting.seuil_jours" min="1" @blur="updateSetting(setting)" />
                <span class="param-hint">{{ getSeuilJoursHint(setting.code) }}</span>
              </label>

              <label class="rh-field-card" v-if="setting.seuil_nombre !== null">
                <span class="rh-field-label">Seuil numérique</span>
                <input class="input" type="number" v-model.number="setting.seuil_nombre" min="1" @blur="updateSetting(setting)" />
                <span class="param-hint">{{ getSeuilNombreHint(setting.code) }}</span>
              </label>

              <label class="rh-field-card" v-if="setting.periode_jours !== null">
                <span class="rh-field-label">Fenêtre d'analyse</span>
                <input class="input" type="number" v-model.number="setting.periode_jours" min="1" @blur="updateSetting(setting)" />
                <span class="param-hint">Nombre de jours utilisés pour le calcul.</span>
              </label>

              <label class="rh-field-card">
                <span class="rh-field-label">Niveau de criticité</span>
                <select class="select" v-model="setting.niveau" @change="updateSetting(setting)">
                  <option value="info">Information</option>
                  <option value="warning">Avertissement</option>
                  <option value="danger">Critique</option>
                </select>
                <span class="param-hint">Impact visuel et ordre de priorité dans le centre d’alertes.</span>
              </label>
            </div>
          </article>
        </div>
      </article>

    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const settings = ref([])
const alertes = ref([])

const loadSettings = async () => {
  try {
    const res = await api.get('/v1/alerte-settings')
    settings.value = res.data.data || []
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const loadAlertes = async () => {
  try {
    const res = await api.get('/v1/alertes')
    alertes.value = res.data.data || []
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const updateSetting = async (setting) => {
  try {
    await api.put(`/v1/alerte-settings/${setting.id}`, {
      actif: setting.actif,
      seuil_jours: setting.seuil_jours,
      seuil_nombre: setting.seuil_nombre,
      periode_jours: setting.periode_jours,
      niveau: setting.niveau,
    })
    loadAlertes()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const activeCount = computed(() => settings.value.filter((setting) => Boolean(setting.actif)).length)
const criticalCount = computed(() => settings.value.filter((setting) => setting.niveau === 'danger').length)
const warningCount = computed(() => settings.value.filter((setting) => setting.niveau === 'warning').length)

const metricCards = computed(() => [
  {
    label: 'Règles disponibles',
    value: settings.value.length,
    caption: 'Catalogue complet de surveillance',
    tag: 'Rules',
  },
  {
    label: 'Actives',
    value: activeCount.value,
    caption: 'Règles actuellement utilisées',
    tag: 'Active',
  },
  {
    label: 'Critiques',
    value: criticalCount.value,
    caption: 'Niveau danger configuré',
    tag: 'Danger',
  },
  {
    label: 'Alertes remontées',
    value: alertes.value.length,
    caption: 'Impact visible sur le moteur',
    tag: 'Preview',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Règles warning',
    value: warningCount.value,
    copy: 'Signalements de niveau intermédiaire.',
    tag: 'Warn',
  },
  {
    label: 'Inactives',
    value: Math.max(settings.value.length - activeCount.value, 0),
    copy: 'Règles visibles mais désactivées.',
    tag: 'Off',
  },
  {
    label: 'Alerte dominante',
    value: alertes.value[0] ? formatType(alertes.value[0].type) : 'Aucune',
    copy: 'Premier signal remonté dans la liste actuelle.',
    tag: 'Top',
  },
  {
    label: 'État global',
    value: alertes.value.length ? 'Sous surveillance' : 'Stable',
    copy: 'Lecture rapide du moteur d’alertes.',
    tag: 'State',
  },
])

const getSeuilJoursHint = (code) => {
  const hints = {
    fin_contrat: 'Jours avant expiration du contrat',
    conge_en_attente: "Délai d'attente avant alerte",
    conge_proche: 'Jours avant début du congé',
    ferie_proche: 'Jours avant le jour férié',
    evenement_rh_proche: "Jours avant l'événement RH",
  }
  return hints[code] || 'Nombre de jours'
}

const getSeuilNombreHint = (code) => {
  const hints = {
    conges_non_pris: 'Nombre minimum de jours non pris',
    absences_maladie: "Nombre d'absences déclenchant l'alerte",
    absences_exceptionnelles: 'Nombre de congés exceptionnels',
  }
  return hints[code] || 'Seuil numérique'
}

const formatType = (type) => {
  const types = {
    fin_contrat: 'Contrat',
    conges_non_pris: 'Congés',
    absences_maladie: 'Maladie',
    absences_exceptionnelles: 'Absences',
    conge_en_attente: 'Demande',
    conge_proche: 'Congé urgent',
    ferie_proche: 'Férié proche',
    evenement_rh_proche: 'Événement RH',
  }
  return types[type] || type
}

onMounted(() => {
  loadSettings()
  loadAlertes()
})
</script>

<style scoped>
.settings-list {
  display: grid;
  gap: 16px;
}

.setting-card {
  padding: 20px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.72);
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.rh-content-grid {
  grid-template-columns: 1fr;
}

body[data-theme='dark'] .setting-card {
  background: rgba(15, 23, 42, 0.72);
}

.setting-card.inactive {
  opacity: 0.62;
}

.setting-header,
.setting-info {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.setting-header {
  justify-content: space-between;
}

.setting-copy {
  display: grid;
  gap: 6px;
}

.setting-top {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.setting-copy h3,
.preview-message {
  margin: 0;
}

.setting-copy h3 {
  font-size: 1rem;
  font-weight: 800;
}

.setting-copy p {
  margin: 0;
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.55;
}

.switch {
  position: relative;
  display: inline-flex;
  width: 48px;
  height: 28px;
  flex: none;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.22);
  transition: 0.2s ease;
}

.slider::before {
  content: '';
  position: absolute;
  left: 4px;
  top: 4px;
  width: 20px;
  height: 20px;
  border-radius: 999px;
  background: #ffffff;
  transition: 0.2s ease;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
}

.switch input:checked + .slider {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.switch input:checked + .slider::before {
  transform: translateX(20px);
}

.niveau-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 700;
  text-transform: uppercase;
}

.niveau-info {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.niveau-warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.niveau-danger {
  background: var(--danger-100);
  color: var(--danger-500);
}

.setting-params {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

.param-hint {
  color: var(--muted);
  font-size: 0.78rem;
  line-height: 1.45;
}

.preview-list {
  display: grid;
  gap: 12px;
}

.preview-item {
  display: grid;
  gap: 8px;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .preview-item {
  background: rgba(15, 23, 42, 0.72);
}

.preview-danger {
  border-color: rgba(240, 68, 56, 0.2);
}

.preview-warning {
  border-color: rgba(247, 144, 9, 0.2);
}

.preview-info {
  border-color: rgba(59, 130, 246, 0.2);
}

.preview-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.preview-level,
.preview-type {
  font-size: 0.78rem;
  font-weight: 700;
}

.preview-level {
  display: inline-flex;
  align-items: center;
  padding: 4px 9px;
  border-radius: 999px;
  text-transform: uppercase;
}

.preview-level-danger {
  background: var(--danger-100);
  color: var(--danger-500);
}

.preview-level-warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.preview-level-info {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.preview-type {
  color: var(--muted);
}

.preview-message {
  font-size: 0.92rem;
  color: var(--text);
  line-height: 1.55;
}

.compact {
  padding-top: 0;
  padding-bottom: 0;
}

@media (max-width: 960px) {
  .setting-params {
    grid-template-columns: 1fr;
  }
}
</style>
