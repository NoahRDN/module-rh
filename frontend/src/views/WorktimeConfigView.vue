<template>
  <div class="worktime-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Worktime settings</p>
        <h1>Configuration des horaires</h1>
        <p class="hero-subtitle">
          Définissez la semaine de travail, les seuils horaires et les majorations pour une gestion
          homogène du temps et de la paie.
        </p>

        <div class="hero-pills">
          <span class="pill">Planning hebdomadaire</span>
          <span class="pill">Heures supplémentaires</span>
          <span class="pill">Règles absences</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="action-row">
          <button class="btn btn-secondary" type="button" @click="loadConfig" :disabled="loading">
            <AppIcon name="refresh" :size="18" />
            <span>Réinitialiser</span>
          </button>
          <button class="btn" type="button" @click="save" :disabled="loading">
            <AppIcon name="save" :size="18" />
            <span>Enregistrer</span>
          </button>
        </div>

        <p class="hero-meta">
          Jours actifs:
          <strong>{{ form.working_days?.length || 0 }}/7</strong>
        </p>
      </div>
    </section>

    <p class="banner success" v-if="statusMessage">{{ statusMessage }}</p>
    <p class="banner danger" v-if="error">{{ error }}</p>

    <section class="metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div class="card loading-card" v-if="loading">Chargement de la configuration...</div>

    <section class="content-grid" v-else>
      <article class="card section-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Work schedule</p>
            <h2>Temps de travail de référence</h2>
          </div>
        </div>

        <div class="fields-grid">
          <label class="field-card field-wide">
            <span class="field-label">Jours travaillés</span>
            <div class="chips">
              <label v-for="d in days" :key="d.code" class="chip">
                <input type="checkbox" :value="d.code" v-model="form.working_days" />
                <span>{{ d.label }}</span>
              </label>
            </div>
          </label>

          <label class="field-card">
            <span class="field-label">Mode samedi</span>
            <select class="input" v-model="form.saturday_mode">
              <option value="normal">Normal</option>
              <option value="hs">Heures sup</option>
            </select>
          </label>
        </div>

        <div class="fields-grid">
          <label class="field-card">
            <span class="field-label">Heure de début</span>
            <div class="time-inputs">
              <input class="input" type="number" min="0" max="23" v-model.number="form.start_hour" />
              <span>:</span>
              <input class="input" type="number" min="0" max="59" v-model.number="form.start_minute" />
            </div>
          </label>

          <label class="field-card">
            <span class="field-label">Heures par jour</span>
            <input class="input" type="number" step="0.1" v-model.number="form.hours_per_day" />
          </label>

          <label class="field-card">
            <span class="field-label">Seuil hebdomadaire</span>
            <input class="input" type="number" step="0.1" v-model.number="form.weekly_threshold" />
          </label>
        </div>
      </article>

      <article class="card section-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Overtime rates</p>
            <h2>Majorations des heures supplémentaires (%)</h2>
          </div>
        </div>

        <div class="fields-grid multipliers">
          <label class="field-card">
            <span class="field-label">Weekday 1ères 8h</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_first8" />
          </label>
          <label class="field-card">
            <span class="field-label">Weekday 12h suivantes</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_next12" />
          </label>
          <label class="field-card">
            <span class="field-label">Weekday au-delà</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_beyond" />
          </label>
          <label class="field-card">
            <span class="field-label">Samedi</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.saturday" />
          </label>
          <label class="field-card">
            <span class="field-label">Dimanche</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.sunday" />
          </label>
          <label class="field-card">
            <span class="field-label">Férié</span>
            <input class="input" type="number" step="1" v-model.number="form.multipliers.holiday" />
          </label>
        </div>
      </article>

      <article class="card section-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Deductions policy</p>
            <h2>Prélèvements absences et retards</h2>
          </div>
        </div>

        <div class="toggle-grid">
          <label class="toggle-row">
            <input type="checkbox" v-model="form.deduct_from_leave_balance" />
            <div>
              <div class="toggle-title">Prélever sur le solde de congé</div>
              <div class="toggle-sub">Aucun impact salaire tant que le solde couvre l'absence.</div>
            </div>
          </label>

          <label class="toggle-row">
            <input type="checkbox" v-model="form.deduct_from_salary" />
            <div>
              <div class="toggle-title">Prélever sur le salaire</div>
              <div class="toggle-sub">Applique les taux journalier et horaire si le solde est insuffisant.</div>
            </div>
          </label>
        </div>
      </article>
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
  working_days: ['mon','tue','wed','thu','fri'],
  saturday_mode: 'hs',
  start_hour: 8,
  start_minute: 0,
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
      deduct_from_leave_balance: data.deduct_from_leave_balance ?? form.value.deduct_from_leave_balance,
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
      tag: 'Weekly',
      label: 'Seuil hebdomadaire',
      value: `${form.value.weekly_threshold || 0} h`,
      caption: 'Déclenchement des heures sup.',
    },
  ]
})

onMounted(loadConfig)
</script>

<style scoped>
.worktime-page {
  display: grid;
  gap: 20px;
}

