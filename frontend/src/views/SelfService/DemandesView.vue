<template>
  <div class="demandes-view">
    <div class="page-header">
      <h1>📋 Mes Demandes</h1>
      <button class="btn btn-primary" @click="showNewDemande = true">
        + Nouvelle demande
      </button>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Type</label>
          <select v-model="filtreType">
            <option value="">Tous</option>
            <option v-for="type in typesDemandes" :key="type.id" :value="type.id">
              {{ type.nom }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Statut</label>
          <select v-model="filtreStatut">
            <option value="">Tous</option>
            <option value="brouillon">Brouillon</option>
            <option value="soumise">Soumise</option>
            <option value="en_cours">En cours</option>
            <option value="approuvee">Approuvée</option>
            <option value="rejetee">Rejetée</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
      <div class="stat-item">
        <span class="stat-value">{{ demandes.length }}</span>
        <span class="stat-label">Total</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ demandesEnAttente }}</span>
        <span class="stat-label">En attente</span>
      </div>
      <div class="stat-item success">
        <span class="stat-value">{{ demandesApprouvees }}</span>
        <span class="stat-label">Approuvées</span>
      </div>
      <div class="stat-item danger">
        <span class="stat-value">{{ demandesRejetees }}</span>
        <span class="stat-label">Rejetées</span>
      </div>
    </div>

    <!-- Liste des demandes -->
    <div class="demandes-list">
      <div v-for="demande in demandesFiltrees" :key="demande.id" class="card demande-card">
        <div class="demande-header">
          <div class="demande-numero">
            <span class="numero">{{ demande.numero }}</span>
            <span class="type-badge">{{ demande.type?.nom }}</span>
          </div>
          <span class="statut-badge" :class="demande.statut">
            {{ getStatutLabel(demande.statut) }}
          </span>
        </div>

        <div class="demande-content">
          <p class="demande-objet">{{ demande.objet }}</p>
          <p class="demande-description" v-if="demande.description">{{ demande.description }}</p>
          
          <div class="demande-meta">
            <span class="meta-item">
              <span class="icon">📅</span>
              Créée le {{ formatDate(demande.created_at) }}
            </span>
            <span class="meta-item" v-if="demande.date_souhaitee">
              <span class="icon">⏰</span>
              Souhaitée pour le {{ formatDate(demande.date_souhaitee) }}
            </span>
          </div>

          <!-- Documents joints -->
          <div v-if="demande.documents?.length" class="documents-list">
            <span class="documents-label">📎 {{ demande.documents.length }} document(s)</span>
          </div>

          <!-- Réponse RH -->
          <div v-if="demande.reponse_rh" class="reponse-rh">
            <strong>Réponse RH:</strong> {{ demande.reponse_rh }}
          </div>
        </div>

        <div class="demande-actions">
          <button class="btn btn-sm btn-secondary" @click="viewDemande(demande)">
            Voir détails
          </button>
          <button 
            v-if="demande.statut === 'brouillon'" 
            class="btn btn-sm btn-primary"
            @click="soumettreDemande(demande)"
          >
            Soumettre
          </button>
          <button 
            v-if="['brouillon', 'soumise'].includes(demande.statut)" 
            class="btn btn-sm btn-danger"
            @click="annulerDemande(demande)"
          >
            Annuler
          </button>
        </div>
      </div>

      <div v-if="!demandesFiltrees.length" class="card empty-state">
        <p>Aucune demande trouvée</p>
        <button class="btn btn-primary" @click="showNewDemande = true">
          Créer ma première demande
        </button>
      </div>
    </div>

    <!-- Modal Nouvelle demande -->
    <div v-if="showNewDemande" class="modal-overlay" @click.self="showNewDemande = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Nouvelle demande</h2>
          <button class="btn btn-icon" @click="showNewDemande = false">✕</button>
        </div>
        <form @submit.prevent="creerDemande" class="modal-body">
          <div class="form-group">
            <label>Type de demande *</label>
            <select v-model="newDemandeForm.type_demande_id" required @change="onTypeChange">
              <option value="">Sélectionner...</option>
              <option v-for="type in typesDemandes" :key="type.id" :value="type.id">
                {{ type.nom }}
              </option>
            </select>
            <p class="type-description" v-if="selectedType?.description">
              {{ selectedType.description }}
            </p>
          </div>

          <div class="form-group">
            <label>Objet *</label>
            <input type="text" v-model="newDemandeForm.objet" required 
              placeholder="Résumé de votre demande" />
          </div>

          <div class="form-group">
            <label>Description détaillée</label>
            <textarea v-model="newDemandeForm.description" rows="4"
              placeholder="Décrivez votre demande en détail..."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date souhaitée</label>
              <input type="date" v-model="newDemandeForm.date_souhaitee" />
            </div>
            <div class="form-group">
              <label>Priorité</label>
              <select v-model="newDemandeForm.priorite">
                <option value="normale">Normale</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>
          </div>

          <!-- Champs spécifiques selon le type -->
          <div v-if="selectedType?.necessite_montant" class="form-group">
            <label>Montant (Ar)</label>
            <input type="number" v-model.number="newDemandeForm.montant" min="0" />
          </div>

          <div class="form-group">
            <label>Documents justificatifs</label>
            <input type="file" @change="handleFiles" multiple />
            <p class="help-text">PDF, images ou documents (max 5MB chacun)</p>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showNewDemande = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-outline" :disabled="saving" @click="saveAsBrouillon = true">
              Enregistrer brouillon
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving" @click="saveAsBrouillon = false">
              {{ saving ? 'Envoi...' : 'Soumettre' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Détails -->
    <div v-if="showDetails" class="modal-overlay" @click.self="showDetails = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Demande {{ selectedDemande?.numero }}</h2>
          <button class="btn btn-icon" @click="showDetails = false">✕</button>
        </div>
        <div class="modal-body" v-if="selectedDemande">
          <div class="detail-header">
            <span class="statut-badge large" :class="selectedDemande.statut">
              {{ getStatutLabel(selectedDemande.statut) }}
            </span>
            <span class="type-badge large">{{ selectedDemande.type?.nom }}</span>
          </div>

          <div class="detail-section">
            <h4>Objet</h4>
            <p>{{ selectedDemande.objet }}</p>
          </div>

          <div class="detail-section" v-if="selectedDemande.description">
            <h4>Description</h4>
            <p>{{ selectedDemande.description }}</p>
          </div>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="label">Date de création</span>
              <span class="value">{{ formatDate(selectedDemande.created_at) }}</span>
            </div>
            <div class="detail-item" v-if="selectedDemande.date_souhaitee">
              <span class="label">Date souhaitée</span>
              <span class="value">{{ formatDate(selectedDemande.date_souhaitee) }}</span>
            </div>
            <div class="detail-item" v-if="selectedDemande.montant">
              <span class="label">Montant</span>
              <span class="value">{{ formatMontant(selectedDemande.montant) }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Priorité</span>
              <span class="value">{{ selectedDemande.priorite || 'Normale' }}</span>
            </div>
          </div>

          <div class="detail-section" v-if="selectedDemande.documents?.length">
            <h4>Documents joints</h4>
            <div class="documents-grid">
              <a v-for="doc in selectedDemande.documents" :key="doc.id" 
                :href="doc.chemin" target="_blank" class="document-item">
                📄 {{ doc.nom_original }}
              </a>
            </div>
          </div>

          <div class="detail-section" v-if="selectedDemande.reponse_rh">
            <h4>Réponse RH</h4>
            <div class="reponse-box">
              <p>{{ selectedDemande.reponse_rh }}</p>
              <span class="reponse-date">
                Répondu le {{ formatDate(selectedDemande.date_traitement) }}
              </span>
            </div>
          </div>

          <!-- Timeline -->
          <div class="detail-section">
            <h4>Historique</h4>
            <div class="timeline">
              <div class="timeline-item">
                <span class="timeline-dot"></span>
                <span class="timeline-content">
                  <strong>Créée</strong>
                  <span>{{ formatDateTime(selectedDemande.created_at) }}</span>
                </span>
              </div>
              <div v-if="selectedDemande.date_soumission" class="timeline-item">
                <span class="timeline-dot submitted"></span>
                <span class="timeline-content">
                  <strong>Soumise</strong>
                  <span>{{ formatDateTime(selectedDemande.date_soumission) }}</span>
                </span>
              </div>
              <div v-if="selectedDemande.date_traitement" class="timeline-item">
                <span class="timeline-dot" :class="selectedDemande.statut"></span>
                <span class="timeline-content">
                  <strong>{{ getStatutLabel(selectedDemande.statut) }}</strong>
                  <span>{{ formatDateTime(selectedDemande.date_traitement) }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import selfServiceService from '../../services/selfServiceService'
import demandeRHService from '../../services/demandeRHService'
import { formatMoneyAmount } from '../../utils/formatters'

const loading = ref(false)
const saving = ref(false)
const demandes = ref([])
const typesDemandes = ref([])
const filtreType = ref('')
const filtreStatut = ref('')

const showNewDemande = ref(false)
const showDetails = ref(false)
const selectedDemande = ref(null)
const saveAsBrouillon = ref(false)
const files = ref([])

const newDemandeForm = ref({
  type_demande_id: '',
  objet: '',
  description: '',
  date_souhaitee: '',
  priorite: 'normale',
  montant: null
})

// Computed
const selectedType = computed(() => 
  typesDemandes.value.find(t => t.id === newDemandeForm.value.type_demande_id)
)

const demandesFiltrees = computed(() => {
  let result = demandes.value
  if (filtreType.value) {
    result = result.filter(d => d.type_demande_id === parseInt(filtreType.value))
  }
  if (filtreStatut.value) {
    result = result.filter(d => d.statut === filtreStatut.value)
  }
  return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const demandesEnAttente = computed(() => 
  demandes.value.filter(d => ['soumise', 'en_cours'].includes(d.statut)).length
)

const demandesApprouvees = computed(() => 
  demandes.value.filter(d => d.statut === 'approuvee').length
)

const demandesRejetees = computed(() => 
  demandes.value.filter(d => d.statut === 'rejetee').length
)

// Helpers
const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR')
const formatDateTime = (date) => new Date(date).toLocaleString('fr-FR')
const formatMontant = (m) => formatMoneyAmount(m, { unit: 'Ar' })

const getStatutLabel = (statut) => {
  const labels = {
    brouillon: 'Brouillon',
    soumise: 'Soumise',
    en_cours: 'En cours',
    approuvee: 'Approuvée',
    rejetee: 'Rejetée',
    annulee: 'Annulée'
  }
  return labels[statut] || statut
}

const onTypeChange = () => {
  // Reset specific fields when type changes
  newDemandeForm.value.montant = null
}

const handleFiles = (event) => {
  files.value = Array.from(event.target.files)
}

// Load
const loadData = async () => {
  loading.value = true
  try {
    const [demandesRes, typesRes] = await Promise.all([
      selfServiceService.getDemandes(),
      demandeRHService.getTypesDemandes()
    ])
    demandes.value = demandesRes.data.data || demandesRes.data
    typesDemandes.value = typesRes.data.data || typesRes.data
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

// Actions
const creerDemande = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    Object.keys(newDemandeForm.value).forEach(key => {
      if (newDemandeForm.value[key] !== null && newDemandeForm.value[key] !== '') {
        formData.append(key, newDemandeForm.value[key])
      }
    })
    formData.append('soumettre', !saveAsBrouillon.value)
    
    files.value.forEach((file, index) => {
      formData.append(`documents[${index}]`, file)
    })

    await selfServiceService.creerDemande(formData)
    showNewDemande.value = false
    resetForm()
    await loadData()
    alert(saveAsBrouillon.value ? 'Brouillon enregistré!' : 'Demande soumise!')
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const soumettreDemande = async (demande) => {
  if (!confirm('Soumettre cette demande ?')) return
  try {
    await demandeRHService.soumettreDemande(demande.id)
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  }
}

const annulerDemande = async (demande) => {
  if (!confirm('Annuler cette demande ?')) return
  try {
    await demandeRHService.annulerDemande(demande.id)
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  }
}

const viewDemande = (demande) => {
  selectedDemande.value = demande
  showDetails.value = true
}

const resetForm = () => {
  newDemandeForm.value = {
    type_demande_id: '',
    objet: '',
    description: '',
    date_souhaitee: '',
    priorite: 'normale',
    montant: null
  }
  files.value = []
}

onMounted(loadData)
</script>

<style scoped>
.demandes-view {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
}

.filters-card {
  padding: 15px;
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
  min-width: 150px;
}

/* Stats row */
.stats-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.stat-item {
  background: white;
  padding: 15px 25px;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  text-align: center;
}

.stat-item .stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: bold;
}

.stat-item .stat-label {
  font-size: 0.85rem;
  color: #666;
}

.stat-item.success .stat-value { color: #388e3c; }
.stat-item.danger .stat-value { color: #c62828; }

/* Demandes list */
.demandes-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.demande-card {
  padding: 20px;
}

.demande-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.demande-numero {
  display: flex;
  align-items: center;
  gap: 10px;
}

.numero {
  font-weight: bold;
  font-family: monospace;
}

.type-badge {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
}

.type-badge.large {
  padding: 6px 14px;
  font-size: 0.9rem;
}

.statut-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.statut-badge.large {
  padding: 8px 16px;
  font-size: 0.9rem;
}

.statut-badge.brouillon { background: #e8e8e8; color: #666; }
.statut-badge.soumise { background: #e3f2fd; color: #1976d2; }
.statut-badge.en_cours { background: #fff3e0; color: #f57c00; }
.statut-badge.approuvee { background: #e8f5e9; color: #388e3c; }
.statut-badge.rejetee { background: #ffebee; color: #c62828; }
.statut-badge.annulee { background: #f5f5f5; color: #999; }

.demande-content {
  margin-bottom: 15px;
}

.demande-objet {
  font-weight: 500;
  margin: 0 0 8px 0;
}

.demande-description {
  color: #666;
  margin: 0 0 12px 0;
  font-size: 0.9rem;
}

.demande-meta {
  display: flex;
  gap: 20px;
  font-size: 0.85rem;
  color: #888;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.documents-list {
  margin-top: 10px;
}

.documents-label {
  font-size: 0.85rem;
  color: #667eea;
}

.reponse-rh {
  margin-top: 12px;
  padding: 10px;
  background: #f5f5f5;
  border-radius: 6px;
  font-size: 0.9rem;
}

.demande-actions {
  display: flex;
  gap: 10px;
  padding-top: 15px;
  border-top: 1px solid #eee;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal.modal-lg {
  max-width: 700px;
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
}

.modal-body {
  padding: 20px;
}

.type-description {
  margin-top: 5px;
  font-size: 0.85rem;
  color: #666;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
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
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.help-text {
  margin-top: 5px;
  font-size: 0.8rem;
  color: #888;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* Detail modal */
.detail-header {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.detail-section {
  margin-bottom: 20px;
}

.detail-section h4 {
  margin: 0 0 10px 0;
  font-size: 0.9rem;
  color: #888;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  margin-bottom: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-item .label {
  font-size: 0.8rem;
  color: #888;
}

.detail-item .value {
  font-weight: 500;
}

.documents-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.document-item {
  padding: 10px;
  background: #f5f5f5;
  border-radius: 6px;
  text-decoration: none;
  color: #333;
}

.document-item:hover {
  background: #e8e8e8;
}

.reponse-box {
  padding: 15px;
  background: #e8f5e9;
  border-radius: 6px;
}

.reponse-date {
  display: block;
  margin-top: 10px;
  font-size: 0.8rem;
  color: #666;
}

/* Timeline */
.timeline {
  display: flex;
  flex-direction: column;
  gap: 15px;
  padding-left: 20px;
  border-left: 2px solid #e8e8e8;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  position: relative;
}

.timeline-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #ccc;
  position: absolute;
  left: -27px;
}

.timeline-dot.submitted { background: #1976d2; }
.timeline-dot.approuvee { background: #388e3c; }
.timeline-dot.rejetee { background: #c62828; }

.timeline-content {
  display: flex;
  flex-direction: column;
}

.timeline-content span {
  font-size: 0.85rem;
  color: #666;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 40px;
}

.empty-state p {
  color: #999;
  margin-bottom: 15px;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
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

.btn-outline {
  background: transparent;
  border: 1px solid #667eea;
  color: #667eea;
}

.btn-danger {
  background: #ffebee;
  color: #c62828;
}

.btn-sm {
  padding: 8px 14px;
  font-size: 0.85rem;
}

.btn-icon {
  padding: 6px 10px;
  background: transparent;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
</style>
