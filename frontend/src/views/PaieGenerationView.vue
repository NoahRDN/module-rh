<template>
  <div class="page">
    <div class="hero">
      <div>
        <p class="eyebrow">Payroll</p>
        <h1>Génération de la paie</h1>
        <p class="subtitle">Calculez brut / net, heures sup et retenues en un clic.</p>
        <div class="chips">
          <span class="pill">Bulletins PDF</span>
          <span class="pill pill-blue">IRSA / CNAPS / OSTIE</span>
          <span class="pill pill-green">Heures sup gérées</span>
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="card glass">
        <div class="card-header">
          <div>
            <p class="eyebrow">Paramètres</p>
            <h3>Employé & mois</h3>
          </div>
          <button class="btn-ghost" @click="fetchEmployes">↻</button>
        </div>
        <form class="form" @submit.prevent="generer">
          <label class="field">
            <span>Employé</span>
            <select class="input" v-model="form.employe_id" required>
              <option value="">Sélectionner</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
            </select>
          </label>
          <label class="field">
            <span>Mois</span>
            <input class="input" type="month" v-model="form.mois" required />
          </label>
          <button class="btn" type="submit">Générer</button>
          <p class="muted" v-if="message">{{ message }}</p>
        </form>
      </div>

      <div class="card glass lg:col-span-2" v-if="paie">
        <div class="card-header">
          <div>
            <p class="eyebrow">Résultat</p>
            <h3>{{ paie.employe?.nom || 'Bulletin' }} — {{ paie.mois }}</h3>
          </div>
          <button class="btn" @click="downloadPdf" :disabled="downloading">
            {{ downloading ? 'Téléchargement...' : 'Télécharger le PDF' }}
          </button>
        </div>
        <div class="stats-grid">
          <div class="stat">
            <p class="label">Salaire brut</p>
            <p class="value">{{ paie.total_brut }}</p>
          </div>
          <div class="stat">
            <p class="label">Net à payer</p>
            <p class="value text-green-500">{{ paie.net_a_payer }}</p>
          </div>
          <div class="stat">
            <p class="label">Heures supp.</p>
            <p class="value">{{ paie.heures_supplementaires }} h</p>
            <p class="muted small">Montant: {{ paie.montant_hs }}</p>
          </div>
          <div class="stat">
            <p class="label">Retenues totales</p>
            <p class="value">{{ paie.total_retenues }}</p>
          </div>
          <div class="stat">
            <p class="label">CNAPS</p>
            <p class="value">{{ paie.retenue_cnaps }}</p>
          </div>
          <div class="stat">
            <p class="label">OSTIE</p>
            <p class="value">{{ paie.retenue_ostie }}</p>
          </div>
          <div class="stat">
            <p class="label">IRSA</p>
            <p class="value">{{ paie.retenue_irsa }}</p>
          </div>
        </div>
      </div>

      <div class="card glass lg:col-span-2 empty" v-else>
        <p class="muted">Sélectionnez un employé et un mois puis cliquez sur “Générer”.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const employes = ref([])
const paie = ref(null)
const message = ref('')
const downloading = ref(false)

const form = ref({
  employe_id: '',
  mois: ''
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: 1 } })
  employes.value = data.data || data || []
}

const generer = async () => {
  try {
    const { data } = await api.post('/v1/paies/generer', form.value)
    paie.value = data.paie
    message.value = data.message
  } catch (e) {
    message.value = 'Erreur lors du calcul'
  }
}

const downloadPdf = async () => {
  if (!paie.value?.id) return
  try {
    downloading.value = true
    const { data } = await api.get(`/v1/paies/${paie.value.id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bulletin_paie_${paie.value.employe_id}_${paie.value.mois}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    message.value = 'Erreur lors du téléchargement'
  } finally {
    downloading.value = false
  }
}

onMounted(fetchEmployes)
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 16px; }
.hero {
  padding: 18px 20px;
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: #ffffff;
  color: #0f172a;
}
.hero h1 { margin: 6px 0; font-size: 26px; }
.subtitle { margin: 0; color: #475569; }
.eyebrow { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #2563eb; margin: 0; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.pill { padding: 6px 10px; border-radius: 999px; background: rgba(148, 163, 184, 0.15); color: #0f172a; font-size: 12px; }
.pill-blue { background: rgba(59, 130, 246, 0.12); color: #1d4ed8; }
.pill-green { background: rgba(16, 185, 129, 0.12); color: #15803d; }

.card.glass {
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: #ffffff;
}
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.form { display: flex; flex-direction: column; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 4px; font-size: 14px; color: #334155; }
.input { border: 1px solid rgba(148, 163, 184, 0.6); border-radius: 10px; padding: 10px; background: #fff; color: #0f172a; }
.input:focus { outline: 2px solid rgba(59,130,246,0.4); }
.btn { padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.4); background: #22c55e; color: #0b172a; cursor: pointer; }
.btn:hover { filter: brightness(1.05); }
.btn-ghost { border: 1px solid rgba(148, 163, 184, 0.6); background: #f8fafc; color: #0f172a; border-radius: 10px; padding: 8px 10px; cursor: pointer; }
.muted { color: #64748b; font-size: 13px; }
.empty { display: flex; align-items: center; justify-content: center; min-height: 140px; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
.stat { padding: 12px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.3); background: #f8fafc; }
.label { color: #475569; font-size: 13px; margin: 0; }
.value { margin: 2px 0 0; font-size: 18px; font-weight: 700; color: #0f172a; }
.value.text-green-500 { color: #16a34a; }

:deep(body[data-theme='dark']) .hero {
  background: #0b1120;
  color: #e2e8f0;
}
:deep(body[data-theme='dark']) .subtitle { color: #cbd5e1; }
:deep(body[data-theme='dark']) .pill { color: #e2e8f0; background: rgba(148,163,184,0.25); }
:deep(body[data-theme='dark']) .card.glass { background: rgba(15, 23, 42, 0.8); border-color: rgba(148,163,184,0.25); }
:deep(body[data-theme='dark']) .input { background: rgba(15,23,42,0.6); color: #e2e8f0; border-color: rgba(148,163,184,0.3); }
:deep(body[data-theme='dark']) .field { color: #cbd5e1; }
:deep(body[data-theme='dark']) .muted { color: #94a3b8; }
:deep(body[data-theme='dark']) .stat { background: rgba(255,255,255,0.04); border-color: rgba(148,163,184,0.25); }
:deep(body[data-theme='dark']) .value { color: #e2e8f0; }

@media (max-width: 1024px) {
  .grid { grid-template-columns: 1fr !important; }
}
</style>
