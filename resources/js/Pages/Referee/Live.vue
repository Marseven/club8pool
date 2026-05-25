<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Pause } from 'lucide-vue-next';

const props = defineProps({ match: Object });

const shotClock = ref(22);
const matchTime = ref(42 * 60 + 18);
const localScore = ref({ a: props.match.score_a, b: props.match.score_b });

let shotI, matchI;
onMounted(() => {
  shotI = setInterval(() => { shotClock.value = shotClock.value <= 0 ? 30 : shotClock.value - 1; }, 1000);
  matchI = setInterval(() => matchTime.value++, 1000);
});
onUnmounted(() => { clearInterval(shotI); clearInterval(matchI); });

const fmt = (s) => {
  const m = Math.floor(s / 60), sec = s % 60;
  return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
};

const danger = computed(() => shotClock.value <= 10);

const winFrame = (side) => {
  localScore.value[side]++;
  router.post(`/arbitre/match/${props.match.id}/frame`, {
    winner: side === 'a' ? 'A' : 'B',
  }, { preserveScroll: true, preserveState: true });
};
</script>

<template>
  <Head title="Match en cours" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">
    <header style="padding: 8px 18px 14px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <a href="/arbitre" style="font-size: 18px; color: var(--mute);">←</a>
      <div>
        <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute);">
          {{ match.round }} · {{ match.table?.name?.toUpperCase() }}
        </div>
        <div style="font-size: 13px; font-weight: 700;">{{ match.competition?.name?.split(' — ')[0] }}</div>
      </div>
      <span class="mono" style="padding: 3px 7px; border: 1px solid rgba(45,168,118,0.4);
                                background: rgba(45,168,118,0.08); border-radius: 2px;
                                font-size: 9px; color: var(--felt-2); letter-spacing: 0.14em;
                                display:inline-flex; align-items:center; gap:4px;">
        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0;"></span>EN LIGNE
      </span>
    </header>

    <div :style="{
      padding: '16px 18px 8px',
      background: danger ? 'rgba(229,72,77,0.06)' : 'transparent',
      borderBottom: '1px solid var(--line)',
      transition: 'background .3s',
    }">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute);">SHOT CLOCK</span>
        <span class="mono" :style="{ fontSize: '9px', letterSpacing: '0.18em',
              color: danger ? 'var(--live)' : 'var(--mute)' }">{{ danger ? 'WARNING' : 'NORMAL' }}</span>
      </div>
      <div class="disp-a tnum" :style="{
        fontSize: '124px', lineHeight: 0.9, textAlign: 'center', marginTop: '4px',
        color: danger ? 'var(--live)' : 'var(--chalk)',
      }">{{ String(shotClock).padStart(2, '0') }}</div>
      <div style="height: 4px; background: var(--line); overflow: hidden;">
        <div :style="{ width: (shotClock / 30) * 100 + '%', height: '100%',
              background: danger ? 'var(--live)' : 'var(--felt-2)', transition: 'width .8s linear' }" />
      </div>
      <div style="display: flex; justify-content: space-between; margin-top: 8px;">
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">
          FRAME {{ localScore.a + localScore.b + 1 }} · RACE TO {{ match.competition?.race_to }}
        </span>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">MATCH {{ fmt(matchTime) }}</span>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid var(--line);">
      <div v-for="(p, i) in [
        { player: match.player_a, score: localScore.a, side: 'a' },
        { player: match.player_b, score: localScore.b, side: 'b' },
      ]" :key="i" :style="{ padding: '18px 16px 16px',
            borderRight: i === 0 ? '1px solid var(--line)' : 'none' }">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="mono" style="font-size: 9px; color: var(--mute);">SEED #{{ p.player?.id }}</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; line-height: 1.1; min-height: 28px;">
          {{ p.player?.first_name }}<br />{{ p.player?.last_name }}
        </div>
        <div class="disp-a tnum" :style="{ fontSize: '80px', lineHeight: 0.9, marginTop: '4px',
              color: p.score > (i === 0 ? localScore.b : localScore.a) ? 'var(--felt-2)' : 'var(--chalk)' }">{{ p.score }}</div>
      </div>
    </div>

    <div style="padding: 14px 16px; display: flex; flex-direction: column; gap: 10px;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <button class="btn btn-felt" style="padding: 16px 12px; justify-content: center;"
                @click="winFrame('a')">+ Frame · {{ match.player_a?.last_name }}</button>
        <button class="btn" style="padding: 16px 12px; justify-content: center;"
                @click="winFrame('b')">+ Frame · {{ match.player_b?.last_name }}</button>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
        <button class="btn" style="padding: 10px 6px; font-size: 11px; justify-content: center;">Faute</button>
        <button class="btn" style="padding: 10px 6px; font-size: 11px; justify-content: center;">Black foul</button>
        <button class="btn" style="padding: 10px 6px; font-size: 11px; justify-content: center;">Empoche 8</button>
      </div>
    </div>

    <div style="margin-top: auto; padding: 12px 16px; border-top: 1px solid var(--line);
                display: flex; justify-content: space-between; align-items: center; background: var(--ink-2);">
      <button class="mono" style="background: transparent; border: none; color: var(--mute);
                                   font-size: 11px; letter-spacing: 0.14em; cursor: pointer;
                                   display:inline-flex; align-items:center; gap:4px;">
        <Pause :size="14" /> PAUSE
      </button>
      <a :href="`/arbitre/match/${match.id}/fin`" class="btn" style="border-color: var(--felt-2); color: var(--felt-2);">
        FIN DE MATCH
      </a>
    </div>
  </div>
</template>
