<script setup>
import { computed } from 'vue';

const props = defineProps({
  match:   { type: Object, default: null },
  isFinal: { type: Boolean, default: false },
});

const m       = computed(() => props.match);
const aWin    = computed(() => m.value?.status === 'done' && m.value.score_a > m.value.score_b);
const bWin    = computed(() => m.value?.status === 'done' && m.value.score_b > m.value.score_a);
const isLive  = computed(() => m.value?.status === 'live');
const isDone  = computed(() => m.value?.status === 'done');
const isPend  = computed(() => !m.value || m.value.status === 'pending' || m.value.status === 'scheduled');

const stLabel = computed(() => {
  const s = m.value?.status;
  if (!s || s === 'pending')   return 'À DÉFINIR';
  if (s === 'scheduled')       return 'À JOUER';
  if (s === 'live')            return 'LIVE';
  if (s === 'done')            return 'TERMINÉ';
  return '—';
});

const pName = (player, source) => {
  if (player) return player.first_name + (player.last_name?.trim() ? ' ' + player.last_name : '');
  return source ?? '—';
};
</script>

<template>
  <div :class="['bc', isLive && 'bc-live', isDone && 'bc-done', isPend && 'bc-pend', isFinal && 'bc-final']">
    <div :class="['bc-row', aWin && 'bc-win']">
      <span class="bc-src">{{ m?.player_a_source ?? '·' }}</span>
      <span class="bc-name">{{ pName(m?.player_a, m?.player_a_source) }}</span>
      <span v-if="!isPend" class="bc-score" :class="aWin && 'bc-sw'">{{ m?.score_a }}</span>
    </div>
    <div class="bc-sep"/>
    <div :class="['bc-row', bWin && 'bc-win']">
      <span class="bc-src">{{ m?.player_b_source ?? '·' }}</span>
      <span class="bc-name">{{ pName(m?.player_b, m?.player_b_source) }}</span>
      <span v-if="!isPend" class="bc-score" :class="bWin && 'bc-sw'">{{ m?.score_b }}</span>
    </div>
    <div :class="['bc-foot', isLive && 'bc-fl']">
      <span class="bc-st">{{ stLabel }}</span>
      <span v-if="isLive" class="bc-dot">●</span>
    </div>
  </div>
</template>

<style scoped>
.bc {
  width: 190px;
  border: 1px solid var(--line);
  background: var(--ink-2);
  overflow: hidden;
  box-sizing: border-box;
}
.bc-live  { border-color: rgba(229,72,77,.5);  background: rgba(229,72,77,.03); }
.bc-done  { border-color: var(--line-strong); }
.bc-pend  { opacity: .45; }
.bc-final { border-color: var(--felt); }

.bc-row {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  min-height: 32px;
  box-sizing: border-box;
}
.bc-win { background: rgba(45,168,118,.07); }

.bc-src {
  flex-shrink: 0;
  min-width: 20px;
  font-size: 9px;
  color: var(--mute);
  letter-spacing: .05em;
  font-family: var(--font-mono);
}
.bc-name {
  flex: 1;
  font-size: 12px;
  font-weight: 600;
  color: var(--chalk-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bc-win .bc-name { color: var(--chalk); }

.bc-score {
  flex-shrink: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--mute);
  font-family: var(--font-display-a);
  min-width: 22px;
  text-align: right;
}
.bc-sw { color: var(--felt-2); }

.bc-sep { height: 1px; background: var(--line); }

.bc-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 10px;
  border-top: 1px solid var(--line);
}
.bc-fl { background: rgba(229,72,77,.05); }

.bc-st {
  font-size: 8px;
  letter-spacing: .18em;
  color: var(--mute);
  font-family: var(--font-mono);
}
.bc-dot { font-size: 10px; color: var(--live); }
</style>
