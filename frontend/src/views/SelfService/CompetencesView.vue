<template>
  <div class="self-competences">
    <div class="page-header">
      <div>
        <h1>🧠 Mes Compétences</h1>
        <p class="subtitle">Niveaux évalués et catégories associées</p>
      </div>
      <input class="search" v-model="search" placeholder="Rechercher une compétence..." />
    </div>

    <div v-if="loading" class="card empty">Chargement...</div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Compétence</th>
            <th>Catégorie</th>
            <th>Niveau</th>
            <th>Dernière évaluation</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="comp in filtered" :key="comp.id">
            <td>{{ comp.nom }}</td>
            <td>{{ comp.categorie || '—' }}</td>
            <td>
              <span class="badge niveau">Niv {{ comp.niveau ?? '—' }}/5</span>
            </td>
            <td>{{ formatDate(comp.date_evaluation) }}</td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="4" class="empty">Aucune compétence trouvée</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import selfService from '../../services/selfServiceService'

const loading = ref(false)
const competences = ref([])
const search = ref('')

const load = async () => {
  loading.value = true
  try {
    const res = await selfService.getCompetences()
    competences.value = res.data?.data || res.data || []
  } catch (error) {
    console.error('Erreur chargement competences:', error)
  } finally {
    loading.value = false
  }
}

onMounted(load)

const filtered = computed(() => {
  const s = search.value.toLowerCase()
  return competences.value.filter(c =>
    !s ||
    (c.nom || '').toLowerCase().includes(s) ||
    (c.categorie || '').toLowerCase().includes(s)
  )
})

const formatDate = (d) => {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR')
}
</script>

<style scoped>
.self-competences {
  padding: 20px;
  max-width: 1100px;
  margin: 0 auto;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}
.subtitle { color: #64748b; margin: 4px 0 0; }
.search {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  min-width: 240px;
}
.card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th, .table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.table th { color: #475569; }
.badge.niveau {
  background: #e0f2fe;
  color: #0369a1;
  padding: 4px 8px;
  border-radius: 8px;
  font-size: 12px;
}
.empty { text-align: center; color: #94a3b8; padding: 12px; }
</style>
