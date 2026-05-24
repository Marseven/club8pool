<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  pools: Array,
  liveMatches: Array,
  schedule: Array,
  stats: Object,
});

const firstLive = computed(() => props.liveMatches?.[0]);

const fmtFcfa = (n) => {
  if (n >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '') + 'M FCFA';
  if (n >= 1000) return (n / 1000).toFixed(0) + 'k FCFA';
  return n + ' FCFA';
};

const statusLabel = (s) => ({ done: 'TERMINÉ', live: 'EN COURS', next: 'À VENIR' }[s] ?? '');
</script>

<template>
  <Head :title="competition?.name ?? 'Club 8 Pool'" />
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <section style="position: relative; padding: 64px 48px 48px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 32px;">
        <Chip variant="live">EN DIRECT · {{ liveMatches?.length || 0 }} TABLES</Chip>
        <Chip>{{ competition?.pool_count }} POULES · {{ stats?.players }} JOUEURS</Chip>
        <Chip>{{ competition?.city?.toUpperCase() }}</Chip>
      </div>
      <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 48px; align-items: end;">
        <div>
          <div class="mono" style="font-size: 12px; letter-spacing: 0.2em; color: var(--mute); margin-bottom: 18px;">
            {{ competition?.discipline?.toUpperCase() }} · POULES + PHASE FINALE
          </div>
          <h1 class="disp-a" style="font-size: 132px; line-height: 0.82;">
            ICONE POOL<br /><span style="color: var(--felt-2);">CHAMPIONSHIP</span>
          </h1>
          <p style="margin-top: 28px; font-size: 16px; max-width: 520px; color: var(--chalk-2); line-height: 1.5;">
            Quatre poules de sept joueurs. Race to {{ competition?.race_to }}. Les {{ competition?.qualifiers_per_pool }} meilleurs
            de chaque poule se qualifient pour le tableau final. Tout commence à {{ competition?.venue }}.
          </p>
          <div style="display: flex; gap: 12px; margin-top: 28px;">
            <Link href="/tv" class="btn btn-felt">Suivre le live →</Link>
            <Link href="/competitions" class="btn">Voir le bracket</Link>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div style="border: 1px solid var(--line); padding: 20px;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">PROCHAINE PHASE</div>
            <div class="disp-a tnum" style="font-size: 64px; margin-top: 8px; display: flex; gap: 4px; align-items: baseline;">
              <span>02</span><span style="color: var(--mute-2);">:</span>
              <span>14</span><span style="color: var(--mute-2);">:</span>
              <span>06</span>
            </div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);
                                      margin-top: 8px; display: flex; justify-content: space-between;">
              <span>JOURS</span><span>HEURES</span><span>MIN</span>
            </div>
          </div>

          <div v-if="firstLive" style="border: 1px solid var(--line);">
            <div style="padding: 10px 14px; border-bottom: 1px solid var(--line);
                        display: flex; justify-content: space-between; align-items: center;">
              <span class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">
                EN DIRECT · {{ firstLive.table?.name?.toUpperCase() || 'TABLE' }}
              </span>
              <Chip variant="live" style="padding: 2px 6px; font-size: 9px;">LIVE</Chip>
            </div>
            <div v-for="(p, i) in [
              { name: firstLive.player_a?.first_name?.[0] + '. ' + firstLive.player_a?.last_name, score: firstLive.score_a, win: firstLive.score_a > firstLive.score_b },
              { name: firstLive.player_b?.first_name?.[0] + '. ' + firstLive.player_b?.last_name, score: firstLive.score_b, win: firstLive.score_b > firstLive.score_a },
            ]" :key="i"
              style="display: grid; grid-template-columns: 1fr auto; align-items: center;
                     padding: 14px 16px;" :style="{ borderTop: i ? '1px solid var(--line)' : 'none' }">
              <span :style="{ fontSize: '15px', fontWeight: 600, color: p.win ? 'var(--chalk)' : 'var(--mute)' }">{{ p.name }}</span>
              <span class="disp-a tnum" :style="{ fontSize: '38px', color: p.win ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ p.score }}</span>
            </div>
            <div style="padding: 10px 14px; border-top: 1px solid var(--line);
                        display: flex; justify-content: space-between;">
              <span class="mono" style="font-size: 10px; color: var(--mute);">RACE TO {{ competition?.race_to ?? 7 }}</span>
              <span class="mono" style="font-size: 10px; color: var(--mute);">{{ competition?.venue?.toUpperCase() }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section style="display: grid; grid-template-columns: repeat(5, 1fr); border-bottom: 1px solid var(--line);">
      <div v-for="(item, i) in [
        [String(stats?.players ?? 0).padStart(2,'0'), 'JOUEURS'],
        [String(stats?.pools ?? 0).padStart(2,'0'), 'POULES'],
        [String(stats?.tables ?? 0).padStart(2,'0'), 'TABLES'],
        [String(stats?.matches ?? 0).padStart(2,'0'), 'MATCHS'],
        [fmtFcfa(stats?.prize_pool ?? 0), 'DOTATION'],
      ]" :key="i" :style="{ padding: '28px 24px', borderRight: i < 4 ? '1px solid var(--line)' : 'none' }">
        <div class="disp-a tnum" style="font-size: 44px;">{{ item[0] }}</div>
        <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.22em; margin-top: 6px;">{{ item[1] }}</div>
      </div>
    </section>

    <section v-if="pools?.length" style="padding: 56px 48px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 28px;">
        <h2 class="disp-a" style="font-size: 56px;">En tête de poule</h2>
        <Link href="/competitions" style="font-size: 13px; color: var(--mute); text-decoration: underline; text-underline-offset: 6px;">
          Voir tous les classements →
        </Link>
      </div>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
        <Link v-for="p in pools" :key="p.id" href="/competitions"
              style="border: 1px solid var(--line); padding: 24px; background: var(--ink-2); display: block;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">POULE {{ p.name }} · LEADER</div>
          <div class="disp-a" style="font-size: 32px; margin-top: 14px;">{{ p.leader?.name ?? '—' }}</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 10px;">
            V {{ p.leader?.v ?? 0 }} · DIFF {{ p.leader?.diff > 0 ? '+' : '' }}{{ p.leader?.diff ?? 0 }}
          </div>
        </Link>
      </div>
    </section>

    <section style="padding: 56px 48px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 28px;">
        <h2 class="disp-a" style="font-size: 56px;">Programme du jour</h2>
        <span class="mono" style="font-size: 12px; color: var(--mute); letter-spacing: 0.16em;">
          SAM. 06 JUIN · {{ competition?.venue?.toUpperCase() }}
        </span>
      </div>
      <div>
        <div v-for="(s, i) in schedule" :key="i"
          style="display: grid; grid-template-columns: 120px 1fr 160px 28px;
                 align-items: center; padding: 18px 0; border-top: 1px solid var(--line);">
          <span class="disp-a tnum" style="font-size: 32px;">{{ s.time }}</span>
          <span style="font-size: 18px; font-weight: 500;">{{ s.round }}</span>
          <Chip :variant="s.status === 'live' ? 'live' : ''" style="width: fit-content;">{{ statusLabel(s.status) }}</Chip>
          <span style="color: var(--mute);">→</span>
        </div>
      </div>
    </section>

    <footer style="padding: 40px 48px; display: flex; justify-content: space-between;
                   align-items: center; color: var(--mute); font-size: 12px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <GabonFlag :width="26" :height="18" />
        <span class="mono" style="letter-spacing: 0.18em; text-transform: uppercase;">
          Icone Pool · {{ competition?.city ?? 'Libreville' }}
        </span>
      </div>
      <div class="mono" style="letter-spacing: 0.18em; text-transform: uppercase;">© 2026 · Club 8 Pool</div>
    </footer>
  </div>
</template>
