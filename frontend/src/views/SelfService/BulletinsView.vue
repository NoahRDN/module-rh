<template>
  <div class="self-service-bulletins">
    <div class="page-header">
      <h1>📄 Mes Bulletins de Paie</h1>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Année</label>
          <select v-model="annee" @change="loadData">
            <option v-for="y in annees" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Liste des bulletins -->
    <div class="bulletins-grid" v-if="bulletins.length">
      <div v-for="bulletin in bulletins" :key="bulletin.id" class="card bulletin-card">
        <div class="bulletin-header">
          <span class="periode">{{ formatPeriode(bulletin.periode) }}</span>
          <span class="date">{{ formatDate(bulletin.created_at) }}</span>
        </div>
        <div class="bulletin-body">
          <div class="montant-row">
            <span class="label">Salaire brut</span>
            <span class="value">{{ formatMontant(bulletin.salaire_brut) }}</span>
          </div>
          <div class="montant-row">
            <span class="label">Cotisations</span>
            <span class="value deduction">-{{ formatMontant(bulletin.total_cotisations) }}</span>
          </div>
          <div class="montant-row total">
            <span class="label">Net à payer</span>
            <span class="value">{{ formatMontant(bulletin.salaire_net) }}</span>
          </div>
        </div>
        <div class="bulletin-actions">
          <button class="btn btn-secondary btn-sm" @click="telechargerPdf(bulletin.id)">
            📥 Télécharger PDF
          </button>
        </div>
      </div>
    </div>

    <div class="card empty-state" v-else>
      <p>Aucun bulletin de paie disponible pour cette année</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import selfServiceService from '../../services/selfServiceService'
import api from '../../services/api'
import { formatMoneyAmount } from '../../utils/formatters'

const loading = ref(false)
const bulletins = ref([])
const annee = ref(new Date().getFullYear())

const annees = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => current - i)
})

const formatPeriode = (periode) => {
  if (!periode) return '—'
  const [year, month] = periode.split('-')
  const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
  return `${months[parseInt(month) - 1]} ${year}`
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatMontant = (montant) => {
  return formatMoneyAmount(montant)
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await selfServiceService.getBulletins({ annee: annee.value })
    bulletins.value = res.data?.data || res.data || []
  } catch (error) {
    console.error('Erreur chargement bulletins:', error)
  } finally {
    loading.value = false
  }
}

const telechargerPdf = async (id) => {
  try {
    const response = await api.get(`/v1/self-service/bulletins/${id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bulletin_${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    alert('Erreur téléchargement: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(loadData)
</script>

<style scoped>
.self-service-bulletins {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 25px;
}

.page-header h1 {
  margin: 0;
}

.filters-card {
  padding: 15px 20px;
  margin-bottom: 20px;
}

.filters {
  display: flex;
  gap: 20px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 0.85rem;
  color: #666;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.bulletins-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.bulletin-card {
  padding: 20px;
}

.bulletin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.periode {
  font-weight: 600;
  font-size: 1.1rem;
}

.date {
  color: #888;
  font-size: 0.85rem;
}

.bulletin-body {
  margin-bottom: 20px;
}

.montant-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.montant-row .label {
  color: #666;
}

.montant-row .value {
  font-weight: 500;
}

.montant-row .deduction {
  color: #e74c3c;
}

.montant-row.total {
  border-top: 2px solid #eee;
  padding-top: 12px;
  margin-top: 8px;
}

.montant-row.total .label,
.montant-row.total .value {
  font-weight: 700;
  font-size: 1.1rem;
  color: #11998e;
}

.bulletin-actions {
  display: flex;
  justify-content: flex-end;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-secondary {
  background: #f0f0f0;
  color: #333;
}

.btn-secondary:hover {
  background: #e0e0e0;
}

.empty-state {
  padding: 60px;
  text-align: center;
  color: #999;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
</style>
