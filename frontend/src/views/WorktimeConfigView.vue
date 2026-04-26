<template>
  <div class="rh-page worktime-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Worktime settings</p>
        <h1>Configuration des horaires</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Définissez la semaine de travail, les seuils horaires et les majorations dans une interface
          plus cohérente avec les autres vues d’administration RH.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Planning hebdomadaire</span>
          <span class="pill">Heures supplémentaires</span>
          <span class="pill">Règles absences</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" type="button" @click="loadConfig" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>Réinitialiser</span>
            </button>
            <button class="btn" type="button" @click="save" :disabled="loading">
              <AppIcon name="save" :size="18" />
              <span>Enregistrer</span>
            </button>
          </div>

          <div class="rh-hero-meta-list hero-meta-list">
            <p class="rh-hero-meta hero-meta">
              Jours actifs:
              <strong>{{ form.working_days?.length || 0 }}/7</strong>
            </p>
            <p class="rh-hero-meta hero-meta">
              Mode samedi:
              <strong>{{ form.saturday_mode === 'hs' ? 'Heures sup' : 'Normal' }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <p class="rh-status-banner success" v-if="statusMessage">
      <span class="rh-status-dot"></span>
      <span>{{ statusMessage }}</span>
    </p>
    <p class="rh-status-banner danger" v-if="error">
      <span class="rh-status-dot"></span>
      <span>{{ error }}</span>
    </p>

    <section class="rh-metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="rh-metric-card">
        <span class="rh-metric-chip">{{ metric.tag }}</span>
        <p class="rh-metric-label">{{ metric.label }}</p>
        <p class="rh-metric-value">{{ metric.value }}</p>
        <p class="rh-metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div class="card rh-loading-card" v-if="loading">
      <p class="rh-loading-title">Chargement de la configuration...</p>
      <p class="muted">Les règles de temps de travail sont en cours de synchronisation.</p>
    </div>

    <section class="rh-content-grid" v-else>
      <div class="main-column">
        <article class="card rh-section-card">
          <div class="rh-section-heading">
            <div>
              <p class="rh-section-kicker">Work schedule</p>
              <h2>Temps de travail de référence</h2>
            </div>
          </div>

          <div class="rh-fields-grid">
            <label class="rh-field-card full">
              <span class="rh-field-label">Jours travaillés</span>
              <div class="chips-grid">
                <label v-for="d in days" :key="d.code" class="chip-option">
                  <input type="checkbox" :value="d.code" v-model="form.working_days" />
                  <span>{{ d.label }}</span>
                </label>
              </div>
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Mode samedi</span>
              <select class="select" v-model="form.saturday_mode">
                <option value="normal">Normal</option>
                <option value="hs">Heures sup</option>
              </select>
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Heure de début</span>
              <div class="time-inputs">
                <input class="input" type="number" min="0" max="23" v-model.number="form.start_hour" />
                <span>:</span>
                <input class="input" type="number" min="0" max="59" v-model.number="form.start_minute" />
              </div>
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Heures par jour</span>
              <input class="input" type="number" step="0.1" v-model.number="form.hours_per_day" />
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Retard tolérable (min)</span>
              <input class="input" type="number" min="0" step="1" v-model.number="form.retard_tolerance_minutes" />
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Seuil retard / absence (h)</span>
              <input class="input" type="number" min="0" step="0.25" v-model.number="form.retard_threshold_hours" />
            </label>

            <label class="rh-field-card">
              <span class="rh-field-label">Seuil hebdomadaire</span>
              <input class="input" type="number" step="0.1" v-model.number="form.weekly_threshold" />
            </label>
          </div>
        </article>

        <article class="card rh-section-card">
          <div class="rh-section-heading">
            <div>
              <p class="rh-section-kicker">Overtime rates</p>
              <h2>Majorations des heures supplémentaires (%)</h2>
            </div>
          </div>

          <div class="rh-fields-grid">
            <label class="rh-field-card">
              <span class="rh-field-label">Weekday 1ères 8h</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_first8" />
            </label>
            <label class="rh-field-card">
              <span class="rh-field-label">Weekday 12h suivantes</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_next12" />
            </label>
            <label class="rh-field-card">
              <span class="rh-field-label">Weekday au-delà</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_beyond" />
            </label>
            <label class="rh-field-card">
              <span class="rh-field-label">Samedi</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.saturday" />
            </label>
            <label class="rh-field-card">
              <span class="rh-field-label">Dimanche</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.sunday" />
            </label>
            <label class="rh-field-card">
              <span class="rh-field-label">Férié</span>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.holiday" />
            </label>
          </div>
        </article>

        <article class="card rh-section-card">
          <div class="rh-section-heading">
            <div>
              <p class="rh-section-kicker">Deductions policy</p>
              <h2>Prélèvements absences et retards</h2>
            </div>
          </div>

          <div class="toggle-grid">
            <label class="toggle-row">
              <div class="toggle-copy">
                <div class="toggle-title">Prélever sur le solde de congé</div>
                <div class="toggle-sub">Aucun impact salaire tant que le solde couvre l'absence.</div>
              </div>
              <span class="toggle-switch">
                <input type="checkbox" v-model="form.deduct_from_leave_balance" />
                <span class="toggle-slider" aria-hidden="true"></span>
              </span>
            </label>

            <label class="toggle-row">
              <div class="toggle-copy">
                <div class="toggle-title">Prélever sur le salaire</div>
                <div class="toggle-sub">Applique les taux journalier et horaire si le solde est insuffisant.</div>
              </div>
              <span class="toggle-switch">
                <input type="checkbox" v-model="form.deduct_from_salary" />
                <span class="toggle-slider" aria-hidden="true"></span>
              </span>
            </label>
          </div>
        </article>
      </div>

    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const loading = ref(false)
const error = ref('')
const statusMessage = ref('')
const form = ref({
  working_days: ['mon', 'tue', 'wed', 'thu', 'fri'],
  saturday_mode: 'hs',
  start_hour: 8,
  start_minute: 0,
  retard_tolerance_minutes: 0,
  retard_threshold_hours: 2,
  hours_per_day: 8,
  weekly_threshold: 40,
  multipliers: {
    weekday_first8: 30,
    weekday_next12: 50,
    weekday_beyond: 50,
    saturday: 40,
    sunday: 40,
    holiday: 100,
  },
  deduct_from_leave_balance: false,
  deduct_from_salary: false,
})

const days = [
  { code: 'mon', label: 'Lundi' },
  { code: 'tue', label: 'Mardi' },
  { code: 'wed', label: 'Mercredi' },
  { code: 'thu', label: 'Jeudi' },
  { code: 'fri', label: 'Vendredi' },
  { code: 'sat', label: 'Samedi' },
  { code: 'sun', label: 'Dimanche' },
]

const loadConfig = async () => {
  loading.value = true
  error.value = ''
  statusMessage.value = ''
  try {
    const { data } = await api.get('/v1/worktime')
    form.value = {
      ...form.value,
      ...data,
      multipliers: { ...form.value.multipliers, ...(data.multipliers || {}) },
      working_days: data.working_days || form.value.working_days,
      deduct_from_leave_balance:
        data.deduct_from_leave_balance ?? form.value.deduct_from_leave_balance,
      deduct_from_salary: data.deduct_from_salary ?? form.value.deduct_from_salary,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

const save = async () => {
  loading.value = true
  error.value = ''
  statusMessage.value = ''
  try {
    await api.put('/v1/worktime', form.value)
    statusMessage.value = 'Configuration des horaires enregistrée avec succès.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de sauvegarde'
  } finally {
    loading.value = false
  }
}

const metrics = computed(() => {
  const workingDays = form.value.working_days?.length || 0
  const hour = String(form.value.start_hour ?? 0).padStart(2, '0')
  const minute = String(form.value.start_minute ?? 0).padStart(2, '0')
  return [
    {
      tag: 'Schedule',
      label: 'Jours travaillés',
      value: `${workingDays}/7`,
      caption: 'Cadence hebdomadaire active',
    },
    {
      tag: 'Start',
      label: 'Heure de début',
      value: `${hour}:${minute}`,
      caption: 'Point de départ journalier',
    },
    {
      tag: 'Daily',
      label: 'Heures par jour',
      value: `${form.value.hours_per_day || 0} h`,
      caption: 'Base utilisée pour les écarts',
    },
    {
      tag: 'Delay',
      label: 'Tolérance retard',
      value: `${form.value.retard_tolerance_minutes || 0} min`,
      caption: 'Retard compté au-delà de ce seuil',
    },
    {
      tag: 'Rule',
      label: 'Seuil retard/absence',
      value: `${form.value.retard_threshold_hours || 0} h`,
      caption: 'Au-delà, la journée partielle est classée en absence',
    },
    {
      tag: 'Weekly',
      label: 'Seuil hebdomadaire',
      value: `${form.value.weekly_threshold || 0} h`,
      caption: 'Déclenchement des heures sup.',
    },
  ]
})

const overviewCards = computed(() => [
  {
    label: 'Mode samedi',
    value: form.value.saturday_mode === 'hs' ? 'HS' : 'Normal',
    copy: 'Interprétation du samedi dans le calcul des heures.',
    tag: 'Saturday',
  },
  {
    label: 'Majoration dimanche',
    value: `${form.value.multipliers.sunday || 0}%`,
    copy: 'Taux appliqué au travail dominical.',
    tag: 'Sunday',
  },
  {
    label: 'Solde de congé',
    value: form.value.deduct_from_leave_balance ? 'Actif' : 'Off',
    copy: 'Prélèvement sur les droits de congé.',
    tag: 'Leave',
  },
  {
    label: 'Impact salaire',
    value: form.value.deduct_from_salary ? 'Actif' : 'Off',
    copy: 'Déduction salariale en cas de besoin.',
    tag: 'Salary',
  },
])

onMounted(loadConfig)
</script>

<style scoped>
.rh-content-grid {
  grid-template-columns: 1fr;
}

.main-column {
  display: grid;
  gap: 18px;
  min-width: 0;
}

.chips-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.chip-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.76);
  color: var(--text);
  font-weight: 600;
}

body[data-theme='dark'] .chip-option {
  background: rgba(15, 23, 42, 0.72);
}

.time-inputs {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  gap: 10px;
  align-items: center;
}

.time-inputs span {
  font-weight: 700;
  color: var(--muted);
}

.toggle-grid {
  display: grid;
  gap: 14px;
}

.toggle-row {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.76);
  cursor: pointer;
}

body[data-theme='dark'] .toggle-row {
  background: rgba(15, 23, 42, 0.72);
}

.toggle-copy {
  display: grid;
  gap: 6px;
  min-width: 0;
}

.toggle-switch {
  position: relative;
  display: inline-flex;
  flex: none;
}

.toggle-switch input {
  position: absolute;
  inset: 0;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}

.toggle-slider {
  position: relative;
  display: inline-flex;
  width: 52px;
  height: 32px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.28);
  border: 1px solid rgba(148, 163, 184, 0.28);
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.toggle-slider::after {
  content: '';
  position: absolute;
  top: 3px;
  left: 3px;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.18);
  transition: transform 0.2s ease;
}

.toggle-switch input:checked + .toggle-slider {
  background: rgba(79, 70, 229, 0.22);
  border-color: rgba(79, 70, 229, 0.3);
}

.toggle-switch input:checked + .toggle-slider::after {
  transform: translateX(20px);
}

.toggle-switch input:focus-visible + .toggle-slider {
  outline: 2px solid rgba(79, 70, 229, 0.32);
  outline-offset: 2px;
}

body[data-theme='dark'] .toggle-slider {
  background: rgba(51, 65, 85, 0.72);
  border-color: rgba(71, 85, 105, 0.7);
}

body[data-theme='dark'] .toggle-slider::after {
  background: #e2e8f0;
}

.toggle-title,
.toggle-sub {
  margin: 0;
}

.toggle-title {
  font-weight: 800;
}

.toggle-sub {
  color: var(--muted);
  font-size: 0.88rem;
  line-height: 1.55;
}

@media (max-width: 720px) {
  .toggle-row {
    align-items: flex-start;
  }
}
</style>
