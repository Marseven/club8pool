<script setup>
defineProps({ pool: Object });

const lbl = (p) => p ? p.first_name + (p.last_name ? ' ' + p.last_name : '') : '—';
</script>

<template>
  <div style="overflow-x: auto;">
    <table class="tbl" style="font-size: 12px; min-width: 600px;">
      <thead>
        <tr>
          <th style="width: 88px;">Match</th>
          <th>Joueur 1</th>
          <th style="text-align: center; width: 36px;">Score</th>
          <th>Joueur 2</th>
          <th style="text-align: center; width: 36px;">Score</th>
          <th>Vainqueur</th>
          <th style="text-align: center; width: 28px;">!</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(m, i) in pool.matches" :key="m.id">
          <td class="mono" style="color: var(--mute); font-size: 11px;">
            {{ pool.name }}{{ findIdx(pool, m.player_a_id) }} vs {{ pool.name }}{{ findIdx(pool, m.player_b_id) }}
          </td>
          <td :style="{ fontWeight: m.score_a > m.score_b && !m.is_draw ? 700 : 500,
                color: m.score_a > m.score_b && !m.is_draw ? 'var(--chalk)' : 'var(--chalk-2)' }">
            {{ lbl(m.player_a) }}
          </td>
          <td class="mono tnum" :style="{ textAlign: 'center', fontWeight: 700,
                color: m.score_a > m.score_b && !m.is_draw ? 'var(--felt-2)' : 'var(--mute)' }">
            <template v-if="m.status === 'done' || m.score_a + m.score_b > 0">{{ m.score_a }}</template>
            <template v-else>—</template>
          </td>
          <td :style="{ fontWeight: m.score_b > m.score_a && !m.is_draw ? 700 : 500,
                color: m.score_b > m.score_a && !m.is_draw ? 'var(--chalk)' : 'var(--chalk-2)' }">
            {{ lbl(m.player_b) }}
          </td>
          <td class="mono tnum" :style="{ textAlign: 'center', fontWeight: 700,
                color: m.score_b > m.score_a && !m.is_draw ? 'var(--felt-2)' : 'var(--mute)' }">
            <template v-if="m.status === 'done' || m.score_a + m.score_b > 0">{{ m.score_b }}</template>
            <template v-else>—</template>
          </td>
          <td>
            <template v-if="m.status === 'live'">
              <span class="chip live">EN COURS</span>
            </template>
            <template v-else-if="m.is_draw">
              <span class="mono" style="font-size: 11px; color: var(--mute);">NUL</span>
            </template>
            <template v-else-if="m.status === 'done' && m.score_a > m.score_b">
              {{ lbl(m.player_a) }}
            </template>
            <template v-else-if="m.status === 'done' && m.score_b > m.score_a">
              {{ lbl(m.player_b) }}
            </template>
            <template v-else>
              <span style="color: var(--mute-2);">·</span>
            </template>
          </td>
          <td style="text-align: center;">
            <span v-if="m.warning_a || m.warning_b" style="color: var(--live);">!</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  methods: {
    findIdx(pool, playerId) {
      const idx = pool.players?.findIndex(p => p.id === playerId);
      return idx >= 0 ? (idx + 1) : '?';
    },
  },
};
</script>
