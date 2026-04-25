<template>
  <div class="formations-view">
    <!-- En-tête -->
    <div class="page-header">
      <h1>🎓 Catalogue des Formations</h1>
      <button class="btn btn-primary" @click="showFormationModal = true">
        + Nouvelle Formation
      </button>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Type</label>
          <select v-model="filtreType">
            <option value="">Tous</option>
            <option value="interne">Interne</option>
            <option value="externe">Externe</option>
            <option value="en_ligne">En ligne</option>
            <option value="certification">Certification</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Niveau</label>
          <select v-model="filtreNiveau">
            <option value="">Tous</option>
            <option value="debutant">Débutant</option>
            <option value="intermediaire">Intermédiaire</option>
            <option value="avance">Avancé</option>
            <option value="expert">Expert</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Recherche</label>
          <input type="text" v-model="recherche" placeholder="Titre de formation..." />
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
      <div class="card stat-card blue">
        <span class="stat-value">{{ formations.length }}</span>
        <span class="stat-label">Formations disponibles</span>
      </div>
      <div class="card stat-card green">
        <span class="stat-value">{{ inscriptionsEnCours }}</span>
        <span class="stat-label">Inscriptions en cours</span>
      </div>
      <div class="card stat-card purple">
        <span class="stat-value">{{ totalHeures }}</span>
        <span class="stat-label">Heures de formation</span>
      </div>
    </div>

    <!-- Liste des formations -->
    <div class="formations-grid">
      <div v-for="formation in formationsFiltrees" :key="formation.id" class="card formation-card">
        <div class="formation-header">
          <span class="formation-type" :class="formation.type">{{ getTypeLabel(formation.type) }}</span>
          <span class="formation-niveau" :class="formation.niveau">{{ getNiveauLabel(formation.niveau) }}</span>
        </div>
        
        <h3 class="formation-titre">{{ formation.titre }}</h3>
        <p class="formation-description">{{ formation.description }}</p>
        
        <div class="formation-details">
          <div class="detail">
            <span class="icon">⏱️</span>
            <span>{{ formation.duree_heures || 0 }}h</span>
          </div>
          <!-- <div class="detail" v-if="formation.cout">
            <span class="icon">💰</span>
            <span>{{ formatMontant(formation.cout) }}</span>
          </div> -->
          <div class="detail" v-if="formation.formateur">
            <span class="icon">👤</span>
            <span>{{ formation.formateur }}</span>
          </div>
        </div>

        <!-- Compétences développées -->
        <div class="formation-competences" v-if="formation.competences?.length">
          <span class="label">Compétences :</span>
          <div class="competence-tags">
            <span v-for="comp in formation.competences.slice(0, 3)" :key="comp.id" class="tag">
              {{ comp.nom }}
            </span>
            <span v-if="formation.competences.length > 3" class="tag more">
              +{{ formation.competences.length - 3 }}
            </span>
          </div>
        </div>

        <div class="formation-footer">
          <div class="inscriptions-count">
            <span>{{ formation.inscriptions_count || 0 }} inscrits</span>
          </div>
          <div class="formation-actions">
            <button class="btn btn-sm btn-secondary" @click="viewFormation(formation)">Détails</button>
            <button class="btn btn-sm btn-primary" @click="inscrireEmploye(formation)">Inscrire</button>
            <button class="btn btn-sm btn-icon" @click="editFormation(formation)">✏️</button>
            <button class="btn btn-sm btn-icon btn-danger" @click="deleteFormation(formation)">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Formation -->
    <div v-if="showFormationModal" class="modal-overlay" @click.self="closeFormationModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>{{ editingFormation ? 'Modifier' : 'Nouvelle' }} formation</h2>
          <button class="btn btn-icon" @click="closeFormationModal">✕</button>
        </div>
        <form @submit.prevent="saveFormation" class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Titre *</label>
              <input type="text" v-model="formationForm.titre" required />
            </div>
            <div class="form-group">
              <label>Code</label>
              <input type="text" v-model="formationForm.code" placeholder="FORM-001" />
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea v-model="formationForm.description" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Type *</label>
              <select v-model="formationForm.type" required>
                <option value="interne">Interne</option>
                <option value="externe">Externe</option>
                <option value="en_ligne">En ligne</option>
                <option value="certification">Certification</option>
              </select>
            </div>
            <div class="form-group">
              <label>Niveau *</label>
              <select v-model="formationForm.niveau" required>
                <option value="debutant">Débutant</option>
                <option value="intermediaire">Intermédiaire</option>
                <option value="avance">Avancé</option>
                <option value="expert">Expert</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Durée (heures)</label>
              <input type="number" v-model.number="formationForm.duree_heures" min="0" />
            </div>
            <div class="form-group">
              <label>Coût ({{ currencyUnit }})</label>
              <input type="number" v-model.number="formationForm.cout" min="0" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Formateur</label>
              <input type="text" v-model="formationForm.formateur" />
            </div>
            <div class="form-group">
              <label>Organisme</label>
              <input type="text" v-model="formationForm.organisme" />
            </div>
          </div>

          <div class="form-group">
            <label>Compétences développées</label>
            <div class="competences-select">
              <div v-for="comp in allCompetences" :key="comp.id" class="competence-checkbox">
                <label>
                  <input type="checkbox" :value="comp.id" v-model="formationForm.competence_ids" />
                  {{ comp.nom }}
                </label>
              </div>
            </div>
          </div>

          <div class="form-group checkbox-group">
            <label>
              <input type="checkbox" v-model="formationForm.actif" />
              Formation active
            </label>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeFormationModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Inscription -->
    <div v-if="showInscriptionModal" class="modal-overlay" @click.self="showInscriptionModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Inscrire un employé</h2>
          <button class="btn btn-icon" @click="showInscriptionModal = false">✕</button>
        </div>
        <form @submit.prevent="confirmerInscription" class="modal-body">
          <p class="info-text">Formation : <strong>{{ selectedFormation?.titre }}</strong></p>
          
          <div class="form-group">
            <label>Employé *</label>
            <select v-model="inscriptionForm.employe_id" required>
              <option value="">Sélectionner un employé...</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Date de début prévue</label>
            <input type="date" v-model="inscriptionForm.date_debut" />
          </div>

          <div class="form-group">
            <label>Commentaire</label>
            <textarea v-model="inscriptionForm.commentaire" rows="2"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showInscriptionModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">Inscrire</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import formationService from '../services/formationService'
