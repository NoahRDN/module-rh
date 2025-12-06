<template>
  <div class="grid cols-3">
    <div class="card stat" v-for="stat in stats" :key="stat.label">
      <p class="muted">{{ stat.label }}</p>
      <div class="stat-main">
        <h2>{{ stat.value }}</h2>
        <span class="chip" v-if="stat.meta">{{ stat.meta }}</span>
      </div>
    </div>
  </div>

  <hr class="divider" />

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Derniers employés</h1>
          <span>Les 5 derniers profils créés</span>
        </div>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>Matricule</th>
            <th>Nom</th>
            <th>Poste</th>
            <th>Département</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in derniersEmployes" :key="emp.id">
            <td>{{ emp.matricule }}</td>
            <td>{{ emp.nom }} {{ emp.prenom }}</td>
            <td>{{ emp.poste?.nom || '—' }}</td>
            <td>{{ emp.departement?.nom || '—' }}</td>
          </tr>
          <tr v-if="!derniersEmployes.length">
            <td colspan="4" class="muted">Aucun employé encore.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="page-title">
        <h1>Raccourcis</h1>
        <span>Actions rapides</span>
      </div>
      <div class="grid" style="margin-top: 12px; gap: 10px;">
        <RouterLink to="/employes" class="btn btn-secondary">👤 Nouvel employé</RouterLink>
        <RouterLink to="/documents" class="btn btn-secondary">📁 Ajouter document</RouterLink>
        <RouterLink to="/contrats" class="btn btn-secondary">📄 Créer contrat</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const stats = ref([
  { label: 'Employés', value: '—', meta: '' },
  { label: 'Départements', value: '—', meta: '' },
  { label: 'Postes', value: '—', meta: '' }
])

const derniersEmployes = ref([])

const loadStats = async () => {
  try {
    const [emp, dep, postes] = await Promise.all([
      api.get('/v1/employes'),
      api.get('/v1/departements'),
      api.get('/v1/postes')
    ])
    stats.value = [
      { label: 'Employés', value: emp.data.total ?? emp.data.data?.length ?? 0, meta: 'Actifs' },
      { label: 'Départements', value: dep.data.total ?? dep.data.data?.length ?? 0, meta: '' },
      { label: 'Postes', value: postes.data.total ?? postes.data.data?.length ?? 0, meta: '' }
    ]
    derniersEmployes.value = (emp.data.data || []).slice(0, 5)
  } catch (e) {
    // keep defaults
  }
}

onMounted(loadStats)
</script>

<style scoped>
.stat h2 {
  margin: 4px 0 0;
  font-size: 28px;
}

.stat-main {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 6px;
}
</style>
