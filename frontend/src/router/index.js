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
import PointageCreateView from '../views/PointageCreateView.vue'
import PaieParametresView from '../views/PaieParametresView.vue'
import PaieGenerationView from '../views/PaieGenerationView.vue'
import EmployeeDetailView from '../views/EmployeeDetailView.vue'
import EmployeeCreateView from '../views/EmployeeCreateView.vue'
import SoldeCongesView from '../views/SoldeCongesView.vue'
import SoldeCongeDetailView from '../views/SoldeCongeDetailView.vue'
import RelevePresenceView from '../views/RelevePresenceView.vue'
import MainLayout from '../components/layout/MainLayout.vue'
import WorktimeConfigView from '../views/WorktimeConfigView.vue'
import PerformancesView from '../views/PerformancesView.vue'
import EvaluationCreateView from '../views/EvaluationCreateView.vue'
import AlerteSettingsView from '../views/AlerteSettingsView.vue'
import JoursFeriesView from '../views/JoursFeriesView.vue'
import JourFerieCreateView from '../views/JourFerieCreateView.vue'
import DepartementCreateView from '../views/DepartementCreateView.vue'
import PosteCreateView from '../views/PosteCreateView.vue'
import DocumentCreateView from '../views/DocumentCreateView.vue'
import DemandeCongeCreateView from '../views/DemandeCongeCreateView.vue'

// Compétences et Formations
import CompetencesView from '../views/CompetencesView.vue'
import CompetencesEmployesView from '../views/CompetencesEmployesView.vue'
import CompetencesPostesView from '../views/CompetencesPostesView.vue'
import CategoriePostesView from '../views/CategoriePostesView.vue'
import FormationsView from '../views/FormationsView.vue'
import MatchingView from '../views/MatchingView.vue'
import AbsenceTypeCreateView from '../views/AbsenceTypeCreateView.vue'

// IA et Automatisation
import TurnoverAnalysisView from '../views/TurnoverAnalysisView.vue'
import AnomaliesDetectionView from '../views/AnomaliesDetectionView.vue'
import AIMatchingView from '../views/AIMatchingView.vue'

// Self-Service Employé
import SelfServiceDashboard from '../views/SelfService/DashboardView.vue'
import SelfServiceProfil from '../views/SelfService/ProfilView.vue'
import SelfServiceDemandes from '../views/SelfService/DemandesView.vue'
import SelfServiceMessagerie from '../views/SelfService/MessagerieView.vue'
import SelfServiceConges from '../views/SelfService/CongesView.vue'
import SelfServiceBulletins from '../views/SelfService/BulletinsView.vue'
import SelfServiceFormations from '../views/SelfService/FormationsReadView.vue'
import SelfServiceCompetences from '../views/SelfService/CompetencesView.vue'
import SelfServiceDocuments from '../views/SelfService/DocumentsView.vue'

// Manager Portal
import ManagerDashboardView from '../views/Manager/DashboardView.vue'

// Audit et Conformité
import AuditView from '../views/AuditView.vue'
import ArchivesView from '../views/ArchivesView.vue'
import PermissionsView from '../views/PermissionsView.vue'

