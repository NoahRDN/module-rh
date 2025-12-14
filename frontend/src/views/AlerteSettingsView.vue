<template>
  <div class="alerte-settings">
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Configuration des alertes</h1>
          <span>Personnalisez les seuils et paramètres des alertes RH</span>
        </div>
      </div>

      <div class="settings-list">
        <div v-for="setting in settings" :key="setting.id" class="setting-card" :class="{ inactive: !setting.actif }">
          <div class="setting-header">
            <div class="setting-info">
              <div class="setting-status">
                <label class="toggle">
                  <input type="checkbox" v-model="setting.actif" @change="updateSetting(setting)" />
                  <span class="slider"></span>
                </label>
              </div>
              <div>
                <h3>{{ setting.libelle }}</h3>
                <p>{{ setting.description }}</p>
              </div>
            </div>
            <span class="niveau-badge" :class="'niveau-' + setting.niveau">
              {{ setting.niveau }}
            </span>
          </div>

          <div class="setting-params" v-if="setting.actif">
            <div class="param-group" v-if="setting.seuil_jours !== null">
              <label>Seuil (jours)</label>
              <input 
                type="number" 
                v-model.number="setting.seuil_jours" 
                min="1"
                @blur="updateSetting(setting)"
              />
              <span class="param-hint">
                {{ getSeuilJoursHint(setting.code) }}
              </span>
            </div>

            <div class="param-group" v-if="setting.seuil_nombre !== null">
              <label>Seuil (nombre)</label>
              <input 
                type="number" 
                v-model.number="setting.seuil_nombre" 
                min="1"
                @blur="updateSetting(setting)"
              />
              <span class="param-hint">
                {{ getSeuilNombreHint(setting.code) }}
              </span>
            </div>

            <div class="param-group" v-if="setting.periode_jours !== null">
              <label>Période d'analyse (jours)</label>
              <input 
                type="number" 
                v-model.number="setting.periode_jours" 
                min="1"
                @blur="updateSetting(setting)"
              />
              <span class="param-hint">Fenêtre de temps pour le calcul</span>
            </div>

            <div class="param-group">
              <label>Niveau de criticité</label>
              <select v-model="setting.niveau" @change="updateSetting(setting)">
                <option value="info">Information</option>
                <option value="warning">Avertissement</option>
                <option value="danger">Critique</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Aperçu des alertes actives -->
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Aperçu des alertes actives</h1>
          <span>{{ alertes.length }} alerte(s) détectée(s) avec la configuration actuelle</span>
        </div>
        <button class="btn btn-secondary" @click="loadAlertes">
          🔄 Actualiser
        </button>
      </div>

      <div class="alertes-preview" v-if="alertes.length">
        <div 
          v-for="(alerte, index) in alertes.slice(0, 10)" 
          :key="index"
          class="alerte-item"
          :class="'alerte-' + alerte.level"
        >
          <span class="alerte-icon">
            {{ alerte.level === 'danger' ? '🚨' : alerte.level === 'warning' ? '⚠️' : 'ℹ️' }}
          </span>
          <span class="alerte-message">{{ alerte.message }}</span>
          <span class="alerte-type">{{ formatType(alerte.type) }}</span>
        </div>
      </div>

      <div class="empty-state" v-else>
        <p>🎉 Aucune alerte active avec la configuration actuelle</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const settings = ref([])
const alertes = ref([])

const loadSettings = async () => {
  try {
    const res = await api.get('/v1/alerte-settings')
    settings.value = res.data.data || []
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const loadAlertes = async () => {
  try {
    const res = await api.get('/v1/alertes')
    alertes.value = res.data.data || []
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const updateSetting = async (setting) => {
  try {
    await api.put(`/v1/alerte-settings/${setting.id}`, {
      actif: setting.actif,
      seuil_jours: setting.seuil_jours,
      seuil_nombre: setting.seuil_nombre,
      periode_jours: setting.periode_jours,
      niveau: setting.niveau,
    })
    // Recharger les alertes pour voir l'impact
    loadAlertes()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const getSeuilJoursHint = (code) => {
  const hints = {
    'fin_contrat': 'Jours avant expiration du contrat',
    'conge_en_attente': 'Délai d\'attente en jours (converti en heures)',
    'conge_proche': 'Jours avant début du congé',
  }
  return hints[code] || 'Nombre de jours'
}

const getSeuilNombreHint = (code) => {
  const hints = {
    'conges_non_pris': 'Nombre minimum de jours non pris',
    'absences_maladie': 'Nombre d\'absences déclenchant l\'alerte',
    'absences_exceptionnelles': 'Nombre de congés exceptionnels',
  }
  return hints[code] || 'Seuil numérique'
}

const formatType = (type) => {
  const types = {
    'fin_contrat': 'Contrat',
    'conges_non_pris': 'Congés',
    'absences_maladie': 'Maladie',
    'absences_exceptionnelles': 'Absences',
    'conge_en_attente': 'Demande',
    'conge_proche': 'Congé urgent'
  }
  return types[type] || type
}

onMounted(() => {
  loadSettings()
  loadAlertes()
})
</script>

<style scoped>
.alerte-settings {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.settings-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 20px;
}

.setting-card {
  padding: 20px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 12px;
  border: 1px solid var(--border);
  transition: opacity 0.2s;
}

.setting-card.inactive {
  opacity: 0.5;
}

.setting-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.setting-info {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.setting-info h3 {
  margin: 0 0 4px;
  font-size: 16px;
}

.setting-info p {
  margin: 0;
  font-size: 13px;
  color: var(--muted);
}

/* Toggle switch */
.toggle {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.1);
  transition: 0.3s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #22c55e;
}

input:checked + .slider:before {
  transform: translateX(20px);
}

.niveau-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.niveau-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.niveau-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.niveau-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.setting-params {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.param-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.param-group label {
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
}

.param-group input,
.param-group select {
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 14px;
}

.param-hint {
  font-size: 11px;
  color: var(--muted);
}

/* Alertes preview */
.alertes-preview {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 16px;
}

.alerte-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.02);
}

.alerte-danger { border-left: 3px solid #ef4444; }
.alerte-warning { border-left: 3px solid #f59e0b; }
.alerte-info { border-left: 3px solid #3b82f6; }

.alerte-icon { font-size: 18px; }
.alerte-message { flex: 1; font-size: 13px; }
.alerte-type {
  font-size: 11px;
  padding: 3px 8px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
  color: var(--muted);
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--muted);
}
</style>
