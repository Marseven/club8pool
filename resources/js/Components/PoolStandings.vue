<script setup>
import Chip from './Chip.vue';

const props = defineProps({
  pool: Object,
  qualifiersPerPool: { type: Number, default: 2 },
  compact: { type: Boolean, default: false },
});
</script>

<template>
  <div :style="{ border: '1px solid var(--line)', background: 'var(--ink-2)', borderRadius: '2px', overflow: 'hidden' }">
    <header style="padding: 14px 18px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: baseline;">
      <div style="display: flex; align-items: baseline; gap: 12px;">
        <h3 class="disp-a" style="font-size: 28px;">POULE {{ pool.name }}</h3>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">
          {{ pool.players?.length || 0 }} JOUEURS
        </span>
      </div>
      <Chip variant="felt">{{ qualifiersPerPool }} QUALIFIÉS</Chip>
    </header>

    <table class="tbl" style="font-size: 12px;">
      <thead>
        <tr>
          <th style="width: 28px; text-align: center;">#</th>
          <th>Joueur</th>
          <th style="text-align: right; width: 36px;">V</th>
          <th style="text-align: right; width: 36px;">W</th>
          <th style="text-align: right; width: 36px;">L</th>
          <th style="text-align: right; width: 44px;">Diff</th>
          <th v-if="!compact" style="text-align: right; width: 28px;" title="Avertissements">!</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(s, i) in pool.standings" :key="s.player_id" :style="{
          background: s.rank <= qualifiersPerPool ? 'rgba(45,168,118,0.04)' : 'transparent',
          borderLeft: s.rank <= qualifiersPerPool ? '2px solid var(--felt-2)' : '2px solid transparent',
        }">
          <td class="mono tnum" style="text-align: center; color: s.rank <= qualifiersPerPool ? 'var(--felt-2)' : 'var(--mute)'; font-weight: 700;">
            {{ s.rank }}
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <span class="mono" style="font-size: 9px; color: var(--mute);">{{ pool.name }}{{ s.pool_slot }}</span>
              <span style="font-weight: 600;">{{ s.name }}</span>
            </div>
          </td>
          <td class="mono tnum" style="text-align: right; font-weight: 700;">{{ s.v }}</td>
          <td class="mono tnum" style="text-align: right; color: var(--felt-2);">{{ s.w }}</td>
          <td class="mono tnum" style="text-align: right; color: var(--mute);">{{ s.l }}</td>
          <td class="mono tnum" :style="{ textAlign: 'right', fontWeight: 600,
                color: s.diff > 0 ? 'var(--felt-2)' : s.diff < 0 ? 'var(--live)' : 'var(--mute)' }">
            {{ s.diff > 0 ? '+' : '' }}{{ s.diff }}
          </td>
          <td v-if="!compact" class="mono tnum" :style="{ textAlign: 'right', color: s.warnings ? 'var(--live)' : 'var(--mute-2)' }">
            {{ s.warnings || '·' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
