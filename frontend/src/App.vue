
<template>
  <RouterView />
 </template>

<script setup>
import { onMounted } from 'vue'
import api from './services/api'
import { setCurrencyCatalog, setStoredCurrency } from './utils/currency'

onMounted(async () => {
  const token = localStorage.getItem('token')
  if (!token) return
  const currentPath = typeof window !== 'undefined' ? window.location.pathname : ''
  if (currentPath === '/' || currentPath.startsWith('/dashboard')) return

  try {
    const [{ data: devises }, { data: entreprise }] = await Promise.all([
      api.get('/v1/devises?active=1'),
      api.get('/v1/entreprise-settings'),
    ])

    setCurrencyCatalog(Array.isArray(devises) ? devises : [])
    if (entreprise?.devise) setStoredCurrency(entreprise.devise)
  } catch (error) {
    // ignore: l'app continue avec la devise locale
  }
})
</script>
