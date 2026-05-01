<template>
  <header class="topbar">
    <div class="topbar-copy">
      <button
        class="menu-toggle"
        type="button"
        @click="$emit('toggle-sidebar')"
        :title="sidebarOpen ? 'Masquer le menu' : 'Afficher le menu'"
        :aria-label="sidebarOpen ? 'Masquer le menu' : 'Afficher le menu'"
      >
        <AppIcon name="grid" :size="18" />
      </button>

      <div>
        <p class="topbar-eyebrow">Navigation</p>
        <p class="topbar-title">{{ subtitle || 'Pilotage RH' }}</p>
      </div>
    </div>

    <div class="topbar-actions">
      <button
        class="theme-toggle"
        type="button"
        @click="toggleTheme"
        :title="theme === 'dark' ? 'Passer en clair' : 'Passer en sombre'"
        :aria-label="theme === 'dark' ? 'Passer en clair' : 'Passer en sombre'"
      >
        <AppIcon :name="theme === 'dark' ? 'sun' : 'moon'" :size="18" />
      </button>

      <div class="notification-menu" ref="notificationRef">
        <button
          class="notification-trigger"
          type="button"
          @click="toggleNotifications"
          title="Ouvrir les notifications"
          aria-label="Ouvrir les notifications"
          aria-haspopup="dialog"
          :aria-expanded="String(notificationOpen)"
          :class="{ active: notificationOpen, highlighted: unreadCount > 0 }"
        >
          <AppIcon name="bell" :size="18" />
          <span v-if="unreadCount > 0" class="notification-badge">{{ displayUnreadCount }}</span>
        </button>

        <transition name="menu">
          <div v-if="notificationOpen" class="notification-dropdown" role="dialog" aria-label="Centre de notifications">
            <div class="notification-header">
              <div>
                <p class="notification-eyebrow">Notifications</p>
                <p class="notification-title">Alertes et activités</p>
              </div>
              <button
                v-if="notifications.length && unreadCount > 0"
                class="notification-mark-all"
                type="button"
                @click="markAllNotificationsRead"
              >
                Tout lire
              </button>
            </div>

            <div class="notification-scroll">
              <div v-if="notificationsLoading" class="notification-state">
                Chargement des notifications...
              </div>

              <div v-else-if="!notifications.length" class="notification-state">
                Aucune notification récente.
              </div>

              <template v-else>
                <button
                  v-for="notification in visibleNotifications"
                  :key="notification.id"
                  class="notification-item"
                  type="button"
                  :class="{ unread: !notification.lu }"
                  @click="markNotificationRead(notification)"
                >
                  <span class="notification-item-icon" :class="{ unread: !notification.lu }">
                    <AppIcon :name="getNotificationIcon(notification.type)" :size="16" />
                  </span>
                  <span class="notification-item-copy">
                    <span class="notification-item-title">{{ notification.titre || 'Notification RH' }}</span>
                    <span class="notification-item-message">{{ notification.message || 'Aucun détail supplémentaire.' }}</span>
                    <span class="notification-item-meta">{{ formatRelativeDate(notification.created_at) }}</span>
                  </span>
                  <span v-if="!notification.lu" class="notification-dot"></span>
                </button>
              </template>
            </div>

            <div class="notification-footer">
              <button
                v-if="notifications.length > visibleCount"
                class="notification-more"
                type="button"
                @click="showMoreNotifications"
              >
                Voir plus
              </button>
              <span v-else-if="notifications.length" class="notification-end">
                Toutes les notifications visibles.
              </span>
            </div>
          </div>
        </transition>
      </div>

      <div class="profile-menu" ref="menuRef">
        <button
          class="profile-trigger"
          type="button"
          @click="toggleMenu"
          aria-haspopup="menu"
          :aria-expanded="String(menuOpen)"
        >
          <span class="avatar">{{ initials }}</span>
          <span class="profile-copy">
            <span class="profile-name">{{ displayName }}</span>
            <span class="profile-meta">
              <span v-if="profileHandle" class="profile-handle">{{ profileHandle }}</span>
              <span class="profile-role">{{ roleLabel }}</span>
            </span>
          </span>
          <span class="profile-caret" :class="{ open: menuOpen }"></span>
        </button>

        <transition name="menu">
          <div v-if="menuOpen" class="profile-dropdown" role="menu">
            <div class="dropdown-profile">
              <span class="avatar large">{{ initials }}</span>
              <div class="dropdown-copy">
                <p class="dropdown-name">{{ displayName }}</p>
                <p class="dropdown-meta">
                  <span v-if="profileHandle">{{ profileHandle }}</span>
                  <span>{{ roleLabel }}</span>
                </p>
              </div>
            </div>
            <button class="menu-item" type="button" @click="logout">
              <AppIcon name="logout" :size="16" />
              <span>Déconnexion</span>
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import AppIcon from '../ui/AppIcon.vue'

