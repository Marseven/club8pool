<script setup>
defineProps({
  variant: { type: String, default: '' },
});
</script>
<template>
  <span class="chip" :class="variant"><slot /></span>
</template>
