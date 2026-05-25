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

const tick = ref(0);
const lastRefresh = ref(new Date());
let pollInterval, tickInterval;

onMounted(() => {
  tickInterval = setInterval(() => tick.value++, 1000);
  pollInterval = setInterval(() => {
    router.reload({
      only: ['liveMatches', 'nextMatches', 'pools'],
      preserveScroll: true,
      onSuccess: () => { lastRefresh.value = new Date(); },
    });
  }, 8000);
});

onUnmounted(() => {
  clearInterval(pollInterval);
  clearInterval(tickInterval);
});

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
      <div style="display: flex; gap: 18px; align-items: center;">
        <Chip variant="live">EN DIRECT · {{ liveMatches?.length || 0 }} {{ (liveMatches?.length || 0) > 1 ? 'TABLES' : 'TABLE' }}</Chip>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">
          MAJ · {{ secondsAgo }}s
        </span>
      </div>
    </header>

    <!-- Section : matchs en direct -->
    <section style="padding: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 class="disp-a" style="font-size: 32px;">▶ Matchs en direct</h2>
        <div v-if="competition?.structure === 'pools_knockout'" class="mono" style="font-size: 11px; color: var(--mute);">
          POULES RACE TO {{ competition?.pool_race_to ?? competition?.race_to }} · FINALE RACE TO {{ competition?.knockout_race_to ?? competition?.race_to }}
        </div>
      </div>

      <div v-if="liveMatches?.length" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
        <div v-for="m in liveMatches" :key="m.id"
             style="border: 1px solid rgba(229,72,77,0.45); background: rgba(229,72,77,0.04); padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <div>
              <div class="disp-a" style="font-size: 22px;">{{ m.table?.name?.toUpperCase() }}</div>
              <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-top: 4px;">
                {{ m.phase === 'pool' ? 'POULE · ROUND-ROBIN' : m.round?.replace('R16', '8e').replace('QF', 'QUARTS').replace('SF', 'DEMI').replace('F', 'FINALE') }}
                · RACE TO {{ raceFor(m) }}
              </div>
            </div>
            <Chip variant="live">LIVE</Chip>
          </div>

          <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px;">
            <div style="text-align: left;">
              <div style="font-size: 14px; color: var(--mute); margin-bottom: 4px;" class="mono">SEED #{{ m.player_a?.id }}</div>
              <div :style="{ fontSize: '22px', fontWeight: 700, color: m.score_a > m.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">
                {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
              </div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 4px;">{{ m.player_a?.club?.name }}</div>
            </div>
            <div class="disp-a tnum" style="font-size: 96px; line-height: 0.9; display: flex; gap: 16px; align-items: baseline;">
              <span :style="{ color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_a }}</span>
              <span style="color: var(--mute-2); font-size: 56px;">—</span>
              <span :style="{ color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_b }}</span>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 14px; color: var(--mute); margin-bottom: 4px;" class="mono">SEED #{{ m.player_b?.id }}</div>
              <div :style="{ fontSize: '22px', fontWeight: 700, color: m.score_b > m.score_a ? 'var(--chalk)' : 'var(--chalk-2)' }">
                {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
              </div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 4px;">{{ m.player_b?.club?.name }}</div>
            </div>
          </div>

          <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 16px;
                                    padding-top: 14px; border-top: 1px solid var(--line);
                                    display: flex; justify-content: space-between;">
            <span>FRAME {{ m.score_a + m.score_b + 1 }} · RACE TO {{ raceFor(m) }}</span>
            <span v-if="m.referee">ARB. {{ m.referee?.name?.toUpperCase() }}</span>
          </div>
        </div>
      </div>

      <div v-else style="padding: 60px; text-align: center; border: 1px dashed var(--line);">
        <div class="disp-a" style="font-size: 32px; color: var(--mute);">AUCUN MATCH EN DIRECT</div>
        <p style="font-size: 13px; color: var(--mute); margin-top: 12px;">
          L'organisateur n'a pas encore démarré de match. Les prochains matchs s'afficheront ici.
        </p>
      </div>

      <!-- Prochains matchs -->
      <div v-if="nextMatches?.length" style="margin-top: 24px;">
        <h3 class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">PROCHAINEMENT</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
          <div v-for="m in nextMatches" :key="m.id"
               style="border: 1px solid var(--line); padding: 14px; background: var(--ink-2);">
            <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.18em;">
              {{ m.table?.name?.toUpperCase() }} · {{ fmtTime(m.scheduled_at) || 'À PROGRAMMER' }}
            </div>
            <div style="font-size: 13px; font-weight: 600; margin-top: 8px;">{{ m.player_a?.first_name }} {{ m.player_a?.last_name }}</div>
            <div style="font-size: 13px; color: var(--mute); margin-top: 4px;">vs {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section : classements de poules -->
    <section v-if="pools?.length" style="padding: 32px; border-top: 1px solid var(--line); background: var(--ink-2);">
      <h2 class="disp-a" style="font-size: 32px; margin-bottom: 20px;">▤ Classements de poules</h2>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
        <div v-for="p in pools" :key="p.id" style="border: 1px solid var(--line); background: var(--ink); overflow: hidden;">
          <div style="padding: 12px 14px; border-bottom: 1px solid var(--line);
                      display: flex; justify-content: space-between; align-items: baseline;">
            <div class="disp-a" style="font-size: 22px;">POULE {{ p.name }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--felt-2);">
              {{ competition?.qualifiers_per_pool ?? 2 }} QUAL.
            </div>
          </div>
          <div style="font-size: 11px;">
            <div v-for="(s, i) in p.standings" :key="s.player_id"
                 :style="{
                   display: 'grid', gridTemplateColumns: '20px 1fr 22px 22px 30px',
                   alignItems: 'center', gap: '4px',
                   padding: '7px 14px',
                   borderTop: i ? '1px solid var(--line)' : 'none',
                   background: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'rgba(45,168,118,0.06)' : 'transparent',
                   borderLeft: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? '2px solid var(--felt-2)' : '2px solid transparent',
                 }">
              <span class="mono tnum" :style="{ fontWeight: 700, color: s.rank <= (competition?.qualifiers_per_pool ?? 2) ? 'var(--felt-2)' : 'var(--mute)' }">
                {{ s.rank }}
              </span>
              <span style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ s.name }}
              </span>
              <span class="mono tnum" style="text-align: right; font-weight: 700;">{{ s.v }}</span>
              <span class="mono tnum" style="text-align: right; color: var(--felt-2); font-size: 10px;">{{ s.w }}</span>
              <span class="mono tnum" :style="{ textAlign: 'right', fontWeight: 600, fontSize: '10px',
                    color: s.diff > 0 ? 'var(--felt-2)' : s.diff < 0 ? 'var(--mute)' : 'var(--chalk-2)' }">
                {{ s.diff > 0 ? '+' : '' }}{{ s.diff }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 16px; text-align: center;">
        V · VICTOIRES · W · MANCHES GAGNÉES · Δ · DIFF.
      </div>
    </section>

    <!-- Footer -->
    <footer style="margin-top: auto; padding: 24px 32px; border-top: 1px solid var(--line);
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