defineProps({
  subtitle: {
    type: String,
    default: '',
  },
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
})
defineEmits(['toggle-sidebar'])

const router = useRouter()
const theme = ref(localStorage.getItem('theme') || 'light')
const menuOpen = ref(false)
const notificationOpen = ref(false)
const menuRef = ref(null)
const notificationRef = ref(null)
const userName = ref(localStorage.getItem('user_name') || '')
const userIdentifiant = ref(localStorage.getItem('user_identifiant') || '')
const userRole = ref(localStorage.getItem('role') || '')
const notifications = ref([])
const unreadCount = ref(0)
const notificationsLoading = ref(false)
const visibleCount = ref(6)
const notificationBatch = 6
let notificationPollId = null
let notificationInitialLoadId = null

const roleMap = {
  admin: 'Administrateur',
  rh: 'RH',
  manager: 'Manager',
  employe: 'Employé',
}

const displayName = computed(() => {
  if (userName.value?.trim()) return userName.value.trim()

  const identifiant = userIdentifiant.value?.trim()
  if (!identifiant) return 'Utilisateur RH'

  const base = identifiant.includes('@') ? identifiant.split('@')[0] : identifiant
  return base.replace(/[._-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
})

const roleLabel = computed(() => roleMap[userRole.value] || 'Compte connecté')

const profileHandle = computed(() => {
  const identifiant = userIdentifiant.value?.trim()
  if (!identifiant) return ''

  const base = identifiant.includes('@') ? identifiant.split('@')[0] : identifiant
  return `@${base.replace(/\s+/g, '').toLowerCase()}`
})

const initials = computed(() => {
  const source = displayName.value.trim()
  if (!source) return 'U'

  const parts = source.split(/\s+/).filter(Boolean)
  return parts
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('')
})

const visibleNotifications = computed(() => notifications.value.slice(0, visibleCount.value))

const displayUnreadCount = computed(() => {
  if (unreadCount.value > 99) return '99+'
  return String(unreadCount.value)
})

const closeMenu = () => {
  menuOpen.value = false
}

const closeNotifications = () => {
  notificationOpen.value = false
}

const onDocumentClick = (event) => {
  if (!menuRef.value?.contains(event.target)) {
    closeMenu()
  }

  if (!notificationRef.value?.contains(event.target)) {
    closeNotifications()
  }
}

const toggleMenu = async () => {
  closeNotifications()
  menuOpen.value = !menuOpen.value
  if (menuOpen.value) {
    await nextTick()
  }
}

const getNotificationIcon = (type = '') => {
  if (type.includes('conge')) return 'calendar'
  if (type.includes('message') || type.includes('conversation')) return 'clipboard'
  if (type.includes('demande')) return 'file'
  if (type.includes('contrat')) return 'briefcase'
  if (type.includes('alerte')) return 'shield'
  return 'bell'
}

const formatRelativeDate = (date) => {
  if (!date) return 'Date inconnue'

  const now = Date.now()
  const target = new Date(date).getTime()
  const diffMs = now - target
  const diffMinutes = Math.max(1, Math.floor(diffMs / 60000))

  if (diffMinutes < 60) return `Il y a ${diffMinutes} min`

  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) return `Il y a ${diffHours} h`

  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `Il y a ${diffDays} j`

  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const loadNotificationCount = async () => {
  if (!localStorage.getItem('token')) return

  try {
    const { data } = await api.get('/notifications/count')
    unreadCount.value = Number(data?.count || 0)
  } catch (error) {
    unreadCount.value = 0
  }
}

const loadNotifications = async () => {
  if (!localStorage.getItem('token')) return

  notificationsLoading.value = true

  try {
    const { data } = await api.get('/notifications', {
      params: { limit: 50 },
    })
    notifications.value = Array.isArray(data) ? data : []
  } catch (error) {
    notifications.value = []
  } finally {
    notificationsLoading.value = false
  }
}

const refreshNotificationCenter = async () => {
  await Promise.all([loadNotificationCount(), loadNotifications()])
}

const toggleNotifications = async () => {
  menuOpen.value = false
  notificationOpen.value = !notificationOpen.value

  if (!notificationOpen.value) return

  visibleCount.value = notificationBatch
  await refreshNotificationCenter()
  await nextTick()
}

const showMoreNotifications = () => {
  visibleCount.value += notificationBatch
}

const markNotificationRead = async (notification) => {
  if (!notification || notification.lu) return

  notification.lu = true
  unreadCount.value = Math.max(0, unreadCount.value - 1)

  try {
    await api.post(`/notifications/${notification.id}/lue`)
  } catch (error) {
    notification.lu = false
    unreadCount.value += 1
  }
}

const markAllNotificationsRead = async () => {
  if (!notifications.value.length || unreadCount.value === 0) return

  const previous = notifications.value.map((notification) => ({
    id: notification.id,
    lu: notification.lu,
  }))

  notifications.value = notifications.value.map((notification) => ({
    ...notification,
    lu: true,
  }))
  unreadCount.value = 0

  try {
    await api.post('/notifications/lire-toutes')
  } catch (error) {
    notifications.value = notifications.value.map((notification) => {
      const snapshot = previous.find((entry) => entry.id === notification.id)
      return snapshot ? { ...notification, lu: snapshot.lu } : notification
    })
    unreadCount.value = previous.filter((entry) => !entry.lu).length
  }
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    // ignore logout failures and clear the local session anyway
  }

  localStorage.removeItem('token')
  localStorage.removeItem('role')
  localStorage.removeItem('user_identifiant')
  localStorage.removeItem('user_name')
  router.push('/login')
}

const applyTheme = () => {
  const targets = [document.documentElement, document.body]

  if (theme.value === 'dark') {
    targets.forEach((node) => node?.setAttribute('data-theme', 'dark'))
  } else {
    targets.forEach((node) => node?.removeAttribute('data-theme'))
  }

  localStorage.setItem('theme', theme.value)
}

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  applyTheme()
}

