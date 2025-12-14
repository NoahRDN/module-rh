<template>
  <div class="competences-view">
    <!-- En-tête -->
    <div class="page-header">
      <h1>🎯 Cartographie des Compétences</h1>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="showCategorieModal = true">
          + Catégorie
        </button>
        <button class="btn btn-primary" @click="showCompetenceModal = true">
          + Compétence
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Catégorie</label>
          <select v-model="filtreCategorie" @change="loadCompetences">
            <option value="">Toutes</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.nom }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Recherche</label>
          <input type="text" v-model="recherche" placeholder="Nom de compétence..." />
        </div>
      </div>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-grid">
      <div class="card stat-card">
        <span class="stat-value">{{ categories.length }}</span>
        <span class="stat-label">Catégories</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ totalCompetences }}</span>
        <span class="stat-label">Compétences</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ cartographie?.employes_avec_competences || 0 }}</span>
        <span class="stat-label">Employés évalués</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ cartographie?.taux_couverture || 0 }}%</span>
        <span class="stat-label">Taux couverture</span>
      </div>
    </div>

    <!-- Liste par catégorie -->
    <div class="categories-container">
      <div v-for="categorie in categoriesFiltrees" :key="categorie.id" class="card category-card">
        <div class="category-header">
          <div class="category-info">
            <span class="category-icon">{{ getIcon(categorie.code) }}</span>
            <h3>{{ categorie.nom }}</h3>
            <span class="badge">{{ categorie.competences?.length || 0 }}</span>
          </div>
          <div class="category-actions">
            <button class="btn btn-icon" @click="editCategorie(categorie)" title="Modifier">✏️</button>
            <button class="btn btn-icon btn-danger" @click="deleteCategorie(categorie)" title="Supprimer">🗑️</button>
          </div>
        </div>

        <p class="category-description" v-if="categorie.description">{{ categorie.description }}</p>

        <div class="competences-list">
          <div v-for="comp in getCompetencesByCategorie(categorie.id)" :key="comp.id" class="competence-item">
            <div class="competence-info">
              <span class="competence-nom">{{ comp.nom }}</span>
              <span class="competence-code">{{ comp.code }}</span>
            </div>
            <div class="competence-stats">
              <span class="stat-mini" title="Employés avec cette compétence">
                👥 {{ comp.employes_count || 0 }}
              </span>
              <span class="stat-mini" title="Postes requérant cette compétence">
                💼 {{ comp.postes_count || 0 }}
              </span>
            </div>
            <div class="competence-actions">
              <button class="btn btn-icon btn-sm" @click="editCompetence(comp)">✏️</button>
              <button class="btn btn-icon btn-sm btn-danger" @click="deleteCompetence(comp)">🗑️</button>
            </div>
          </div>
          <div v-if="!getCompetencesByCategorie(categorie.id).length" class="empty-state">
            Aucune compétence dans cette catégorie
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Catégorie -->
    <div v-if="showCategorieModal" class="modal-overlay" @click.self="showCategorieModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingCategorie ? 'Modifier' : 'Nouvelle' }} catégorie</h2>
          <button class="btn btn-icon" @click="showCategorieModal = false">✕</button>
        </div>
        <form @submit.prevent="saveCategorie" class="modal-body">
          <div class="form-group">
            <label>Code *</label>
            <input type="text" v-model="categorieForm.code" required placeholder="ex: technique" />
          </div>
          <div class="form-group">
            <label>Nom *</label>
            <input type="text" v-model="categorieForm.nom" required placeholder="Compétences techniques" />
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="categorieForm.description" rows="3" placeholder="Description de la catégorie..."></textarea>
          </div>
          <div class="form-group">
            <label>Ordre d'affichage</label>
            <input type="number" v-model.number="categorieForm.ordre" min="0" />
          </div>
          <div class="form-group checkbox-group">
            <label>
              <input type="checkbox" v-model="categorieForm.actif" />
              Actif
            </label>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showCategorieModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Compétence -->
    <div v-if="showCompetenceModal" class="modal-overlay" @click.self="showCompetenceModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingCompetence ? 'Modifier' : 'Nouvelle' }} compétence</h2>
          <button class="btn btn-icon" @click="showCompetenceModal = false">✕</button>
        </div>
        <form @submit.prevent="saveCompetence" class="modal-body">
          <div class="form-group">
            <label>Catégorie *</label>
            <select v-model="competenceForm.categorie_competence_id" required>
              <option value="">Sélectionner...</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Code *</label>
            <input type="text" v-model="competenceForm.code" required placeholder="ex: php_laravel" />
          </div>
          <div class="form-group">
            <label>Nom *</label>
            <input type="text" v-model="competenceForm.nom" required placeholder="PHP / Laravel" />
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="competenceForm.description" rows="3" placeholder="Description de la compétence..."></textarea>
          </div>
          <div class="form-group checkbox-group">
            <label>
              <input type="checkbox" v-model="competenceForm.actif" />
              Actif
            </label>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showCompetenceModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import competenceService from '../services/competenceService'

const categories = ref([])
const competences = ref([])
const cartographie = ref(null)
const loading = ref(false)
const saving = ref(false)
const filtreCategorie = ref('')
const recherche = ref('')

