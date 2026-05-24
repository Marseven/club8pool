<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import Bracket from '@/Components/Bracket.vue';
import RankingTable from '@/Components/RankingTable.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  matches: Object,
  ranking: Array,
  liveMatches: Array,
  schedule: Array,
});

const tab = ref('bracket');
const tabs = [
  ['bracket', 'Bracket'],
  ['ranking', 'Classement'],
  ['players', 'Joueurs'],
  ['schedule', 'Calendrier'],
  ['live', 'Live'],
];

const fmtTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}:${d.getUTCMinutes().toString().padStart(2, '0')}`;
};

const matchesTotal = computed(() => (props.schedule || []).length);
const matchesLive = computed(() => (props.liveMatches || []).length);
</script>

<template>
  <Head :title="competition?.name ?? 'Compétition'" />
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />
    <section style="padding: 32px 48px 0; border-bottom: 1px solid var(--line);">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
        <Chip variant="live">EN DIRECT</Chip>
        <span class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--mute);">
          SAM. 06 JUIN 2026 · {{ competition?.venue?.toUpperCase() }}
        </span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: end;">
        <h1 class="disp-a" style="font-size: 88px;">
          {{ competition?.name?.split(' — ')[0] }} <span style="color: var(--felt-2);">{{ competition?.discipline }}</span>
        </h1>
        <div style="display: flex; gap: 28px; padding-bottom: 14px;">
          <div v-for="(item, i) in [
            ['16', 'JOUEURS'],
            [`${matchesTotal}`, 'MATCHS'],
            [`T-${matchesLive}`, 'TABLES'],
            ['EN COURS', 'PHASE'],
          ]" :key="i">
            <div class="disp-a tnum" style="font-size: 26px;">{{ item[0] }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">{{ item[1] }}</div>
          </div>
        </div>
      </div>
      <div style="display: flex; gap: 0; margin-top: 32px;">
        <button v-for="[k, l] in tabs" :key="k" @click="tab = k"
          :style="{ background: 'transparent', border: 'none', cursor: 'pointer',
                    padding: '14px 22px',
                    borderBottom: tab === k ? '2px solid var(--chalk)' : '2px solid transparent',
                    color: tab === k ? 'var(--chalk)' : 'var(--mute)',
                    fontSize: '12px', fontWeight: 700, letterSpacing: '0.12em', textTransform: 'uppercase' }">
          {{ l }}
        </button>
      </div>
    </section>

    <section style="display: grid; grid-template-columns: 1fr 360px; border-bottom: 1px solid var(--line);">
      <div style="padding: 32px 48px; border-right: 1px solid var(--line); overflow: hidden;">
        <Bracket v-if="tab === 'bracket'" :matches="matches" />
        <RankingTable v-else-if="tab === 'ranking'" :players="ranking" />
        <div v-else-if="tab === 'schedule'" style="display: flex; flex-direction: column;">
          <div v-for="(m, i) in schedule" :key="m.id" :style="{
            display: 'grid', gridTemplateColumns: '100px 1fr auto', alignItems: 'center',
            padding: '14px 0', borderTop: i ? '1px solid var(--line)' : '1px solid var(--line-strong)'
          }">
            <span class="disp-a tnum" style="font-size: 22px;">{{ fmtTime(m.scheduled_at) }}</span>
            <span style="font-size: 14px;">
              {{ m.player_a?.last_name ?? 'À déterminer' }}
              <span style="color: var(--mute);">vs</span>
              {{ m.player_b?.last_name ?? 'À déterminer' }}
            </span>
            <span class="mono" style="font-size: 10px; color: var(--mute);">{{ m.table?.name ?? '—' }} · {{ m.round }}</span>
          </div>
        </div>
        <div v-else style="padding: 80px; text-align: center; color: var(--mute);" class="mono">
          VUE « {{ tab.toUpperCase() }} » À VENIR
        </div>
      </div>
      <aside style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span class="disp-a" style="font-size: 22px;">En direct</span>
          <Chip variant="live">{{ matchesLive }} TABLES</Chip>
        </div>
        <div v-for="m in liveMatches" :key="m.id"
             style="border: 1px solid var(--line); padding: 16px; background: var(--ink-2);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <span class="mono" style="font-size: 10px; letter-spacing: 0.2em; color: var(--mute);">
              {{ m.table?.name?.toUpperCase() }}
            </span>
          </div>
          <div v-for="(p, j) in [m.player_a, m.player_b]" :key="j"
               style="display: grid; grid-template-columns: 1fr auto; align-items: center; padding: 6px 0;">
            <span :style="{ fontSize: '13px', fontWeight: 600,
                            color: (j === 0 ? m.score_a : m.score_b) > (j === 0 ? m.score_b : m.score_a) ? 'var(--chalk)' : 'var(--mute)' }">
              {{ p.first_name }} {{ p.last_name }}
            </span>
            <span class="disp-a tnum" :style="{ fontSize: '28px',
                  color: (j === 0 ? m.score_a : m.score_b) > (j === 0 ? m.score_b : m.score_a) ? 'var(--felt-2)' : 'var(--chalk-2)' }">
              {{ j === 0 ? m.score_a : m.score_b }}
            </span>
          </div>
          <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;
                                    margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line);">
            RACE TO {{ competition?.race_to }}
          </div>
        </div>
      </aside>
    </section>
  </div>
</template>
