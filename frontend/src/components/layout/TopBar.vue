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
const menuRef = ref(null)
const userName = ref(localStorage.getItem('user_name') || '')
const userIdentifiant = ref(localStorage.getItem('user_identifiant') || '')
const userRole = ref(localStorage.getItem('role') || '')

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

const closeMenu = () => {
  menuOpen.value = false
}

const onDocumentClick = (event) => {
  if (!menuRef.value?.contains(event.target)) {
    closeMenu()
  }
}

const toggleMenu = async () => {
  menuOpen.value = !menuOpen.value
  if (menuOpen.value) {
    await nextTick()
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
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
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

.theme-toggle {
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

body[data-theme='dark'] .theme-toggle {
  background: rgba(15, 23, 42, 0.72);
}

.theme-toggle:hover {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  background: var(--panel-solid);
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
}
</style>