// Modals
const showCategorieModal = ref(false)
const showCompetenceModal = ref(false)
const editingCategorie = ref(null)
const editingCompetence = ref(null)

// Forms
const categorieForm = ref({
  code: '',
  nom: '',
  description: '',
  ordre: 0,
  actif: true
})

const competenceForm = ref({
  categorie_competence_id: '',
  code: '',
  nom: '',
  description: '',
  actif: true
})

// Computed
const totalCompetences = computed(() => competences.value.length)

const categoriesFiltrees = computed(() => {
  let result = categories.value.filter(c => c.actif)
  if (filtreCategorie.value) {
    result = result.filter(c => c.id === parseInt(filtreCategorie.value))
  }
  return result.sort((a, b) => a.ordre - b.ordre)
})

const getCompetencesByCategorie = (categorieId) => {
  let result = competences.value.filter(c => c.categorie_competence_id === categorieId && c.actif)
  if (recherche.value) {
    const term = recherche.value.toLowerCase()
    result = result.filter(c => c.nom.toLowerCase().includes(term) || c.code.toLowerCase().includes(term))
  }
  return result
}

// Icons par catégorie
const getIcon = (code) => {
  const icons = {
    technique: '💻',
    linguistique: '🌍',
    management: '👔',
    soft_skills: '🤝',
    bureautique: '📊',
    metier: '🏢'
  }
  return icons[code] || '📋'
}

// Load data
const loadData = async () => {
  loading.value = true
  try {
    const [catRes, compRes, cartoRes] = await Promise.all([
      competenceService.getCategories(),
      competenceService.getCompetences(),
      competenceService.getCartographie()
    ])
    categories.value = catRes.data.data || catRes.data
    competences.value = compRes.data.data || compRes.data
    cartographie.value = cartoRes.data
  } catch (error) {
    console.error('Erreur chargement:', error)
  } finally {
    loading.value = false
  }
}

const loadCompetences = async () => {
  try {
    const params = filtreCategorie.value ? { categorie_id: filtreCategorie.value } : {}
    const res = await competenceService.getCompetences(params)
    competences.value = res.data.data || res.data
  } catch (error) {
    console.error('Erreur:', error)
  }
}

// Catégorie CRUD
const editCategorie = (cat) => {
  editingCategorie.value = cat
  categorieForm.value = { ...cat }
  showCategorieModal.value = true
}

const saveCategorie = async () => {
  saving.value = true
  try {
    if (editingCategorie.value) {
      await competenceService.updateCategorie(editingCategorie.value.id, categorieForm.value)
    } else {
      await competenceService.createCategorie(categorieForm.value)
    }
    showCategorieModal.value = false
    resetCategorieForm()
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const deleteCategorie = async (cat) => {
  if (!confirm(`Supprimer la catégorie "${cat.nom}" ?`)) return
  try {
    await competenceService.deleteCategorie(cat.id)
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  }
}

const resetCategorieForm = () => {
  editingCategorie.value = null
  categorieForm.value = { code: '', nom: '', description: '', ordre: 0, actif: true }
}

// Compétence CRUD
const editCompetence = (comp) => {
  editingCompetence.value = comp
  competenceForm.value = { ...comp }
  showCompetenceModal.value = true
}

const saveCompetence = async () => {
  saving.value = true
  try {
    if (editingCompetence.value) {
      await competenceService.updateCompetence(editingCompetence.value.id, competenceForm.value)
    } else {
      await competenceService.createCompetence(competenceForm.value)
    }
    showCompetenceModal.value = false
    resetCompetenceForm()
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const deleteCompetence = async (comp) => {
  if (!confirm(`Supprimer la compétence "${comp.nom}" ?`)) return
  try {
    await competenceService.deleteCompetence(comp.id)
    await loadData()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  }
}

const resetCompetenceForm = () => {
  editingCompetence.value = null
  competenceForm.value = { categorie_competence_id: '', code: '', nom: '', description: '', actif: true }
}

onMounted(loadData)
</script>

<style scoped>
.competences-view {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-actions {
  display: flex;
  gap: 10px;
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
  min-width: 180px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.stat-card {
  padding: 20px;
  text-align: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-value {
  display: block;
  font-size: 2rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.categories-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.category-card {
  padding: 20px;
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.category-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.category-icon {
  font-size: 1.5rem;
}

.category-info h3 {
  margin: 0;
  font-size: 1.2rem;
}

.badge {
  background: #e8e8e8;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8rem;
}

.category-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 15px;
}

.competences-list {
  display: grid;
  gap: 10px;
}

.competence-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 15px;
  background: #f8f9fa;
  border-radius: 8px;
  transition: all 0.2s;
}

.competence-item:hover {
  background: #e9ecef;
}

.competence-info {
  display: flex;
  flex-direction: column;
}

.competence-nom {
  font-weight: 500;
}

.competence-code {
  font-size: 0.8rem;
  color: #888;
}

.competence-stats {
  display: flex;
  gap: 15px;
}

.stat-mini {
  font-size: 0.85rem;
  color: #666;
}

.competence-actions {
  display: flex;
  gap: 5px;
}

.empty-state {
  text-align: center;
  padding: 30px;
  color: #999;
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

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.3rem;
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
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
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

.btn-icon:hover {
  background: #f0f0f0;
}

.btn-sm {
  padding: 4px 8px;
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