import competenceService from '../services/competenceService'
import api from '../services/api'
import { formatMoneyAmount } from '../utils/formatters'
import { getStoredCurrency } from '../utils/currency'

const formations = ref([])
const allCompetences = ref([])
const employes = ref([])
const loading = ref(false)
const saving = ref(false)
const currencyUnit = getStoredCurrency()

// Filtres
const filtreType = ref('')
const filtreNiveau = ref('')
const recherche = ref('')

// Modals
const showFormationModal = ref(false)
const showInscriptionModal = ref(false)
const editingFormation = ref(null)
const selectedFormation = ref(null)

// Forms
const formationForm = ref({
  titre: '',
  code: '',
  description: '',
  type: 'interne',
  niveau: 'intermediaire',
  duree_heures: null,
  cout: null,
  formateur: '',
  organisme: '',
  competence_ids: [],
  actif: true
})

const inscriptionForm = ref({
  employe_id: '',
  date_debut: '',
  commentaire: ''
})

// Computed
const inscriptionsEnCours = computed(() => {
  return formations.value.reduce((total, f) => total + (f.inscriptions_en_cours_count || 0), 0)
})

const totalHeures = computed(() => {
  return formations.value.reduce((total, f) => total + (f.duree_heures || 0), 0)
})

const formationsFiltrees = computed(() => {
  let result = formations.value
  
  if (filtreType.value) {
    result = result.filter(f => f.type === filtreType.value)
  }
  if (filtreNiveau.value) {
    result = result.filter(f => f.niveau === filtreNiveau.value)
  }
  if (recherche.value) {
    const term = recherche.value.toLowerCase()
    result = result.filter(f => 
      f.titre.toLowerCase().includes(term) ||
      f.description?.toLowerCase().includes(term)
    )
  }
  
  return result
})

// Helpers
const getTypeLabel = (type) => {
  const labels = { interne: 'Interne', externe: 'Externe', en_ligne: 'En ligne', certification: 'Certification' }
  return labels[type] || type
}

const getNiveauLabel = (niveau) => {
  const labels = { debutant: 'Débutant', intermediaire: 'Intermédiaire', avance: 'Avancé', expert: 'Expert' }
  return labels[niveau] || niveau
}

const formatMontant = (montant) => {
  return formatMoneyAmount(montant)
}

