import api from './api'

/**
 * Service pour la gestion des permissions
 */
export const permissionService = {
  /**
   * Récupère toutes les permissions
   */
  getAll() {
    return api.get('/v1/permissions')
  },

  /**
   * Récupère les permissions groupées
   */
  getGrouped() {
    return api.get('/v1/permissions/grouped')
  },

  /**
   * Récupère les rôles disponibles
   */
  getRoles() {
    return api.get('/v1/permissions/roles')
  },

  /**
   * Récupère la matrice des permissions
   */
  getMatrix() {
    return api.get('/v1/permissions/matrix')
  },

  /**
   * Récupère les permissions d'un rôle
   */
  getForRole(role) {
    return api.get(`/v1/permissions/role/${role}`)
  },

  /**
   * Met à jour les permissions d'un rôle
   */
  updateRole(role, permissions) {
    return api.put(`/v1/permissions/role/${role}`, { permissions })
  },

  /**
   * Vérifie si un rôle a une permission
   */
  check(role, permission) {
    return api.post('/v1/permissions/check', { role, permission })
  }
}

export default permissionService