// Layout Self-Service (utilise MainLayout pour le moment)
const SelfServiceLayout = MainLayout

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
      { path: 'departements/nouveau', name: 'departement-create', component: DepartementCreateView, meta: { subtitle: 'Nouveau département' } },
      { path: 'postes', name: 'postes', component: PostesView, meta: { subtitle: 'Fonctions' } },
      { path: 'postes/nouveau', name: 'poste-create', component: PosteCreateView, meta: { subtitle: 'Nouveau poste' } },
      { path: 'categories-postes', name: 'categories-postes', component: CategoriePostesView, meta: { subtitle: 'Catégories de postes' } },
      { path: 'documents/nouveau', name: 'document-create', component: DocumentCreateView, meta: { subtitle: 'Nouveau document' } },
      { path: 'contrats', name: 'contrats', component: ContratsView, meta: { subtitle: 'Contrats' } },
      { path: 'contrats/nouveau', name: 'contrats-create', component: ContratCreateView, meta: { subtitle: 'Nouveau contrat' } },
      { path: 'contrats/:id', name: 'contrat-detail', component: ContratDetailView, meta: { subtitle: 'Fiche contrat' } },
      { path: 'contrats-historiques', name: 'contrats-historiques', component: ContratsHistoriqueView, meta: { subtitle: 'Historique contrats' } },
      { path: 'absences-types', name: 'absences-types', component: AbsenceTypesView, meta: { subtitle: 'Types de congé' } },
      { path: 'absences-types/nouveau', name: 'absence-type-create', component: AbsenceTypeCreateView, meta: { subtitle: 'Nouveau type' } },
      { path: 'soldes-conges', name: 'soldes-conges', component: SoldeCongesView, meta: { subtitle: 'Soldes congés' } },
      { path: 'soldes-conges/:id', name: 'solde-conge-detail', component: SoldeCongeDetailView, meta: { subtitle: 'Détail solde congé' } },
      { path: 'demandes-conges', name: 'demandes-conges', component: DemandesCongesView, meta: { subtitle: 'Congés' } },
      { path: 'demandes-conges/nouveau', name: 'demandes-conges-create', component: DemandeCongeCreateView, meta: { subtitle: 'Nouvelle demande' } },
      { path: 'calendrier', name: 'calendrier', component: CalendrierEvenementsView, meta: { subtitle: 'Calendrier' } },
      { path: 'alertes', name: 'alertes', component: AlertesView, meta: { subtitle: 'Alertes' } },
      { path: 'alerte-settings', name: 'alerte-settings', component: AlerteSettingsView, meta: { subtitle: 'Config. Alertes' } },
      { path: 'jours-feries', name: 'jours-feries', component: JoursFeriesView, meta: { subtitle: 'Jours fériés' } },
      { path: 'jours-feries/nouveau', name: 'jour-ferie-create', component: JourFerieCreateView, meta: { subtitle: 'Nouveau jour férié' } },
      { path: 'performances', name: 'performances', component: PerformancesView, meta: { subtitle: 'Performances' } },
      { path: 'performances/nouvelle', name: 'evaluation-create', component: EvaluationCreateView, meta: { subtitle: 'Nouvelle évaluation' } },
      { path: 'pointages', name: 'pointages', component: PointagesView, meta: { subtitle: 'Pointage' } },
      { path: 'pointages/nouveau', name: 'pointages-create', component: PointageCreateView, meta: { subtitle: 'Nouveau pointage' } },
      { path: 'releve-presence', name: 'releve-presence', component: RelevePresenceView, meta: { subtitle: 'Présence' } },
      { path: 'worktime-config', name: 'worktime-config', component: WorktimeConfigView, meta: { subtitle: 'Horaires' } },
      { path: 'paie-parametres', name: 'paie-parametres', component: PaieParametresView, meta: { subtitle: 'Paie' } },
      { path: 'paie-generation', name: 'paie-generation', component: PaieGenerationView, meta: { subtitle: 'Paie' } },
      { path: 'documents', name: 'documents', component: DocumentsView, meta: { subtitle: 'Documents' } },
      { path: 'historiques', name: 'historiques', component: HistoriquePostesView, meta: { subtitle: 'Mobilités' } },
      { path: 'historiques/nouveau', name: 'historiques-create', component: HistoriqueCreateView, meta: { subtitle: 'Nouvelle mobilité' } },
      
      // Compétences et Formations
      { path: 'competences', name: 'competences', component: CompetencesView, meta: { subtitle: 'Cartographie des compétences' } },
      { path: 'competences-employes', name: 'competences-employes', component: CompetencesEmployesView, meta: { subtitle: 'Compétences employés' } },
      { path: 'competences-postes', name: 'competences-postes', component: CompetencesPostesView, meta: { subtitle: 'Compétences postes' } },
      { path: 'formations', name: 'formations', component: FormationsView, meta: { subtitle: 'Catalogue des formations' } },
      { path: 'matching', name: 'matching', component: MatchingView, meta: { subtitle: 'Matching Profil/Poste' } },
      
      // IA et Automatisation
      { path: 'turnover', name: 'turnover', component: TurnoverAnalysisView, meta: { subtitle: 'Prédiction Turnover', roles: ['admin', 'rh'] } },
      { path: 'anomalies', name: 'anomalies', component: AnomaliesDetectionView, meta: { subtitle: 'Détection Anomalies', roles: ['admin', 'rh'] } },
      { path: 'matching-ia', name: 'matching-ia', component: AIMatchingView, meta: { subtitle: 'Matching IA', roles: ['admin', 'rh'] } },
      
      // Audit et Conformité
      { path: 'audit', name: 'audit', component: AuditView, meta: { subtitle: 'Journal d\'audit', roles: ['admin', 'rh'] } },
      { path: 'archives', name: 'archives', component: ArchivesView, meta: { subtitle: 'Archives', roles: ['admin', 'rh'] } },
      { path: 'permissions', name: 'permissions', component: PermissionsView, meta: { subtitle: 'Permissions', roles: ['admin'] } }
    ]
  },
  // Routes Manager Portal
  {
    path: '/manager',
    component: MainLayout,
    meta: { requiresAuth: true, roles: ['admin', 'rh', 'manager'] },
    children: [
      { path: '', redirect: '/manager/dashboard' },
      { path: 'dashboard', name: 'manager-dashboard', component: ManagerDashboardView, meta: { subtitle: 'Tableau de bord Manager' } }
    ]
  },
  // Routes Self-Service Employé
  {
    path: '/self-service',
    component: SelfServiceLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/self-service/dashboard' },
      { path: 'dashboard', name: 'self-service-dashboard', component: SelfServiceDashboard, meta: { subtitle: 'Mon espace' } },
      { path: 'profil', name: 'self-service-profil', component: SelfServiceProfil, meta: { subtitle: 'Mon profil' } },
      // { path: 'demandes', name: 'self-service-demandes', component: SelfServiceDemandes, meta: { subtitle: 'Mes demandes' } },
      { path: 'messagerie', name: 'self-service-messagerie', component: SelfServiceMessagerie, meta: { subtitle: 'Messagerie RH' } },
      { path: 'conges', name: 'self-service-conges', component: SelfServiceConges, meta: { subtitle: 'Mes congés' } },
      { path: 'bulletins', name: 'self-service-bulletins', component: SelfServiceBulletins, meta: { subtitle: 'Mes bulletins' } },
      { path: 'formations', name: 'self-service-formations', component: SelfServiceFormations, meta: { subtitle: 'Mes formations' } },
      { path: 'competences', name: 'self-service-competences', component: SelfServiceCompetences, meta: { subtitle: 'Mes compétences' } },
      { path: 'documents', name: 'self-service-documents', component: SelfServiceDocuments, meta: { subtitle: 'Mes documents' } },
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
