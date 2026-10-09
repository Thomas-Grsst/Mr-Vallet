<script setup lang="ts">
import type { Anomaly } from '~/types/vallet'

const { data: anomalies, status, refresh } = useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })
</script>

<template>
  <section class="card">
    <p class="muted">Réservations reprises des Excel qui ne respectent pas les règles. Traitez-les depuis le planning ou l'atelier.</p>
    <LoadError v-if="status === 'error'" @retry="refresh()" />
    <LoadingMessage v-else-if="status !== 'success'" label="Chargement des anomalies…" />
    <template v-else>
      <div v-if="!anomalies.length" class="alert alert--ok">Aucune anomalie.</div>
      <div v-for="anomaly in anomalies" :key="anomaly.message" class="alert alert--ko">
        <strong>{{ anomaly.code === 'overlap' ? 'Double réservation' : 'VGP non à jour' }}</strong> — {{ anomaly.message }}
      </div>
    </template>
  </section>
</template>
