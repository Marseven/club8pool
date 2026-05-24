<script setup>
defineProps({
  width: { type: Number, default: 20 },
  height: { type: Number, default: 14 },
});
</script>

<template>
  <svg :width="width" :height="height" viewBox="0 0 30 21" style="border-radius: 2px; overflow: hidden;">
    <rect x="0" y="0" width="30" height="7" fill="#3A9D23" />
    <rect x="0" y="7" width="30" height="7" fill="#FCD116" />
    <rect x="0" y="14" width="30" height="7" fill="#3B7DC4" />
    <rect x="0" y="0" width="30" height="21" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="0.5" />
  </svg>
</template>
