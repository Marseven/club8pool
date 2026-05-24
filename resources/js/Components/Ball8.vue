<script setup>
defineProps({
  size: { type: Number, default: 40 },
  mono: { type: Boolean, default: false },
});
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 40 40" aria-hidden="true">
    <defs>
      <radialGradient :id="`g8${size}`" cx="35%" cy="30%">
        <stop offset="0%" stop-color="#444" />
        <stop offset="60%" stop-color="#0A0A0B" />
        <stop offset="100%" stop-color="#000" />
      </radialGradient>
    </defs>
    <circle cx="20" cy="20" r="19" :fill="mono ? '#0A0A0B' : `url(#g8${size})`" stroke="rgba(255,255,255,0.08)" />
    <circle cx="20" cy="16" r="8" fill="#F4F2EC" />
    <text x="20" y="20" text-anchor="middle" font-family="Antonio, Oswald, Impact, sans-serif"
          font-weight="700" font-size="13" fill="#0A0A0B">8</text>
  </svg>
</template>
