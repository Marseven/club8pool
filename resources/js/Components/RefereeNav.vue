<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ active: { type: String, default: 'queue' } });
</script>

<template>
  <nav style="display: flex; justify-content: space-around;
              padding: 10px 16px; border-top: 1px solid var(--line); background: var(--ink-2);">
    <Link href="/arbitre"
          :style="{ textAlign: 'center', color: active === 'queue' ? 'var(--felt-2)' : 'var(--mute)' }">
      <div style="font-size: 16px;">◰</div>
      <div class="mono" style="font-size: 10px; margin-top: 4px; letter-spacing: 0.12em;">MATCHS</div>
    </Link>
    <Link href="/arbitre/tables"
          :style="{ textAlign: 'center', color: active === 'tables' ? 'var(--felt-2)' : 'var(--mute)' }">
      <div style="font-size: 16px;">▢</div>
      <div class="mono" style="font-size: 10px; margin-top: 4px; letter-spacing: 0.12em;">TABLES</div>
    </Link>
    <Link as="button" method="post" href="/logout"
          style="text-align: center; color: var(--mute); background: transparent; border: none; padding: 0; font-family: inherit;">
      <div style="font-size: 16px;">↪</div>
      <div class="mono" style="font-size: 10px; margin-top: 4px; letter-spacing: 0.12em;">DÉCO.</div>
    </Link>
  </nav>
</template>
