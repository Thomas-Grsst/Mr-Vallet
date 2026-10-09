<script setup lang="ts">
import type { Anomaly } from '~/types/vallet'

const anomalyTitles: Record<string, string> = {
  overlap: 'Double réservation',
  workshop: 'Réservée pendant un passage en atelier',
  vgp_expired: 'VGP non à jour',
}

const { data: anomalies, status, refresh } = useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })
</script>

<template>
  <section class="card">
    <p class="muted">Réservations qui ne respectent pas les règles : reprises des Excel, ou touchées par un passage en atelier. Traitez-les depuis le planning ou l'atelier.</p>
    <LoadError v-if="status === 'error'" @retry="refresh()" />
    <LoadingMessage v-else-if="status !== 'success'" label="Chargement des anomalies…" />
    <template v-else>
      <div v-if="!anomalies.length" class="alert alert--ok">Aucune anomalie.</div>
      <div v-for="anomaly in anomalies" :key="anomaly.message" class="alert alert--ko">
        <strong>{{ anomalyTitles[anomaly.code] ?? 'Anomalie' }}</strong> — {{ anomaly.message }}
      </div>
    </template>
  </section>
</template>