.hero {
  display: grid;
  grid-template-columns: 1.2fr minmax(280px, 0.8fr);
  gap: 18px;
  border: 1px solid color-mix(in srgb, var(--border) 84%, var(--brand-primary));
  border-radius: 20px;
  padding: 22px;
  background: linear-gradient(
    140deg,
    color-mix(in srgb, var(--brand-primary) 8%, var(--panel) 92%),
    var(--panel)
  );
}

.hero-kicker {
  margin: 0;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--brand-primary);
  font-weight: 700;
}

.hero-copy h1 {
  margin: 8px 0;
  font-size: clamp(1.5rem, 2.5vw, 2rem);
}

.hero-subtitle {
  margin: 0;
  color: color-mix(in srgb, var(--muted) 82%, var(--text));
  max-width: 64ch;
  line-height: 1.55;
}

.hero-pills {
  margin-top: 12px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 11px;
  border-radius: 999px;
  border: 1px solid var(--border);
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
  background: color-mix(in srgb, var(--brand-primary) 10%, transparent);
}

.hero-actions {
  border: 1px solid color-mix(in srgb, var(--border) 84%, var(--brand-primary));
  border-radius: 16px;
  padding: 14px;
  background: color-mix(in srgb, var(--panel) 88%, var(--panel-soft) 12%);
  display: grid;
  gap: 12px;
  align-content: start;
}

.action-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}

.action-row > * {
  flex: 0 0 auto;
}

.hero-meta {
  margin: 0;
  font-size: 13px;
  color: color-mix(in srgb, var(--muted) 82%, var(--text));
}

.hero-meta strong {
  color: var(--text);
}

.btn {
  border: 1px solid color-mix(in srgb, var(--brand-primary) 35%, var(--border));
  background: var(--brand-primary);
  color: #fff;
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.btn:disabled {
  opacity: 1;
  background: color-mix(in srgb, var(--panel) 88%, var(--brand-primary) 12%);
  border-color: color-mix(in srgb, var(--border) 86%, var(--brand-primary));
  color: var(--muted);
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--panel);
  color: var(--text);
  border-color: var(--border);
}

.btn-secondary:disabled {
  background: color-mix(in srgb, var(--panel) 92%, var(--panel-soft) 8%);
}

.banner {
  margin: 0;
  border-radius: 12px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  font-size: 13px;
  font-weight: 600;
}

.banner.success {
  border-color: color-mix(in srgb, #16a34a 45%, var(--border));
  color: color-mix(in srgb, #166534 72%, var(--text));
  background: color-mix(in srgb, #22c55e 12%, transparent);
}

.banner.danger {
  border-color: color-mix(in srgb, #dc2626 45%, var(--border));
  color: color-mix(in srgb, #991b1b 72%, var(--text));
  background: color-mix(in srgb, #ef4444 10%, transparent);
}

.metric-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
}

.metric-card {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
  background: var(--panel);
}

.metric-chip {
  display: inline-flex;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid var(--border);
  color: var(--muted);
  font-size: 11px;
  font-weight: 600;
}

.metric-label {
  margin: 10px 0 2px;
  color: var(--muted);
  font-size: 12px;
}

.metric-value {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
}

.metric-caption {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--muted);
}

.loading-card {
  border: 1px dashed var(--border);
  border-radius: 14px;
  padding: 14px;
  color: var(--muted);
  text-align: center;
}

.content-grid {
  display: grid;
  gap: 16px;
}

.card.section-card {
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px;
  background: var(--panel);
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.section-kicker {
  margin: 0;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--brand-primary);
  font-weight: 700;
}

.section-heading h2 {
  margin: 5px 0 0;
  font-size: 1.1rem;
}

.fields-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  margin-top: 10px;
}

.field-card {
  display: grid;
  gap: 6px;
}

.field-wide {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 12px;
  color: var(--muted);
  font-weight: 600;
}

.input {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
  width: 100%;
  background: var(--panel);
  color: var(--text);
}

.input:focus {
  outline: 2px solid color-mix(in srgb, var(--brand-primary) 35%, transparent);
  outline-offset: 1px;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: color-mix(in srgb, var(--panel-soft) 65%, transparent);
}

.chip input {
  margin: 0;
}

.time-inputs {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 8px;
}

.time-inputs span {
  color: var(--muted);
  font-weight: 700;
}

.multipliers .field-card .input {
  text-align: right;
}

.toggle-grid {
  display: grid;
  gap: 10px;
}

.toggle-row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 10px;
  align-items: start;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: color-mix(in srgb, var(--panel-soft) 68%, transparent);
}

.toggle-row input {
  width: 18px;
  height: 18px;
  margin-top: 2px;
}

.toggle-title {
  font-weight: 700;
}

.toggle-sub {
  font-size: 13px;
  color: var(--muted);
  margin-top: 2px;
}

@media (max-width: 1120px) {
  .hero {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .worktime-page {
    gap: 14px;
  }

  .hero,
  .card.section-card {
    padding: 14px;
    border-radius: 14px;
  }

  .action-row {
    width: 100%;
  }

  .action-row .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
