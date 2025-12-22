<template>
  <div class="worktime-settings">
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Configuration des horaires</h1>
          <span>Jours travaillés, seuils et majorations</span>
        </div>
        <div class="actions">
          <button class="btn btn-secondary" @click="loadConfig">↻ Réinitialiser</button>
          <button class="btn" @click="save">💾 Enregistrer</button>
        </div>
      </div>

      <div class="placeholder" v-if="loading">Chargement…</div>
      <div class="error-card" v-else-if="error">{{ error }}</div>

      <div class="panel-grid" v-else>
        <div class="setting-card">
          <div class="setting-header">
            <h3>Temps de travail</h3>
            <p>Cadrez les jours et horaires de référence</p>
          </div>
          <div class="fields-grid">
            <div class="field">
              <label>Jours travaillés</label>
              <div class="chips">
                <label v-for="d in days" :key="d.code" class="chip">
                  <input type="checkbox" :value="d.code" v-model="form.working_days" />
                  <span>{{ d.label }}</span>
                </label>
              </div>
            </div>
            <div class="field">
              <label>Mode samedi</label>
              <select class="input" v-model="form.saturday_mode">
                <option value="normal">Normal</option>
                <option value="hs">Heures sup</option>
              </select>
            </div>
          </div>

          <div class="fields-grid mt-2">
            <div class="field">
              <label>Heure de début</label>
              <div class="time-inputs">
                <input class="input" type="number" min="0" max="23" v-model.number="form.start_hour" />
                <span>:</span>
                <input class="input" type="number" min="0" max="59" v-model.number="form.start_minute" />
              </div>
            </div>
            <div class="field">
              <label>Heures par jour</label>
              <input class="input" type="number" step="0.1" v-model.number="form.hours_per_day" />
            </div>
            <div class="field">
              <label>Seuil hebdomadaire</label>
              <input class="input" type="number" step="0.1" v-model.number="form.weekly_threshold" />
            </div>
          </div>
        </div>

        <div class="setting-card">
          <div class="setting-header">
            <h3>Majorations (%)</h3>
            <p>Coefficients appliqués aux heures supplémentaires</p>
          </div>
          <div class="fields-grid multipliers">
            <div class="field">
              <label>Weekday 1ères 8h</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_first8" />
            </div>
            <div class="field">
              <label>Weekday 12h suivantes</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_next12" />
            </div>
            <div class="field">
              <label>Weekday au-delà</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.weekday_beyond" />
            </div>
            <div class="field">
              <label>Samedi</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.saturday" />
            </div>
            <div class="field">
              <label>Dimanche</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.sunday" />
            </div>
            <div class="field">
              <label>Férié</label>
              <input class="input" type="number" step="1" v-model.number="form.multipliers.holiday" />
            </div>
          </div>
        </div>

        <div class="setting-card">
          <div class="setting-header">
            <h3>Prélèvements absences/retards</h3>
            <p>Choisissez l'imputation des absences et retards</p>
          </div>
          <div class="fields-grid">
            <label class="toggle-row">
              <input type="checkbox" v-model="form.deduct_from_leave_balance" />
              <div>
                <div class="toggle-title">Prélever sur le solde de congé</div>
                <div class="toggle-sub">Aucun impact salaire tant que le solde couvre l'absence</div>
              </div>
            </label>
            <label class="toggle-row">
              <input type="checkbox" v-model="form.deduct_from_salary" />
              <div>
                <div class="toggle-title">Prélever sur le salaire</div>
                <div class="toggle-sub">Utilise les taux journalier/horaire quand le solde est insuffisant</div>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const loading = ref(false)
const error = ref('')
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
  try {
    await api.put('/v1/worktime', form.value)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de sauvegarde'
  } finally {
    loading.value = false
  }
}

onMounted(loadConfig)
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 16px;
}
.page-title h1 { margin: 0; font-size: 20px; }
.page-title span { color: var(--muted); }
.actions { display: flex; gap: 8px; }
.panel-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
}
.card {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  background: var(--card, #fff);
  box-shadow: var(--shadow, 0 10px 30px rgba(15, 23, 42, 0.06));
}
.setting-card {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  background: var(--card, #fff);
  box-shadow: var(--shadow, 0 10px 30px rgba(15, 23, 42, 0.06));
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.setting-header h3 { margin: 0 0 4px; font-size: 16px; }
.setting-header p { margin: 0; color: var(--muted); font-size: 13px; }
.grid { display: grid; gap: 12px; }
.field { display: grid; gap: 6px; }
.field label { font-size: 12px; color: #64748b; }
.fields-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}
.field label { font-size: 12px; color: var(--muted); }
.input {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
  width: 100%;
  background: var(--card, #fff);
  color: var(--text);
}
.btn {
  border: none;
  background: linear-gradient(135deg, #0ea5e9, #2563eb);
  color: #fff;
  padding: 9px 12px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
}
.btn-secondary { background: rgba(148, 163, 184, 0.2); color: var(--text); box-shadow: none; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; }
.chip { display: inline-flex; align-items: center; gap: 6px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 10px; background: rgba(255, 255, 255, 0.02); }
.chip input { margin: 0; }
.time-inputs { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px; }
.time-inputs span { color: var(--muted); font-weight: 600; }
.placeholder, .error-card {
  border: 1px dashed var(--border);
  border-radius: 12px;
  padding: 12px;
  text-align: center;
  color: var(--muted);
}
.error-card { border-color: #ef4444; color: #ef4444; }
.multipliers .field input { text-align: right; }
.mt-2 { margin-top: 8px; }
.toggle-row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
}
.toggle-row input { width: 18px; height: 18px; }
.toggle-title { font-weight: 600; }
.toggle-sub { font-size: 13px; color: var(--muted); }
</style>
