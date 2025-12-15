<template>
  <div class="formations-self">
    <div class="page-header">
      <div class="page-title">
        <h1>Formations disponibles</h1>
        <span>Lecture seule. Pour y participer, demande un congé de formation.</span>
      </div>
      <RouterLink class="btn btn-primary" :to="{ name: 'self-service-conges' }">
        📄 Demander un congé formation
      </RouterLink>
    </div>

    <div class="card mb-3">
      <div class="filters">
        <input class="input" v-model="search" placeholder="Rechercher une formation..." />
        <select class="select" v-model="filtreType">
          <option value="">Tous les types</option>
          <option value="interne">Interne</option>
          <option value="externe">Externe</option>
          <option value="en_ligne">En ligne</option>
          <option value="certification">Certification</option>
        </select>
        <select class="select" v-model="filtreNiveau">
          <option value="">Tous niveaux</option>
          <option value="debutant">Débutant</option>
          <option value="intermediaire">Intermédiaire</option>
          <option value="avance">Avancé</option>
          <option value="expert">Expert</option>
        </select>
      </div>
      <p class="hint">
        Les RH inscriront les collaborateurs après validation du congé formation.
      </p>
    </div>

    <div class="grid cards">
      <div v-for="f in formationsFiltrees" :key="f.id" class="card formation-card">
        <div class="header">
          <span class="badge type" :class="f.type">{{ typeLabel(f.type) }}</span>
          <span class="badge niveau">{{ niveauLabel(f.niveau) }}</span>
        </div>
        <h3 class="title">{{ f.titre }}</h3>
        <p class="description">{{ f.description || 'Pas de description' }}</p>
        <div class="meta">
          <span>⏱️ {{ f.duree_heures || 0 }}h</span>
          <span v-if="f.formateur">👤 {{ f.formateur }}</span>
          <span v-if="f.organisme">🏢 {{ f.organisme }}</span>
        </div>
      </div>
      <div v-if="!formationsFiltrees.length" class="empty">Aucune formation trouvée</div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import selfService from '../../services/selfServiceService'

const formations = ref([])
const search = ref('')
const filtreType = ref('')
const filtreNiveau = ref('')

const load = async () => {
  const { data } = await selfService.getFormations()
  formations.value = data.data || data || []
}

onMounted(load)

const typeLabel = (t) => ({
  interne: 'Interne',
  externe: 'Externe',
  en_ligne: 'En ligne',
  certification: 'Certification'
}[t] || t || 'Type')

const niveauLabel = (n) => ({
  debutant: 'Débutant',
  intermediaire: 'Intermédiaire',
  avance: 'Avancé',
  expert: 'Expert'
}[n] || n || 'Niveau')

const formationsFiltrees = computed(() => {
  const s = search.value.toLowerCase()
  return formations.value.filter((f) =>
    (!filtreType.value || f.type === filtreType.value) &&
    (!filtreNiveau.value || f.niveau === filtreNiveau.value) &&
    (!s || (f.titre || '').toLowerCase().includes(s) || (f.description || '').toLowerCase().includes(s))
  )
})
</script>

<style scoped>
.formations-self { display: flex; flex-direction: column; gap: 12px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.page-title h1 { margin: 0; }
.page-title span { color: #64748b; }
.filters { display: flex; flex-wrap: wrap; gap: 8px; }
.input, .select {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 12px;
}
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; background: #fff; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; }
.formation-card { display: flex; flex-direction: column; gap: 8px; }
.header { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.badge { padding: 4px 8px; border-radius: 8px; font-size: 12px; }
.badge.type { background: #eff6ff; color: #1d4ed8; }
.badge.niveau { background: #f5f3ff; color: #6d28d9; }
.title { margin: 0; font-size: 16px; }
.description { margin: 0; color: #475569; min-height: 44px; }
.meta { display: flex; flex-wrap: wrap; gap: 8px; color: #475569; font-size: 13px; }
.empty { text-align: center; padding: 24px; color: #94a3b8; grid-column: 1 / -1; }
.btn-primary { background: linear-gradient(135deg, #0ea5e9, #2563eb); color: #fff; border: none; padding: 9px 12px; border-radius: 10px; cursor: pointer; }
</style>
