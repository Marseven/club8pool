<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  liveMatches: Array,
  nextMatches: Array,
  pools: Array,
});

// Refresh / clock state
const tick = ref(0);
const lastRefresh = ref(new Date());
let pollInterval, tickInterval, carouselInterval;

// Carousel state
const currentPoolIdx = ref(0);
const carouselCountdown = ref(10);
const carouselPaused = ref(false);
const isFullscreen = ref(false);

const POLL_MS = 10000;
const CAROUSEL_MS = 10000;

onMounted(() => {
  // 1s clock + carousel countdown
  tickInterval = setInterval(() => {
    tick.value++;
    if (! carouselPaused.value && (props.pools?.length ?? 0) > 0) {
      carouselCountdown.value--;
      if (carouselCountdown.value <= 0) {
        currentPoolIdx.value = (currentPoolIdx.value + 1) % props.pools.length;
        carouselCountdown.value = CAROUSEL_MS / 1000;
      }
    }
  }, 1000);

  // 10s data refresh
  pollInterval = setInterval(() => {
    router.reload({
      only: ['liveMatches', 'nextMatches', 'pools'],
      preserveScroll: true,
      onSuccess: () => { lastRefresh.value = new Date(); },
    });
  }, POLL_MS);

  // Fullscreen change listener
  document.addEventListener('fullscreenchange', updateFullscreen);
  document.addEventListener('webkitfullscreenchange', updateFullscreen);
});

onUnmounted(() => {
  clearInterval(pollInterval);
  clearInterval(tickInterval);
  clearInterval(carouselInterval);
  document.removeEventListener('fullscreenchange', updateFullscreen);
  document.removeEventListener('webkitfullscreenchange', updateFullscreen);
});

const updateFullscreen = () => {
  isFullscreen.value = !!(document.fullscreenElement || document.webkitFullscreenElement);
};

const toggleFullscreen = () => {
  if (isFullscreen.value) {
    (document.exitFullscreen || document.webkitExitFullscreen).call(document);
  } else {
    const el = document.documentElement;
    (el.requestFullscreen || el.webkitRequestFullscreen).call(el);
  }
};

const prevPool = () => {
  if ((props.pools?.length ?? 0) === 0) return;
  currentPoolIdx.value = (currentPoolIdx.value - 1 + props.pools.length) % props.pools.length;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};
const nextPool = () => {
  if ((props.pools?.length ?? 0) === 0) return;
  currentPoolIdx.value = (currentPoolIdx.value + 1) % props.pools.length;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};
const selectPool = (i) => {
  currentPoolIdx.value = i;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};

const currentPool = computed(() => props.pools?.[currentPoolIdx.value] ?? null);

const secondsAgo = computed(() => {
  void tick.value;
  return Math.floor((Date.now() - lastRefresh.value.getTime()) / 1000);
});

const fmtTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}h${d.getUTCMinutes().toString().padStart(2, '0')}`;
};

const raceFor = (m) => m.phase === 'knockout'
  ? (props.competition?.knockout_race_to ?? props.competition?.race_to)
  : (props.competition?.pool_race_to ?? props.competition?.race_to);
</script>

<template>
  <Head title="Live · Club 8 Pool">
    <meta name="description" content="Suivi en direct des matchs du Icone Pool Championship — scores live, classements de poules en temps réel." head-key="description" />
  </Head>

  <div style="min-height: 100vh; background: var(--ink); display: flex; flex-direction: column;">
    <!-- Header -->
    <header style="display: flex; justify-content: space-between; align-items: center;
                   padding: 18px 32px; border-bottom: 1px solid var(--line);
                   background: rgba(10,10,11,0.85); backdrop-filter: blur(8px);
                   position: sticky; top: 0; z-index: 10;">
      <div style="display: flex; align-items: center; gap: 16px;">
        <Ball8 :size="40" />
        <div>
          <div class="disp-a" style="font-size: 22px;">{{ competition?.name?.toUpperCase() }}</div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-top: 4px;">
            {{ competition?.venue?.toUpperCase() }}
          </div>
        </div>
      </div>
      <div style="display: flex; gap: 14px; align-items: center;">
        <Chip variant="live">EN DIRECT · {{ liveMatches?.length || 0 }} {{ (liveMatches?.length || 0) > 1 ? 'TABLES' : 'TABLE' }}</Chip>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">
          MAJ · {{ secondsAgo }}s
        </span>
        <button @click="toggleFullscreen"
                class="btn"
                style="padding: 6px 12px; font-size: 11px;"
                :title="isFullscreen ? 'Quitter plein écran' : 'Plein écran'">
          {{ isFullscreen ? '⛶ Quitter' : '⛶ Plein écran' }}
        </button>
      </div>
    </header>

    <!-- Section : matchs en direct (toujours visible, haut de page) -->
    <section style="padding: 24px 32px;">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
        <h2 class="disp-a" style="font-size: 28px;">▸ MATCHS EN DIRECT</h2>
        <div v-if="competition?.structure === 'pools_knockout'" class="mono" style="font-size: 11px; color: var(--mute);">
          POULES RACE TO {{ competition?.pool_race_to ?? competition?.race_to }} · FINALE RACE TO {{ competition?.knockout_race_to ?? competition?.race_to }}
        </div>
      </div>

      <div v-if="liveMatches?.length" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
        <div v-for="m in liveMatches" :key="m.id"
             style="border: 1px solid rgba(229,72,77,0.45); background: rgba(229,72,77,0.04); padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <div>
              <div class="disp-a" style="font-size: 22px;">{{ m.table?.name?.toUpperCase() }}</div>
              <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-top: 4px;">
                {{ m.phase === 'pool' ? 'POULE · ROUND-ROBIN' : m.round?.replace('R16', '8e').replace('QF', 'QUARTS').replace('SF', 'DEMI').replace('F', 'FINALE') }}
                · RACE TO {{ raceFor(m) }}
              </div>
            </div>
            <Chip variant="live">LIVE</Chip>
          </div>

          <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 18px;">
            <div style="text-align: left; min-width: 0;">
              <div :style="{ fontSize: '22px', fontWeight: 700, color: m.score_a > m.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">
                {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
              </div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 4px;">{{ m.player_a?.club?.name }}</div>
            </div>
            <div class="disp-a tnum" style="font-size: 80px; line-height: 0.9; display: flex; gap: 14px; align-items: baseline;">
              <span :style="{ color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_a }}</span>
              <span style="color: var(--mute-2); font-size: 48px;">—</span>
              <span :style="{ color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_b }}</span>
            </div>
            <div style="text-align: right; min-width: 0;">
              <div :style="{ fontSize: '22px', fontWeight: 700, color: m.score_b > m.score_a ? 'var(--chalk)' : 'var(--chalk-2)' }">
                {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
              </div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 4px;">{{ m.player_b?.club?.name }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-else style="padding: 32px; text-align: center; border: 1px dashed var(--line);">
        <div class="disp-a" style="font-size: 28px; color: var(--mute);">AUCUN MATCH EN DIRECT</div>
      </div>
    </section>

    <!-- Section : carousel de poules (1 à la fois, rotation 10s) -->
    <section v-if="currentPool" style="flex: 1; padding: 16px 32px 24px; border-top: 1px solid var(--line); background: var(--ink-2);">
      <!-- Toolbar -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: baseline; gap: 18px;">
          <h2 class="disp-a" style="font-size: 64px; line-height: 0.92;">POULE {{ currentPool.name }}</h2>
          <span class="mono" style="font-size: 12px; color: var(--mute); letter-spacing: 0.18em;">
            {{ currentPoolIdx + 1 }}/{{ pools.length }} · SUIVANT DANS {{ Math.max(0, carouselCountdown) }}s
          </span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <button @click="prevPool" class="btn" style="padding: 8px 12px;" title="Poule précédente">◀</button>
          <button @click="carouselPaused = ! carouselPaused" class="btn" style="padding: 8px 12px;"
                  :title="carouselPaused ? 'Reprendre' : 'Mettre en pause'">
            {{ carouselPaused ? '▸' : '‖' }}
          </button>
          <button @click="nextPool" class="btn" style="padding: 8px 12px;" title="Poule suivante">▶</button>
          <span style="width: 16px;"></span>
          <button v-for="(p, i) in pools" :key="p.id" @click="selectPool(i)"
                  :style="{
                    width: '36px', height: '36px', cursor: 'pointer',
                    background: i === currentPoolIdx ? 'var(--felt-2)' : 'transparent',
                    color: i === currentPoolIdx ? 'var(--ink)' : 'var(--chalk-2)',
                    border: '1px solid ' + (i === currentPoolIdx ? 'var(--felt-2)' : 'var(--line-strong)'),
                    fontFamily: 'var(--font-display-a)', fontSize: '16px', fontWeight: 700,
                  }">{{ p.name }}</button>
        </div>
      </div>

      <!-- Progress bar -->
      <div style="height: 3px; background: var(--ink-3); margin-bottom: 18px;">
        <div :style="{
          height: '100%',
          background: 'var(--felt-2)',
          width: ((10 - Math.max(0, carouselCountdown)) / 10 * 100) + '%',
          transition: carouselPaused ? 'none' : 'width 1s linear',
        }" />
      </div>

      <!-- Standings table — pleine largeur, gros texte -->
      <div style="border: 1px solid var(--line); background: var(--ink); overflow: hidden;">
        <div style="display: grid; grid-template-columns: 70px 1fr 80px 80px 80px 100px; gap: 0;
                    background: var(--ink-2); border-bottom: 1px solid var(--line); padding: 14px 24px;">
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em;">RANG</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em;">JOUEUR</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">V</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">W</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">L</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">DIFF</div>
        </div>

        <div v-for="(s, i) in currentPool.standings" :key="s.player_id" :style="{
          display: 'grid', gridTemplateColumns: '70px 1fr 80px 80px 80px 100px',
          padding: '20px 24px', alignItems: 'center',
          borderTop: i ? '1px solid var(--line)' : 'none',
          background: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'rgba(45,168,118,0.06)' : 'transparent',
          borderLeft: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? '4px solid var(--felt-2)' : '4px solid transparent',
        }">
          <div class="disp-a tnum" :style="{ fontSize: '38px', color: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'var(--felt-2)' : 'var(--chalk)' }">
            {{ s.rank }}
          </div>
          <div>
            <div style="font-size: 22px; font-weight: 700;">{{ s.name }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-top: 4px;">
              {{ currentPool.name }}{{ s.pool_slot }}
            </div>
          </div>
          <div class="disp-a tnum" style="font-size: 30px; text-align: right;">{{ s.v }}</div>
          <div class="disp-a tnum" style="font-size: 26px; color: var(--felt-2); text-align: right;">{{ s.w }}</div>
          <div class="disp-a tnum" style="font-size: 26px; color: var(--mute); text-align: right;">{{ s.l }}</div>
          <div class="disp-a tnum" :style="{ fontSize: '28px', textAlign: 'right',
                color: s.diff > 0 ? 'var(--felt-2)' : s.diff < 0 ? 'var(--live)' : 'var(--chalk-2)' }">
            {{ s.diff > 0 ? '+' : '' }}{{ s.diff }}
          </div>
        </div>
      </div>

      <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em; margin-top: 14px; text-align: center;">
        TOP {{ competition?.qualifiers_per_pool ?? 2 }} QUALIFIÉS POUR LA PHASE FINALE · V VICTOIRES · W MANCHES GAGNÉES · L MANCHES PERDUES · DIFF = W − L
      </div>
    </section>

    <!-- Prochains matchs (optionnel, petit) -->
    <section v-if="nextMatches?.length && !isFullscreen" style="padding: 16px 32px; border-top: 1px solid var(--line);">
      <h3 class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 10px;">PROCHAINEMENT</h3>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
        <div v-for="m in nextMatches" :key="m.id"
             style="border: 1px solid var(--line); padding: 10px 14px; background: var(--ink-2);">
          <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.18em;">
            {{ m.table?.name?.toUpperCase() ?? 'À PROGRAMMER' }} · {{ fmtTime(m.scheduled_at) }}
          </div>
          <div style="font-size: 12px; font-weight: 600; margin-top: 4px;">{{ m.player_a?.first_name }} {{ m.player_a?.last_name }}</div>
          <div style="font-size: 12px; color: var(--mute); margin-top: 2px;">vs {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}</div>
        </div>
      </div>
    </section>

    <!-- Footer (caché en plein écran) -->
    <footer v-if="!isFullscreen" style="padding: 16px 32px; border-top: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; align-items: center; gap: 14px;">
        <GabonFlag :width="22" :height="15" />
        <span class="mono" style="font-size: 11px; letter-spacing: 0.18em; color: var(--mute);">
          {{ competition?.city?.toUpperCase() }} · CLUB 8 POOL
        </span>
      </div>
      <a href="/tv" target="_blank" class="mono" style="font-size: 11px; letter-spacing: 0.18em; color: var(--mute);">
        SCOREBOARD TV ↗
      </a>
    </footer>
  </div>
</template>
