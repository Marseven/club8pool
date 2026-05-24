<script setup>
import { computed } from 'vue';
import MatchCell from './MatchCell.vue';
import Ball8 from './Ball8.vue';

const props = defineProps({ matches: Object });

const rounds = computed(() => [
  { label: '8e DE FINALE', matches: props.matches?.R16 ?? [], spacing: 12 },
  { label: 'QUARTS', matches: props.matches?.QF ?? [], spacing: 100 },
  { label: 'DEMI-FINALES', matches: props.matches?.SF ?? [], spacing: 300 },
  { label: 'FINALE', matches: props.matches?.F ?? [], spacing: 600 },
]);
</script>

<template>
  <div style="display: flex; gap: 60px; align-items: flex-start; padding: 24px 0; overflow-x: auto;">
    <div v-for="(r, ri) in rounds" :key="ri"
         :style="{ display: 'flex', flexDirection: 'column', gap: r.spacing + 'px', paddingTop: (r.spacing / 2) + 'px' }">
      <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 4px;">
        {{ r.label }}
      </div>
      <MatchCell v-for="m in r.matches" :key="m.id" :match="m" />
    </div>
    <div style="padding-top: 600px; display: flex; align-items: center; gap: 16px;">
      <Ball8 :size="56" />
      <div>
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">VAINQUEUR</div>
        <div class="disp-a" style="font-size: 28px; margin-top: 6px; color: var(--mute);">À écrire</div>
      </div>
    </div>
  </div>
</template>
