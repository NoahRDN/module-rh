<template>
  <div class="evaluation-form">
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Nouvelle évaluation</h1>
          <span>Évaluation mensuelle de performance</span>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="form">
        <!-- Sélection employé et période -->
        <div class="form-row">
          <div class="form-group">
            <label>Employé *</label>
            <select v-model="form.employe_id" required>
              <option value="">Sélectionner un employé</option>
              <option v-for="emp in employes" :key="emp.id" :value="emp.id">
                {{ emp.nom }} {{ emp.prenom }} ({{ emp.matricule }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Période *</label>
            <input type="month" v-model="form.periode" required />
          </div>
        </div>

        <!-- Critères d'évaluation -->
        <div class="section">
          <h2>Critères d'évaluation</h2>
          <p class="hint">Notez chaque critère de 0 à 100%</p>

          <div class="criteres-grid">
            <div v-for="critere in criteres" :key="critere.id" class="critere-card">
              <div class="critere-header">
                <span class="critere-libelle">{{ critere.libelle }}</span>
                <span class="critere-poids">Poids: {{ critere.poids }}%</span>
              </div>
              <p class="critere-desc">{{ critere.description }}</p>
              
              <div class="note-input">
                <input 
                  type="range" 
                  min="0" 
                  max="100" 
                  v-model.number="notes[critere.id].note"
                  :class="getScoreClass(notes[critere.id].note)"
                />
                <span class="note-value" :class="getScoreClass(notes[critere.id].note)">
                  {{ notes[critere.id].note }}%
                </span>
              </div>

              <textarea 
                v-model="notes[critere.id].commentaire"
                placeholder="Commentaire (optionnel)"
                rows="2"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Score prévu -->
        <div class="score-preview card">
          <div class="score-label">Score global estimé</div>
          <div class="score-value" :class="getScoreClass(scoreEstime)">
            {{ scoreEstime }}%
          </div>
          <div class="score-niveau">{{ getNiveauLabel(scoreEstime) }}</div>
        </div>

        <!-- Commentaires généraux -->
        <div class="section">
          <h2>Appréciation générale</h2>
          
          <div class="form-group">
            <label>Points forts</label>
            <textarea 
              v-model="form.points_forts"
              placeholder="Points forts identifiés chez l'employé..."
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Axes d'amélioration</label>
            <textarea 
              v-model="form.axes_amelioration"
              placeholder="Points à améliorer..."
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Objectifs pour la prochaine période</label>
            <textarea 
              v-model="form.objectifs"
              placeholder="Objectifs à atteindre..."
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Commentaire général</label>
            <textarea 
              v-model="form.commentaire_general"
              placeholder="Synthèse de l'évaluation..."
              rows="3"
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="$router.back()">
            Annuler
          </button>
          <button type="submit" class="btn btn-secondary" @click="form.statut = 'brouillon'">
            💾 Enregistrer brouillon
          </button>
          <button type="submit" class="btn btn-primary" @click="form.statut = 'valide'">
            ✓ Valider l'évaluation
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const employes = ref([])
const criteres = ref([])
const notes = ref({})
const form = ref({
  employe_id: '',
  periode: new Date().toISOString().slice(0, 7),
  points_forts: '',
  axes_amelioration: '',
  objectifs: '',
  commentaire_general: '',
  statut: 'brouillon'
})

const scoreEstime = computed(() => {
  if (!Object.keys(notes.value).length) return 0
  
  let totalPoids = 0
  let totalScore = 0
  
  for (const critereId in notes.value) {
    const critere = criteres.value.find(c => c.id == critereId)
    if (critere) {
      totalPoids += critere.poids
      totalScore += notes.value[critereId].note * critere.poids
    }
  }
  
  return totalPoids > 0 ? Math.round(totalScore / totalPoids) : 0
})

const loadData = async () => {
  try {
    const [empRes, critRes] = await Promise.all([
      api.get('/v1/employes'),
      api.get('/v1/criteres-evaluation')
    ])
    
    employes.value = empRes.data.data || []
    criteres.value = critRes.data.data || []
    
    // Initialiser les notes
    criteres.value.forEach(c => {
      notes.value[c.id] = { note: 50, commentaire: '' }
    })
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const submitForm = async () => {
  try {
    const payload = {
      ...form.value,
      notes: Object.entries(notes.value).map(([critere_id, data]) => ({
        critere_id: parseInt(critere_id),
        note: data.note,
        commentaire: data.commentaire
      }))
    }
    
    await api.post('/v1/evaluations', payload)
    router.push('/performances')
  } catch (e) {
    console.error('Erreur:', e)
    alert(e.response?.data?.message || 'Erreur lors de l\'enregistrement')
  }
}

const getScoreClass = (score) => {
  if (score >= 90) return 'excellent'
  if (score >= 75) return 'good'
  if (score >= 60) return 'average'
  if (score >= 50) return 'satisfactory'
  return 'low'
}

const getNiveauLabel = (score) => {
  if (score >= 90) return 'Excellent'
  if (score >= 75) return 'Très bien'
  if (score >= 60) return 'Bien'
  if (score >= 50) return 'Satisfaisant'
  if (score >= 40) return 'À améliorer'
  return 'Insuffisant'
}

onMounted(loadData)
</script>

<style scoped>
.evaluation-form {
  max-width: 900px;
  margin: 0 auto;
}

.form {
  margin-top: 24px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.form-group label {
  font-weight: 600;
  font-size: 14px;
}

.form-group select,
.form-group input,
.form-group textarea {
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
}

.section {
  margin: 32px 0;
}

.section h2 {
  font-size: 18px;
  margin: 0 0 8px;
}

.section .hint {
  color: var(--muted);
  font-size: 13px;
  margin: 0 0 16px;
}

.criteres-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

.critere-card {
  padding: 20px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 12px;
  border: 1px solid var(--border);
}

.critere-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.critere-libelle {
  font-weight: 700;
  font-size: 15px;
}

.critere-poids {
  font-size: 12px;
  color: var(--accent);
  padding: 3px 8px;
  background: rgba(34, 197, 94, 0.1);
  border-radius: 10px;
}

.critere-desc {
  font-size: 12px;
  color: var(--muted);
  margin: 0 0 16px;
}

.note-input {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.note-input input[type="range"] {
  flex: 1;
  height: 8px;
  -webkit-appearance: none;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  outline: none;
}

.note-input input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--accent);
  cursor: pointer;
}

.note-input input[type="range"].excellent::-webkit-slider-thumb { background: #22c55e; }
.note-input input[type="range"].good::-webkit-slider-thumb { background: #3b82f6; }
.note-input input[type="range"].average::-webkit-slider-thumb { background: #06b6d4; }
.note-input input[type="range"].satisfactory::-webkit-slider-thumb { background: #f59e0b; }
.note-input input[type="range"].low::-webkit-slider-thumb { background: #ef4444; }

.note-value {
  min-width: 50px;
  text-align: center;
  font-weight: 700;
  font-size: 16px;
}

.note-value.excellent { color: #22c55e; }
.note-value.good { color: #3b82f6; }
.note-value.average { color: #06b6d4; }
.note-value.satisfactory { color: #f59e0b; }
.note-value.low { color: #ef4444; }

.critere-card textarea {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 13px;
  resize: none;
}

.score-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px;
  text-align: center;
  background: rgba(34, 197, 94, 0.05);
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.score-preview .score-label {
  font-size: 14px;
  color: var(--muted);
  margin-bottom: 8px;
}

.score-preview .score-value {
  font-size: 48px;
  font-weight: 800;
}

.score-preview .score-value.excellent { color: #22c55e; }
.score-preview .score-value.good { color: #3b82f6; }
.score-preview .score-value.average { color: #06b6d4; }
.score-preview .score-value.satisfactory { color: #f59e0b; }
.score-preview .score-value.low { color: #ef4444; }

.score-preview .score-niveau {
  font-size: 16px;
  font-weight: 600;
  margin-top: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>
