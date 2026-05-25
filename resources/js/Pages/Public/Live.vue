<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';
import { Play, Pause, ChevronRight, ChevronLeft } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  liveMatches: Array,
  nextMatches: Array,
  poolsDone: Array,
  knockoutDone: Array,
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
    if (! carouselPaused.value && (props.poolsDone?.length ?? 0) > 0) {
      carouselCountdown.value--;
      if (carouselCountdown.value <= 0) {
        currentPoolIdx.value = (currentPoolIdx.value + 1) % props.poolsDone.length;
        carouselCountdown.value = CAROUSEL_MS / 1000;
      }
    }
  }, 1000);

  // 10s data refresh
  pollInterval = setInterval(() => {
    router.reload({
      only: ['liveMatches', 'nextMatches', 'poolsDone', 'knockoutDone'],
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
  if ((props.poolsDone?.length ?? 0) === 0) return;
  currentPoolIdx.value = (currentPoolIdx.value - 1 + props.poolsDone.length) % props.poolsDone.length;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};
const nextPool = () => {
  if ((props.poolsDone?.length ?? 0) === 0) return;
  currentPoolIdx.value = (currentPoolIdx.value + 1) % props.poolsDone.length;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};
const selectPool = (i) => {
  currentPoolIdx.value = i;
  carouselCountdown.value = CAROUSEL_MS / 1000;
};

const currentPool = computed(() => props.poolsDone?.[currentPoolIdx.value] ?? null);
const totalDone = computed(() => (props.poolsDone ?? []).reduce((s, p) => s + (p.matches?.length ?? 0), 0) + (props.knockoutDone?.length ?? 0));

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
        <Chip v-if="liveMatches?.length" variant="live">EN DIRECT · {{ liveMatches.length }} {{ liveMatches.length > 1 ? 'TABLES' : 'TABLE' }}</Chip>
        <Chip v-else>PAUSE · {{ nextMatches?.length || 0 }} À VENIR</Chip>
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
        <h2 class="disp-a" style="font-size: 28px;">{{ liveMatches?.length ? 'MATCHS EN DIRECT' : 'PROCHAINS MATCHS' }}</h2>
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

      <!-- Pas de match live : affiche les prochains -->
      <template v-else>
        <div v-if="nextMatches?.length" class="live-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
          <div v-for="m in nextMatches" :key="m.id"
               style="border: 1px solid var(--line); background: var(--ink-2); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
              <div>
                <div class="disp-a" style="font-size: 22px;">{{ m.table?.name?.toUpperCase() ?? 'TABLE À DÉFINIR' }}</div>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-top: 4px;">
                  {{ m.phase === 'pool' ? 'POULE · ROUND-ROBIN' : m.round?.replace('R16','8e').replace('QF','QUARTS').replace('SF','DEMI').replace('F','FINALE') }}
                  · RACE TO {{ raceFor(m) }}
                  <template v-if="fmtTime(m.scheduled_at)"> · {{ fmtTime(m.scheduled_at) }}</template>
                </div>
              </div>
              <span class="mono" style="font-size: 9px; letter-spacing: 0.18em; padding: 3px 8px;
                                        border: 1px solid var(--line-strong); color: var(--mute);">À VENIR</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 18px;">
              <div style="text-align: left; min-width: 0;">
                <div style="font-size: 22px; font-weight: 700; color: var(--chalk-2);">
                  {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
                </div>
                <div style="font-size: 12px; color: var(--mute); margin-top: 4px;">{{ m.player_a?.club?.name }}</div>
              </div>
              <div class="disp-a" style="font-size: clamp(24px,4vh,48px); color: var(--mute-2);">VS</div>
              <div style="text-align: right; min-width: 0;">
                <div style="font-size: 22px; font-weight: 700; color: var(--chalk-2);">
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
      </template>
    </section>

    <!-- Section : résultats des matchs joués (remplace le classement) -->
    <section v-if="currentPool || knockoutDone?.length"
             style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;
                    padding: clamp(8px,1.2vh,16px) clamp(12px,2.5vw,32px) clamp(6px,1vh,12px);
                    border-top: 1px solid var(--line); background: var(--ink-2);">

      <!-- Toolbar -->
      <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;
                  margin-bottom: clamp(6px,1vh,14px); gap: 10px; flex-wrap: wrap;">
        <div style="display: flex; align-items: baseline; gap: 18px;">
          <h2 class="disp-a pool-title" style="line-height: 0.92;">
            {{ currentPool ? 'POULE ' + currentPool.name : 'PHASE FINALE' }}
          </h2>
          <span class="mono" style="font-size: 12px; color: var(--mute); letter-spacing: 0.18em;">
            {{ totalDone }} MATCHS JOUÉS ·
            <template v-if="currentPool">
              {{ currentPool.matches?.length ?? 0 }} DANS CETTE POULE ·
            </template>
            SUIVANT DANS {{ Math.max(0, carouselCountdown) }}s
          </span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <button @click="prevPool" class="btn" style="padding: 8px 12px;" title="Poule précédente"><ChevronLeft :size="14" /></button>
          <button @click="carouselPaused = ! carouselPaused" class="btn" style="padding: 8px 12px;"
                  :title="carouselPaused ? 'Reprendre' : 'Mettre en pause'">
            <Play v-if="carouselPaused" :size="14" />
            <Pause v-else :size="14" />
          </button>
          <button @click="nextPool" class="btn" style="padding: 8px 12px;" title="Poule suivante"><ChevronRight :size="14" /></button>
          <span style="width: 16px;"></span>
          <button v-for="(p, i) in poolsDone" :key="p.id" @click="selectPool(i)" class="pool-btn"
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
          height: '100%', background: 'var(--felt-2)',
          width: ((10 - Math.max(0, carouselCountdown)) / 10 * 100) + '%',
          transition: carouselPaused ? 'none' : 'width 1s linear',
        }" />
      </div>

      <!-- Bandeau phase finale (visible quelle que soit la poule sélectionnée) -->
      <div v-if="knockoutDone?.length" class="ko-strip" style="flex-shrink: 0; margin-bottom: clamp(6px,1vh,12px);">
        <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);
                                  padding: 4px clamp(10px,2vw,20px); background: var(--ink-3); border: 1px solid var(--line);">
          PHASE FINALE · {{ knockoutDone.length }} {{ knockoutDone.length > 1 ? 'MATCHS' : 'MATCH' }} JOUÉ{{ knockoutDone.length > 1 ? 'S' : '' }}
        </div>
        <div class="ko-grid">
          <div v-for="m in knockoutDone" :key="m.id" class="ko-row">
            <span class="mono ko-round">{{ roundLabel(m.round) }}</span>
            <span class="ko-name ko-a" :style="{ fontWeight: m.score_a > m.score_b ? 700 : 400, color: m.score_a > m.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">
              {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
            </span>
            <span class="disp-a tnum ko-score" :style="{ color: 'var(--chalk)' }">{{ m.score_a }} — {{ m.score_b }}</span>
            <span class="ko-name ko-b" :style="{ fontWeight: m.score_b > m.score_a ? 700 : 400, color: m.score_b > m.score_a ? 'var(--chalk)' : 'var(--chalk-2)' }">
              {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
            </span>
            <span class="mono ko-time">{{ fmtTime(m.ended_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Résultats de la poule sélectionnée -->
      <div class="results-wrap" style="flex: 1; min-height: 0; display: flex; flex-direction: column;
                                        border: 1px solid var(--line); background: var(--ink); overflow: hidden;">
        <!-- En-tête colonnes -->
        <div class="res-head" style="flex-shrink: 0; background: var(--ink-2); border-bottom: 1px solid var(--line);">
          <div class="mono rh-time" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em;">HEURE</div>
          <div class="mono rh-a" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em;">JOUEUR</div>
          <div class="mono rh-score" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em; text-align: center;">SCORE</div>
          <div class="mono rh-b" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em; text-align: right;">ADVERSAIRE</div>
        </div>

        <!-- Lignes de résultats -->
        <template v-if="currentPool?.matches?.length">
          <div v-for="(m, i) in currentPool.matches" :key="m.id" class="res-row"
               :style="{ borderTop: i ? '1px solid var(--line)' : 'none', alignItems: 'center' }">
            <div class="mono rh-time res-time">{{ fmtTime(m.ended_at) }}</div>
            <div class="rh-a res-name"
                 :style="{ fontWeight: m.score_a > m.score_b ? 700 : 400, color: m.score_a > m.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">
              {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
            </div>
            <div class="disp-a tnum rh-score res-score" style="text-align: center;">
              {{ m.score_a }} — {{ m.score_b }}
            </div>
            <div class="rh-b res-name"
                 :style="{ textAlign: 'right', fontWeight: m.score_b > m.score_a ? 700 : 400, color: m.score_b > m.score_a ? 'var(--chalk)' : 'var(--chalk-2)' }">
              {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
            </div>
          </div>
        </template>
        <div v-else style="flex: 1; display: flex; align-items: center; justify-content: center;">
          <span class="mono" style="font-size: 12px; color: var(--mute); letter-spacing: 0.18em;">AUCUN MATCH JOUÉ DANS CETTE POULE</span>
        </div>
      </div>

    </section>

    <!-- Prochains matchs (seulement si des matchs live sont affichés en haut) -->
    <section v-if="nextMatches?.length && liveMatches?.length && !isFullscreen" style="flex-shrink: 0; padding: clamp(8px,1.2vh,16px) clamp(12px,2.5vw,32px); border-top: 1px solid var(--line);">
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
/* ── Results table (replaces standings) ── */
.res-head,
.res-row {
  display: grid;
  grid-template-columns: clamp(38px,5vw,68px) 1fr clamp(64px,9vw,110px) 1fr;
  align-items: center;
  padding: 0 clamp(10px,2vw,24px);
}
.res-head {
  padding-top:    clamp(6px,1vh,12px);
  padding-bottom: clamp(6px,1vh,12px);
}
.res-row {
  flex: 1;
  min-height: 0;
}
.res-time  { font-size: clamp(9px, 1.2vh, 13px); color: var(--mute); }
.res-name  { font-size: clamp(11px, 2vh, 22px); }
.res-score { font-size: clamp(14px, 2.8vh, 34px); }

/* ── Knockout strip ── */
.ko-grid {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--line);
  border-top: none;
}
.ko-row {
  display: grid;
  grid-template-columns: clamp(60px,9vw,110px) 1fr clamp(60px,8vw,90px) 1fr clamp(36px,4vw,52px);
  align-items: center;
  gap: 8px;
  padding: clamp(5px,0.9vh,10px) clamp(10px,2vw,20px);
  border-top: 1px solid var(--line);
  background: var(--ink);
}
.ko-round { font-size: clamp(8px,1vh,11px); color: var(--felt-2); letter-spacing: 0.14em; white-space: nowrap; }
.ko-name  { font-size: clamp(10px,1.5vh,16px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ko-a     { text-align: left; }
.ko-b     { text-align: right; }
.ko-score { font-size: clamp(12px,2vh,20px); text-align: center; }
.ko-time  { font-size: clamp(8px,1vh,11px); color: var(--mute); text-align: right; }

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

/* ── Hide time column on narrow ── */
@media (max-width: 640px) {
  .res-head, .res-row { grid-template-columns: 1fr clamp(54px,8vw,80px) 1fr; }
  .rh-time            { display: none; }
  .ko-row             { grid-template-columns: clamp(50px,8vw,80px) 1fr clamp(50px,7vw,70px) 1fr; }
  .ko-time            { display: none; }
}

/* ── Next matches grid ── */
@media (max-width: 860px)  { .next-grid { grid-template-columns: repeat(2, 1fr) !important; } }
@media (max-width: 480px)  { .next-grid { grid-template-columns: 1fr !important; } }

/* ── Header on small screens ── */
@media (max-width: 640px) {
  header { flex-wrap: wrap; gap: 8px; }
}
</style>