const loadProfile = async () => {
  if (!localStorage.getItem('token')) return
  if (userName.value && userIdentifiant.value) return

  try {
    const { data } = await api.get('/me')
    userName.value = data?.name || userName.value
    userIdentifiant.value = data?.email || data?.identifiant || userIdentifiant.value
    userRole.value = data?.role || userRole.value

    localStorage.setItem('user_name', userName.value)
    localStorage.setItem('user_identifiant', userIdentifiant.value)
    localStorage.setItem('role', userRole.value)
  } catch (error) {
    // ignore profile fetch failures and keep local fallbacks
  }
}

onMounted(() => {
  applyTheme()
  loadProfile()
  notificationInitialLoadId = window.setTimeout(() => {
    loadNotificationCount()
  }, 1500)
  document.addEventListener('click', onDocumentClick)

  notificationPollId = window.setInterval(() => {
    loadNotificationCount()
  }, 60000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  if (notificationInitialLoadId) {
    window.clearTimeout(notificationInitialLoadId)
  }
  if (notificationPollId) {
    window.clearInterval(notificationPollId)
  }
})
</script>

<style scoped>
.topbar {
  position: relative;
  z-index: 120;
  isolation: isolate;
  overflow: visible;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 22px;
  border: 1px solid var(--border);
  border-radius: 0;
  border-top: 0;
  border-right: 0;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
  backdrop-filter: blur(18px);
}

.topbar-copy,
.topbar-actions {
  display: flex;
  align-items: center;
}

.topbar-copy {
  gap: 16px;
  min-width: 0;
}

.menu-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.74);
  color: var(--text);
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

