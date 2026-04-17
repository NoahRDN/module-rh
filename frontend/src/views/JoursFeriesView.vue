<template>
  <div class="feries-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Legal calendar</p>
        <h1>Jours fériés</h1>
        <p class="hero-subtitle">
          Maintenez la base des jours fériés pour fiabiliser les absences, la présence et les calculs
          de paie.
        </p>
      </div>

      <div class="hero-actions">
        <button class="btn btn-secondary" @click="fetchFeries" :disabled="loading">
          <AppIcon name="refresh" :size="18" />
          <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
        </button>
        <RouterLink class="btn" to="/jours-feries/nouveau">
          <AppIcon name="plus" :size="18" />
          <span>Ajouter</span>
        </RouterLink>
      </div>
    </section>

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Holiday list</p>
          <h2>Calendrier férié</h2>
        </div>
        <span class="section-chip">{{ formatInteger(feries.length) }} lignes</span>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Date</th>
              <th>Récurrent</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in feries" :key="f.id">
              <td>{{ f.nom }}</td>
              <td>{{ f.date }}</td>
              <td>{{ f.recurrent ? 'Oui' : 'Non' }}</td>
              <td>
                <button class="btn btn-danger btn-xs" @click="remove(f)">Supprimer</button>
              </td>
            </tr>
            <tr v-if="!feries.length">
              <td colspan="4" class="empty-state">
                <p>Aucun jour férié enregistré.</p>
                <span>Ajoutez une date pour compléter le calendrier.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const feries = ref([])
const loading = ref(false)

const fetchFeries = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/v1/jours-feries')
    feries.value = data.data || []
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
</script>

<style scoped>
.feries-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 24px;
}

.hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 18px;
  padding: 28px;
  border: 1px solid rgba(79, 70, 229, 0.14);
  border-radius: 30px;
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(255, 255, 255, 0)),
    rgba(255, 255, 255, 0.9);
  box-shadow: var(--shadow-lg);
}

body[data-theme='dark'] .hero {
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.18), rgba(15, 23, 42, 0)),
    rgba(15, 23, 42, 0.88);
}

.hero-kicker,
.section-kicker,
.empty-state span {
  margin: 0;
  color: var(--muted);
}

.hero-kicker,
.section-kicker {
  color: var(--brand-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.hero h1,
.section-heading h2 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-subtitle {
  margin: 12px 0 0;
  max-width: 700px;
  color: var(--muted);
  font-size: 1rem;
  line-height: 1.7;
}

.hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.section-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.section-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.section-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.76rem;
  font-weight: 700;
}

.table-shell {
  overflow: auto;
}

.empty-state {
  padding: 26px 14px;
  text-align: center;
}

.empty-state p {
  margin: 0;
  font-weight: 700;
}

@media (max-width: 680px) {
  .hero {
    padding: 22px;
  }

  .section-heading {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
