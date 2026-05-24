<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ players: Array });

const initials = (p) => `${p.first_name[0]}${p.last_name[0]}`;
</script>

<template>
  <table class="tbl">
    <thead>
      <tr>
        <th style="width: 40px;">#</th>
        <th>Joueur</th>
        <th>Club</th>
        <th style="text-align: right;">Elo</th>
        <th style="text-align: right;">V</th>
        <th style="text-align: right;">D</th>
        <th style="text-align: right;">Forme</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(p, i) in players" :key="p.id">
        <td class="mono tnum" style="color: var(--mute);">{{ String(i + 1).padStart(2, '0') }}</td>
        <td>
          <Link :href="`/joueurs/${p.id}`" style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--ink-3);
                        display: flex; align-items: center; justify-content: center;
                        font-family: var(--font-display-a); font-weight: 600; font-size: 12px;">{{ initials(p) }}</div>
            <div style="font-weight: 600; font-size: 13px;">{{ p.first_name }} {{ p.last_name }}</div>
          </Link>
        </td>
        <td style="color: var(--mute); font-size: 12px;">{{ p.club?.name }} · {{ p.club?.city }}</td>
        <td class="mono tnum" style="text-align: right; font-weight: 600;">{{ p.rating }}</td>
        <td class="mono tnum" style="text-align: right; color: var(--felt-2);">{{ p.wins }}</td>
        <td class="mono tnum" style="text-align: right; color: var(--mute);">{{ p.losses }}</td>
        <td style="text-align: right;">
          <span style="display: inline-flex; gap: 3px;">
            <span v-for="(v, k) in [1,1,0,1,1]" :key="k"
                  :style="{ width: '6px', height: '14px', background: v ? 'var(--felt-2)' : 'var(--ink-4)' }" />
          </span>
        </td>
      </tr>
    </tbody>
  </table>
</template>
