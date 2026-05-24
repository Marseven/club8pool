<script setup>
import { computed } from 'vue';
const props = defineProps({ match: Object });

const m = computed(() => props.match);
const a = computed(() => m.value?.player_a);
const b = computed(() => m.value?.player_b);
const aWin = computed(() => m.value?.status === 'done' && m.value.score_a > m.value.score_b);
const bWin = computed(() => m.value?.status === 'done' && m.value.score_b > m.value.score_a);
const isLive = computed(() => m.value?.status === 'live');

const shortName = (p) => p ? `${p.first_name[0]}. ${p.last_name}` : '—';

const statusLabel = computed(() => {
  if (!m.value) return 'À DÉFINIR';
  if (m.value.status === 'done') return 'TERMINÉ';
  if (m.value.status === 'live') return `LIVE · ${m.value.table?.name?.toUpperCase() || 'TABLE'}`;
  if (m.value.scheduled_at) {
    const d = new Date(m.value.scheduled_at);
    return `À ${d.getUTCHours().toString().padStart(2, '0')}:${d.getUTCMinutes().toString().padStart(2, '0')}`;
  }
  return 'À DÉFINIR';
});
</script>

<template>
  <div :style="{
    border: '1px solid ' + (isLive ? 'rgba(229,72,77,0.45)' : 'var(--line)'),
    background: isLive ? 'rgba(229,72,77,0.04)' : 'var(--ink-2)',
    borderRadius: '2px',
    overflow: 'hidden',
    width: '240px',
  }">
    <div :style="{ display: 'grid', gridTemplateColumns: '20px 1fr 36px', alignItems: 'center',
                   padding: '10px 12px', opacity: a ? 1 : 0.45 }">
      <span class="mono" style="font-size: 10px; color: var(--mute);">{{ a?.id || '·' }}</span>
      <span :style="{ fontSize: '13px', fontWeight: aWin ? 700 : 500, color: aWin ? 'var(--chalk)' : 'var(--chalk-2)' }">{{ shortName(a) }}</span>
      <span class="tnum disp-a" :style="{ fontSize: '20px', textAlign: 'right',
            color: aWin ? 'var(--felt-2)' : 'var(--mute)' }">{{ m ? m.score_a : '' }}</span>
    </div>
    <div style="height: 1px; background: var(--line);" />
    <div :style="{ display: 'grid', gridTemplateColumns: '20px 1fr 36px', alignItems: 'center',
                   padding: '10px 12px', opacity: b ? 1 : 0.45 }">
      <span class="mono" style="font-size: 10px; color: var(--mute);">{{ b?.id || '·' }}</span>
      <span :style="{ fontSize: '13px', fontWeight: bWin ? 700 : 500, color: bWin ? 'var(--chalk)' : 'var(--chalk-2)' }">{{ shortName(b) }}</span>
      <span class="tnum disp-a" :style="{ fontSize: '20px', textAlign: 'right',
            color: bWin ? 'var(--felt-2)' : 'var(--mute)' }">{{ m ? m.score_b : '' }}</span>
    </div>
    <div :style="{ padding: '6px 12px', borderTop: '1px solid var(--line)',
                   display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                   background: isLive ? 'rgba(229,72,77,0.06)' : 'transparent' }">
      <span class="mono" style="font-size: 9px; letter-spacing: 0.18em; color: var(--mute);">{{ statusLabel }}</span>
      <span v-if="isLive" class="chip live" style="padding: 1px 5px; font-size: 8px;">LIVE</span>
    </div>
  </div>
</template>
