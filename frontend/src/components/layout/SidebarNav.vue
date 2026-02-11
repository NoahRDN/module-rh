<template>
  <aside class="sidebar card">
    <div class="brand">
      <div class="dot"></div>
      <div>
        <p class="brand-name">HR Core</p>
        <p class="brand-sub">Module RH</p>
      </div>
    </div>

    <nav class="menu">
      <div v-for="group in filteredGroups" :key="group.label" class="menu-group">
        <button class="group-toggle" @click="toggle(group.label)">
          <span class="icon">{{ group.icon }}</span>
          <span class="label">{{ group.label }}</span>
          <span class="chevron" :class="{ open: openGroups[group.label] }">▾</span>
        </button>
        <div v-show="openGroups[group.label]" class="group-items">
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="menu-item"
            :class="{ active: route.path === item.to || route.path.startsWith(item.to + '/') }"
          >
            <span class="icon">{{ item.icon }}</span>
            <div>
              <p class="label">{{ item.label }}</p>
              <p class="hint" v-if="item.hint">{{ item.hint }}</p>
            </div>
          </RouterLink>
        </div>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { reactive } from 'vue'
import { useRoute } from 'vue-router'

import { computed } from 'vue'
const route = useRoute()
const role = localStorage.getItem('role') || ''

const groups = [
  {
    label: 'Vue globale',
    icon: '📊',
    items: [{ to: '/dashboard', label: 'Tableau de bord', hint: 'Vue d’ensemble', icon: '📈' }]
  },
  {
    label: 'Ressources humaines',
    icon: '👥',
    items: [
      { to: '/employes', label: 'Employés', hint: 'Annuaire & contrats', icon: '👤' },
      { to: '/departements', label: 'Départements', hint: 'Structure', icon: '🏢' },
      { to: '/postes', label: 'Postes', hint: 'Fonctions', icon: '🪜' },
      { to: '/categories-postes', label: 'Catégories de postes', hint: 'Niveaux / familles', icon: '🏷️' },
      { to: '/historiques', label: 'Historique postes', hint: 'Mobilités', icon: '⏱️' },
      { to: '/contrats', label: 'Contrats', hint: 'Contrats actifs', icon: '📄' },
      { to: '/documents', label: 'Documents', hint: 'RH & pièces', icon: '📁' }
    ]
  },
  {
    label: 'Congés & absences',
    icon: '🗓️',
    items: [
      { to: '/absences-types', label: 'Types de congé', hint: 'Congés / absences', icon: '🏷️' },
      { to: '/soldes-conges', label: 'Soldes congés', hint: 'Payés / maladie / exceptionnels', icon: '🧮' },
      { to: '/demandes-conges', label: 'Demandes de congés', hint: 'Workflow', icon: '🗓️' },
      { to: '/calendrier', label: 'Calendrier', hint: 'Vue globale', icon: '📆' },
      { to: '/alertes', label: 'Alertes', hint: 'Congés / absences', icon: '🔔' },
      { to: '/alerte-settings', label: 'Config. Alertes', hint: 'Seuils & paramètres', icon: '⚙️' },
      { to: '/jours-feries', label: 'Jours fériés', hint: 'Liste & ajout', icon: '🎉' },
      { to: '/pointages', label: 'Pointage & HS', hint: 'Entrées / sorties', icon: '⏱️' },
      { to: '/releve-presence', label: 'Relevé présence', hint: 'Heures & absences', icon: '📄' }
    ]
  },
  {
    label: 'Paie',
    icon: '💰',
    items: [
      { to: '/paie-parametres', label: 'Paramètres paie', hint: 'CNAPS/OSTIE/IRSA', icon: '⚙️' },
      { to: '/paie-generation', label: 'Génération paie', hint: 'Brut/Net', icon: '💰' },
      { to: '/worktime-config', label: 'Worktime', hint: 'configuration', icon: '⚙️' }
    ]
  },
  // {
  //   label: 'IA & Analytics',
  //   icon: '📈',
  //   items: [
  //     { to: '/turnover', label: 'Prédiction Turnover', hint: 'Analyse IA', icon: '📈' },
  //     { to: '/anomalies', label: 'Détection Anomalies', hint: 'Surveillance IA', icon: '🔍' }
  //   ]
  // },
  // {
  //   label: 'Manager',
  //   icon: '👔',
  //   items: [{ to: '/manager', label: 'Portail Manager', hint: 'Mon équipe', icon: '👥' }]
  // },
  // {
  //   label: 'Audit & Conformité',
  //   icon: '🔐',
  //   items: [
  //     { to: '/audit', label: 'Journal d\'audit', hint: 'Traçabilité', icon: '🔍' },
  //     // { to: '/archives', label: 'Archives', hint: 'Documents légaux', icon: '🗄️' },
  //     { to: '/permissions', label: 'Permissions', hint: 'Contrôle d\'accès', icon: '🔐' }
  //   ]
  // }
]

const openGroups = reactive(
  Object.fromEntries(groups.map((g) => [g.label, true]))
)

const filteredGroups = computed(() => {
  if (role === 'employe') {
    return [{
      label: 'Self-Service',
      icon: '🏠',
      items: [{ to: '/self-service', label: 'Mon Espace', hint: 'Self-Service', icon: '🏠' }]
    }]
  }
  return groups.filter((g) => g.label !== 'Self-Service')
})

const toggle = (label) => {
  openGroups[label] = !openGroups[label]
}
</script>

<style scoped>
.sidebar {
  position: sticky;
  top: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 20px 16px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 8px;
}

.dot {
  width: 14px;
  height: 14px;
  background: linear-gradient(120deg, var(--accent), var(--accent-strong));
  border-radius: 50%;
  box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.15);
}

.brand-name {
  margin: 0;
  font-weight: 800;
}

.brand-sub {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
}

.menu {
  display: grid;
  gap: 8px;
}

.menu-group {
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}

.group-toggle {
  width: 100%;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.02);
  border: none;
  color: var(--text);
  cursor: pointer;
}

.group-toggle .label {
  margin: 0;
  font-weight: 700;
}

.group-toggle .chevron {
  margin-left: auto;
  transition: transform 120ms ease;
}
.group-toggle .chevron.open {
  transform: rotate(180deg);
}

.group-items {
  display: grid;
  gap: 4px;
  padding: 6px;
}

.menu-item {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid transparent;
  color: var(--text);
  transition: border-color 120ms ease, background 120ms ease;
}

.menu-item:hover {
  border-color: var(--border);
  background: rgba(255, 255, 255, 0.02);
}

.menu-item.active {
  border-color: rgba(34, 197, 94, 0.3);
  background: rgba(34, 197, 94, 0.06);
}

.icon {
  font-size: 18px;
}

.label {
  margin: 0;
  font-weight: 700;
}

.hint {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
}
</style>
