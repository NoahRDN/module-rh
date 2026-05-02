<template>
  <div class="archives-view">
    <!-- En-tête -->
    <div class="page-header">
      <div class="header-content">
        <h1>📁 Archives documentaires</h1>
        <p class="subtitle">Gestion des documents archivés et paramètres de rétention</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="showSettingsModal = true">
          ⚙️ Paramètres
        </button>
        <button class="btn btn-warning" @click="runMaintenance">
          🧹 Maintenance
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid" v-if="stats">
      <div class="card stat-card">
        <span class="stat-value">{{ stats.total || 0 }}</span>
        <span class="stat-label">Documents archivés</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ formatBytes(stats.total_size || 0) }}</span>
        <span class="stat-label">Espace utilisé</span>
      </div>
      <div class="card stat-card warning" v-if="stats.expiring_soon > 0">
        <span class="stat-value">{{ stats.expiring_soon }}</span>
        <span class="stat-label">Expirent dans 30 jours</span>
      </div>
      <div class="card stat-card danger" v-if="stats.expired > 0">
        <span class="stat-value">{{ stats.expired }}</span>
        <span class="stat-label">Expirés (à supprimer)</span>
      </div>
    </div>

    <!-- Onglets -->
    <div class="tabs">
      <button 
        class="tab" 
        :class="{ active: activeTab === 'documents' }"
        @click="activeTab = 'documents'"
      >
        📄 Documents
      </button>
      <button 
        class="tab" 
        :class="{ active: activeTab === 'settings' }"
        @click="activeTab = 'settings'"
      >
        ⚙️ Paramètres de rétention
      </button>
    </div>

    <!-- Documents archivés -->
    <div class="card" v-if="activeTab === 'documents'">
      <!-- Filtres -->
      <div class="filters">
        <div class="filter-group">
          <label>Type</label>
          <select v-model="filters.type" @change="applyFilters">
            <option value="">Tous</option>
            <option v-for="type in documentTypes" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Statut</label>
          <select v-model="filters.status" @change="applyFilters">
            <option value="">Tous</option>
            <option value="valid">Valide</option>
            <option value="expiring">Expire bientôt</option>
            <option value="expired">Expiré</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Recherche</label>
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Nom, référence..."
            @keyup.enter="applyFilters"
          />
        </div>
        <button class="btn btn-primary" @click="applyFilters" :disabled="loading">
          🔍 Filtrer
        </button>
      </div>

      <!-- Tableau -->
      <div class="table-container">
        <table class="data-table" v-if="documents.length">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Nom</th>
              <th>Type</th>
              <th>Archivé le</th>
              <th>Expire le</th>
              <th>Taille</th>
              <th>Intégrité</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents" :key="doc.id">
              <td class="ref-cell">{{ doc.archive_reference }}</td>
              <td>{{ doc.original_filename }}</td>
              <td>{{ doc.document_type }}</td>
              <td>{{ formatDate(doc.archived_at) }}</td>
              <td>
                <span :class="getExpirationClass(doc)">
                  {{ formatDate(doc.expires_at) }}
                </span>
              </td>
              <td>{{ formatBytes(doc.file_size) }}</td>
              <td>
                <span 
                  class="integrity-badge" 
                  :class="doc.integrity_verified ? 'valid' : 'invalid'"
                >
                  {{ doc.integrity_verified ? '✓ Vérifié' : '⚠️ À vérifier' }}
                </span>
              </td>
              <td class="actions-cell">
                <button 
                  class="btn btn-sm btn-secondary" 
                  @click="downloadDocument(doc)"
                  title="Télécharger"
                >
                  📥
                </button>
                <button 
                  class="btn btn-sm btn-secondary" 
                  @click="verifyDocument(doc)"
                  title="Vérifier intégrité"
                >
                  🔍
                </button>
                <button 
                  class="btn btn-sm btn-danger" 
                  @click="confirmDelete(doc)"
                  title="Supprimer"
                  v-if="canDelete(doc)"
                >
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="empty-state" v-else-if="!loading">
          <p>Aucun document archivé</p>
        </div>

        <div class="loading-state" v-if="loading">
          <div class="spinner"></div>
          <p>Chargement...</p>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.total > pagination.per_page">
        <button 
          class="btn btn-sm" 
          :disabled="loading || pagination.current_page <= 1"
          @click="changePage(pagination.current_page - 1)"
        >
          ← Précédent
        </button>
        <span class="page-info">
          Page {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button 
          class="btn btn-sm" 
          :disabled="loading || pagination.current_page >= pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          Suivant →
        </button>
      </div>
    </div>

    <!-- Paramètres de rétention -->
    <div class="card" v-if="activeTab === 'settings'">
      <div class="settings-header">
        <h3>Durées de conservation</h3>
        <p>Ces paramètres définissent la durée légale de conservation pour chaque type de document.</p>
      </div>

      <div class="settings-grid">
        <div 
          class="setting-card" 
          v-for="setting in archiveSettings" 
          :key="setting.id"
        >
          <div class="setting-info">
            <h4>{{ setting.document_type }}</h4>
            <p class="description">{{ setting.description }}</p>
          </div>
          <div class="setting-value">
            <span class="retention">{{ setting.retention_years }} ans</span>
            <span 
              class="status-badge" 
              :class="setting.is_active ? 'active' : 'inactive'"
            >
              {{ setting.is_active ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          <div class="setting-actions">
            <button 
              class="btn btn-sm btn-secondary" 
              @click="editSetting(setting)"
            >
              ✏️ Modifier
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal édition paramètre -->
    <div class="modal" v-if="editingSetting" @click.self="editingSetting = null">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Modifier les paramètres</h3>
          <button class="close-btn" @click="editingSetting = null">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveSetting">
            <div class="form-group">
              <label>Type de document</label>
              <input 
                type="text" 
                v-model="editingSetting.document_type" 
                disabled 
                class="form-input"
              />
            </div>
            <div class="form-group">
              <label>Durée de conservation (années)</label>
              <input 
                type="number" 
                v-model.number="editingSetting.retention_years" 
                min="1"
                max="99"
                class="form-input"
                required
              />
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea 
                v-model="editingSetting.description" 
                class="form-input"
                rows="3"
              ></textarea>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="editingSetting.is_active" />
                Activer ce type d'archivage
              </label>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="editingSetting = null">
                Annuler
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal confirmation suppression -->
    <div class="modal" v-if="deletingDocument" @click.self="deletingDocument = null">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h3>⚠️ Confirmer la suppression</h3>
          <button class="close-btn" @click="deletingDocument = null">×</button>
        </div>
        <div class="modal-body">
          <p>
            Êtes-vous sûr de vouloir supprimer définitivement le document 
            <strong>{{ deletingDocument.original_filename }}</strong> ?
          </p>
          <p class="warning-text">
            Cette action est irréversible et sera enregistrée dans le journal d'audit.
          </p>
          <div class="form-group">
            <label>Raison de la suppression</label>
            <textarea 
              v-model="deleteReason" 
              class="form-input"
              rows="2"
              required
              placeholder="Obligatoire pour les archives"
            ></textarea>
          </div>
          <div class="form-actions">
            <button class="btn btn-secondary" @click="deletingDocument = null">
              Annuler
            </button>
            <button 
              class="btn btn-danger" 
              @click="deleteDocument"
              :disabled="!deleteReason || deleting"
            >
              {{ deleting ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import archiveService from '@/services/archiveService'

export default {
  name: 'ArchivesView',
  data() {
    return {
      loading: false,
      saving: false,
      deleting: false,
      activeTab: 'documents',
      documents: [],
      archiveSettings: [],
      stats: null,
      documentTypes: [],
      filters: {
        type: '',
        status: '',
        search: ''
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0
      },
      editingSetting: null,
      deletingDocument: null,
      deleteReason: '',
      showSettingsModal: false
    }
  },
  async mounted() {
    await Promise.all([
      this.loadStats(),
      this.loadDocuments(),
      this.loadSettings(),
      this.loadTypes()
    ])
  },
  methods: {
    async loadDocuments() {
      this.loading = true
      try {
        const params = {
          ...this.filters,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page
        }
        const response = await archiveService.getDocuments(params)
        this.documents = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total
        }
        archiveService.prefetchDocuments(params, this.pagination)
      } catch (err) {
        console.error('Erreur chargement documents:', err)
      } finally {
        this.loading = false
      }
    },
    async loadSettings() {
      try {
        const response = await archiveService.getSettings()
        this.archiveSettings = response.data
      } catch (err) {
        console.error('Erreur chargement paramètres:', err)
      }
    },
    async loadStats() {
      try {
        const response = await archiveService.getStatistiques()
        this.stats = response.data
      } catch (err) {
        console.error('Erreur stats:', err)
      }
    },
    async loadTypes() {
      try {
        const response = await archiveService.getTypes()
        this.documentTypes = response.data
      } catch (err) {
        console.error('Erreur types:', err)
      }
    },
    editSetting(setting) {
      this.editingSetting = { ...setting }
    },
    async saveSetting() {
      this.saving = true
      try {
        await archiveService.updateSetting(this.editingSetting.id, this.editingSetting)
        await this.loadSettings()
        this.editingSetting = null
      } catch (err) {
        alert('Erreur lors de la sauvegarde')
      } finally {
        this.saving = false
      }
    },
    async downloadDocument(doc) {
      try {
        const response = await archiveService.downloadDocument(doc.id)
        const blob = new Blob([response.data])
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = doc.original_filename
        a.click()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        alert('Erreur lors du téléchargement')
      }
    },
    async verifyDocument(doc) {
      try {
        const response = await archiveService.verifyIntegrity(doc.id)
        if (response.data.valid) {
          alert('✓ Intégrité vérifiée : le document est intact')
        } else {
          alert('⚠️ Attention : l\'intégrité du document ne peut être vérifiée !')
        }
        await this.loadDocuments()
      } catch (err) {
        alert('Erreur lors de la vérification')
      }
    },
    confirmDelete(doc) {
      this.deletingDocument = doc
      this.deleteReason = ''
    },
    async deleteDocument() {
      this.deleting = true
      try {
        await archiveService.deleteDocument(this.deletingDocument.id, {
          reason: this.deleteReason
        })
        this.deletingDocument = null
        await this.loadDocuments()
        await this.loadStats()
      } catch (err) {
        alert('Erreur lors de la suppression')
      } finally {
        this.deleting = false
      }
    },
    async runMaintenance() {
      if (!confirm('Lancer la maintenance des archives ? Les documents expirés seront traités.')) {
        return
      }
      try {
        const response = await archiveService.runMaintenance()
        alert(`Maintenance terminée :\n- ${response.data.processed} documents traités\n- ${response.data.deleted} documents supprimés`)
        await this.loadDocuments()
        await this.loadStats()
      } catch (err) {
        alert('Erreur lors de la maintenance')
      }
    },
    canDelete(doc) {
      // Peut supprimer si expiré
      return doc.is_expired
    },
    applyFilters() {
      this.pagination.current_page = 1
      this.loadDocuments()
    },
    getExpirationClass(doc) {
      if (doc.is_expired) return 'expired'
      if (doc.is_expiring_soon) return 'expiring'
      return ''
    },
    changePage(page) {
      if (this.loading || page < 1 || page > this.pagination.last_page) return
      this.pagination.current_page = page
      this.loadDocuments()
    },
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('fr-FR')
    },
    formatBytes(bytes) {
      if (!bytes) return '0 B'
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i]
    }
  }
}
</script>

<style scoped>
.archives-view {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.subtitle {
  color: #64748b;
  margin-top: 4px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
}

.stat-card.danger {
  border-left: 4px solid #dc2626;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #3b82f6;
}

.stat-card.warning .stat-value { color: #f59e0b; }
.stat-card.danger .stat-value { color: #dc2626; }

.stat-label {
  font-size: 14px;
  color: #64748b;
  text-align: center;
}

.tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 20px;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 8px;
  width: fit-content;
}

.tab {
  padding: 10px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s;
}

.tab.active {
  background: white;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: flex-end;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  min-width: 150px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-sm { padding: 6px 10px; font-size: 13px; }
.btn-primary { background: #3b82f6; color: white; }
.btn-secondary { background: #e2e8f0; color: #475569; }
.btn-warning { background: #f59e0b; color: white; }
.btn-danger { background: #dc2626; color: white; }
.btn:hover { opacity: 0.9; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  font-weight: 600;
  color: #64748b;
  font-size: 13px;
  text-transform: uppercase;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.ref-cell {
  font-family: monospace;
  font-size: 12px;
}

.actions-cell {
  display: flex;
  gap: 6px;
}

.expired {
  color: #dc2626;
  font-weight: 500;
}

.expiring {
  color: #f59e0b;
  font-weight: 500;
}

.integrity-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.integrity-badge.valid {
  background: #d1fae5;
  color: #059669;
}

.integrity-badge.invalid {
  background: #fef3c7;
  color: #d97706;
}

/* Settings */
.settings-header {
  margin-bottom: 24px;
}

.settings-header h3 {
  margin: 0;
  color: #1e293b;
}

.settings-header p {
  margin: 8px 0 0;
  color: #64748b;
}

.settings-grid {
  display: grid;
  gap: 16px;
}

.setting-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  gap: 24px;
}

.setting-info {
  flex: 1;
}

.setting-info h4 {
  margin: 0;
  color: #1e293b;
}

.setting-info .description {
  margin: 4px 0 0;
  font-size: 13px;
  color: #64748b;
}

.setting-value {
  display: flex;
  align-items: center;
  gap: 12px;
}

.retention {
  font-size: 20px;
  font-weight: 600;
  color: #3b82f6;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.active {
  background: #d1fae5;
  color: #059669;
}

.status-badge.inactive {
  background: #e2e8f0;
  color: #64748b;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.page-info {
  color: #64748b;
  font-size: 14px;
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
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-small {
  max-width: 400px;
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

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

.form-input:disabled {
  background: #f3f4f6;
  color: #6b7280;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.warning-text {
  color: #dc2626;
  font-size: 14px;
  background: #fef2f2;
  padding: 10px;
  border-radius: 6px;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 40px;
  color: #64748b;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
