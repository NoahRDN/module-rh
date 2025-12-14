<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Configuration des horaires</h1>
      <span>Jours travaillés, seuil hebdomadaire, majorations</span>
    </div>
  </div>

  <div class="card" v-if="loading">Chargement…</div>
  <div class="card text-red-500" v-else-if="error">{{ error }}</div>

  <div class="card grid gap-3" v-else>
    <div class="grid md:grid-cols-2 gap-3">
      <div class="grid gap-1">
        <label class="text-xs text-slate-500">Jours travaillés</label>
        <div class="flex flex-wrap gap-2">
          <label v-for="d in days" :key="d.code" class="flex items-center gap-1">
            <input type="checkbox" :value="d.code" v-model="form.working_days" />
            <span>{{ d.label }}</span>
          </label>
        </div>
      </div>
      <div class="grid gap-1">
        <label class="text-xs text-slate-500">Mode samedi</label>
        <select class="select" v-model="form.saturday_mode">
          <option value="normal">Normal</option>
          <option value="hs">Heures sup</option>
        </select>
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-3">
      <div class="grid gap-1">
        <label class="text-xs text-slate-500">Heure de début</label>
        <div class="flex gap-2">
          <input class="input w-20" type="number" min="0" max="23" v-model.number="form.start_hour" />
          <input class="input w-20" type="number" min="0" max="59" v-model.number="form.start_minute" />
        </div>
      </div>
      <div class="grid gap-1">
        <label class="text-xs text-slate-500">Heures par jour</label>
        <input class="input" type="number" step="0.1" v-model.number="form.hours_per_day" />
      </div>
      <div class="grid gap-1">
        <label class="text-xs text-slate-500">Seuil hebdomadaire</label>
        <input class="input" type="number" step="0.1" v-model.number="form.weekly_threshold" />
      </div>
    </div>

    <div class="card muted">
      <h3 class="font-semibold mb-2">Majorations</h3>
      <div class="grid md:grid-cols-3 gap-2">
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Weekday 1ères 8h</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.weekday_first8" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Weekday 12h suivantes</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.weekday_next12" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Weekday au-delà</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.weekday_beyond" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Samedi</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.saturday" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Dimanche</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.sunday" />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-500">Férié</label>
          <input class="input" type="number" step="0.1" v-model.number="form.multipliers.holiday" />
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2">
      <button class="btn btn-secondary" @click="loadConfig">Réinitialiser</button>
      <button class="btn" @click="save">Enregistrer</button>
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
  saturday_mode: 'normal',
  start_hour: 8,
  start_minute: 0,
  hours_per_day: 8,
  weekly_threshold: 40,
  multipliers: {
    weekday_first8: 1.3,
    weekday_next12: 1.5,
    weekday_beyond: 1.5,
    saturday: 1.4,
    sunday: 1.4,
    holiday: 2.0,
  }
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
.page-title h1 { margin: 0; }
.card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
.grid { display: grid; gap: 12px; }
.muted { color: #94a3b8; }
.select, .input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; }
.btn { border: none; background: #0f172a; color: #fff; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
</style>
