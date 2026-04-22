<template>
  <div class="rh-page paie-generation-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Payroll engine</p>
        <h1>Génération de la paie</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Lancez le calcul d’un bulletin mensuel avec une lecture claire du brut, du net, des heures
          supplémentaires et des retenues sociales.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Bulletins PDF</span>
          <span class="pill">CNAPS / OSTIE / IRSA</span>
          <span class="pill">Calcul mensuel</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" type="button" @click="fetchEmployes">
              <AppIcon name="refresh" :size="18" />
              <span>Actualiser employés</span>
            </button>
            <button class="btn" type="button" @click="generer" :disabled="generating">
              <AppIcon name="save" :size="18" />
              <span>{{ generating ? 'Calcul en cours...' : 'Générer' }}</span>
            </button>
          </div>

          <div class="rh-hero-meta-list hero-meta-list">
            <p class="rh-hero-meta hero-meta">
              Employés disponibles:
              <strong>{{ formatInteger(employes.length) }}</strong>
            </p>
            <p class="rh-hero-meta hero-meta">
              Bulletin actif:
              <strong>{{ paie ? 'Oui' : 'Non' }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <div v-if="message" class="rh-status-banner" :class="paie ? 'success' : 'danger'">
      <span class="rh-status-dot"></span>
      <span>{{ message }}</span>
    </div>

    <section class="rh-metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="rh-metric-card">
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
            <p class="rh-section-kicker">Payroll input</p>
            <h2>Employé et période</h2>
          </div>
        </div>

        <p class="rh-section-copy">
          Sélectionnez le collaborateur et le mois à traiter, puis lancez le calcul du bulletin.
        </p>

        <form class="form-grid" @submit.prevent="generer">
          <label class="rh-field-card">
            <span class="rh-field-label">Employé</span>
            <select class="select" v-model="form.employe_id" required>
              <option value="">Sélectionner</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.matricule }} - {{ emp.nom }}
              </option>
            </select>
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Mois</span>
            <input class="input" type="month" v-model="form.mois" required />
          </label>

          <div class="rh-action-row submit-row">
            <button class="btn" type="submit" :disabled="generating">
              <AppIcon name="wallet" :size="18" />
              <span>{{ generating ? 'Calcul en cours...' : 'Lancer le calcul' }}</span>
            </button>
          </div>
        </form>
      </article>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Overview</p>
            <h2>Résumé de calcul</h2>
          </div>
        </div>

        <p class="rh-summary-intro">
          Contrôlez rapidement l’état de la génération, le collaborateur ciblé et le mois traité.
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
            <li>La génération doit être relancée après tout changement de collaborateur ou de mois.</li>
            <li>Le PDF n’est disponible qu’après calcul réussi d’un bulletin.</li>
            <li>Le panneau de droite résume l’état courant sans surcharger le formulaire.</li>
          </ul>
        </div>
      </aside>
    </section>

    <article class="card rh-section-card" v-if="paie">
      <div class="rh-section-heading">
        <div>
          <p class="rh-section-kicker">Payroll result</p>
          <h2>{{ paie.employe?.nom || 'Bulletin généré' }} - {{ paie.mois }}</h2>
        </div>
        <button class="btn btn-secondary" type="button" @click="downloadPdf" :disabled="downloading">
          <AppIcon name="download" :size="18" />
          <span>{{ downloading ? 'Téléchargement...' : 'Télécharger PDF' }}</span>
        </button>
      </div>

      <div class="stats-grid">
        <article class="stat-box">
          <p class="stat-label">Salaire brut</p>
          <p class="stat-value">{{ paie.total_brut }}</p>
        </article>
        <article class="stat-box accent">
          <p class="stat-label">Net à payer</p>
          <p class="stat-value">{{ paie.net_a_payer }}</p>
        </article>
        <article class="stat-box">
          <p class="stat-label">Heures supp.</p>
          <p class="stat-value">{{ paie.heures_supplementaires }} h</p>
          <p class="stat-copy">Montant: {{ paie.montant_hs }}</p>
        </article>
        <article class="stat-box">
          <p class="stat-label">Retenues totales</p>
          <p class="stat-value">{{ paie.total_retenues }}</p>
        </article>
      </div>

      <div class="rh-table-shell">
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

    <article class="card rh-section-card rh-empty-state" v-else>
      <p>Aucun bulletin calculé</p>
      <span>Lancez une génération pour afficher le détail de paie et activer l’export PDF.</span>
    </article>
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
const generating = ref(false)

const form = ref({
  employe_id: '',
  mois: '',
})

const selectedEmploye = computed(
  () => employes.value.find((item) => String(item.id) === String(form.value.employe_id)) || null,
)

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

const metrics = computed(() => {
  const hasPaie = Boolean(paie.value)
  return [
    {
      tag: 'Coverage',
      label: 'Employés chargés',
      value: formatInteger(employes.value.length),
      caption: 'Liste disponible pour la sélection',
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

const overviewCards = computed(() => [
  {
    label: 'Employé ciblé',
    value: selectedEmploye.value?.matricule || 'Aucun',
    copy: selectedEmploye.value
      ? `${selectedEmploye.value.nom || ''} ${selectedEmploye.value.prenom || ''}`.trim()
      : 'Aucun collaborateur sélectionné.',
    tag: 'Person',
  },
  {
    label: 'Mois traité',
    value: form.value.mois || '—',
    copy: form.value.mois ? 'Période actuellement préparée pour le calcul.' : 'Choisissez un mois de paie.',
    tag: 'Period',
  },
  {
    label: 'État',
    value: paie.value ? 'Calculé' : 'En attente',
    copy: paie.value ? 'Un bulletin est disponible à l’écran.' : 'Aucun bulletin encore généré.',
    tag: 'State',
  },
  {
    label: 'Export PDF',
    value: paie.value ? 'Disponible' : 'Bloqué',
    copy: paie.value ? 'Le téléchargement est activé.' : 'Le PDF nécessite un calcul valide.',
    tag: 'PDF',
  },
])

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: 1 } })
  employes.value = data.data || data || []
}

const generer = async () => {
  try {
    generating.value = true
    const { data } = await api.post('/v1/paies/generer', form.value)
    paie.value = data.paie
    message.value = data.message
  } catch (e) {
    paie.value = null
    message.value = 'Erreur lors du calcul'
  } finally {
    generating.value = false
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
.form-grid {
  display: grid;
  gap: 14px;
}

.submit-row {
  justify-content: flex-start;
}

.stats-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.stat-box {
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .stat-box {
  background: rgba(15, 23, 42, 0.72);
}

.stat-box.accent {
  border-color: rgba(18, 183, 106, 0.18);
  background: rgba(18, 183, 106, 0.08);
}

.stat-label,
.stat-value,
.stat-copy {
  margin: 0;
}

.stat-label {
  color: var(--muted);
  font-size: 0.84rem;
  font-weight: 700;
}

.stat-value {
  margin-top: 6px;
  font-size: 1.28rem;
  font-weight: 800;
}

.stat-copy {
  margin-top: 6px;
  color: var(--muted);
  font-size: 0.82rem;
}

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 680px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
