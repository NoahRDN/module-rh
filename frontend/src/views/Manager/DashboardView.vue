<template>
  <div class="manager-dashboard">
    <!-- En-tête -->
    <div class="page-header">
      <h1>Portail Manager</h1>
      <p class="subtitle" v-if="dashboard.departement">
        {{ dashboard.departement.nom }}
      </p>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement du tableau de bord...</p>
    </div>

    <!-- Erreur accès -->
    <div v-else-if="error" class="error-state card">
      <div class="error-icon">🚫</div>
      <h3>Accès non autorisé</h3>
      <p>{{ error }}</p>
      <router-link to="/" class="btn btn-primary">Retour à l'accueil</router-link>
    </div>

    <template v-else>
      <!-- Cartes KPI -->
      <div class="kpi-grid">
        <div class="card kpi-card">
          <div class="kpi-icon">👥</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.equipe?.total || 0 }}</span>
            <span class="kpi-label">Membres de l'équipe</span>
          </div>
        </div>

        <div class="card kpi-card kpi-warning" @click="showPendingConges = true">
          <div class="kpi-icon">📋</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.demandes_en_attente?.conges || 0 }}</span>
            <span class="kpi-label">Congés à valider</span>
            <span class="kpi-meta" v-if="dashboard.demandes_en_attente?.conges > 0">
              Cliquez pour voir
            </span>
          </div>
        </div>

        <div class="card kpi-card kpi-info" @click="showPendingRH = true">
          <div class="kpi-icon">📝</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.demandes_en_attente?.rh || 0 }}</span>
            <span class="kpi-label">Demandes RH</span>
            <span class="kpi-meta" v-if="dashboard.demandes_en_attente?.rh > 0">
              Cliquez pour voir
            </span>
          </div>
        </div>

        <div class="card kpi-card">
          <div class="kpi-icon">📅</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.absences_mois?.total_jours_absences || 0 }}</span>
            <span class="kpi-label">Jours d'absence (mois)</span>
          </div>
        </div>

        <div class="card kpi-card">
          <div class="kpi-icon">⭐</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.performance?.note_moyenne || 'N/A' }}</span>
            <span class="kpi-label">Note moyenne équipe</span>
          </div>
        </div>

        <div class="card kpi-card">
          <div class="kpi-icon">📊</div>
          <div class="kpi-content">
            <span class="kpi-value">{{ dashboard.performance?.nombre_evaluations || 0 }}</span>
            <span class="kpi-label">Évaluations cette année</span>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="content-grid">
        <!-- Liste de l'équipe -->
        <div class="card team-card">
          <div class="card-header">
            <h3>👥 Mon équipe</h3>
          </div>
          <div class="team-list" v-if="dashboard.equipe?.employes?.length">
            <div 
              class="team-member" 
              v-for="employe in dashboard.equipe.employes" 
              :key="employe.id"
            >
              <div class="member-avatar">
                <img v-if="employe.photo" :src="employe.photo" :alt="employe.nom" />
                <span v-else class="avatar-placeholder">
                  {{ employe.prenom?.charAt(0) }}{{ employe.nom?.charAt(0) }}
                </span>
              </div>
              <div class="member-info">
                <span class="member-name">{{ employe.prenom }} {{ employe.nom }}</span>
                <span class="member-role">{{ employe.poste || 'Poste non défini' }}</span>
              </div>
            </div>
          </div>
          <div class="empty-state" v-else>
            <p>Aucun membre dans l'équipe</p>
          </div>
        </div>

        <!-- Demandes de congés en attente -->
        <div class="card requests-card">
          <div class="card-header">
            <h3>📋 Congés en attente de validation</h3>
            <button class="btn btn-sm" @click="loadDemandesConges">🔄</button>
          </div>
          <table class="table" v-if="demandesConges.length">
            <thead>
              <tr>
                <th>Employé</th>
                <th>Type</th>
                <th>Période</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="demande in demandesConges" :key="demande.id">
                <td>
                  <div class="request-employee">{{ demande.employe?.prenom }} {{ demande.employe?.nom }}</div>
                  <div class="muted">{{ demande.employe?.matricule || '—' }}</div>
                </td>
                <td>{{ demande.typeConge?.libelle || 'Congé' }}</td>
                <td class="muted">{{ formatDate(demande.date_debut) }} → {{ formatDate(demande.date_fin) }} ({{ demande.jours_demandes }} j)</td>
                <td><span class="statut-badge" :class="'statut-' + (demande.statut || 'en_attente')">{{ demande.statut || 'en_attente' }}</span></td>
                <td class="request-actions">
                  <template v-if="isPending(demande)">
                  <button 
                    class="btn btn-success btn-sm" 
                    @click="openValidateModal(demande)"
                    title="Valider"
                  >
                    ✓
                  </button>
                  <button 
                    class="btn btn-danger btn-sm" 
                    @click="openRejectModal(demande)"
                    title="Rejeter"
                  >
                    ✗
                  </button>
                  </template>
                  <span v-else class="muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="empty-state" v-else>
            <p>✅ Aucune demande en attente</p>
          </div>
          <router-link 
            v-if="demandesConges.length"
            to="/manager/demandes-conges" 
            class="see-all-link"
          >
            Voir toutes les demandes
          </router-link>
        </div>

        <!-- Performance de l'équipe -->
        <div class="card performance-card">
          <div class="card-header">
            <h3>⭐ Performance de l'équipe</h3>
          </div>
          <div class="performance-stats" v-if="dashboard.performance">
            <div class="perf-chart">
              <div class="perf-bar">
                <div 
                  class="perf-fill excellent" 
                  :style="{ width: (dashboard.performance.repartition_notes?.excellent / dashboard.equipe?.total * 100) + '%' }"
                ></div>
                <div 
                  class="perf-fill bon" 
                  :style="{ width: (dashboard.performance.repartition_notes?.bon / dashboard.equipe?.total * 100) + '%' }"
                ></div>
                <div 
                  class="perf-fill moyen" 
                  :style="{ width: (dashboard.performance.repartition_notes?.moyen / dashboard.equipe?.total * 100) + '%' }"
                ></div>
                <div 
                  class="perf-fill insuffisant" 
                  :style="{ width: (dashboard.performance.repartition_notes?.insuffisant / dashboard.equipe?.total * 100) + '%' }"
                ></div>
              </div>
            </div>
            <div class="perf-legend">
              <span class="legend-item">
                <span class="dot excellent"></span>
                Excellent ({{ dashboard.performance.repartition_notes?.excellent || 0 }})
              </span>
              <span class="legend-item">
                <span class="dot bon"></span>
                Bon ({{ dashboard.performance.repartition_notes?.bon || 0 }})
              </span>
              <span class="legend-item">
                <span class="dot moyen"></span>
                Moyen ({{ dashboard.performance.repartition_notes?.moyen || 0 }})
              </span>
              <span class="legend-item">
                <span class="dot insuffisant"></span>
                Insuffisant ({{ dashboard.performance.repartition_notes?.insuffisant || 0 }})
              </span>
            </div>
          </div>
          <div class="empty-state" v-else>
            <p>Aucune donnée de performance</p>
          </div>
        </div>

        <!-- Absences du mois -->
        <div class="card absences-card">
          <div class="card-header">
            <h3>📅 Absences du mois</h3>
          </div>
          <div class="absences-stats" v-if="dashboard.absences_mois">
            <div class="stat-row">
              <span class="stat-label">Total absences</span>
              <span class="stat-value">{{ dashboard.absences_mois.nombre_absences || 0 }}</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Jours cumulés</span>
              <span class="stat-value">{{ dashboard.absences_mois.total_jours_absences || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal de validation -->
    <div class="modal" v-if="validateModal.show" @click.self="closeModals">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Valider la demande</h3>
          <button class="close-btn" @click="closeModals">×</button>
        </div>
        <div class="modal-body">
          <p>
            <strong>{{ validateModal.demande?.employe?.prenom }} {{ validateModal.demande?.employe?.nom }}</strong><br>
            {{ validateModal.demande?.typeConge?.libelle || 'Congé' }} - {{ validateModal.demande?.jours_demandes }} jour(s)<br>
            Du {{ formatDate(validateModal.demande?.date_debut) }} au {{ formatDate(validateModal.demande?.date_fin) }}
          </p>
          <div class="form-group">
            <label>Commentaire (optionnel)</label>
            <textarea v-model="validateModal.commentaire" rows="3" placeholder="Ajouter un commentaire..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModals">Annuler</button>
          <button class="btn btn-success" @click="validerDemande" :disabled="validating">
            {{ validating ? 'Validation...' : 'Valider' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <div class="modal" v-if="rejectModal.show" @click.self="closeModals">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Rejeter la demande</h3>
          <button class="close-btn" @click="closeModals">×</button>
        </div>
        <div class="modal-body">
          <p>
            <strong>{{ rejectModal.demande?.employe?.prenom }} {{ rejectModal.demande?.employe?.nom }}</strong><br>
            {{ rejectModal.demande?.typeConge?.libelle || 'Congé' }} - {{ rejectModal.demande?.jours_demandes }} jour(s)
          </p>
          <div class="form-group">
            <label>Motif du rejet <span class="required">*</span></label>
            <textarea 
              v-model="rejectModal.commentaire" 
              rows="3" 
              placeholder="Indiquez le motif du rejet..."
              required
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModals">Annuler</button>
          <button 
            class="btn btn-danger" 
            @click="rejeterDemande" 
            :disabled="validating || !rejectModal.commentaire"
          >
            {{ validating ? 'Rejet...' : 'Rejeter' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import managerService from '@/services/managerService'

export default {
  name: 'ManagerDashboardView',
  data() {
    return {
      loading: true,
      error: null,
      dashboard: {},
      demandesConges: [],
      demandesRH: [],
      showPendingConges: false,
      showPendingRH: false,
      validateModal: {
        show: false,
        demande: null,
        commentaire: ''
      },
      rejectModal: {
        show: false,
        demande: null,
        commentaire: ''
      },
      validating: false
    }
  },
  async mounted() {
    await this.loadDashboard()
    await this.loadDemandesConges()
  },
  methods: {
    async loadDashboard() {
      this.loading = true
      this.error = null
      try {
        const response = await managerService.getDashboard()
        this.dashboard = response.data
      } catch (err) {
        console.error('Erreur chargement dashboard manager:', err)
        this.error = err.response?.data?.message || 'Impossible de charger le tableau de bord'
      } finally {
        this.loading = false
      }
    },
    async loadDemandesConges() {
      try {
        // même logique que la page /demandes-conges : filtre en_attente + tri
        const response = await managerService.getDemandesConges({ statut: 'en_attente' })
        const data = response.data?.data || response.data || []
        // normaliser quelques champs pour l'affichage
        this.demandesConges = data.map(d => ({
          ...d,
          employe: d.employe || d.user || d.employee,
          typeConge: d.typeConge || d.type_conge || d.type,
        }))
      } catch (err) {
        console.error('Erreur chargement demandes congés:', err)
      }
    },
    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },
    isPending(demande) {
      return (demande.statut || 'en_attente') === 'en_attente'
    },
    openValidateModal(demande) {
      this.validateModal = {
        show: true,
        demande,
        commentaire: ''
      }
    },
    openRejectModal(demande) {
      this.rejectModal = {
        show: true,
        demande,
        commentaire: ''
      }
    },
    closeModals() {
      this.validateModal = { show: false, demande: null, commentaire: '' }
      this.rejectModal = { show: false, demande: null, commentaire: '' }
    },
    async validerDemande() {
      if (!this.validateModal.demande) return
      this.validating = true
      try {
        await managerService.validerDemandeConge(
          this.validateModal.demande.id,
          this.validateModal.commentaire
        )
        this.closeModals()
        await this.loadDemandesConges()
        await this.loadDashboard()
        alert('Demande validée avec succès')
      } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors de la validation')
      } finally {
        this.validating = false
      }
    },
    async rejeterDemande() {
      if (!this.rejectModal.demande || !this.rejectModal.commentaire) return
      this.validating = true
      try {
        await managerService.rejeterDemandeConge(
          this.rejectModal.demande.id,
          this.rejectModal.commentaire
        )
        this.closeModals()
        await this.loadDemandesConges()
        await this.loadDashboard()
        alert('Demande rejetée')
      } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors du rejet')
      } finally {
        this.validating = false
      }
    }
  }
}
</script>

<style scoped>
.manager-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.page-header .subtitle {
  color: #64748b;
  margin-top: 4px;
}

.loading-state, .error-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.kpi-warning {
  border-left: 4px solid #f59e0b;
}

.kpi-info {
  border-left: 4px solid #3b82f6;
}

.kpi-icon {
  font-size: 32px;
}

.kpi-content {
  display: flex;
  flex-direction: column;
}

.kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
}

.kpi-label {
  font-size: 14px;
  color: #64748b;
}

.kpi-meta {
  font-size: 12px;
  color: #94a3b8;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.team-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 300px;
  overflow-y: auto;
}

.team-member {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border-radius: 8px;
  transition: background 0.2s;
}

.team-member:hover {
  background: #f8fafc;
}

.member-avatar img,
.avatar-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-placeholder {
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.member-info {
  display: flex;
  flex-direction: column;
}

.member-name {
  font-weight: 500;
  color: #1e293b;
}

.member-role {
  font-size: 13px;
  color: #64748b;
}

.request-actions { display: flex; gap: 8px; align-items: center; }

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 14px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 10px 8px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}

.statut-badge {
  padding: 4px 8px;
  border-radius: 8px;
  font-size: 12px;
  text-transform: capitalize;
  background: #f1f5f9;
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.empty-state {
  text-align: center;
  padding: 24px;
  color: #64748b;
}

.see-all-link {
  display: block;
  text-align: center;
  margin-top: 16px;
  color: #3b82f6;
  text-decoration: none;
  font-size: 14px;
}

.see-all-link:hover {
  text-decoration: underline;
}

.perf-bar {
  display: flex;
  height: 24px;
  border-radius: 12px;
  overflow: hidden;
  background: #e2e8f0;
  margin-bottom: 16px;
}

.perf-fill {
  transition: width 0.3s;
}

.perf-fill.excellent { background: #10b981; }
.perf-fill.bon { background: #3b82f6; }
.perf-fill.moyen { background: #f59e0b; }
.perf-fill.insuffisant { background: #ef4444; }

.perf-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot.excellent { background: #10b981; }
.dot.bon { background: #3b82f6; }
.dot.moyen { background: #f59e0b; }
.dot.insuffisant { background: #ef4444; }

.stat-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.stat-row:last-child {
  border-bottom: none;
}

.stat-label {
  color: #64748b;
}

.stat-value {
  font-weight: 600;
  color: #1e293b;
}

/* Modal */
.modal {
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

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #64748b;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #e2e8f0;
}

.form-group {
  margin-top: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  resize: vertical;
  font-family: inherit;
}

.required {
  color: #ef4444;
}
</style>
