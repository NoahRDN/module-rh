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
      <RouterLink
        v-for="item in items"
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
    </nav>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const items = [
  { to: '/dashboard', label: 'Tableau de bord', hint: 'Vue d’ensemble', icon: '📊' },
  { to: '/employes', label: 'Employés', hint: 'Annuaire & contrats', icon: '👤' },
  { to: '/departements', label: 'Départements', hint: 'Structure', icon: '🏢' },
  { to: '/postes', label: 'Postes', hint: 'Fonctions', icon: '🪜' },
  { to: '/contrats', label: 'Contrats', hint: 'CDD / CDI / Stage', icon: '📄' },
  { to: '/contrats-historiques', label: 'Hist. contrats', hint: 'Versions & renouvellements', icon: '🗂️' },
  { to: '/absences-types', label: 'Types d\'absence', hint: 'Congés / absences', icon: '🏷️' },
  { to: '/soldes-conges', label: 'Soldes congés', hint: 'Payés / maladie / exceptionnels', icon: '🧮' },
  { to: '/demandes-conges', label: 'Demandes de congés', hint: 'Workflow', icon: '🗓️' },
  { to: '/calendrier', label: 'Calendrier', hint: 'Vue globale', icon: '📆' },
  { to: '/alertes', label: 'Alertes', hint: 'Congés / absences', icon: '🔔' },
  { to: '/pointages', label: 'Pointage & HS', hint: 'Entrées / sorties', icon: '⏱️' },
  { to: '/releve-presence', label: 'Relevé présence', hint: 'Heures & absences', icon: '📄' },
  { to: '/paie-parametres', label: 'Paramètres paie', hint: 'CNAPS/OSTIE/IRSA', icon: '⚙️' },
  { to: '/paie-generation', label: 'Génération paie', hint: 'Brut/Net', icon: '💰' },
  { to: '/documents', label: 'Documents', hint: 'RH & pièces', icon: '📁' },
  { to: '/historiques', label: 'Historique postes', hint: 'Mobilités', icon: '⏱️' }
]
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
