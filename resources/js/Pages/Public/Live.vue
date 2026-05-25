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

  <div style="height: 100dvh; background: var(--ink); display: flex; flex-direction: column; overflow: hidden;">
    <!-- Header -->
    <header style="display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
                   padding: clamp(8px,1.4vh,18px) clamp(12px,2.5vw,32px); border-bottom: 1px solid var(--line);
                   background: rgba(10,10,11,0.92);">
      <div style="display: flex; align-items: center; gap: 16px;">
        <img v-if="competition?.logo_url" :src="competition.logo_url" :alt="competition.name + ' logo'"
             style="height: 44px; width: 44px; object-fit: contain;" />
        <Ball8 v-else :size="40" />
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
    <section class="section-live" style="flex-shrink: 0; padding: clamp(8px,1.4vh,20px) clamp(12px,2.5vw,32px);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
        <h2 class="disp-a" style="font-size: 28px;">▸ MATCHS EN DIRECT</h2>
        <div v-if="competition?.structure === 'pools_knockout'" class="mono" style="font-size: 11px; color: var(--mute);">
          POULES RACE TO {{ competition?.pool_race_to ?? competition?.race_to }} · FINALE RACE TO {{ competition?.knockout_race_to ?? competition?.race_to }}
        </div>
      </div>

      <div v-if="liveMatches?.length" class="live-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
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
            <div class="disp-a tnum live-score" style="line-height: 0.9; display: flex; gap: 14px; align-items: baseline;">
              <span :style="{ color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_a }}</span>
              <span class="live-dash" style="color: var(--mute-2);">—</span>
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
    <section v-if="currentPool" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;
                                        padding: clamp(8px,1.2vh,16px) clamp(12px,2.5vw,32px) clamp(6px,1vh,12px);
                                        border-top: 1px solid var(--line); background: var(--ink-2);">
      <!-- Toolbar -->
      <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;
                  margin-bottom: clamp(6px,1vh,14px); gap: 10px; flex-wrap: wrap;">
        <div style="display: flex; align-items: baseline; gap: 18px;">
          <h2 class="disp-a pool-title" style="font-size: 64px; line-height: 0.92;">POULE {{ currentPool.name }}</h2>
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
          <button v-for="(p, i) in pools" :key="p.id" @click="selectPool(i)" class="pool-btn"
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
      <div style="flex-shrink: 0; height: 3px; background: var(--ink-3); margin-bottom: clamp(6px,1vh,14px);">
        <div :style="{
          height: '100%',
          background: 'var(--felt-2)',
          width: ((10 - Math.max(0, carouselCountdown)) / 10 * 100) + '%',
          transition: carouselPaused ? 'none' : 'width 1s linear',
        }" />
      </div>

      <!-- Standings table — pleine largeur, gros texte -->
      <div class="standings-wrap" style="flex: 1; min-height: 0; display: flex; flex-direction: column;
                                          border: 1px solid var(--line); background: var(--ink); overflow: hidden;">
        <div class="standings-head"
             style="background: var(--ink-2); border-bottom: 1px solid var(--line);">
          <div class="mono col-rank" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em;">RANG</div>
          <div class="mono col-name" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em;">JOUEUR</div>
          <div class="mono col-v" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">V</div>
          <div class="mono col-w" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">W</div>
          <div class="mono col-l" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">L</div>
          <div class="mono col-diff" style="font-size: 11px; color: var(--mute); letter-spacing: 0.22em; text-align: right;">DIFF</div>
        </div>

        <div v-for="(s, i) in currentPool.standings" :key="s.player_id" class="standings-row" :style="{
          alignItems: 'center',
          borderTop: i ? '1px solid var(--line)' : 'none',
          background: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'rgba(45,168,118,0.06)' : 'transparent',
          borderLeft: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? '4px solid var(--felt-2)' : '4px solid transparent',
        }">
          <div class="disp-a tnum col-rank rank-num" :style="{ color: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'var(--felt-2)' : 'var(--chalk)' }">
            {{ s.rank }}
          </div>
          <div class="col-name">
            <div class="row-name">{{ s.name }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-top: 4px;">
              {{ currentPool.name }}{{ s.pool_slot }}
            </div>
          </div>
          <div class="disp-a tnum col-v row-v" style="text-align: right;">{{ s.v }}</div>
          <div class="disp-a tnum col-w row-w" style="color: var(--felt-2); text-align: right;">{{ s.w }}</div>
          <div class="disp-a tnum col-l row-l" style="color: var(--mute); text-align: right;">{{ s.l }}</div>
          <div class="disp-a tnum col-diff row-diff" :style="{ textAlign: 'right',
                color: s.diff > 0 ? 'var(--felt-2)' : s.diff < 0 ? 'var(--live)' : 'var(--chalk-2)' }">
            {{ s.diff > 0 ? '+' : '' }}{{ s.diff }}
          </div>
        </div>
      </div>

    </section>

    <!-- Prochains matchs (optionnel, petit) -->
    <section v-if="nextMatches?.length && !isFullscreen" style="flex-shrink: 0; padding: clamp(8px,1.2vh,16px) clamp(12px,2.5vw,32px); border-top: 1px solid var(--line);">
      <h3 class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 10px;">PROCHAINEMENT</h3>
      <div class="next-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
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
    <footer v-if="!isFullscreen" style="flex-shrink: 0; padding: clamp(8px,1.2vh,16px) clamp(12px,2.5vw,32px);
                   border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
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

