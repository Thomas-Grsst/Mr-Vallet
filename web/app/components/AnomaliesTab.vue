<script setup lang="ts">
import type { Anomaly } from '~/types/vallet'

const { data: anomalies } = await useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })
</script>

<template>
  <section class="card">
    <p class="muted">Réservations reprises des Excel qui ne respectent pas les règles. Traitez-les depuis le planning ou l'atelier.</p>
    <div v-if="!anomalies.length" class="alert alert--ok">Aucune anomalie.</div>
    <div v-for="anomaly in anomalies" :key="anomaly.message" class="alert alert--ko">
      <strong>{{ anomaly.code === 'overlap' ? 'Double réservation' : 'VGP non à jour' }}</strong> — {{ anomaly.message }}
    </div>
  </section>
</template>
