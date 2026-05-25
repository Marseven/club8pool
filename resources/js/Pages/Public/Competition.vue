<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PoolStandings from '@/Components/PoolStandings.vue';
import PoolMatches from '@/Components/PoolMatches.vue';
import Bracket from '@/Components/Bracket.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  pools: Array,
  knockoutMatches: Object,
  liveMatches: Array,
});

const tab = ref('standings');
const tabs = [
  ['standings', 'Classements'],
  ['matches', 'Matchs'],
  ['knockout', 'Phase finale'],
  ['live', 'Live'],
];

const matchesLive = computed(() => (props.liveMatches || []).length);
const hasKnockout = computed(() => Object.keys(props.knockoutMatches || {}).length > 0);
</script>

<template>
  <Head :title="competition?.name ?? 'Compétition'">
    <meta name="description" :content="`Bracket, classements de poules et résultats live du ${competition?.name}. ${competition?.pool_count} poules, ${competition?.qualifiers_per_pool} qualifiés par poule.`" head-key="description" />
    <meta property="og:description" :content="`Bracket, classements de poules et résultats live du ${competition?.name}.`" head-key="og:description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />
    <section style="padding: 32px 48px 0; border-bottom: 1px solid var(--line);">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
        <Chip v-if="matchesLive" variant="live">EN DIRECT · {{ matchesLive }} TABLES</Chip>
        <span class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--mute);">
          {{ competition?.starts_on?.slice(0, 10) }} → {{ competition?.ends_on?.slice(0, 10) }} · {{ competition?.venue?.toUpperCase() }}
        </span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: end;">
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
          <img v-if="competition?.logo_url" :src="competition.logo_url" :alt="competition.name + ' logo'"
               style="height: 80px; width: auto; max-width: 160px; object-fit: contain;" />
          <h1 class="disp-a" style="font-size: 72px;">
            {{ competition?.name }}
          </h1>
        </div>
        <div style="display: flex; gap: 28px; padding-bottom: 14px;">
          <div>
            <div class="disp-a tnum" style="font-size: 26px;">{{ String(competition?.pool_count ?? 0).padStart(2, '0') }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">POULES</div>
          </div>
          <div>
            <div class="disp-a tnum" style="font-size: 26px;">{{ String(competition?.player_slots ?? 0).padStart(2, '0') }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">JOUEURS</div>
          </div>
          <div v-if="competition?.structure === 'pools_knockout'">
            <div class="disp-a tnum" style="font-size: 26px;">{{ competition?.pool_race_to ?? competition?.race_to }} / {{ competition?.knockout_race_to ?? competition?.race_to }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">RACE POULES / FINALE</div>
          </div>
          <div v-else>
            <div class="disp-a tnum" style="font-size: 26px;">RACE TO {{ competition?.race_to ?? 3 }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">FORMAT</div>
          </div>
          <div>
            <div class="disp-a tnum" style="font-size: 26px;">{{ competition?.qualifiers_per_pool ?? 2 }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute); margin-top: 2px;">QUALIFIÉS / POULE</div>
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

    <section v-if="tab === 'standings'" style="padding: 32px 48px;
                                                display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
      <PoolStandings v-for="pool in pools" :key="pool.id" :pool="pool" :qualifiers-per-pool="competition?.qualifiers_per_pool ?? 2" />
    </section>

    <section v-else-if="tab === 'matches'" style="padding: 32px 48px;">
      <div v-for="pool in pools" :key="pool.id" style="margin-bottom: 40px;">
        <h3 class="disp-a" style="font-size: 28px; margin-bottom: 16px;">POULE {{ pool.name }}</h3>
        <PoolMatches :pool="pool" />
      </div>
    </section>

    <section v-else-if="tab === 'knockout'" style="padding: 32px 48px;">
      <div v-if="hasKnockout" style="overflow-x: auto;">
        <Bracket :matches="knockoutMatches" />
      </div>
      <div v-else style="padding: 80px; text-align: center;">
        <div class="disp-a" style="font-size: 40px; color: var(--mute);">EN ATTENTE</div>
        <p style="margin-top: 14px; color: var(--mute); font-size: 14px;">
          Le tableau de la phase finale sera tiré dès que les poules seront terminées.
        </p>
      </div>
    </section>

    <section v-else-if="tab === 'live'" style="padding: 32px 48px;">
      <div v-if="liveMatches.length === 0" style="padding: 80px; text-align: center;">
        <div class="disp-a" style="font-size: 36px; color: var(--mute);">AUCUN MATCH EN DIRECT</div>
      </div>
      <div v-else style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
        <div v-for="m in liveMatches" :key="m.id"
             style="border: 1px solid rgba(229,72,77,0.45); background: rgba(229,72,77,0.04); padding: 20px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 14px;">
            <span class="mono" style="font-size: 10px; letter-spacing: 0.2em; color: var(--mute);">
              {{ m.table?.name?.toUpperCase() }}
            </span>
            <Chip variant="live">LIVE</Chip>
          </div>
          <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px;">
            <span style="font-size: 18px; font-weight: 600;">{{ m.player_a?.first_name }} {{ m.player_a?.last_name }}</span>
            <span class="disp-a tnum" style="font-size: 48px;">
              <span :style="{ color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_a }}</span>
              <span style="color: var(--mute-2);"> — </span>
              <span :style="{ color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_b }}</span>
            </span>
            <span style="font-size: 18px; font-weight: 600; text-align: right;">{{ m.player_b?.first_name }} {{ m.player_b?.last_name }}</span>
          </div>
          <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 12px;">
            RACE TO {{ m.phase === 'knockout'
              ? (competition?.knockout_race_to ?? competition?.race_to)
              : (competition?.pool_race_to ?? competition?.race_to) }}
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