<style scoped>
/* ── Standings : flex rows fill available height evenly ── */
.standings-head {
  flex-shrink: 0;
  display: grid;
  grid-template-columns: clamp(32px,5vw,70px) 1fr clamp(36px,6vw,80px) clamp(36px,6vw,80px) clamp(36px,6vw,80px) clamp(48px,7vw,100px);
  align-items: center;
  padding: clamp(6px,1vh,12px) clamp(10px,2vw,24px);
  background: var(--ink-2);
  border-bottom: 1px solid var(--line);
}
.standings-row {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: clamp(32px,5vw,70px) 1fr clamp(36px,6vw,80px) clamp(36px,6vw,80px) clamp(36px,6vw,80px) clamp(48px,7vw,100px);
  align-items: center;
  padding: 0 clamp(10px,2vw,24px);
}

/* Font sizes scale with viewport height */
.rank-num  { font-size: clamp(14px, 3.2vh, 38px); }
.row-name  { font-size: clamp(11px, 2vh, 22px); font-weight: 700; }
.row-v     { font-size: clamp(13px, 2.6vh, 30px); text-align: right; }
.row-w     { font-size: clamp(12px, 2.2vh, 26px); text-align: right; color: var(--felt-2); }
.row-l     { font-size: clamp(12px, 2.2vh, 26px); text-align: right; color: var(--mute); }
.row-diff  { font-size: clamp(12px, 2.4vh, 28px); text-align: right; }

/* Pool subtitle slot hint */
.standings-row .mono { font-size: clamp(9px, 1.1vh, 11px); }

/* ── Live score ── */
.live-score { font-size: clamp(28px, 6vh, 80px); }
.live-dash  { font-size: clamp(18px, 3.8vh, 48px); }

/* ── Pool title ── */
.pool-title { font-size: clamp(20px, 5vh, 64px) !important; line-height: 0.92; }

/* ── Pool A/B/C/D selector buttons ── */
.pool-btn {
  width:  clamp(24px, 3.2vw, 36px) !important;
  height: clamp(24px, 3.2vw, 36px) !important;
  font-size: clamp(11px, 1.4vw, 16px) !important;
}

/* ── Live match section: header title ── */
.section-live h2 { font-size: clamp(14px, 2.2vh, 28px); }

/* ── Live grid: 1 col on narrow screens ── */
@media (max-width: 860px) {
  .live-grid { grid-template-columns: 1fr !important; }
}

/* ── Hide W and L on narrow widths ── */
@media (max-width: 860px) {
  .standings-head,
  .standings-row {
    grid-template-columns: clamp(28px,4vw,44px) 1fr clamp(32px,5vw,56px) clamp(44px,7vw,76px);
  }
  .col-w, .col-l { display: none; }
}
@media (max-width: 480px) {
  .standings-head,
  .standings-row {
    grid-template-columns: clamp(24px,4vw,36px) 1fr clamp(30px,5vw,44px) clamp(40px,7vw,60px);
  }
}

/* ── Next matches grid ── */
@media (max-width: 860px)  { .next-grid { grid-template-columns: repeat(2, 1fr) !important; } }
@media (max-width: 480px)  { .next-grid { grid-template-columns: 1fr !important; } }

/* ── Header on small screens ── */
@media (max-width: 640px) {
  header { flex-wrap: wrap; gap: 8px; }
}
</style>
