<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  match: Object,
  nextMatch: Object,
});

const shotClock = ref(17);
const matchTime = ref(58 * 60 + 22);

let shotInterval, matchInterval;

onMounted(() => {
  shotInterval = setInterval(() => {
    shotClock.value = shotClock.value <= 0 ? 30 : shotClock.value - 1;
  }, 1000);
  matchInterval = setInterval(() => matchTime.value++, 1000);
});

onUnmounted(() => {
  clearInterval(shotInterval);
  clearInterval(matchInterval);
});

const fmt = (s) => {
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = s % 60;
  const pad = (n) => String(n).padStart(2, '0');
  return (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(sec);
};

const fmtTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}:${d.getUTCMinutes().toString().padStart(2, '0')}`;
};
</script>

<template>
  <Head title="TV · Scoreboard" />
  <div style="width: 100vw; height: 100vh; background: #000;
              display: grid; grid-template-rows: 88px 1fr 92px; overflow: hidden;">
    <header style="display: flex; justify-content: space-between; align-items: center;
                   padding: 0 48px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; align-items: center; gap: 18px;">
        <Ball8 :size="48" />
        <div>
          <div class="disp-a" style="font-size: 28px; letter-spacing: 0.02em;">{{ competition?.name?.split(' — ')[0]?.toUpperCase() }}</div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.3em; color: var(--mute);">
            ÉDITION 04 · 2026 · {{ competition?.venue?.toUpperCase() }}
          </div>
        </div>
      </div>
      <div style="display: flex; gap: 32px; align-items: baseline;">
        <Chip variant="live" style="font-size: 12px; padding: 6px 12px;">LIVE · {{ match?.table?.name?.toUpperCase() }}</Chip>
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">{{ match?.round === 'QF' ? 'QUART DE FINALE' : match?.round }}</div>
          <div class="disp-a tnum" style="font-size: 26px;">RACE TO {{ competition?.race_to }} · FRAME {{ String((match?.score_a ?? 0) + (match?.score_b ?? 0) + 1).padStart(2, '0') }}</div>
        </div>
      </div>
    </header>

    <main v-if="match" style="display: grid; grid-template-columns: 1fr 280px 1fr; align-items: center; padding: 0 64px;">
      <div style="text-align: left;">
        <div style="display: flex; gap: 10px; margin-bottom: 14px;">
          <Chip variant="felt">SEED #{{ match.player_a?.id }}</Chip>
        </div>
        <div class="disp-a" style="font-size: 84px; line-height: 0.88;">{{ match.player_a?.first_name?.toUpperCase() }}</div>
        <div class="disp-a" style="font-size: 130px; line-height: 0.88; color: var(--felt-2);">{{ match.player_a?.last_name }}</div>
        <div class="mono" style="margin-top: 16px; font-size: 18px; color: var(--mute);">
          {{ match.player_a?.club?.name?.toUpperCase() }} · {{ match.player_a?.club?.city?.toUpperCase() }} · ELO {{ match.player_a?.rating }}
        </div>
      </div>

      <div style="text-align: center; border-left: 1px solid var(--line); border-right: 1px solid var(--line);
                  padding: 32px 0; height: 100%; display: flex; flex-direction: column;
                  justify-content: center; align-items: center; gap: 20px;">
        <div class="disp-a tnum" style="font-size: 240px; line-height: 0.85; display: flex; align-items: baseline; gap: 24px;">
          <span style="color: var(--felt-2);">{{ match.score_a }}</span>
          <span style="color: var(--mute-2); font-size: 100px;">—</span>
          <span style="color: var(--chalk);">{{ match.score_b }}</span>
        </div>
        <div style="display: flex; gap: 24px; align-items: center;">
          <div :style="{ width: '88px', height: '88px', borderRadius: '50%',
                         border: '4px solid ' + (shotClock <= 10 ? 'var(--live)' : 'var(--felt-2)'),
                         display: 'flex', alignItems: 'center', justifyContent: 'center' }">
            <span class="disp-a tnum" :style="{ fontSize: '40px', color: shotClock <= 10 ? 'var(--live)' : 'var(--chalk)' }">
              {{ String(shotClock).padStart(2, '0') }}
            </span>
          </div>
          <div style="text-align: left;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.24em; color: var(--mute);">MATCH</div>
            <div class="disp-a tnum" style="font-size: 36px;">{{ fmt(matchTime) }}</div>
          </div>
        </div>
      </div>

      <div style="text-align: right;">
        <div style="display: flex; gap: 10px; margin-bottom: 14px; justify-content: flex-end;">
          <Chip>SEED #{{ match.player_b?.id }}</Chip>
        </div>
        <div class="disp-a" style="font-size: 84px; line-height: 0.88; color: var(--mute);">{{ match.player_b?.first_name?.toUpperCase() }}</div>
        <div class="disp-a" style="font-size: 130px; line-height: 0.88; color: var(--chalk);">{{ match.player_b?.last_name }}</div>
        <div class="mono" style="margin-top: 16px; font-size: 18px; color: var(--mute);">
          {{ match.player_b?.club?.name?.toUpperCase() }} · {{ match.player_b?.club?.city?.toUpperCase() }} · ELO {{ match.player_b?.rating }}
        </div>
      </div>
    </main>

    <footer style="border-top: 1px solid var(--line); display: flex; justify-content: space-between;
                   align-items: center; padding: 0 48px;">
      <div style="display: flex; align-items: center; gap: 14px;">
        <GabonFlag :width="28" :height="20" />
        <span class="mono" style="font-size: 12px; letter-spacing: 0.2em; color: var(--mute);">
          FÉDÉRATION GABONAISE DE BILLARD
        </span>
      </div>
      <div style="display: flex; gap: 36px; align-items: center;">
        <span v-for="s in ['SPONSOR PRINCIPAL', 'PARTENAIRE TABLE', 'BRUNSWICK', 'PREDATOR', 'KAMUI']" :key="s"
              class="mono" style="font-size: 12px; letter-spacing: 0.16em; color: var(--chalk-2);">{{ s }}</span>
      </div>
      <span v-if="nextMatch" class="mono tnum" style="font-size: 12px; color: var(--mute); letter-spacing: 0.16em;">
        PROCHAIN MATCH · {{ fmtTime(nextMatch.scheduled_at) }} · {{ nextMatch.table?.name?.toUpperCase() ?? 'À DÉFINIR' }}
      </span>
    </footer>
  </div>
</template>