body[data-theme='dark'] .menu-toggle {
  background: rgba(15, 23, 42, 0.72);
}

.menu-toggle:hover {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  background: var(--panel-solid);
}

.topbar-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.08);
  color: var(--brand-600);
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
}

.topbar-eyebrow,
.topbar-title,
.profile-name,
.profile-role {
  margin: 0;
}

.topbar-eyebrow {
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.topbar-title {
  margin-top: 4px;
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.topbar-actions {
  gap: 10px;
}

.theme-toggle,
.notification-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  position: relative;
  width: 44px;
  height: 44px;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.74);
  color: var(--text);
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

body[data-theme='dark'] .theme-toggle,
body[data-theme='dark'] .notification-trigger {
  background: rgba(15, 23, 42, 0.72);
}

.theme-toggle:hover,
.notification-trigger:hover,
.notification-trigger.active {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  background: var(--panel-solid);
}

.notification-trigger.highlighted {
  border-color: rgba(79, 70, 229, 0.24);
  color: var(--brand-600);
}

.notification-menu {
  position: relative;
  z-index: 180;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -5px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  line-height: 18px;
  box-shadow: 0 8px 18px rgba(79, 70, 229, 0.28);
}

.notification-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 230;
  width: min(380px, calc(100vw - 28px));
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 22px 50px rgba(15, 23, 42, 0.16);
}

body[data-theme='dark'] .notification-dropdown {
  background: #0f172a;
}

.notification-header,
.notification-summary,
.notification-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.notification-header {
  padding: 4px 4px 12px;
}

.notification-eyebrow,
.notification-title {
  margin: 0;
}

