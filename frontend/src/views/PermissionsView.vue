<template>
  <div class="permissions-view">
    <!-- En-tête -->
    <div class="page-header">
      <div class="header-content">
        <h1>🔐 Gestion des permissions</h1>
        <p class="subtitle">Configuration des accès par rôle</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="showCreateModal = true">
          + Nouvelle permission
        </button>
      </div>
    </div>

    <!-- Matrice des permissions -->
    <div class="card">
      <h3>Matrice des permissions</h3>
      <p class="description">
        Cochez les permissions pour chaque rôle. Les modifications sont enregistrées automatiquement.
      </p>

      <div class="table-container" v-if="permissions.length">
        <table class="matrix-table">
          <thead>
            <tr>
              <th class="permission-header">Permission</th>
              <th 
                v-for="role in roles" 
                :key="role.code"
                class="role-header"
              >
                <span class="role-badge" :class="role.code">
                  {{ role.label }}
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="permission in groupedPermissions" 
              :key="permission.id"
              :class="{ 'group-header': permission.isGroupHeader }"
            >
              <td class="permission-cell">
                <span v-if="permission.isGroupHeader" class="group-name">
                  {{ permission.group }}
                </span>
                <span v-else class="permission-name">
                  <span class="permission-code">{{ permission.code }}</span>
                  {{ permission.name }}
                  <span class="permission-desc" v-if="permission.description">
                    {{ permission.description }}
                  </span>
                </span>
              </td>
              <td 
                v-for="role in roles" 
                :key="role.name"
                class="checkbox-cell"
              >
                <label class="checkbox-wrapper" v-if="!permission.isGroupHeader">
                  <input 
                    type="checkbox"
                    :checked="hasPermission(permission.code, role.code)"
                    @change="togglePermission(permission.code, role.code, $event.target.checked)"
                    :disabled="saving"
                  />
                  <span class="checkmark"></span>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="loading-state" v-if="loading">
        <div class="spinner"></div>
        <p>Chargement...</p>
      </div>
    </div>

    <!-- Liste des permissions -->
    <div class="card">
      <div class="card-header">
        <h3>Liste des permissions</h3>
        <div class="filter-group">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Rechercher..."
            class="search-input"
          />
        </div>
      </div>

      <div class="permissions-list">
        <div 
          class="permission-item" 
          v-for="permission in filteredPermissions" 
          :key="permission.id"
        >
          <div class="permission-info">
            <span class="permission-code">{{ permission.code }}</span>
            <span class="permission-name">{{ permission.name }}</span>
            <span class="permission-desc">{{ permission.description }}</span>
          </div>
          <div class="permission-roles">
            <span 
              class="role-tag"
              :class="role"
              v-for="role in permission.roles" 
              :key="role"
            >
              {{ role }}
            </span>
          </div>
          <div class="permission-actions">
            <button 
              class="btn btn-sm btn-secondary" 
              @click="editPermission(permission)"
            >
              ✏️
            </button>
            <button 
              class="btn btn-sm btn-danger" 
              @click="confirmDelete(permission)"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal création/édition -->
    <div class="modal" v-if="showCreateModal || editingPermission" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingPermission ? 'Modifier la permission' : 'Nouvelle permission' }}</h3>
          <button class="close-btn" @click="closeModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePermission">
            <div class="form-group">
              <label>Code <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.code" 
                class="form-input"
                placeholder="ex: gerer_conges"
                pattern="[a-z_]+"
                required
                :disabled="editingPermission"
              />
              <small>Minuscules et underscores uniquement</small>
            </div>
            <div class="form-group">
              <label>Nom <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                class="form-input"
                placeholder="Gérer les congés"
                required
              />
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea 
                v-model="form.description" 
                class="form-input"
                rows="2"
                placeholder="Description de la permission"
              ></textarea>
            </div>
            <div class="form-group">
              <label>Module</label>
              <select v-model="form.module" class="form-input">
                <option value="">Non spécifié</option>
                <option v-for="mod in modules" :key="mod" :value="mod">
                  {{ mod }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Rôles ayant cette permission</label>
              <div class="roles-checkboxes">
                <label 
                  v-for="role in roles" 
                  :key="role.name"
                  class="role-checkbox"
                >
                  <input 
                    type="checkbox" 
                    :value="role.name" 
                    v-model="form.roles"
                  />
                  <span class="role-badge" :class="role.name">{{ role.label }}</span>
                </label>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeModal">
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
    <div class="modal" v-if="deletingPermission" @click.self="deletingPermission = null">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h3>⚠️ Confirmer la suppression</h3>
          <button class="close-btn" @click="deletingPermission = null">×</button>
        </div>
        <div class="modal-body">
          <p>
            Êtes-vous sûr de vouloir supprimer la permission 
            <strong>{{ deletingPermission.code }}</strong> ?
          </p>
          <p class="warning-text">
            Cette action supprimera également cette permission de tous les rôles.
          </p>
          <div class="form-actions">
            <button class="btn btn-secondary" @click="deletingPermission = null">
              Annuler
            </button>
            <button 
              class="btn btn-danger" 
              @click="deletePermission"
              :disabled="deleting"
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
import permissionService from '@/services/permissionService'

export default {
  name: 'PermissionsView',
  data() {
    return {
      loading: false,
      saving: false,
      deleting: false,
      permissions: [],
      matrix: { roles: [], permissions: [] },
      searchQuery: '',
      showCreateModal: false,
      editingPermission: null,
      deletingPermission: null,
      form: {
        code: '',
        name: '',
        description: '',
        module: '',
        roles: []
      },
      roles: [
        { code: 'admin', label: 'Administrateur' },
        { code: 'rh', label: 'RH' },
        { code: 'manager', label: 'Manager' },
        { code: 'employe', label: 'Employé' }
      ],
      modules: [
        'conges',
        'employes',
        'departements',
        'contrats',
        'documents',
        'rapports',
        'administration',
        'audit'
      ]
    }
  },
  computed: {
    filteredPermissions() {
      if (!this.searchQuery) return this.permissions
      const query = this.searchQuery.toLowerCase()
      return this.permissions.filter(p => 
        p.code.toLowerCase().includes(query) ||
        p.name.toLowerCase().includes(query) ||
        (p.description && p.description.toLowerCase().includes(query))
      )
    },
    groupedPermissions() {
      const groups = {}
      this.permissions.forEach(p => {
        const group = p.module || 'Autre'
        if (!groups[group]) groups[group] = []
        groups[group].push(p)
      })
      
      const result = []
      Object.keys(groups).sort().forEach(group => {
        result.push({ isGroupHeader: true, group })
        groups[group].forEach(p => result.push(p))
      })
      return result
    }
  },
  async mounted() {
    await this.loadPermissions()
    await this.loadMatrix()
  },
  methods: {
    async loadPermissions() {
      this.loading = true
      try {
        const response = await permissionService.getAll()
        this.permissions = response.data
      } catch (err) {
        console.error('Erreur chargement permissions:', err)
      } finally {
        this.loading = false
      }
    },
    async loadMatrix() {
      try {
        const response = await permissionService.getMatrix()
        this.matrix = response.data
      } catch (err) {
        console.error('Erreur chargement matrice:', err)
      }
    },
    hasPermission(permissionCode, role) {
      // La matrice a une structure: { roles: [...], permissions: [{ code, libelle, groupe, admin: bool, rh: bool, ... }] }
      const perm = this.matrix.permissions?.find(p => p.code === permissionCode)
      return perm ? perm[role] === true : false
    },
    async togglePermission(permissionCode, role, checked) {
      this.saving = true
      try {
        // Récupérer les permissions actuelles du rôle
        const rolePermsResponse = await permissionService.getForRole(role)
        let currentPerms = rolePermsResponse.data.data?.map(p => p.code) || []
        
        if (checked) {
          // Ajouter la permission
          if (!currentPerms.includes(permissionCode)) {
            currentPerms.push(permissionCode)
          }
        } else {
          // Retirer la permission
          currentPerms = currentPerms.filter(p => p !== permissionCode)
        }
        
        // Mettre à jour toutes les permissions du rôle
        await permissionService.updateRole(role, currentPerms)
        await this.loadMatrix()
        await this.loadPermissions()
      } catch (err) {
        console.error('Erreur toggle permission:', err)
        alert('Erreur lors de la modification')
      } finally {
        this.saving = false
      }
    },
    editPermission(permission) {
      this.editingPermission = permission
      this.form = {
        code: permission.code,
        name: permission.name,
        description: permission.description || '',
        module: permission.module || '',
        roles: [...(permission.roles || [])]
      }
    },
    confirmDelete(permission) {
      this.deletingPermission = permission
    },
    async deletePermission() {
      this.deleting = true
      try {
        await permissionService.delete(this.deletingPermission.id)
        this.deletingPermission = null
        await this.loadPermissions()
        await this.loadMatrix()
      } catch (err) {
        alert('Erreur lors de la suppression')
      } finally {
        this.deleting = false
      }
    },
    async savePermission() {
      this.saving = true
      try {
        if (this.editingPermission) {
          await permissionService.update(this.editingPermission.id, this.form)
        } else {
          await permissionService.create(this.form)
        }
        this.closeModal()
        await this.loadPermissions()
        await this.loadMatrix()
      } catch (err) {
        alert('Erreur lors de l\'enregistrement')
      } finally {
        this.saving = false
      }
    },
    closeModal() {
      this.showCreateModal = false
      this.editingPermission = null
      this.form = {
        code: '',
        name: '',
        description: '',
        module: '',
        roles: []
      }
    }
  }
}
</script>

<style scoped>
.permissions-view {
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

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.card h3 {
  margin: 0 0 8px;
  color: #1e293b;
}

.description {
  color: #64748b;
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  min-width: 200px;
}

/* Matrice */
.table-container {
  overflow-x: auto;
}

.matrix-table {
  width: 100%;
  border-collapse: collapse;
}

.matrix-table th,
.matrix-table td {
  padding: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.permission-header {
  text-align: left;
  min-width: 300px;
}

.role-header {
  text-align: center;
  width: 120px;
}

.role-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.role-badge.admin { background: #fee2e2; color: #dc2626; }
.role-badge.rh { background: #dbeafe; color: #2563eb; }
.role-badge.manager { background: #fef3c7; color: #d97706; }
.role-badge.employe { background: #d1fae5; color: #059669; }

.group-header {
  background: #f8fafc;
}

.group-name {
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  font-size: 13px;
}

.permission-cell {
  text-align: left;
}

.permission-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.permission-code {
  font-family: monospace;
  font-size: 11px;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  width: fit-content;
}

.permission-desc {
  font-size: 12px;
  color: #94a3b8;
}

.checkbox-cell {
  text-align: center;
}

.checkbox-wrapper {
  display: inline-flex;
  position: relative;
  cursor: pointer;
}

.checkbox-wrapper input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  transition: all 0.2s;
}

.checkbox-wrapper input:checked + .checkmark {
  background: #3b82f6;
  border-color: #3b82f6;
}

.checkbox-wrapper input:checked + .checkmark::after {
  content: '✓';
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

/* Liste des permissions */
.permissions-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.permission-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  gap: 16px;
}

.permission-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.permission-info .permission-name {
  font-weight: 500;
  color: #1e293b;
}

.permission-roles {
  display: flex;
  gap: 6px;
}

.role-tag {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
}

.role-tag.admin { background: #fee2e2; color: #dc2626; }
.role-tag.rh { background: #dbeafe; color: #2563eb; }
.role-tag.manager { background: #fef3c7; color: #d97706; }
.role-tag.employe { background: #d1fae5; color: #059669; }

.permission-actions {
  display: flex;
  gap: 6px;
}

/* Buttons */
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
.btn-danger { background: #dc2626; color: white; }
.btn:hover { opacity: 0.9; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

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

.form-group small {
  display: block;
  margin-top: 4px;
  color: #94a3b8;
  font-size: 12px;
}

.required {
  color: #dc2626;
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

.roles-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.role-checkbox {
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

.loading-state {
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
