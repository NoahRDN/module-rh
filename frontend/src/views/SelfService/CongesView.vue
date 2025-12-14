<template>
  <div class="self-service-conges">
    <div class="page-header">
      <h1>🏖️ Mes Congés</h1>
      <button class="btn btn-primary" @click="showNewDemande = true">
        + Nouvelle demande
      </button>
    </div>

    <!-- Solde de congés -->
    <div class="solde-section" v-if="soldeConges">
      <div class="card solde-card">
        <div class="solde-content">
          <span class="solde-value">{{ soldeConges.solde_jours || 0 }}</span>
          <span class="solde-label">Jours restants</span>
        </div>
        <div class="solde-details">
          <div class="detail-item">
            <span class="label">Acquis</span>
            <span class="value">{{ soldeConges.acquis || 0 }} jours</span>
          </div>
          <div class="detail-item">
            <span class="label">Pris</span>
            <span class="value">{{ soldeConges.pris || 0 }} jours</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Historique des demandes -->
    <div class="card">
      <div class="section-header">
        <h3>Historique des demandes</h3>
        <div class="filters">
          <select v-model="filtreStatut" @change="loadData">
            <option value="">Tous les statuts</option>
            <option value="en_attente">En attente</option>
            <option value="manager_valide">Validé manager</option>
            <option value="rh_valide">Validé RH</option>
            <option value="rejete">Rejeté</option>
          </select>
        </div>
      </div>

      <table class="table" v-if="demandes.length">
        <thead>
          <tr>
            <th>Type</th>
            <th>Date début</th>
            <th>Date fin</th>
            <th>Jours</th>
            <th>Statut</th>
            <th>Date demande</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in demandes" :key="d.id">
            <td>{{ d.type_conge?.libelle || d.typeConge?.libelle || 'Congé' }}</td>
            <td>{{ formatDate(d.date_debut) }}</td>
            <td>{{ formatDate(d.date_fin) }}</td>
            <td>{{ d.jours_demandes }}</td>
            <td>
              <span class="statut-badge" :class="'statut-' + d.statut">
                {{ getStatutLabel(d.statut) }}
              </span>
            </td>
            <td>{{ formatDate(d.created_at) }}</td>
          </tr>
        </tbody>
      </table>
      <div class="empty-state" v-else>
        <p>Aucune demande de congé</p>
      </div>
    </div>

    <!-- Modal nouvelle demande -->
    <div v-if="showNewDemande" class="modal-overlay" @click.self="showNewDemande = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Nouvelle demande de congé</h2>
          <button class="btn-close" @click="showNewDemande = false">×</button>
        </div>
        <form @submit.prevent="submitDemande" class="modal-body">
          <div class="form-group">
            <label>Type de congé *</label>
            <select v-model="form.type_conge_id" required>
              <option value="">Sélectionner...</option>
              <option v-for="type in typesConges" :key="type.id" :value="type.id">
                {{ type.libelle }}
              </option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Date de début *</label>
              <input type="date" v-model="form.date_debut" required />
            </div>
            <div class="form-group">
              <label>Date de fin *</label>
              <input type="date" v-model="form.date_fin" required />
            </div>
          </div>
          <div class="form-group">
            <label>Motif</label>
            <textarea v-model="form.motif" rows="3" placeholder="Motif de la demande..."></textarea>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showNewDemande = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? 'Envoi...' : 'Soumettre' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import selfServiceService from '../../services/selfServiceService'
import api from '../../services/api'

const loading = ref(false)
const showNewDemande = ref(false)
const demandes = ref([])
const soldeConges = ref(null)
const typesConges = ref([])
const filtreStatut = ref('')

const form = ref({
  type_conge_id: '',
  date_debut: '',
  date_fin: '',
  motif: ''
})

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR')
}

const getStatutLabel = (statut) => {
  const labels = {
    'en_attente': 'En attente',
    'manager_valide': 'Validé manager',
    'rh_valide': 'Validé RH',
    'rejete': 'Rejeté',
    'annule': 'Annulé'
  }
  return labels[statut] || statut
}

const loadData = async () => {
  loading.value = true
  try {
    const [demandesRes, soldeRes, typesRes] = await Promise.all([
      selfServiceService.getDemandesConges({ statut: filtreStatut.value }),
      selfServiceService.getSoldeConges(),
      api.get('/v1/types-conges')
    ])
    demandes.value = demandesRes.data.data || demandesRes.data || []
    soldeConges.value = soldeRes.data[0] || soldeRes.data || null
    typesConges.value = typesRes.data.data || typesRes.data || []
  } catch (error) {
    console.error('Erreur chargement:', error)
  } finally {
    loading.value = false
  }
}

const submitDemande = async () => {
  loading.value = true
  try {
    await selfServiceService.creerDemandeConge(form.value)
    showNewDemande.value = false
    form.value = { type_conge_id: '', date_debut: '', date_fin: '', motif: '' }
    await loadData()
    alert('Demande soumise avec succès!')
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.self-service-conges {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}

.page-header h1 {
  margin: 0;
}

.solde-section {
  margin-bottom: 25px;
}

.solde-card {
  padding: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.solde-content {
  display: flex;
  flex-direction: column;
}

.solde-value {
  font-size: 3rem;
  font-weight: bold;
  color: #11998e;
}

.solde-label {
  color: #666;
}

.solde-details {
  display: flex;
  gap: 30px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.detail-item .label {
  color: #888;
  font-size: 0.85rem;
}

.detail-item .value {
  font-weight: 600;
  font-size: 1.2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
}

.section-header h3 {
  margin: 0;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th, .table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table th {
  background: #f8f9fa;
  font-weight: 600;
}

.statut-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.statut-en_attente { background: #fff3e0; color: #f57c00; }
.statut-manager_valide { background: #e3f2fd; color: #1976d2; }
.statut-rh_valide { background: #e8f5e9; color: #388e3c; }
.statut-rejete { background: #ffebee; color: #c62828; }
.statut-annule { background: #f5f5f5; color: #999; }

.empty-state {
  padding: 40px;
  text-align: center;
  color: #999;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.2rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-secondary {
  background: #e8e8e8;
  color: #333;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
</style>