.notification-eyebrow {
  color: var(--brand-600);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.notification-title {
  margin-top: 4px;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.notification-mark-all,
.notification-more {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 36px;
  padding: 0 14px;
  border: 1px solid rgba(79, 70, 229, 0.16);
  border-radius: 999px;
  background: rgba(79, 70, 229, 0.08);
  color: var(--brand-600);
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

.notification-mark-all:hover,
.notification-more:hover {
  transform: translateY(-1px);
  border-color: rgba(79, 70, 229, 0.28);
  background: rgba(79, 70, 229, 0.12);
}

.notification-summary {
  padding: 12px 14px;
  margin-bottom: 10px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: rgba(79, 70, 229, 0.04);
}

.notification-summary-copy {
  display: grid;
  gap: 4px;
}

.notification-summary-copy strong {
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.notification-summary-copy span,
.notification-summary-meta,
.notification-end {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 600;
}

.notification-scroll {
  display: grid;
  gap: 10px;
  max-height: 420px;
  overflow-y: auto;
  padding-right: 2px;
}

.notification-scroll::-webkit-scrollbar {
  width: 8px;
}

.notification-scroll::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.38);
}

.notification-state {
  padding: 18px 12px;
  border: 1px dashed var(--border);
  border-radius: 16px;
  color: var(--muted);
  font-size: 0.9rem;
  text-align: center;
}

.notification-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: transparent;
  color: var(--text);
  text-align: left;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

.notification-item:hover {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  background: rgba(79, 70, 229, 0.04);
}

.notification-item.unread {
  border-color: rgba(79, 70, 229, 0.18);
  background: rgba(79, 70, 229, 0.06);
}

.notification-item-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 12px;
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
  flex: none;
}

.notification-item-icon.unread {
  background: rgba(79, 70, 229, 0.12);
  color: var(--brand-600);
}

.notification-item-copy {
  display: grid;
  gap: 4px;
  min-width: 0;
}

.notification-item-title,
.notification-item-message,
.notification-item-meta {
  display: block;
}

.notification-item-title {
  font-size: 0.88rem;
  font-weight: 700;
  line-height: 1.35;
}

.notification-item-message {
  color: var(--muted);
  font-size: 0.8rem;
  line-height: 1.45;
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.notification-item-meta {
  color: var(--muted);
  font-size: 0.72rem;
  font-weight: 700;
}

.notification-dot {
  width: 8px;
  height: 8px;
  margin-top: 6px;
  border-radius: 999px;
  background: var(--brand-500);
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
}

.notification-footer {
  padding: 12px 4px 2px;
}

.profile-menu {
  position: relative;
  z-index: 160;
}

.profile-trigger {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  min-height: 52px;
  min-width: 220px;
  max-width: 280px;
  padding: 8px 12px 8px 8px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.78);
  color: var(--text);
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

body[data-theme='dark'] .profile-trigger {
  background: rgba(15, 23, 42, 0.72);
}

.profile-trigger:hover {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  background: var(--panel-solid);
  box-shadow: var(--shadow-sm);
}

.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  flex: none;
  background: linear-gradient(135deg, #14b8a6, #0f766e);
  color: #fff;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.avatar.large {
  width: 42px;
  height: 42px;
  font-size: 0.88rem;
}

.profile-copy {
  display: grid;
  min-width: 0;
  flex: 1;
  gap: 3px;
  text-align: left;
}

.profile-name {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.profile-meta {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 8px;
}

.profile-handle,
.profile-role {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 600;
  line-height: 1.2;
}

.profile-handle {
  color: #0f766e;
  font-weight: 700;
}

body[data-theme='dark'] .profile-handle {
  color: #2dd4bf;
}

.profile-caret {
  width: 8px;
  height: 8px;
  border-right: 1.8px solid currentColor;
  border-bottom: 1.8px solid currentColor;
  transform: rotate(45deg);
  color: var(--muted);
  margin-right: 2px;
  transition: transform 0.18s ease;
}

.profile-caret.open {
  transform: rotate(225deg);
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 220;
  min-width: 240px;
  padding: 8px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}

body[data-theme='dark'] .profile-dropdown {
  background: #0f172a;
}

.dropdown-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 10px 12px;
  margin-bottom: 6px;
  border-bottom: 1px solid var(--border);
}

.dropdown-copy {
  min-width: 0;
}

.dropdown-name,
.dropdown-meta {
  margin: 0;
}

.dropdown-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.92rem;
  font-weight: 700;
}

.dropdown-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 600;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 40px;
  padding: 0 12px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: var(--text);
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease;
}

.menu-item:hover {
  background: rgba(240, 68, 56, 0.08);
  color: var(--danger-500);
}

.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (max-width: 900px) {
  .topbar {
    flex-direction: column;
    align-items: stretch;
  }

  .topbar-copy,
  .topbar-actions {
    justify-content: space-between;
  }
}

@media (max-width: 680px) {
  .topbar-copy,
  .topbar-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .topbar-copy {
    gap: 12px;
  }

  .topbar-actions {
    flex-direction: row;
    justify-content: space-between;
  }

  .profile-trigger {
    width: 100%;
    justify-content: space-between;
  }

  .profile-dropdown {
    left: 0;
    right: 0;
  }

  .notification-dropdown {
    left: 0;
    right: auto;
  }
}
</style>
