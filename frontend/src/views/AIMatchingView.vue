<template>
  <div class="ai-matching-view">
    <div class="page-header">
      <h1>🤖 Matching IA Avancé</h1>
      <p class="subtitle">Recommandations intelligentes de candidats et formations</p>
    </div>

    <!-- Onglets -->
    <div class="tabs-container">
      <button 
        v-for="tab in tabs" 
        :key="tab.key"
        class="tab-btn"
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        {{ tab.icon }} {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Analyse Profil/Poste -->
    <div v-if="activeTab === 'profil'" class="card">
      <div class="card-header">
        <h3>📊 Analyser la Compatibilité Employé/Poste</h3>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Employé</label>
            <select v-model="profilForm.employe_id">
              <option value="">Sélectionnez un employé</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Poste</label>
            <select v-model="profilForm.poste_id">
              <option value="">Sélectionnez un poste</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre || poste.nom }}
              </option>
            </select>
          </div>
          <button 
            class="btn btn-primary"
            :disabled="!profilForm.employe_id || !profilForm.poste_id || analysing"
            @click="analyserProfil"
          >
            {{ analysing ? '⏳ Analyse...' : '🔍 Analyser' }}
          </button>
        </div>

        <!-- Résultat de l'analyse -->
        <div v-if="profilResult" class="analysis-result">
          <div class="result-header">
            <div class="score-display" :class="getScoreClass(profilResult.score_global)">
              <span class="score-value">{{ profilResult.score_global }}%</span>
              <span class="score-label">Compatibilité</span>
            </div>
            <div class="result-info">
              <h4>{{ normalizeEmployeName(profilResult.employe) }} → {{ normalizePosteTitle(profilResult.poste) }}</h4>
              <p class="compatibility-text">{{ getCompatibilityText(profilResult.score_global) }}</p>
            </div>
          </div>

          <div class="result-sections">
            <div class="section">
              <h5>✅ Points Forts</h5>
              <ul>
                <li v-for="(point, i) in profilResult.analyse_ia?.points_forts || []" :key="i">
                  {{ point }}
                </li>
                <li v-if="!(profilResult.analyse_ia?.points_forts || []).length" class="muted">Aucune donnée</li>
              </ul>
            </div>
            <div class="section">
              <h5>⚠️ Axes d'Amélioration</h5>
              <ul>
                <li v-for="(point, i) in profilResult.analyse_ia?.points_amelioration || []" :key="i">
                  {{ point }}
                </li>
                <li v-if="!(profilResult.analyse_ia?.points_amelioration || []).length" class="muted">Aucune donnée</li>
              </ul>
            </div>
            <div class="section full-width">
              <h5>📚 Formations Recommandées</h5>
              <div class="recommendations-grid">
                <div 
                  v-for="(formation, i) in profilResult.analyse_ia?.formations_recommandees || []" 
                  :key="i"
                  class="recommendation-card"
                >
                  🎓 {{ formation }}
                </div>
                <div v-if="!(profilResult.analyse_ia?.formations_recommandees || []).length" class="muted">Aucune recommandation</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Analyse CV -->
    <div v-if="activeTab === 'cv'" class="card">
      <div class="card-header">
          <h3>📄 Analyser un CV</h3>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label>Poste cible</label>
            <select v-model="cvForm.poste_id">
              <option value="">Sélectionnez un poste</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre || poste.nom }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
          <label>Contenu du CV</label>
          <textarea 
            v-model="cvForm.cv_text"
            rows="8"
            placeholder="Collez le contenu du CV ici ou décrivez le profil du candidat..."
          ></textarea>
        </div>

        <button 
          class="btn btn-primary"
          :disabled="!cvForm.poste_id || !cvForm.cv_text || analysing"
          @click="analyserCV"
        >
          {{ analysing ? '⏳ Analyse en cours...' : '🔍 Analyser le CV' }}
        </button>

        <!-- Résultat analyse CV -->
          <div v-if="cvResult" class="analysis-result cv-result">
            <div class="result-header">
              <div class="score-display" :class="getScoreClass(cvResult.score)">
                <span class="score-value">{{ cvResult.score }}%</span>
                <span class="score-label">Match</span>
              </div>
              <div class="result-info">
                <h4>Analyse pour : {{ normalizePosteTitle(cvResult.poste) }}</h4>
                <p>{{ getCompatibilityText(cvResult.score) }}</p>
              </div>
            </div>

          <div class="ai-analysis">
            <h5>🤖 Analyse IA</h5>
            <div class="analysis-text" v-html="formatAnalysis(cvResult.analyse)"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Candidats pour Poste -->
    <div v-if="activeTab === 'candidats'" class="card">
      <div class="card-header">
        <h3>👥 Meilleurs Candidats pour un Poste</h3>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Poste</label>
            <select v-model="candidatsForm.poste_id" @change="loadCandidats">
              <option value="">Sélectionnez un poste</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre || poste.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Nombre max</label>
            <select v-model="candidatsForm.limit" @change="loadCandidats">
              <option value="5">Top 5</option>
              <option value="10">Top 10</option>
              <option value="20">Top 20</option>
            </select>
          </div>
        </div>

        <div v-if="loadingCandidats" class="loading-state">
          <div class="spinner"></div>
          <p>Recherche des meilleurs profils...</p>
        </div>

        <div v-else-if="candidats.length > 0" class="candidates-list">
          <div 
            v-for="(candidat, index) in candidats" 
            :key="candidat.employe_id"
            class="candidate-card"
          >
            <div class="rank">{{ index + 1 }}</div>
            <div class="candidate-info">
              <h4>{{ candidat.employe_nom }}</h4>
              <p>{{ candidat.poste_actuel || 'N/A' }}</p>
            </div>
            <div class="score-badge" :class="getScoreClass(candidat.score)">
              {{ candidat.score }}%
            </div>
            <div class="competences-preview">
              <span 
                v-for="comp in (candidat.competences_matchees || []).slice(0, 3)" 
                :key="comp"
                class="comp-tag"
              >
                {{ comp }}
              </span>
            </div>
            <button class="btn btn-sm btn-outline" @click="viewEmploye(candidat.employe.id)">
              Voir profil
            </button>
          </div>
        </div>

        <div v-else-if="candidatsForm.poste_id" class="empty-state">
          <span>📭</span>
          <p>Aucun candidat trouvé pour ce poste</p>
        </div>
      </div>
    </div>

    <!-- Tab: Plan de Carrière -->
    <div v-if="activeTab === 'carriere'" class="card">
      <div class="card-header">
        <h3>🚀 Plan de Carrière IA</h3>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Employé</label>
            <select v-model="carriereForm.employe_id">
              <option value="">Sélectionnez un employé</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </div>
          <button 
            class="btn btn-primary"
            :disabled="!carriereForm.employe_id || loadingCarriere"
            @click="loadPlanCarriere"
          >
            {{ loadingCarriere ? '⏳ Génération...' : '🎯 Générer le plan' }}
          </button>
        </div>

        <div v-if="planCarriere" class="career-plan">
          <div class="plan-header">
            <h4>Plan de carrière pour {{ planCarriere.employe?.nom }}</h4>
            <p class="current-position">
              Poste actuel : {{ planCarriere.employe?.poste || 'N/A' }}
            </p>
          </div>

          <div v-if="planCarriere.parcours?.length" class="plan-section">
            <h5>📈 Parcours conseillé</h5>
            <ul>
              <li v-for="(etape, idx) in planCarriere.parcours" :key="idx">
                {{ etape.poste || etape.titre || etape.position || 'Étape' }} 
                <span v-if="etape.horizon"> - {{ etape.horizon }}</span>
              </li>
            </ul>
          </div>

          <div v-if="planCarriere.plan_action?.length" class="plan-section">
            <h5>🛠️ Plan d'action</h5>
            <ul>
              <li v-for="(action, idx) in planCarriere.plan_action" :key="idx">
                {{ action.action || action.objectif || action.action_plan }} 
                <span v-if="action.delai"> ({{ action.delai }})</span>
              </li>
            </ul>
          </div>

          <div v-if="planCarriere.conseil" class="plan-section">
            <h5>💡 Conseil personnalisé</h5>
            <p>{{ planCarriere.conseil }}</p>
          </div>

          <div v-if="!planCarriere.parcours?.length && !planCarriere.plan_action?.length && !planCarriere.conseil" class="plan-content" v-html="formatCareerPlan(planCarriere.planText || planCarriere.plan)"></div>

          <div v-if="planCarriere.postes_compatibles" class="compatible-positions">
            <h5>📌 Postes compatibles suggérés</h5>
            <div class="positions-grid">
              <div 
                v-for="poste in planCarriere.postes_compatibles.slice(0, 5)" 
                :key="poste.id || poste.poste_id || poste.titre"
                class="position-card"
              >
                <span class="position-title">{{ poste.titre }}</span>
                <span class="position-score">{{ poste.score }}%</span>
              </div>
              <div v-if="!planCarriere.postes_compatibles.length" class="muted">Aucun poste compatible proposé</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Suggestions Formations -->
    <div v-if="activeTab === 'formations'" class="card">
      <div class="card-header">
        <h3>📚 Suggestions de Formations IA</h3>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Employé</label>
            <select v-model="formationsForm.employe_id">
              <option value="">Sélectionnez un employé</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Poste cible (optionnel)</label>
            <select v-model="formationsForm.poste_id">
              <option value="">Aucun</option>
              <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                {{ poste.titre || poste.nom }}
              </option>
            </select>
          </div>
          <button 
            class="btn btn-primary"
            :disabled="!formationsForm.employe_id || loadingFormations"
            @click="loadSuggestionsFormations"
          >
            {{ loadingFormations ? '⏳...' : '💡 Suggérer' }}
          </button>
        </div>

        <div v-if="suggestionsFormations" class="formations-suggestions">
          <h4>Formations recommandées</h4>
          <div class="suggestions-list">
            <ul v-if="Array.isArray(suggestionsFormations.suggestions) && suggestionsFormations.suggestions.length">
              <li v-for="(s, idx) in suggestionsFormations.suggestions" :key="idx">{{ s }}</li>
            </ul>
            <div v-else-if="suggestionsFormations.formations?.length">
              <div 
                v-for="(item, idx) in suggestionsFormations.formations" 
                :key="item.formation?.id || idx"
                class="formation-card"
              >
                <div class="formation-header">
                  <div>
                    <h5>{{ item.formation?.titre || 'Formation' }}</h5>
                    <p class="muted">{{ item.formation?.code }}</p>
                  </div>
                  <span class="badge">{{ item.priorite || 'priorité' }}</span>
                </div>
                <p class="muted">{{ item.formation?.description }}</p>
                <div class="formation-meta">
                  <span>{{ item.formation?.duree_heures || '?' }}h • {{ item.formation?.type }}</span>
                  <span v-if="item.pour_competence">Cible : {{ item.pour_competence }}</span>
                  <span v-if="item.ecart_comble">Écart comblé : {{ item.ecart_comble }}</span>
                </div>
                <div v-if="item.raison" class="muted">{{ item.raison }}</div>
              </div>
            </div>
            <div v-else class="muted">Aucune suggestion reçue</div>
          </div>
          
          <div v-if="suggestionsFormations.ecarts_competences?.length" class="gaps-section">
            <h5>📉 Écarts de compétences identifiés</h5>
            <div class="gaps-list">
              <span 
                v-for="gap in suggestionsFormations.ecarts_competences" 
                :key="gap"
                class="gap-tag"
              >
                {{ gap }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/**
 * @remarks Feature prête mais désactivée temporairement.
 * @deprecated Activation ultérieure (phase 2).
 * 
 * Vue pour le matching IA avancé
 * Features: Matching automatique profil/poste, Suggestion de formations, 
 * Recommandation de candidats (matching CV ↔ poste)
 */
import aiMatchingService from '@/services/aiMatchingService'
import api from '@/services/api'

export default {
  name: 'AIMatchingView',
  data() {
    return {
      activeTab: 'profil',
      tabs: [
        { key: 'profil', icon: '📊', label: 'Analyse Profil' },
        // { key: 'cv', icon: '📄', label: 'Analyse CV' },
        { key: 'candidats', icon: '👥', label: 'Candidats' },
        // { key: 'carriere', icon: '🚀', label: 'Plan Carrière' },
        { key: 'formations', icon: '📚', label: 'Formations' }
      ],
      employes: [],
      postes: [],
      
      // Analyse profil
      profilForm: { employe_id: '', poste_id: '' },
      profilResult: null,
      analysing: false,
      
      // Analyse CV
      cvForm: { poste_id: '', cv_text: '' },
      cvResult: null,
      
      // Candidats
      candidatsForm: { poste_id: '', limit: 10 },
      candidats: [],
      loadingCandidats: false,
      
      // Plan carrière
      carriereForm: { employe_id: '' },
      planCarriere: null,
      loadingCarriere: false,
      
      // Suggestions formations
      formationsForm: { employe_id: '', poste_id: '' },
      suggestionsFormations: null,
      loadingFormations: false
    }
  },
  mounted() {
    this.loadEmployes()
    this.loadPostes()
  },
  methods: {
    normalizePosteTitle(poste) {
      if (!poste) return 'Poste';
      return poste.titre || poste.nom || poste.poste_titre || 'Poste';
    },
    normalizeEmployeName(emp) {
      if (!emp) return 'Employé';
      if (typeof emp === 'string') return emp;
      return `${emp.nom || ''} ${emp.prenom || ''}`.trim() || emp.employe_nom || 'Employé';
    },
    normalizeList(val, fallback = []) {
      if (Array.isArray(val)) {
        return val.filter(Boolean);
      }
      if (typeof val === 'string') {
        return val
          .split(/[\n;•-]+/)
          .map(v => v.trim())
          .filter(Boolean);
      }
      return fallback;
    },
    async loadEmployes() {
      try {
        const response = await api.get('/employes')
        this.employes = response.data?.data || response.data || []
      } catch (error) {
        console.error('Erreur chargement employés:', error)
      }
    },
    async loadPostes() {
      try {
        const response = await api.get('/postes')
        this.postes = response.data?.data || response.data || []
      } catch (error) {
        console.error('Erreur chargement postes:', error)
      }
    },
    async analyserProfil() {
      this.analysing = true
      this.profilResult = null
      try {
        const response = await aiMatchingService.analyserProfil(
          this.profilForm.employe_id,
          this.profilForm.poste_id
        )
        const res = response.data || {}
        const analyseIa = res.analyse_ia || {}
        const matching = res.matching_classique || {}
        const employeObj = this.employes.find(e => e.id === this.profilForm.employe_id) || res.employe
        const posteObj = this.postes.find(p => p.id === this.profilForm.poste_id) || res.poste
        const pointsForts = this.normalizeList(
          analyseIa.points_forts,
          matching.competences_ok?.map(c => `${c.nom} (niveau ${c.niveau_employe}/${c.niveau_requis})`) || []
        )
        const pointsAmelioration = this.normalizeList(
          analyseIa.points_amelioration ?? analyseIa.points_faibles ?? analyseIa.points_fort,
          [
            ...(matching.competences_manquantes || []).map(c => `${c.nom} à acquérir (niv ${c.niveau_requis})`),
            ...(matching.competences_insuffisantes || []).map(c => `${c.nom} à renforcer (niv ${c.niveau_employe}/${c.niveau_requis})`)
          ]
        )
        let formationsRec = this.normalizeList(
          analyseIa.formations_recommandees ?? analyseIa.recommandations,
          []
        )
        if (!formationsRec.length && matching.competences_manquantes?.length) {
          formationsRec = matching.competences_manquantes.map(c => `Formation sur ${c.nom} (objectif niveau ${c.niveau_requis})`)
        }

        this.profilResult = {
          score_global: res.score_combine ?? matching.score_global ?? res.score ?? 0,
          employe: employeObj,
          poste: posteObj,
          analyse_ia: {
            points_forts: pointsForts,
            points_amelioration: pointsAmelioration,
            formations_recommandees: formationsRec,
            commentaire_global: analyseIa.commentaire_global || analyseIa.commentaire
          },
          matching_classique: matching
        }
      } catch (error) {
        console.error('Erreur analyse profil:', error)
        alert('Erreur lors de l\'analyse')
      } finally {
        this.analysing = false
      }
    },
    async analyserCV() {
      this.analysing = true
      this.cvResult = null
      try {
        const response = await aiMatchingService.analyserCV(
          this.cvForm.cv_text,
          this.cvForm.poste_id
        )
        const res = response.data || {}
        const posteObj = this.postes.find(p => p.id === this.cvForm.poste_id)
        this.cvResult = {
          ...res,
          poste: posteObj || res.poste,
          score: res.score ?? res.score_compatibilite ?? res.score_global ?? 0,
          analyse: res.analyse || res.commentaire || res.recommandation || res.analysis
        }
      } catch (error) {
        console.error('Erreur analyse CV:', error)
        alert('Erreur lors de l\'analyse du CV')
      } finally {
        this.analysing = false
      }
    },
    async loadCandidats() {
      if (!this.candidatsForm.poste_id) return
      
      this.loadingCandidats = true
      this.candidats = []
      try {
        const response = await aiMatchingService.candidatsPourPoste(
          this.candidatsForm.poste_id,
          this.candidatsForm.limit
        )
        const raw = response.data?.candidats || response.data?.candidats_ia || response.data?.autres_candidats || response.data || []
        this.candidats = raw.map(c => {
          const employe = c.employe || {}
          const compat = c.compatibilite || {}
          return {
            ...c,
            employe_nom: c.employe_nom || this.normalizeEmployeName(employe),
            poste_actuel: c.poste_actuel || employe.poste_actuel || employe.poste || employe.poste_nom,
            score: c.score ?? c.score_global ?? compat.score_global ?? 0,
            competences_matchees: this.normalizeList(
              c.competences_matchees || compat.competences_ok?.map(cc => cc.nom),
              []
            )
          }
        })
      } catch (error) {
        console.error('Erreur chargement candidats:', error)
      } finally {
        this.loadingCandidats = false
      }
    },
    async loadPlanCarriere() {
      this.loadingCarriere = true
      this.planCarriere = null
      try {
        const response = await aiMatchingService.planCarriere(this.carriereForm.employe_id)
        const res = response.data || {}
        const plan = res.plan_carriere || res.plan || ''
        const postesCompatibles = plan?.postes_cibles || plan?.postes_compatibles || res.postes_compatibles || []
        const parcours = Array.isArray(plan?.parcours) ? plan.parcours : []
        const planAction = Array.isArray(plan?.plan_action) ? plan.plan_action : []
        const conseil = plan?.conseil_personnalise || plan?.conseil || ''
        this.planCarriere = {
          employe: res.employe,
          planText: typeof plan === 'string' ? plan : JSON.stringify(plan, null, 2),
          parcours,
          plan_action: planAction,
          conseil,
          postes_compatibles: postesCompatibles.map(p => ({
            id: p.poste_id || p.id,
            titre: p.poste || p.titre || p.poste_titre || p.nom || 'Poste',
            score: p.score || p.score_compatibilite || ''
          }))
        }
      } catch (error) {
        console.error('Erreur plan carrière:', error)
        alert('Erreur lors de la génération du plan')
      } finally {
        this.loadingCarriere = false
      }
    },
    async loadSuggestionsFormations() {
      this.loadingFormations = true
      this.suggestionsFormations = null
      try {
        const response = await aiMatchingService.suggestionsFormations(
          this.formationsForm.employe_id,
          this.formationsForm.poste_id || null
        )
        const res = response.data || {}
        const formationsArray = Array.isArray(res) ? res : (Array.isArray(res.suggestions_classiques) ? res.suggestions_classiques : [])
        const suggestionsText = res.suggestions || res.recommandations || res.recommandation || res.analysis || []
        const suggestionsList = Array.isArray(suggestionsText) ? suggestionsText : this.normalizeList(suggestionsText, [])
        const formationsList = formationsArray
        this.suggestionsFormations = {
          suggestions: suggestionsList,
          formations: formationsList,
          ecarts_competences: this.normalizeList(res.ecarts_competences || res.gaps, [])
        }
      } catch (error) {
        console.error('Erreur suggestions formations:', error)
        alert('Erreur lors de la génération des suggestions')
      } finally {
        this.loadingFormations = false
      }
    },
    viewEmploye(id) {
      this.$router.push(`/employes/${id}`)
    },
    getScoreClass(score) {
      if (score >= 80) return 'excellent'
      if (score >= 60) return 'good'
      if (score >= 40) return 'medium'
      return 'low'
    },
    getCompatibilityText(score) {
      if (score >= 80) return 'Excellent match ! Profil très adapté.'
      if (score >= 60) return 'Bonne compatibilité avec quelques ajustements.'
      if (score >= 40) return 'Compatibilité moyenne, formation recommandée.'
      return 'Faible correspondance, reconversion nécessaire.'
    },
    formatAnalysis(text) {
      if (!text) return ''
      const value = typeof text === 'string' ? text : JSON.stringify(text, null, 2)
      return value.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    },
    formatCareerPlan(plan) {
      if (!plan) return ''
      const value = typeof plan === 'string' ? plan : JSON.stringify(plan, null, 2)
      return value.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    },
    formatSuggestions(suggestions) {
      if (!suggestions) return ''
      const value = typeof suggestions === 'string' ? suggestions : JSON.stringify(suggestions, null, 2)
      return value.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    }
  }
}
</script>

<style scoped>
.ai-matching-view {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.page-header h1 {
  font-size: 28px;
  color: #1e293b;
  margin: 0;
}

.subtitle {
  color: #64748b;
  margin-top: 5px;
}

/* Tabs */
.tabs-container {
  display: flex;
  gap: 10px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.tab-btn {
  padding: 12px 20px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #f8fafc;
}

.tab-btn.active {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

/* Cards */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}

.card-body {
  padding: 25px;
}

/* Form Elements */
.form-row {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
  min-width: 200px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.form-group select,
.form-group input,
.form-group textarea {
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
}

.form-group textarea {
  resize: vertical;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-primary:disabled {
  background: #94a3b8;
  cursor: not-allowed;
}

.btn-outline {
  background: transparent;
  border: 1px solid #e5e7eb;
  color: #64748b;
}

.btn-outline:hover {
  background: #f8fafc;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

/* Analysis Result */
.analysis-result {
  margin-top: 30px;
  padding-top: 25px;
  border-top: 1px solid #e5e7eb;
}

.result-header {
  display: flex;
  align-items: center;
  gap: 25px;
  margin-bottom: 25px;
}

.score-display {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.score-display.excellent { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; }
.score-display.good { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.score-display.medium { background: linear-gradient(135deg, #eab308, #ca8a04); color: white; }
.score-display.low { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

.score-value {
  font-size: 28px;
  font-weight: bold;
}

.score-label {
  font-size: 12px;
  opacity: 0.9;
}

.result-info h4 {
  margin: 0 0 8px 0;
  font-size: 18px;
  color: #1e293b;
}

.compatibility-text {
  margin: 0;
  color: #64748b;
}

/* Result Sections */
.result-sections {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.section {
  background: #f8fafc;
  border-radius: 10px;
  padding: 20px;
}

.section.full-width {
  grid-column: 1 / -1;
}

.section h5 {
  margin: 0 0 15px 0;
  font-size: 14px;
  color: #374151;
}

.section ul {
  margin: 0;
  padding-left: 20px;
}

.section li {
  margin-bottom: 8px;
  color: #4b5563;
  font-size: 14px;
}

.recommendations-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.recommendation-card {
  background: white;
  padding: 10px 15px;
  border-radius: 8px;
  font-size: 14px;
  color: #374151;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.muted {
  color: #94a3b8;
  font-size: 13px;
}

.formation-card {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 10px;
  background: #f8fafc;
}

.formation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.formation-header h5 {
  margin: 0;
  font-size: 15px;
  color: #1e293b;
}

.badge {
  padding: 4px 8px;
  border-radius: 8px;
  background: #e0e7ff;
  color: #4f46e5;
  font-size: 12px;
  text-transform: capitalize;
}

.formation-meta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 13px;
  color: #475569;
}
/* AI Analysis */
.ai-analysis {
  background: #f0f9ff;
  border-radius: 10px;
  padding: 20px;
  margin-top: 20px;
}

.ai-analysis h5 {
  margin: 0 0 15px 0;
  color: #0369a1;
}

.analysis-text {
  font-size: 14px;
  line-height: 1.7;
  color: #334155;
}

/* Candidates List */
.candidates-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.candidate-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8fafc;
  border-radius: 10px;
  transition: background 0.2s;
}

.candidate-card:hover {
  background: #f1f5f9;
}

.rank {
  width: 30px;
  height: 30px;
  background: #2563eb;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}

.candidate-info {
  flex: 1;
}

.candidate-info h4 {
  margin: 0;
  font-size: 15px;
  color: #1e293b;
}

.candidate-info p {
  margin: 3px 0 0;
  font-size: 13px;
  color: #64748b;
}

.score-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: bold;
  font-size: 14px;
}

.score-badge.excellent { background: #dcfce7; color: #16a34a; }
.score-badge.good { background: #dbeafe; color: #2563eb; }
.score-badge.medium { background: #fef9c3; color: #ca8a04; }
.score-badge.low { background: #fee2e2; color: #dc2626; }

.competences-preview {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.comp-tag {
  background: #e0e7ff;
  color: #4f46e5;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 12px;
}

/* Career Plan */
.career-plan {
  margin-top: 25px;
}

.plan-header {
  margin-bottom: 20px;
}

.plan-header h4 {
  margin: 0 0 5px;
  color: #1e293b;
}

.current-position {
  color: #64748b;
  margin: 0;
}

.plan-content {
  background: #f8fafc;
  padding: 20px;
  border-radius: 10px;
  font-size: 14px;
  line-height: 1.8;
  color: #334155;
}

.compatible-positions {
  margin-top: 25px;
}

.compatible-positions h5 {
  margin: 0 0 15px;
  color: #374151;
}

.positions-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.position-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  background: white;
  padding: 12px 18px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.position-title {
  font-size: 14px;
  color: #374151;
}

.position-score {
  font-weight: bold;
  color: #2563eb;
}

/* Formations Suggestions */
.formations-suggestions {
  margin-top: 25px;
}

.formations-suggestions h4 {
  margin: 0 0 15px;
  color: #1e293b;
}

.suggestions-list {
  background: #f0fdf4;
  padding: 20px;
  border-radius: 10px;
  font-size: 14px;
  line-height: 1.8;
  color: #334155;
}

.gaps-section {
  margin-top: 20px;
}

.gaps-section h5 {
  margin: 0 0 12px;
  color: #374151;
}

.gaps-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.gap-tag {
  background: #fef2f2;
  color: #dc2626;
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 13px;
}

/* Loading & Empty States */
.loading-state,
.empty-state {
  padding: 50px;
  text-align: center;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state span {
  font-size: 48px;
  display: block;
  margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
  }
  
  .result-sections {
    grid-template-columns: 1fr;
  }
  
  .result-header {
    flex-direction: column;
    text-align: center;
  }
  
  .candidate-card {
    flex-wrap: wrap;
  }
}
</style>
