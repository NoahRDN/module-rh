<template>
  <div class="profil-view">
    <div class="page-header">
      <h1>👤 Mon Profil</h1>
    </div>

    <div v-if="loading" class="loading">Chargement...</div>

    <div v-else class="profil-content">
      <!-- Carte profil principal -->
      <div class="card profil-card">
        <div class="profil-header">
          <div class="avatar">
            {{ getInitiales }}
          </div>
          <div class="profil-info">
            <h2>{{ profil.nom }} {{ profil.prenom }}</h2>
            <p class="poste">{{ profil.poste?.titre || 'Non assigné' }}</p>
            <p class="departement">{{ profil.departement?.nom }}</p>
          </div>
          <button class="btn btn-primary" @click="showEditModal = true">
            ✏️ Modifier
          </button>
        </div>

        <div class="profil-details">
          <div class="detail-group">
            <h4>Informations personnelles</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Email</span>
                <span class="value">{{ profil.email }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Téléphone</span>
                <span class="value">{{ profil.telephone || '-' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Date de naissance</span>
                <span class="value">{{ formatDate(profil.date_naissance) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Adresse</span>
                <span class="value">{{ profil.adresse || '-' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Situation familiale</span>
                <span class="value">{{ profil.situation_familiale || '-' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Nationalité</span>
                <span class="value">{{ profil.nationalite || '-' }}</span>
              </div>
            </div>
          </div>

          <div class="detail-group">
            <h4>Informations professionnelles</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Matricule</span>
                <span class="value">{{ profil.matricule }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Date d'embauche</span>
                <span class="value">{{ formatDate(profil.date_embauche) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Ancienneté</span>
                <span class="value">{{ getAnciennete }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Type de contrat</span>
                <span class="value">{{ profil.contrat_actuel?.type || '-' }}</span>
              </div>
            </div>
          </div>

          <div class="detail-group">
            <h4>Contact d'urgence</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Nom</span>
                <span class="value">{{ profil.contact_urgence_nom || '-' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Téléphone</span>
                <span class="value">{{ profil.contact_urgence_telephone || '-' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Relation</span>
                <span class="value">{{ profil.contact_urgence_relation || '-' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Changement de mot de passe -->
      <div class="card password-card">
        <h3>🔐 Sécurité</h3>
        <form @submit.prevent="changerMotDePasse" class="password-form">
          <div class="form-group">
            <label>Mot de passe actuel</label>
            <input type="password" v-model="passwordForm.current_password" required />
          </div>
          <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input type="password" v-model="passwordForm.new_password" required minlength="8" />
          </div>
          <div class="form-group">
            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" v-model="passwordForm.new_password_confirmation" required />
          </div>
          <button type="submit" class="btn btn-primary" :disabled="savingPassword">
            {{ savingPassword ? 'Modification...' : 'Changer le mot de passe' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Modal Edition -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Modifier mes informations</h2>
          <button class="btn btn-icon" @click="showEditModal = false">✕</button>
        </div>
        <form @submit.prevent="saveProfil" class="modal-body">
          <p class="info-note">
            ℹ️ Certaines informations ne sont modifiables que par les RH.
          </p>

          <div class="form-section">
            <h4>Informations personnelles</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" v-model="editForm.telephone" />
              </div>
              <div class="form-group">
                <label>Email personnel</label>
                <input type="email" v-model="editForm.email_personnel" />
              </div>
            </div>
            <div class="form-group">
              <label>Adresse</label>
              <textarea v-model="editForm.adresse" rows="2"></textarea>
            </div>
          </div>

          <div class="form-section">
            <h4>Contact d'urgence</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Nom</label>
                <input type="text" v-model="editForm.contact_urgence_nom" />
              </div>
              <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" v-model="editForm.contact_urgence_telephone" />
              </div>
            </div>
            <div class="form-group">
              <label>Relation</label>
              <select v-model="editForm.contact_urgence_relation">
                <option value="">Sélectionner...</option>
                <option value="conjoint">Conjoint(e)</option>
                <option value="parent">Parent</option>
                <option value="enfant">Enfant</option>
                <option value="frere_soeur">Frère/Sœur</option>
                <option value="ami">Ami(e)</option>
                <option value="autre">Autre</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Annuler</button>
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
import selfServiceService from '../../services/selfServiceService'

const loading = ref(true)
const saving = ref(false)
const savingPassword = ref(false)
const profil = ref({})
const showEditModal = ref(false)

const editForm = ref({
  telephone: '',
  email_personnel: '',
  adresse: '',
  contact_urgence_nom: '',
  contact_urgence_telephone: '',
  contact_urgence_relation: ''
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// Computed
const getInitiales = computed(() => {
  const nom = profil.value.nom || ''
  const prenom = profil.value.prenom || ''
  return (prenom[0] || '') + (nom[0] || '')
})

const getAnciennete = computed(() => {
  if (!profil.value.date_embauche) return '-'
  const embauche = new Date(profil.value.date_embauche)
  const now = new Date()
  const years = Math.floor((now - embauche) / (365.25 * 24 * 60 * 60 * 1000))
  const months = Math.floor(((now - embauche) % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000))
  
  if (years > 0) return `${years} an${years > 1 ? 's' : ''} ${months} mois`
  return `${months} mois`
})

// Helpers
const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

// Load
const loadProfil = async () => {
  loading.value = true
  try {
    const res = await selfServiceService.getProfil()
    profil.value = res.data.data || res.data
    
    // Préremplir le formulaire d'édition
    editForm.value = {
      telephone: profil.value.telephone || '',
      email_personnel: profil.value.email_personnel || '',
      adresse: profil.value.adresse || '',
      contact_urgence_nom: profil.value.contact_urgence_nom || '',
      contact_urgence_telephone: profil.value.contact_urgence_telephone || '',
      contact_urgence_relation: profil.value.contact_urgence_relation || ''
    }
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

// Save profil
const saveProfil = async () => {
  saving.value = true
  try {
    await selfServiceService.updateProfil(editForm.value)
    showEditModal.value = false
    await loadProfil()
    alert('Profil mis à jour avec succès!')
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

// Change password
const changerMotDePasse = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    alert('Les mots de passe ne correspondent pas')
    return
  }
  
  savingPassword.value = true
  try {
    await selfServiceService.changerMotDePasse(passwordForm.value)
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
    alert('Mot de passe modifié avec succès!')
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    savingPassword.value = false
  }
}

onMounted(loadProfil)
</script>

<style scoped>
.profil-view {
  padding: 20px;
  max-width: 1000px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 25px;
}

.page-header h1 {
  margin: 0;
}

.loading {
  text-align: center;
  padding: 50px;
  color: #666;
}

.profil-content {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

/* Profil card */
.profil-card {
  padding: 25px;
}

.profil-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
  margin-bottom: 25px;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  font-weight: bold;
}

.profil-info {
  flex-grow: 1;
}

.profil-info h2 {
  margin: 0 0 5px 0;
}

.profil-info .poste {
  margin: 0;
  color: #667eea;
  font-weight: 500;
}

.profil-info .departement {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

/* Detail groups */
.profil-details {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.detail-group h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 1rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.detail-item .label {
  font-size: 0.8rem;
  color: #888;
}

.detail-item .value {
  font-weight: 500;
}

/* Password card */
.password-card {
  padding: 25px;
}

.password-card h3 {
  margin: 0 0 20px 0;
}

.password-form {
  max-width: 400px;
}

.password-form .form-group {
  margin-bottom: 15px;
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
  max-width: 600px;
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

.info-note {
  background: #e3f2fd;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 0.9rem;
}

.form-section {
  margin-bottom: 25px;
}

.form-section h4 {
  margin: 0 0 15px 0;
  font-size: 1rem;
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
  font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
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
