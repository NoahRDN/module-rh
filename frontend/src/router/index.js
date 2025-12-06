import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import EmployeesView from '../views/EmployeesView.vue'
import DepartementsView from '../views/DepartementsView.vue'
import PostesView from '../views/PostesView.vue'
import ContratsView from '../views/ContratsView.vue'
import DocumentsView from '../views/DocumentsView.vue'
import HistoriquePostesView from '../views/HistoriquePostesView.vue'
import MainLayout from '../components/layout/MainLayout.vue'

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
      { path: 'departements', name: 'departements', component: DepartementsView, meta: { subtitle: 'Structure' } },
      { path: 'postes', name: 'postes', component: PostesView, meta: { subtitle: 'Fonctions' } },
      { path: 'contrats', name: 'contrats', component: ContratsView, meta: { subtitle: 'Contrats' } },
      { path: 'documents', name: 'documents', component: DocumentsView, meta: { subtitle: 'Documents' } },
      { path: 'historiques', name: 'historiques', component: HistoriquePostesView, meta: { subtitle: 'Mobilités' } }
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
