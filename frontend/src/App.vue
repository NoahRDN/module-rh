
<template>
  <RouterView />
 </template>

<script setup>
import { onMounted } from 'vue'
import api from './services/api'
import { setStoredCurrency } from './utils/currency'

onMounted(async () => {
  const token = localStorage.getItem('token')
  if (!token) return

  try {
    const { data } = await api.get('/v1/entreprise-settings')
    if (data?.devise) {
      setStoredCurrency(data.devise)
    }
  } catch (error) {
    // ignore: l'app continue avec la devise locale
  }
})
</script>
