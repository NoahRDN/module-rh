<template>
  <div class="audit-view">
    <!-- En-tête -->
    <div class="page-header">
      <div class="header-content">
        <h1>🔍 Journal d'audit</h1>
        <p class="subtitle">Suivi des actions et traçabilité</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="exportLogs">
          📥 Exporter CSV
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid" v-if="stats">
      <div class="card stat-card">
        <span class="stat-value">{{ stats.total || 0 }}</span>
        <span class="stat-label">Actions cette semaine</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ stats.logins || 0 }}</span>
        <span class="stat-label">Connexions</span>
      </div>
      <div class="card stat-card">
        <span class="stat-value">{{ stats.modifications || 0 }}</span>
        <span class="stat-label">Modifications</span>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Action</label>
          <select v-model="filters.action" @change="applyFilters">
            <option value="">Toutes</option>
            <option v-for="(label, key) in actions" :key="key" :value="key">
              {{ label }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Type d'entité</label>
          <select v-model="filters.type" @change="applyFilters">
            <option value="">Tous</option>
            <option v-for="type in types" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Utilisateur</label>
          <select v-model="filters.user_id" @change="applyFilters">
            <option value="">Tous</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.role }})
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Du</label>
          <input type="date" v-model="filters.from" @change="applyFilters" />
        </div>
        <div class="filter-group">
          <label>Au</label>
          <input type="date" v-model="filters.to" @change="applyFilters" />
        </div>
        <div class="filter-group">
          <label>Recherche</label>
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Description, IP..."
            @keyup.enter="applyFilters"
          />
        </div>
        <button class="btn btn-primary" @click="applyFilters" :disabled="loading">
          🔍 Filtrer
        </button>
        <button class="btn btn-secondary" @click="resetFilters">
          ↻ Réinitialiser
        </button>
      </div>
    </div>

    <!-- Tableau des logs -->
    <div class="card">
      <div class="table-container">
        <table class="data-table" v-if="logs.length">
          <thead>
            <tr>
              <th>Date/Heure</th>
              <th>Utilisateur</th>
              <th>Action</th>
              <th>Entité</th>
              <th>Description</th>
              <th>IP</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="date-cell">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td>
                <span class="user-badge" v-if="log.user">
                  {{ log.user.name }}
                  <span class="role-tag" :class="log.user.role">
                    {{ log.user.role }}
                  </span>
                </span>
                <span class="system-badge" v-else>Système</span>
              </td>
              <td>
                <span class="action-badge" :class="log.action">
                  {{ getActionLabel(log.action) }}
                </span>
              </td>
              <td>{{ getEntityName(log.auditable_type) }}</td>
              <td class="description-cell">{{ log.description || '-' }}</td>
              <td class="ip-cell">{{ log.ip_address || '-' }}</td>
              <td>
                <button 
                  class="btn btn-sm btn-secondary" 
                  @click="showDetails(log)"
                  title="Voir détails"
                >
                  👁️
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="empty-state" v-else-if="!loading">
          <p>Aucun log trouvé avec ces critères</p>
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
          ({{ pagination.total }} résultats)
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

    <!-- Modal détails -->
    <div class="modal" v-if="selectedLog" @click.self="selectedLog = null">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h3>Détails du log #{{ selectedLog.id }}</h3>
          <button class="close-btn" @click="selectedLog = null">×</button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <label>Date/Heure</label>
              <span>{{ formatDateTime(selectedLog.created_at) }}</span>
            </div>
            <div class="detail-item">
              <label>Utilisateur</label>
              <span>{{ selectedLog.user?.name || 'Système' }}</span>
            </div>
            <div class="detail-item">
              <label>Rôle</label>
              <span>{{ selectedLog.user?.role || '-' }}</span>
            </div>
            <div class="detail-item">
              <label>Action</label>
              <span class="action-badge" :class="selectedLog.action">
                {{ getActionLabel(selectedLog.action) }}
              </span>
            </div>
            <div class="detail-item">
              <label>Type d'entité</label>
              <span>{{ getEntityName(selectedLog.auditable_type) }}</span>
            </div>
            <div class="detail-item">
              <label>ID Entité</label>
              <span>{{ selectedLog.auditable_id || '-' }}</span>
            </div>
            <div class="detail-item full-width">
              <label>Description</label>
              <span>{{ selectedLog.description || '-' }}</span>
            </div>
            <div class="detail-item">
              <label>Adresse IP</label>
              <span>{{ selectedLog.ip_address || '-' }}</span>
            </div>
            <div class="detail-item">
              <label>URL</label>
              <span class="url-text">{{ selectedLog.url || '-' }}</span>
            </div>
            <div class="detail-item">
              <label>Méthode HTTP</label>
              <span class="method-badge" :class="selectedLog.method?.toLowerCase()">
                {{ selectedLog.method || '-' }}
              </span>
            </div>
            <div class="detail-item full-width">
              <label>User Agent</label>
              <span class="small-text">{{ selectedLog.user_agent || '-' }}</span>
            </div>
          </div>

          <!-- Valeurs modifiées -->
          <div class="changes-section" v-if="selectedLog.old_values || selectedLog.new_values">
            <h4>Modifications</h4>
            <div class="changes-container">
              <div class="change-column" v-if="selectedLog.old_values">
                <h5>Avant</h5>
                <pre>{{ formatJson(selectedLog.old_values) }}</pre>
              </div>
              <div class="change-column" v-if="selectedLog.new_values">
                <h5>Après</h5>
                <pre>{{ formatJson(selectedLog.new_values) }}</pre>
              </div>
            </div>
          </div>

          <!-- Métadonnées -->
          <div class="metadata-section" v-if="selectedLog.metadata">
            <h4>Métadonnées</h4>
            <pre>{{ formatJson(selectedLog.metadata) }}</pre>
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
 * Vue pour le journal d'audit
 * Features: Journalisation des actions (traces d'audit), Gestion des autorisations par rôle,
 * Sauvegarde et archivage légal des documents RH
 */
import auditService from '@/services/auditService'

export default {
  name: 'AuditView',
  data() {
    return {
      loading: false,
      logs: [],
      stats: null,
      actions: {},
      types: [],
      users: [],
      filters: {
        action: '',
        type: '',
        user_id: '',
        from: '',
        to: '',
        search: ''
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0
      },
      selectedLog: null
    }
  },
  async mounted() {
    await Promise.all([
      this.loadStats(),
      this.loadActions(),
      this.loadTypes(),
      this.loadUsers(),
      this.loadLogs()
    ])
  },
  methods: {
    async loadLogs() {
      this.loading = true
      try {
        const params = {
          ...this.filters,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page
        }
        const response = await auditService.getLogs(params)
        this.logs = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total
        }
      } catch (err) {
        console.error('Erreur chargement logs:', err)
      } finally {
        this.loading = false
      }
    },
    async loadStats() {
      try {
        const response = await auditService.getStatistiques('week')
        this.stats = response.data
      } catch (err) {
        console.error('Erreur stats:', err)
      }
    },
    async loadActions() {
      try {
        const response = await auditService.getActions()
        this.actions = response.data
      } catch (err) {
        console.error('Erreur actions:', err)
      }
    },
    async loadTypes() {
      try {
        const response = await auditService.getTypes()
        this.types = response.data
      } catch (err) {
        console.error('Erreur types:', err)
      }
    },
    async loadUsers() {
      try {
        const response = await auditService.getUsers()
        this.users = response.data
      } catch (err) {
        console.error('Erreur users:', err)
      }
    },
    async exportLogs() {
      try {
        const response = await auditService.exportCsv(this.filters)
        const blob = new Blob([response.data], { type: 'text/csv' })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`
        a.click()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        alert('Erreur lors de l\'export')
      }
    },
    resetFilters() {
      this.filters = {
        action: '',
        type: '',
        user_id: '',
        from: '',
        to: '',
        search: ''
      }
      this.pagination.current_page = 1
      this.loadLogs()
    },
    applyFilters() {
      this.pagination.current_page = 1
      this.loadLogs()
    },
    changePage(page) {
      if (this.loading || page < 1 || page > this.pagination.last_page) return
      this.pagination.current_page = page
      this.loadLogs()
    },
    showDetails(log) {
      this.selectedLog = log
    },
    formatDateTime(date) {
      if (!date) return '-'
      return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    getActionLabel(action) {
      return this.actions[action] || action
    },
    getEntityName(type) {
      if (!type) return '-'
      const parts = type.split('\\')
      return parts[parts.length - 1]
    },
    formatJson(obj) {
      try {
        return JSON.stringify(obj, null, 2)
      } catch {
        return String(obj)
      }
    }
  }
}
</script>

<style scoped>
.audit-view {
  padding: 20px;
  max-width: 1600px;
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

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #3b82f6;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.filters-card .filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: flex-end;
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

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn:hover {
  opacity: 0.9;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

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

.date-cell {
  white-space: nowrap;
  font-size: 13px;
}

.description-cell {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ip-cell {
  font-family: monospace;
  font-size: 13px;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 8px;
}

.role-tag {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

.role-tag.admin { background: #fee2e2; color: #dc2626; }
.role-tag.rh { background: #dbeafe; color: #2563eb; }
.role-tag.manager { background: #fef3c7; color: #d97706; }
.role-tag.employe { background: #d1fae5; color: #059669; }

.system-badge {
  color: #94a3b8;
  font-style: italic;
}

.action-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.action-badge.create { background: #d1fae5; color: #059669; }
.action-badge.update { background: #dbeafe; color: #2563eb; }
.action-badge.delete { background: #fee2e2; color: #dc2626; }
.action-badge.login { background: #f0fdf4; color: #16a34a; }
.action-badge.logout { background: #f1f5f9; color: #64748b; }
.action-badge.view { background: #f8fafc; color: #475569; }
.action-badge.export { background: #fef3c7; color: #d97706; }
.action-badge.approve { background: #d1fae5; color: #059669; }
.action-badge.reject { background: #fee2e2; color: #dc2626; }
.action-badge.archive { background: #e0e7ff; color: #4f46e5; }

.method-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.method-badge.get { background: #dbeafe; color: #2563eb; }
.method-badge.post { background: #d1fae5; color: #059669; }
.method-badge.put, .method-badge.patch { background: #fef3c7; color: #d97706; }
.method-badge.delete { background: #fee2e2; color: #dc2626; }

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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  background: white;
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

.detail-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-item label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.url-text {
  word-break: break-all;
  font-size: 13px;
}

.small-text {
  font-size: 12px;
  color: #64748b;
  word-break: break-all;
}

.changes-section, .metadata-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.changes-section h4, .metadata-section h4 {
  margin: 0 0 16px;
  font-size: 16px;
  color: #1e293b;
}

.changes-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.change-column h5 {
  margin: 0 0 8px;
  font-size: 13px;
  color: #64748b;
}

pre {
  background: #f8fafc;
  padding: 12px;
  border-radius: 8px;
  font-size: 12px;
  overflow-x: auto;
  margin: 0;
}

@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .changes-container {
    grid-template-columns: 1fr;
  }
}
</style>
