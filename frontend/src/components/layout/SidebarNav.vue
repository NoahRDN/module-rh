<template>
  <aside class="sidebar" :class="{ closed: !open }">
    <div class="sidebar-shell">
      <div class="brand">
        <div class="brand-mark">
          <AppIcon name="grid" :size="18" />
        </div>
        <div>
          <p class="brand-eyebrow">Executive Layer</p>
          <p class="brand-name">Module RH</p>
          <p class="brand-sub">People ops and payroll</p>
        </div>
      </div>

      <div class="workspace-card">
        <div class="workspace-head">
          <span class="workspace-chip">Workspace</span>
          <span class="workspace-badge">HR ops</span>
        </div>
        <p class="workspace-title">Gestion RH</p>
        <p class="workspace-copy">Paie, structure et opérations RH dans une interface unique.</p>
        <div class="workspace-metrics">
          <span class="workspace-stat">Annuaire</span>
          <span class="workspace-stat">Paie</span>
          <span class="workspace-stat">Temps</span>
        </div>
      </div>

      <nav class="nav">
        <section v-for="group in filteredGroups" :key="group.label" class="nav-group">
          <div class="nav-group-label">
            <AppIcon :name="group.icon" :size="15" />
            <span>{{ group.label }}</span>
          </div>

          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
          >
            <div class="nav-icon">
              <AppIcon :name="item.icon" :size="18" />
            </div>
            <div class="nav-copy">
              <div class="nav-title">{{ item.label }}</div>
              <div v-if="item.hint" class="nav-hint">{{ item.hint }}</div>
            </div>
          </RouterLink>
        </section>
      </nav>

      <div class="sidebar-footer">
        <span class="footer-badge">
          <span class="footer-dot"></span>
          Stack active
        </span>
        <p class="footer-text">Paie, contrats et présence prêts à piloter.</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '../ui/AppIcon.vue'

defineProps({
  open: {
    type: Boolean,
    default: true,
  },
})

const route = useRoute()
const role = localStorage.getItem('role') || ''

const groups = [
  {
    label: 'Pilotage',
    icon: 'grid',
    items: [
      { to: '/dashboard', label: 'Tableau de bord', hint: 'Vue d’ensemble', icon: 'grid' },
    ],
  },
  {
    label: 'Organisation',
    icon: 'users',
    items: [
      { to: '/employes', label: 'Employés', hint: 'Annuaire et contrats', icon: 'users' },
      { to: '/departements', label: 'Départements', hint: 'Structure', icon: 'building' },
      { to: '/postes', label: 'Postes', hint: 'Fonctions', icon: 'briefcase' },
      { to: '/categories-postes', label: 'Catégories', hint: 'Familles et niveaux', icon: 'layers' },
      { to: '/historiques', label: 'Historique postes', hint: 'Mobilités internes', icon: 'history' },
      { to: '/contrats', label: 'Contrats', hint: 'Contrats actifs', icon: 'file' },
      { to: '/documents', label: 'Documents', hint: 'Pièces RH', icon: 'folder' },
    ],
  },
  {
    label: 'Temps & absences',
    icon: 'calendar',
    items: [
      { to: '/absences-types', label: 'Types de congé', hint: 'Congés et absences', icon: 'tag' },
      { to: '/soldes-conges', label: 'Soldes congés', hint: 'Suivi des droits', icon: 'wallet' },
      { to: '/demandes-conges', label: 'Demandes', hint: 'Workflow', icon: 'calendar' },
      { to: '/calendrier', label: 'Calendrier', hint: 'Vue globale', icon: 'calendar' },
      { to: '/alertes', label: 'Alertes', hint: 'Seuils et surveillance', icon: 'bell' },
      { to: '/alerte-settings', label: 'Paramètres alertes', hint: 'Configuration', icon: 'settings' },
      { to: '/jours-feries', label: 'Jours fériés', hint: 'Calendrier légal', icon: 'sparkles' },
      { to: '/pointages', label: 'Pointages', hint: 'Entrées et sorties', icon: 'clock' },
      { to: '/releve-presence', label: 'Présence', hint: 'Relevés', icon: 'clipboard' },
    ],
  },
  {
    label: 'Payroll',
    icon: 'wallet',
    items: [
      { to: '/paie-parametres', label: 'Paramètres paie', hint: 'CNAPS, OSTIE, IRSA', icon: 'settings' },
      { to: '/paie-generation', label: 'Génération paie', hint: 'Brut, net, PDF', icon: 'receipt' },
      { to: '/worktime-config', label: 'Horaires', hint: 'Temps de travail', icon: 'clock' },
    ],
  },
]

