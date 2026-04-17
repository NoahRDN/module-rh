<template>
  <div class="paie-generation-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll engine</p>
        <h1>Génération de la paie</h1>
        <p class="hero-subtitle">
          Lancez le calcul d'un bulletin mensuel avec ventilation instantanée du brut, du net,
          des heures supplémentaires et des retenues sociales.
        </p>

        <div class="hero-pills">
          <span class="pill">Bulletins PDF</span>
          <span class="pill">IRSA / CNAPS / OSTIE</span>
          <span class="pill">Calcul mensuel</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="action-row">
          <button class="btn btn-secondary" type="button" @click="fetchEmployes">
            <AppIcon name="refresh" :size="18" />
            <span>Actualiser employés</span>
          </button>
          <button class="btn" type="button" @click="generer">
            <AppIcon name="plus" :size="18" />
            <span>Générer maintenant</span>
          </button>
        </div>

        <p class="hero-meta">
          Employés disponibles:
          <strong>{{ formatInteger(employes.length) }}</strong>
        </p>
      </div>
    </section>

    <p v-if="message" class="banner" :class="{ success: paie, danger: !paie }">{{ message }}</p>

    <section class="metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <section class="content-grid">
      <article class="card section-card controls-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Payroll input</p>
            <h2>Employé et période</h2>
          </div>
        </div>

        <p class="section-copy">
          Sélectionnez un collaborateur et le mois de traitement pour générer la fiche de paie.
        </p>

        <form class="form-grid" @submit.prevent="generer">
          <label class="field-card">
            <span class="field-label">Employé</span>
            <select class="input" v-model="form.employe_id" required>
              <option value="">Sélectionner</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Mois</span>
            <input class="input" type="month" v-model="form.mois" required />
          </label>

          <div class="action-row">
            <button class="btn" type="submit">
              <AppIcon name="save" :size="18" />
              <span>Lancer le calcul</span>
            </button>
          </div>
        </form>
      </article>

      <article class="card section-card table-card" v-if="paie">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Payroll result</p>
            <h2>{{ paie.employe?.nom || 'Bulletin généré' }} - {{ paie.mois }}</h2>
          </div>
          <button class="btn btn-secondary" type="button" @click="downloadPdf" :disabled="downloading">
            <AppIcon name="download" :size="18" />
            <span>{{ downloading ? 'Téléchargement...' : 'Télécharger PDF' }}</span>
          </button>
        </div>

        <div class="stats-grid">
          <div class="stat-box">
            <p class="stat-label">Salaire brut</p>
            <p class="stat-value">{{ paie.total_brut }}</p>
          </div>
          <div class="stat-box accent">
            <p class="stat-label">Net à payer</p>
            <p class="stat-value">{{ paie.net_a_payer }}</p>
          </div>
          <div class="stat-box">
            <p class="stat-label">Heures supp.</p>
            <p class="stat-value">{{ paie.heures_supplementaires }} h</p>
            <p class="stat-copy">Montant: {{ paie.montant_hs }}</p>
          </div>
          <div class="stat-box">
            <p class="stat-label">Retenues totales</p>
            <p class="stat-value">{{ paie.total_retenues }}</p>
          </div>
        </div>

        <div class="table-shell compact">
          <table class="table detail-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Montant</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>CNAPS</td>
                <td>{{ paie.retenue_cnaps }}</td>
              </tr>
              <tr>
                <td>OSTIE</td>
                <td>{{ paie.retenue_ostie }}</td>
              </tr>
              <tr>
                <td>IRSA</td>
                <td>{{ paie.retenue_irsa }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="card section-card empty-state" v-else>
        <p class="empty-title">Aucun bulletin calculé</p>
        <p class="empty-copy">Lancez une génération pour afficher le détail de paie et activer l'export PDF.</p>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const employes = ref([])
const paie = ref(null)
const message = ref('')
const downloading = ref(false)

const form = ref({
  employe_id: '',
  mois: ''
})

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

const metrics = computed(() => {
  const hasPaie = Boolean(paie.value)
  return [
    {
      tag: 'Coverage',
      label: 'Employés chargés',
      value: formatInteger(employes.value.length),
      caption: 'Liste utilisée pour la sélection',
    },
    {
      tag: 'Gross',
      label: 'Salaire brut',
      value: hasPaie ? paie.value.total_brut : '—',
      caption: hasPaie ? 'Dernier bulletin calculé' : 'Disponible après génération',
    },
    {
      tag: 'Net',
      label: 'Net à payer',
      value: hasPaie ? paie.value.net_a_payer : '—',
      caption: hasPaie ? 'Valeur finale collaborateur' : 'En attente de calcul',
    },
    {
      tag: 'Deductions',
      label: 'Retenues',
      value: hasPaie ? paie.value.total_retenues : '—',
      caption: hasPaie ? 'Somme des déductions sociales' : 'IRSA, CNAPS et OSTIE',
    },
  ]
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
.paie-generation-page {
  display: grid;
  gap: 20px;
}

.hero {
  display: grid;
  grid-template-columns: 1.2fr minmax(280px, 0.8fr);
  gap: 18px;
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 22px;
  background: linear-gradient(140deg, color-mix(in srgb, var(--brand-primary) 6%, var(--panel) 94%), var(--panel));
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
  font-size: clamp(1.5rem, 2.6vw, 2.05rem);
  line-height: 1.15;
}

.hero-subtitle {
  margin: 0;
  color: var(--muted);
  max-width: 62ch;
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
  background: color-mix(in srgb, var(--brand-primary) 12%, transparent);
}

.hero-actions {
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 14px;
  background: color-mix(in srgb, var(--panel-soft) 80%, transparent);
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
  color: var(--muted);
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
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--panel);
  color: var(--text);
  border-color: var(--border);
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

.content-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: 360px minmax(0, 1fr);
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
  margin-bottom: 8px;
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
  font-size: 1.15rem;
}

.section-copy {
  margin: 0 0 14px;
  color: var(--muted);
  font-size: 13px;
}

.form-grid {
  display: grid;
  gap: 12px;
}

.field-card {
  display: grid;
  gap: 6px;
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
  background: var(--panel);
  color: var(--text);
  width: 100%;
}

.input:focus {
  outline: 2px solid color-mix(in srgb, var(--brand-primary) 35%, transparent);
  outline-offset: 1px;
}

.stats-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

.stat-box {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px;
  background: color-mix(in srgb, var(--panel-soft) 65%, transparent);
}

.stat-box.accent {
  background: color-mix(in srgb, #22c55e 10%, var(--panel));
  border-color: color-mix(in srgb, #16a34a 35%, var(--border));
}

.stat-label {
  margin: 0;
  font-size: 12px;
  color: var(--muted);
}

.stat-value {
  margin: 4px 0 0;
  font-size: 1.1rem;
  font-weight: 700;
}

.stat-copy {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--muted);
}

.table-shell {
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: auto;
  margin-top: 12px;
}

.table-shell.compact {
  margin-top: 14px;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
}

.detail-table th,
.detail-table td {
  border-bottom: 1px solid var(--border);
  padding: 10px 12px;
  text-align: left;
}

.detail-table tbody tr:last-child td {
  border-bottom: none;
}

.empty-state {
  display: grid;
  place-items: center;
  min-height: 260px;
  text-align: center;
}

.empty-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.empty-copy {
  margin: 8px 0 0;
  color: var(--muted);
  max-width: 46ch;
}

@media (max-width: 1120px) {
  .hero {
    grid-template-columns: 1fr;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .paie-generation-page {
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
