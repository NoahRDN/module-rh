<template>
  <div class="matching-view">
    <!-- En-tête -->
    <div class="page-header">
      <h1>🎯 Matching Profil / Poste</h1>
    </div>

    <!-- Onglets -->
    <div class="tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.key" 
        class="tab" 
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        {{ tab.icon }} {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Analyse individuelle -->
    <div v-if="activeTab === 'individuel'" class="tab-content">
      <div class="card selection-card">
        <div class="selection-row">
          <div class="form-group">
            <label>Employé</label>
            <select v-model="selectedEmploye" @change="onEmployeChange">
              <option value="">Sélectionner un employé...</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }} - {{ emp.poste?.titre || 'Sans poste' }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Poste cible</label>
            <select v-model="selectedPoste" @change="calculerMatch">
              <option value="">Sélectionner un poste...</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre }}
              </option>
            </select>
          </div>
          <button class="btn btn-primary" @click="calculerMatch" :disabled="!selectedEmploye || !selectedPoste">
            Analyser
          </button>
        </div>
      </div>

      <!-- Résultat du matching -->
      <div v-if="matchResult" class="match-result">
        <div class="card result-summary">
          <div class="score-circle" :class="getScoreClass(matchResult.score_global)">
            <span class="score-value">{{ matchResult.score_global }}%</span>
            <span class="score-label">Compatibilité</span>
          </div>
          <div class="result-details">
            <h3>{{ matchResult.employe?.nom }} {{ matchResult.employe?.prenom }}</h3>
            <p>pour le poste de <strong>{{ matchResult.poste?.titre }}</strong></p>
            <div class="scores-breakdown">
              <div class="score-item">
                <span class="label">Obligatoires</span>
                <div class="progress-bar">
                  <div class="progress" :style="{ width: matchResult.score_obligatoires + '%' }"></div>
                </div>
                <span class="value">{{ matchResult.score_obligatoires }}%</span>
              </div>
              <div class="score-item">
                <span class="label">Souhaitées</span>
                <div class="progress-bar">
                  <div class="progress secondary" :style="{ width: matchResult.score_souhaitees + '%' }"></div>
                </div>
                <span class="value">{{ matchResult.score_souhaitees }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Détail compétences -->
        <div class="competences-detail">
          <div class="card competence-section">
            <h4>✅ Compétences acquises</h4>
            <div class="competence-list">
              <div v-for="comp in matchResult.competences_acquises" :key="comp.id" class="competence-item ok">
                <span class="name">{{ comp.nom }}</span>
                <span class="niveau">Niveau {{ comp.niveau_employe }} / {{ comp.niveau_requis }} requis</span>
              </div>
              <div v-if="!matchResult.competences_acquises?.length" class="empty">Aucune</div>
            </div>
          </div>
          <div class="card competence-section">
            <h4>⚠️ Compétences à développer</h4>
            <div class="competence-list">
              <div v-for="comp in matchResult.competences_manquantes" :key="comp.id" class="competence-item warning">
                <span class="name">{{ comp.nom }}</span>
                <span class="niveau">
                  Niveau {{ comp.niveau_employe || 0 }} → {{ comp.niveau_requis }} requis
                  <span v-if="comp.obligatoire" class="badge-obligatoire">Obligatoire</span>
                </span>
              </div>
              <div v-if="!matchResult.competences_manquantes?.length" class="empty">Aucune lacune</div>
            </div>
          </div>
        </div>

        <!-- Formations suggérées -->
        <div v-if="matchResult.formations_suggerees?.length" class="card formations-suggested">
          <h4>🎓 Formations recommandées</h4>
          <div class="formations-list">
            <div v-for="formation in matchResult.formations_suggerees" :key="formation.id" class="formation-item">
              <div class="formation-info">
                <span class="titre">{{ formation.titre }}</span>
                <span class="details">{{ formation.duree_heures }}h • {{ formation.type }}</span>
              </div>
              <div class="formation-competences">
                <span v-for="comp in formation.competences_couvertes" :key="comp" class="tag">{{ comp }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Candidats pour un poste -->
    <div v-if="activeTab === 'candidats'" class="tab-content">
      <div class="card selection-card">
        <div class="selection-row">
          <div class="form-group flex-grow">
            <label>Poste à pourvoir</label>
            <select v-model="posteRecherche" @change="chercherCandidats">
              <option value="">Sélectionner un poste...</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre }} ({{ poste.departement?.nom }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Score minimum</label>
            <input type="number" v-model.number="scoreMinimum" min="0" max="100" />
          </div>
          <button class="btn btn-primary" @click="chercherCandidats" :disabled="!posteRecherche">
            Rechercher
          </button>
        </div>
      </div>

      <!-- Liste des candidats -->
      <div v-if="candidats.length" class="candidats-list">
        <div v-for="candidat in candidats" :key="candidat.employe.id" class="card candidat-card">
          <div class="candidat-score" :class="getScoreClass(candidat.score_global)">
            {{ candidat.score_global }}%
          </div>
          <div class="candidat-info">
            <h4>{{ candidat.employe.nom }} {{ candidat.employe.prenom }}</h4>
            <p>{{ candidat.employe.poste?.titre || 'Sans poste actuel' }}</p>
          </div>
          <div class="candidat-details">
            <span class="detail">
              <span class="icon">✅</span> {{ candidat.competences_acquises?.length || 0 }} acquises
            </span>
            <span class="detail">
              <span class="icon">⚠️</span> {{ candidat.competences_manquantes?.length || 0 }} à développer
            </span>
          </div>
          <button class="btn btn-secondary btn-sm" @click="voirDetailCandidat(candidat)">
            Voir détail
          </button>
        </div>
      </div>
      <div v-else-if="posteRecherche && !loading" class="card empty-state">
        Aucun candidat trouvé avec un score supérieur à {{ scoreMinimum }}%
      </div>
    </div>

    <!-- Tab: Postes compatibles -->
    <div v-if="activeTab === 'postes'" class="tab-content">
      <div class="card selection-card">
        <div class="selection-row">
          <div class="form-group flex-grow">
            <label>Employé</label>
            <select v-model="employeRecherche" @change="chercherPostes">
              <option value="">Sélectionner un employé...</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </div>
          <button class="btn btn-primary" @click="chercherPostes" :disabled="!employeRecherche">
            Rechercher
          </button>
        </div>
      </div>

      <!-- Liste des postes compatibles -->
      <div v-if="postesCompatibles.length" class="postes-list">
        <div v-for="item in postesCompatibles" :key="item.poste.id" class="card poste-card">
          <div class="poste-score" :class="getScoreClass(item.score_global)">
            {{ item.score_global }}%
          </div>
          <div class="poste-info">
            <h4>{{ item.poste.titre }}</h4>
            <p>{{ item.poste.departement?.nom }}</p>
          </div>
          <div class="poste-details">
            <span>{{ item.competences_requises }} compétences requises</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Analyse globale -->
    <div v-if="activeTab === 'global'" class="tab-content">
      <button class="btn btn-primary mb-20" @click="lancerAnalyseGlobale" :disabled="loading">
        {{ loading ? 'Analyse en cours...' : '🔍 Lancer l\'analyse globale' }}
      </button>

      <div v-if="analyseGlobale" class="analyse-globale">
        <div class="stats-grid">
          <div class="card stat-card">
            <span class="stat-value">{{ analyseGlobale.employes_evalues }}</span>
            <span class="stat-label">Employés évalués</span>
          </div>
          <div class="card stat-card">
            <span class="stat-value">{{ analyseGlobale.postes_avec_requis }}</span>
            <span class="stat-label">Postes avec requis</span>
          </div>
          <div class="card stat-card">
            <span class="stat-value">{{ analyseGlobale.taux_couverture_moyen }}%</span>
            <span class="stat-label">Couverture moyenne</span>
          </div>
        </div>

        <div class="card">
          <h4>🔥 Compétences les plus demandées</h4>
          <div class="top-list">
            <div v-for="comp in analyseGlobale.competences_demandees" :key="comp.id" class="top-item">
              <span class="name">{{ comp.nom }}</span>
              <span class="count">{{ comp.postes_count }} postes</span>
            </div>
          </div>
        </div>

        <div class="card">
          <h4>⚠️ Lacunes critiques</h4>
          <div class="top-list">
            <div v-for="lacune in analyseGlobale.lacunes_critiques" :key="lacune.competence_id" class="top-item warning">
              <span class="name">{{ lacune.competence_nom }}</span>
              <span class="count">{{ lacune.employes_sans }} employés sans cette compétence</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import competenceService from '../services/competenceService'
import api from '../services/api'

const tabs = [
  { key: 'individuel', label: 'Analyse individuelle', icon: '👤' },
  { key: 'candidats', label: 'Candidats pour un poste', icon: '🎯' },
  { key: 'postes', label: 'Postes compatibles', icon: '💼' },
  { key: 'global', label: 'Analyse globale', icon: '📊' }
]

const activeTab = ref('individuel')
const loading = ref(false)

// Data
const employes = ref([])
const postes = ref([])

// Tab: Individuel
const selectedEmploye = ref('')
const selectedPoste = ref('')
const matchResult = ref(null)

// Tab: Candidats
const posteRecherche = ref('')
const scoreMinimum = ref(50)
const candidats = ref([])

// Tab: Postes
const employeRecherche = ref('')
const postesCompatibles = ref([])

// Tab: Global
const analyseGlobale = ref(null)

// Helpers
const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  if (score >= 40) return 'medium'
  return 'low'
}

// Load data
const loadData = async () => {
  try {
    const [empRes, posteRes] = await Promise.all([
      api.get('/v1/employes'),
      api.get('/v1/postes')
    ])
    employes.value = empRes.data.data || empRes.data
    postes.value = posteRes.data.data || posteRes.data
  } catch (error) {
    console.error('Erreur chargement:', error)
  }
}

// Tab: Individuel
const onEmployeChange = () => {
  matchResult.value = null
}

const calculerMatch = async () => {
  if (!selectedEmploye.value || !selectedPoste.value) return
  
  loading.value = true
  try {
    const res = await competenceService.calculerCompatibilite(selectedEmploye.value, selectedPoste.value)
    matchResult.value = res.data
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors du calcul: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

// Tab: Candidats
const chercherCandidats = async () => {
  if (!posteRecherche.value) return
  
  loading.value = true
  try {
    const res = await competenceService.getCandidatsPourPoste(posteRecherche.value, { 
      score_minimum: scoreMinimum.value 
    })
    candidats.value = res.data.data || res.data
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

const voirDetailCandidat = (candidat) => {
  selectedEmploye.value = candidat.employe.id
  selectedPoste.value = posteRecherche.value
  matchResult.value = candidat
  activeTab.value = 'individuel'
}

// Tab: Postes
const chercherPostes = async () => {
  if (!employeRecherche.value) return
  
  loading.value = true
  try {
    const res = await competenceService.getPostesCompatibles(employeRecherche.value)
    postesCompatibles.value = res.data.data || res.data
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

// Tab: Global
const lancerAnalyseGlobale = async () => {
  loading.value = true
  try {
    const res = await competenceService.getAnalyseGlobale()
    analyseGlobale.value = res.data
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.matching-view {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
}

.tabs {
  display: flex;
  gap: 5px;
  margin-bottom: 20px;
  background: #f5f5f5;
  padding: 5px;
  border-radius: 10px;
}

.tab {
  flex: 1;
  padding: 12px 20px;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.tab:hover {
  background: #e8e8e8;
}

.tab.active {
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tab-content {
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.selection-card {
  padding: 20px;
  margin-bottom: 20px;
}

.selection-row {
  display: flex;
  gap: 20px;
  align-items: flex-end;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-group.flex-grow {
  flex-grow: 1;
}

.form-group label {
  font-size: 0.85rem;
  color: #666;
}

.form-group select,
.form-group input {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  min-width: 200px;
}

/* Match Result */
.match-result {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.result-summary {
  display: flex;
  gap: 30px;
  padding: 25px;
  align-items: center;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.score-circle.excellent { background: linear-gradient(135deg, #11998e, #38ef7d); }
.score-circle.good { background: linear-gradient(135deg, #667eea, #764ba2); }
.score-circle.medium { background: linear-gradient(135deg, #f093fb, #f5576c); }
.score-circle.low { background: linear-gradient(135deg, #c0392b, #e74c3c); }

.score-value {
  font-size: 1.8rem;
  font-weight: bold;
}

.score-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

.result-details h3 {
  margin: 0 0 5px 0;
}

.result-details p {
  color: #666;
  margin-bottom: 15px;
}

.scores-breakdown {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 300px;
}

.score-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.score-item .label {
  width: 100px;
  font-size: 0.85rem;
}

.progress-bar {
  flex-grow: 1;
  height: 8px;
  background: #e8e8e8;
  border-radius: 4px;
  overflow: hidden;
}

.progress {
  height: 100%;
  background: #667eea;
  border-radius: 4px;
  transition: width 0.5s ease;
}

.progress.secondary {
  background: #a8b5ea;
}

.score-item .value {
  width: 50px;
  text-align: right;
  font-weight: 500;
}

/* Compétences detail */
.competences-detail {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.competence-section {
  padding: 20px;
}

.competence-section h4 {
  margin: 0 0 15px 0;
}

.competence-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.competence-item {
  display: flex;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: 6px;
}

.competence-item.ok {
  background: #e8f5e9;
}

.competence-item.warning {
  background: #fff3e0;
}

.competence-item .name {
  font-weight: 500;
}

.competence-item .niveau {
  font-size: 0.85rem;
  color: #666;
}

.badge-obligatoire {
  background: #f44336;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  margin-left: 8px;
}

.empty {
  color: #999;
  text-align: center;
  padding: 20px;
}

/* Formations suggested */
.formations-suggested {
  padding: 20px;
}

.formations-suggested h4 {
  margin: 0 0 15px 0;
}

.formations-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.formation-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.formation-info .titre {
  font-weight: 500;
  display: block;
}

.formation-info .details {
  font-size: 0.85rem;
  color: #666;
}

.formation-competences {
  display: flex;
  gap: 5px;
}

.tag {
  background: #667eea;
  color: white;
  padding: 3px 8px;
  border-radius: 10px;
  font-size: 0.75rem;
}

/* Candidats list */
.candidats-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.candidat-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
}

.candidat-score {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  flex-shrink: 0;
}

.candidat-score.excellent { background: #11998e; }
.candidat-score.good { background: #667eea; }
.candidat-score.medium { background: #f5576c; }
.candidat-score.low { background: #c0392b; }

.candidat-info {
  flex-grow: 1;
}

.candidat-info h4 {
  margin: 0 0 5px 0;
}

.candidat-info p {
  margin: 0;
  color: #666;
}

.candidat-details {
  display: flex;
  gap: 15px;
}

.detail {
  font-size: 0.9rem;
}

/* Postes list */
.postes-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 15px;
}

.poste-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
}

.poste-score {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
}

.poste-score.excellent { background: #11998e; }
.poste-score.good { background: #667eea; }
.poste-score.medium { background: #f5576c; }
.poste-score.low { background: #c0392b; }

.poste-info h4 {
  margin: 0 0 3px 0;
}

.poste-info p {
  margin: 0;
  font-size: 0.85rem;
  color: #666;
}

.poste-details {
  margin-left: auto;
  font-size: 0.85rem;
  color: #888;
}

/* Analyse globale */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin-bottom: 20px;
}

.stat-card {
  padding: 20px;
  text-align: center;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.stat-value {
  display: block;
  font-size: 2rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.9rem;
}

.top-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.top-item {
  display: flex;
  justify-content: space-between;
  padding: 10px 12px;
  background: #f8f9fa;
  border-radius: 6px;
}

.top-item.warning {
  background: #fff3e0;
}

.top-item .name {
  font-weight: 500;
}

.top-item .count {
  color: #666;
  font-size: 0.9rem;
}

.mb-20 {
  margin-bottom: 20px;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #999;
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

.btn-primary:hover:not(:disabled) {
  background: #5a6fd6;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e8e8e8;
  color: #333;
}

.btn-sm {
  padding: 8px 14px;
  font-size: 0.85rem;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.card h4 {
  margin: 0 0 15px 0;
}
</style>
