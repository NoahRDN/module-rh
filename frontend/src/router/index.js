import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import EmployeesView from '../views/EmployeesView.vue'
import DepartementsView from '../views/DepartementsView.vue'
import PostesView from '../views/PostesView.vue'
import ContratsView from '../views/ContratsView.vue'
import ContratCreateView from '../views/ContratCreateView.vue'
import ContratDetailView from '../views/ContratDetailView.vue'
import ContratsHistoriqueView from '../views/ContratsHistoriqueView.vue'
import DocumentsView from '../views/DocumentsView.vue'
import HistoriquePostesView from '../views/HistoriquePostesView.vue'
import HistoriqueCreateView from '../views/HistoriqueCreateView.vue'
import AbsenceTypesView from '../views/AbsenceTypesView.vue'
import DemandesCongesView from '../views/DemandesCongesView.vue'
import CalendrierEvenementsView from '../views/CalendrierEvenementsView.vue'
import AlertesView from '../views/AlertesView.vue'
import PointagesView from '../views/PointagesView.vue'
import PaieParametresView from '../views/PaieParametresView.vue'
import PaieGenerationView from '../views/PaieGenerationView.vue'
import EmployeeDetailView from '../views/EmployeeDetailView.vue'
import EmployeeCreateView from '../views/EmployeeCreateView.vue'
import SoldeCongesView from '../views/SoldeCongesView.vue'
import SoldeCongeDetailView from '../views/SoldeCongeDetailView.vue'
import RelevePresenceView from '../views/RelevePresenceView.vue'
import MainLayout from '../components/layout/MainLayout.vue'
import WorktimeConfigView from '../views/WorktimeConfigView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard', component: DashboardView, meta: { subtitle: 'Vue d’ensemble' } },
      { path: 'employes', name: 'employes', component: EmployeesView, meta: { subtitle: 'Annuaire' } },
      { path: 'employes/nouveau', name: 'employes-create', component: EmployeeCreateView, meta: { subtitle: 'Nouvel employé' } },
      { path: 'employes/:id', name: 'employe-detail', component: EmployeeDetailView, meta: { subtitle: 'Fiche employé' } },
      { path: 'departements', name: 'departements', component: DepartementsView, meta: { subtitle: 'Structure' } },
      { path: 'postes', name: 'postes', component: PostesView, meta: { subtitle: 'Fonctions' } },
      { path: 'contrats', name: 'contrats', component: ContratsView, meta: { subtitle: 'Contrats' } },
      { path: 'contrats/nouveau', name: 'contrats-create', component: ContratCreateView, meta: { subtitle: 'Nouveau contrat' } },
      { path: 'contrats/:id', name: 'contrat-detail', component: ContratDetailView, meta: { subtitle: 'Fiche contrat' } },
      { path: 'contrats-historiques', name: 'contrats-historiques', component: ContratsHistoriqueView, meta: { subtitle: 'Historique contrats' } },
      { path: 'absences-types', name: 'absences-types', component: AbsenceTypesView, meta: { subtitle: 'Types de congé' } },
      { path: 'soldes-conges', name: 'soldes-conges', component: SoldeCongesView, meta: { subtitle: 'Soldes congés' } },
      { path: 'soldes-conges/:id', name: 'solde-conge-detail', component: SoldeCongeDetailView, meta: { subtitle: 'Détail solde congé' } },
      { path: 'demandes-conges', name: 'demandes-conges', component: DemandesCongesView, meta: { subtitle: 'Congés' } },
      { path: 'calendrier', name: 'calendrier', component: CalendrierEvenementsView, meta: { subtitle: 'Calendrier' } },
      { path: 'alertes', name: 'alertes', component: AlertesView, meta: { subtitle: 'Alertes' } },
      { path: 'pointages', name: 'pointages', component: PointagesView, meta: { subtitle: 'Pointage' } },
      { path: 'releve-presence', name: 'releve-presence', component: RelevePresenceView, meta: { subtitle: 'Présence' } },
      { path: 'worktime-config', name: 'worktime-config', component: WorktimeConfigView, meta: { subtitle: 'Horaires' } },
      { path: 'paie-parametres', name: 'paie-parametres', component: PaieParametresView, meta: { subtitle: 'Paie' } },
      { path: 'paie-generation', name: 'paie-generation', component: PaieGenerationView, meta: { subtitle: 'Paie' } },
      { path: 'documents', name: 'documents', component: DocumentsView, meta: { subtitle: 'Documents' } },
      { path: 'historiques', name: 'historiques', component: HistoriquePostesView, meta: { subtitle: 'Mobilités' } },
      { path: 'historiques/nouveau', name: 'historiques-create', component: HistoriqueCreateView, meta: { subtitle: 'Nouvelle mobilité' } }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// garde globale
router.beforeEach((to) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('token')
    if (!token) return { name: 'login' }
  }
})

export default router