// Load data
const loadData = async () => {
  loading.value = true
  try {
    const [formRes, compRes, empRes] = await Promise.all([
      formationService.getFormations(),
      competenceService.getCompetences(),
      api.get('/v1/employes')
    ])
    formations.value = formRes.data.data || formRes.data
    allCompetences.value = compRes.data.data || compRes.data
    employes.value = empRes.data.data || empRes.data
  } catch (error) {
    console.error('Erreur chargement:', error)
  } finally {
    loading.value = false
  }
}

// Formation CRUD
const editFormation = (formation) => {
  editingFormation.value = formation
  formationForm.value = {
    ...formation,
    competence_ids: formation.competences?.map(c => c.id) || []
  }
  showFormationModal.value = true
}

const viewFormation = (formation) => {
  // TODO: Implémenter la vue détail
  alert('Détails de la formation: ' + formation.titre)
}

const saveFormation = async () => {
  saving.value = true
  try {
    if (editingFormation.value) {
      await formationService.updateFormation(editingFormation.value.id, formationForm.value)
    } else {
      await formationService.createFormation(formationForm.value)
    }
    closeFormationModal()
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const deleteFormation = async (formation) => {
  if (!confirm(`Supprimer la formation "${formation.titre}" ?`)) return
  try {
    await formationService.deleteFormation(formation.id)
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  }
}

const closeFormationModal = () => {
  showFormationModal.value = false
  editingFormation.value = null
  formationForm.value = {
    titre: '', code: '', description: '', type: 'interne', niveau: 'intermediaire',
    duree_heures: null, cout: null, formateur: '', organisme: '', competence_ids: [], actif: true
  }
}

// Inscriptions
const inscrireEmploye = (formation) => {
  selectedFormation.value = formation
  inscriptionForm.value = { employe_id: '', date_debut: '', commentaire: '' }
  showInscriptionModal.value = true
}

const confirmerInscription = async () => {
  saving.value = true
  try {
    await formationService.inscrireEmploye({
      formation_id: selectedFormation.value.id,
      ...inscriptionForm.value
    })
    showInscriptionModal.value = false
    await loadData()
    alert('Employé inscrit avec succès!')
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.formations-view {
  padding: 20px;
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
  flex-wrap: wrap;
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

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  min-width: 160px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.stat-card {
  padding: 20px;
  text-align: center;
  color: white;
}

.stat-card.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-card.purple { background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%); }

.stat-value {
  display: block;
  font-size: 2rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.formations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.formation-card {
  padding: 20px;
  display: flex;
  flex-direction: column;
}

.formation-header {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.formation-type,
.formation-niveau {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.formation-type.interne { background: #e3f2fd; color: #1976d2; }
.formation-type.externe { background: #fce4ec; color: #c2185b; }
.formation-type.en_ligne { background: #e8f5e9; color: #388e3c; }
.formation-type.certification { background: #fff3e0; color: #f57c00; }

.formation-niveau.debutant { background: #f5f5f5; color: #666; }
.formation-niveau.intermediaire { background: #e3f2fd; color: #1976d2; }
.formation-niveau.avance { background: #fff3e0; color: #f57c00; }
.formation-niveau.expert { background: #fce4ec; color: #c2185b; }

.formation-titre {
  margin: 0 0 8px 0;
  font-size: 1.1rem;
}

.formation-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 15px;
  flex-grow: 1;
}

.formation-details {
  display: flex;
  gap: 15px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.detail {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.85rem;
  color: #555;
}

.formation-competences {
  margin-bottom: 15px;
}

.formation-competences .label {
  font-size: 0.8rem;
  color: #888;
  display: block;
  margin-bottom: 5px;
}

.competence-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.tag {
  background: #e8e8e8;
  padding: 3px 8px;
  border-radius: 10px;
  font-size: 0.75rem;
}

.tag.more {
  background: #667eea;
  color: white;
}

.formation-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.inscriptions-count {
  font-size: 0.85rem;
  color: #666;
}

.formation-actions {
  display: flex;
  gap: 8px;
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
  max-width: 500px;
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

.competences-select {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 10px;
}

.competence-checkbox {
  margin-bottom: 8px;
}

.competence-checkbox label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.info-text {
  background: #f5f5f5;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 15px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a6fd6;
}

.btn-secondary {
  background: #e8e8e8;
  color: #333;
}

.btn-icon {
  padding: 6px 10px;
  background: transparent;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.85rem;
}

.btn-danger:hover {
  background: #fee;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
</style>