const selfServiceGroups = [
  {
    label: 'Mon espace',
    icon: 'grid',
    items: [
      { to: '/self-service/dashboard', label: 'Dashboard', hint: 'Vue personnelle', icon: 'grid' },
      { to: '/self-service/profil', label: 'Profil', hint: 'Informations personnelles', icon: 'users' },
      { to: '/self-service/conges', label: 'Congés', hint: 'Demandes et solde', icon: 'calendar' },
      { to: '/self-service/bulletins', label: 'Bulletins', hint: 'Paie et PDF', icon: 'receipt' },
      { to: '/self-service/documents', label: 'Documents', hint: 'Pièces disponibles', icon: 'folder' },
      { to: '/self-service/messagerie', label: 'Messagerie', hint: 'Échanges RH', icon: 'bell' },
    ],
  },
]

const filteredGroups = computed(() => (role === 'employe' ? selfServiceGroups : groups))

const isActive = (path) => route.path === path || route.path.startsWith(`${path}/`)
</script>

<style scoped>
.sidebar {
  position: sticky;
  top: 0;
  align-self: start;
  min-height: 100vh;
  width: 288px;
  overflow: hidden;
  transition: width 0.25s ease;
}

.sidebar.closed {
  width: 0;
}

.sidebar-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 100vh;
  padding: 20px 16px 16px;
  border: 1px solid var(--border);
  border-radius: 0;
  border-left: 0;
  background: var(--panel);
  box-shadow: var(--shadow-lg);
  backdrop-filter: blur(18px);
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
  color: #ffffff;
  box-shadow: 0 16px 28px rgba(79, 70, 229, 0.24);
}

.brand-eyebrow,
.brand-sub,
.workspace-copy,
.footer-text {
  margin: 0;
}

.brand-eyebrow {
  color: var(--brand-600);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.brand-name {
  margin: 2px 0 0;
  font-size: 1.08rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.brand-sub {
  margin-top: 2px;
  color: var(--muted);
  font-size: 0.86rem;
}

.workspace-card {
  display: grid;
  gap: 12px;
  padding: 16px;
  border-radius: 24px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background:
    linear-gradient(180deg, rgba(79, 70, 229, 0.09), rgba(255, 255, 255, 0)),
    var(--panel-soft);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
}

.workspace-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.workspace-chip,
.footer-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.08);
  color: var(--brand-600);
  font-size: 0.74rem;
  font-weight: 700;
}

.workspace-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 5px 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  color: var(--muted);
  font-size: 0.72rem;
  font-weight: 700;
}

body[data-theme='dark'] .workspace-badge {
  background: rgba(15, 23, 42, 0.72);
}

.workspace-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.workspace-copy {
  color: var(--muted);
  font-size: 0.82rem;
  line-height: 1.45;
}

.workspace-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.workspace-stat {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 30px;
  padding: 0 10px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.74);
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 700;
}

body[data-theme='dark'] .workspace-stat {
  background: rgba(15, 23, 42, 0.72);
}

.nav {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 14px;
  min-height: 0;
  overflow: auto;
  padding-right: 4px;
}

.nav-group {
  display: grid;
  gap: 8px;
}

.nav-group-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 0 6px 2px;
}

.nav-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 12px;
  align-items: center;
  min-width: 0;
  padding: 10px 12px;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.66);
  color: inherit;
  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.nav-item:hover {
  transform: translateY(-1px);
  border-color: rgba(79, 70, 229, 0.14);
  background: rgba(255, 255, 255, 0.74);
}

body[data-theme='dark'] .nav-item:hover {
  background: rgba(15, 23, 42, 0.62);
}

.nav-item.active {
  border-color: rgba(79, 70, 229, 0.18);
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(255, 255, 255, 0.84)),
    rgba(255, 255, 255, 0.82);
  box-shadow: 0 10px 26px rgba(79, 70, 229, 0.08);
}

body[data-theme='dark'] .nav-item.active {
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.2), rgba(15, 23, 42, 0.82));
  box-shadow: none;
}

.nav-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 13px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.8);
  color: var(--muted);
}

body[data-theme='dark'] .nav-icon {
  background: rgba(15, 23, 42, 0.78);
}

.nav-item.active .nav-icon {
  border-color: rgba(79, 70, 229, 0.18);
  background: rgba(79, 70, 229, 0.14);
  color: var(--brand-600);
}

.nav-copy {
  display: grid;
  min-width: 0;
  gap: 3px;
}

.nav-title {
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.25;
}

.nav-hint {
  display: block;
  color: var(--muted);
  font-size: 0.8rem;
  line-height: 1.3;
  white-space: normal;
  overflow-wrap: anywhere;
}

.sidebar-footer {
  display: grid;
  gap: 8px;
  padding: 2px 4px 0;
}

.footer-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #22c55e;
  box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.12);
}

.footer-text {
  color: var(--muted);
  font-size: 0.8rem;
  line-height: 1.45;
}

@media (max-width: 1100px) {
  .sidebar {
    width: 0;
    min-height: 0;
  }

  .sidebar-shell {
    min-height: 100vh;
  }
}
</style>
