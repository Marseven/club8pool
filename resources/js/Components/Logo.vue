<script setup>
import Ball8 from './Ball8.vue';
defineProps({ size: { type: Number, default: 32 } });
</script>

<template>
  <div style="display: flex; align-items: center; gap: 10px;">
    <Ball8 :size="size" />
    <div style="display: flex; flex-direction: column; line-height: 1;">
      <span :style="{
        fontFamily: 'Antonio, Oswald, sans-serif',
        fontSize: (size * 0.55) + 'px',
        fontWeight: 700,
        letterSpacing: '0.02em',
        textTransform: 'uppercase',
      }">Club 8 Pool</span>
      <span :style="{
        fontFamily: 'JetBrains Mono, monospace',
        fontSize: (size * 0.27) + 'px',
        letterSpacing: '0.18em',
        opacity: 0.55,
        marginTop: '2px',
        textTransform: 'uppercase',
      }">Gabon · billard</span>
    </div>
  </div>
</template>
