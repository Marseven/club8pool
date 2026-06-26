<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition:    Object,
  pools:          Array,
  liveMatches:    Array,
  schedule:       Array,
  stats:          Object,
  countdownTo:    String,   // ISO datetime string or null
  countdownLabel: String,   // e.g. 'DÉBUT DU TOURNOI'
});

const firstLive = computed(() => props.liveMatches?.[0]);

// ── Countdown ─────────────────────────────────────────────────────────────────
const countdown = ref(null);

const pad = (n) => String(Math.max(0, n)).padStart(2, '0');

const tick = () => {
  if (!props.countdownTo) { countdown.value = null; return; }
  const diff = new Date(props.countdownTo) - Date.now();
  if (diff <= 0) {
    countdown.value = { days: '00', hours: '00', mins: '00', secs: '00' };
    return;
  }
  countdown.value = {
    days:  pad(Math.floor(diff / 86400000)),
    hours: pad(Math.floor((diff % 86400000) / 3600000)),
    mins:  pad(Math.floor((diff % 3600000) / 60000)),
    secs:  pad(Math.floor((diff % 60000) / 1000)),
  };
};

let timer = null;
onMounted(() => { tick(); timer = setInterval(tick, 1000); });
onUnmounted(() => clearInterval(timer));

// ── Description paragraph ─────────────────────────────────────────────────────
const descParts = computed(() => {
  const c = props.competition;
  if (!c) return [];
  const parts = [];
  if (c.structure?.includes('pool') && c.pool_count) {
    const poolRace = c.pool_race_to ?? c.race_to;
    parts.push(`${c.pool_count} poule${c.pool_count > 1 ? 's' : ''} de ${c.pool_size ?? '?'} joueurs.`);
    parts.push(`Race to ${poolRace}.`);
    if (c.qualifiers_per_pool) {
      parts.push(`Les ${c.qualifiers_per_pool} meilleur${c.qualifiers_per_pool > 1 ? 's' : ''} de chaque poule se qualifient pour le tableau final.`);
    }
  } else if (c.structure === 'knockout') {
    parts.push(`Élimination directe. Race to ${c.race_to}.`);
  } else if (c.race_to) {
    parts.push(`Race to ${c.race_to}.`);
  }
  if (c.venue) {
    const prefix = c.status === 'in_progress' ? 'En cours à' : c.status === 'finished' ? 'S\'est déroulé à' : 'Tout commence à';
    parts.push(`${prefix} ${c.venue}.`);
  }
  return parts;
});

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmtFcfa = (n) => {
  if (!n) return '—';
  if (n >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '') + 'M FCFA';
  if (n >= 1000) return (n / 1000).toFixed(0) + 'k FCFA';
  return n + ' FCFA';
};

const statusLabel = (s) => ({ done: 'TERMINÉ', live: 'EN COURS', next: 'À VENIR', rest: 'REPOS' }[s] ?? '');
</script>

<template>
  <Head :title="competition?.name ?? 'Club 8 Pool'">
    <meta name="description" :content="`${competition?.name ?? 'Club 8 Pool'} — ${competition?.pool_count} poules, ${stats?.players} joueurs, race to ${competition?.race_to}. Suivez classements et matchs en direct à ${competition?.venue}.`" head-key="description" />
    <meta property="og:description" :content="`${competition?.name ?? 'Club 8 Pool'} — ${competition?.pool_count} poules, ${stats?.players} joueurs, race to ${competition?.race_to}.`" head-key="og:description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <section style="position: relative; padding: 64px 0 48px; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div v-if="competition?.logo_url" style="margin-bottom: 24px;">
          <img :src="competition.logo_url" :alt="competition.name + ' logo'"
               style="max-height: 96px; max-width: 200px; object-fit: contain;" />
        </div>

        <!-- Status chips -->
        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 32px; flex-wrap: wrap;">
          <!-- Live badge only when actually in progress -->
          <Chip v-if="competition?.status === 'in_progress'" variant="live">
            EN DIRECT · {{ liveMatches?.length || 0 }} TABLES
          </Chip>
          <Chip v-else-if="competition?.status === 'registration'" variant="felt">INSCRIPTIONS OUVERTES</Chip>
          <Chip v-else-if="competition?.status === 'draft'" variant="">BIENTÔT</Chip>
          <Chip v-else-if="competition?.status === 'finished'" variant="">TERMINÉE</Chip>

          <Chip v-if="competition?.pool_count">
            {{ competition.pool_count }} POULES · {{ stats?.players }} JOUEURS
          </Chip>
          <Chip v-if="competition?.city">{{ competition.city.toUpperCase() }}</Chip>
        </div>

        <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 48px; align-items: end;">
          <div>
            <!-- Discipline + format line -->
            <div class="mono" style="font-size: 12px; letter-spacing: 0.2em; color: var(--mute); margin-bottom: 18px;">
              <template v-if="competition?.discipline">{{ competition.discipline.toUpperCase() }} · </template>
              <template v-if="competition?.structure === 'pools_knockout'">
                POULES<template v-if="['in_progress', 'finished'].includes(competition?.status)"> + PHASE FINALE</template>
              </template>
              <template v-else-if="competition?.structure === 'pools_only'">PHASE DE POULES</template>
              <template v-else-if="competition?.structure === 'knockout'">ÉLIMINATION DIRECTE</template>
              <template v-else-if="competition?.structure">{{ competition.structure.toUpperCase() }}</template>
            </div>

            <!-- Competition name -->
            <h1 class="disp-a" style="font-size: clamp(40px, 10vw, 112px); line-height: 0.88; word-break: break-word;">
              {{ competition?.name?.toUpperCase() }}
            </h1>

            <!-- Dynamic description -->
            <p style="margin-top: 28px; font-size: 16px; max-width: 520px; color: var(--chalk-2); line-height: 1.7;">
              {{ descParts.join(' ') }}
            </p>

            <!-- CTA buttons adapt to competition status -->
            <div style="display: flex; gap: 12px; margin-top: 28px; flex-wrap: wrap;">
              <template v-if="competition?.status === 'in_progress'">
                <a href="/live" target="_blank" rel="noopener" class="btn btn-felt">Suivre le live ↗</a>
                <Link href="/competitions" class="btn">Voir le bracket</Link>
              </template>
              <template v-else-if="competition?.status === 'registration'">
                <Link :href="`/inscription/${competition.slug}`" class="btn btn-felt">S'inscrire →</Link>
                <Link href="/competitions" class="btn">En savoir plus</Link>
              </template>
              <template v-else-if="competition?.status === 'finished'">
                <Link href="/tournois" class="btn">Voir l'archive →</Link>
                <Link href="/competitions" class="btn">Résultats</Link>
              </template>
              <template v-else>
                <Link href="/competitions" class="btn">En savoir plus</Link>
              </template>
            </div>
          </div>

          <!-- Right column: countdown + live match -->
          <div style="display: flex; flex-direction: column; gap: 14px;">

            <!-- Countdown block -->
            <div style="border: 1px solid var(--line); padding: 20px 18px;">
              <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">
                {{ countdownLabel ?? 'PROCHAINE PHASE' }}
              </div>

              <!-- Active countdown -->
              <template v-if="countdown && countdownTo">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 12px;">
                  <div v-for="unit in [
                    [countdown.days,  'JOURS'],
                    [countdown.hours, 'H'],
                    [countdown.mins,  'MIN'],
                    [countdown.secs,  'SEC'],
                  ]" :key="unit[1]" style="text-align: center;">
                    <div class="disp-a tnum" style="font-size: 42px; line-height: 1.05;">{{ unit[0] }}</div>
                    <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.16em; margin-top: 4px;">{{ unit[1] }}</div>
                  </div>
                </div>
              </template>

              <!-- No date available -->
              <template v-else>
                <div class="disp-a" style="font-size: 22px; margin-top: 12px; color: var(--mute);">
                  DATE À CONFIRMER
                </div>
              </template>
            </div>

            <!-- Live match card (only when in_progress) -->
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
      </div>
    </section>

    <!-- Stats bar — intentionally full-width split-cell layout, no container -->
    <section style="display: grid; grid-template-columns: repeat(5, 1fr); border-bottom: 1px solid var(--line);">
      <div v-for="(item, i) in [
        [String(stats?.players ?? 0).padStart(2,'0'), 'JOUEURS'],
        [String(stats?.pools ?? 0).padStart(2,'0'), 'POULES'],
        [String(stats?.tables ?? 0).padStart(2,'0'), 'TABLES'],
        [String(stats?.matches_done ?? stats?.matches ?? 0).padStart(2,'0'), 'MATCHS JOUÉS'],
        [fmtFcfa(stats?.prize_pool ?? 0), 'DOTATION'],
      ]" :key="i" :style="{ padding: '28px 24px', borderRight: i < 4 ? '1px solid var(--line)' : 'none' }">
        <div class="disp-a tnum" style="font-size: 44px;">{{ item[0] }}</div>
        <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.22em; margin-top: 6px;">{{ item[1] }}</div>
      </div>
    </section>

    <!-- Pool leaders -->
    <section v-if="pools?.length" style="padding: 56px 0; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 28px; gap: 14px; flex-wrap: wrap;">
          <h2 class="disp-a" style="font-size: 56px;">En tête de poule</h2>
          <Link href="/competitions" style="font-size: 13px; color: var(--mute); text-decoration: underline; text-underline-offset: 6px;">
            Voir tous les classements →
          </Link>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
          <Link v-for="p in pools" :key="p.id" href="/competitions"
                style="border: 1px solid var(--line); padding: 24px; background: var(--ink-2); display: block; text-decoration: none;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">POULE {{ p.name }} · LEADER</div>
            <div class="disp-a" style="font-size: 32px; margin-top: 14px;">{{ p.leader?.name ?? '—' }}</div>
            <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 10px;">
              V {{ p.leader?.v ?? 0 }} · DIFF {{ p.leader?.diff > 0 ? '+' : '' }}{{ p.leader?.diff ?? 0 }}
            </div>
          </Link>
        </div>
      </div>
    </section>

    <!-- Schedule -->
    <section v-if="schedule?.length" style="padding: 56px 0; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 28px; gap: 14px; flex-wrap: wrap;">
          <h2 class="disp-a" style="font-size: 56px;">Programme</h2>
          <span class="mono" style="font-size: 12px; color: var(--mute); letter-spacing: 0.16em;">
            {{ competition?.venue?.toUpperCase() }}
          </span>
        </div>
        <div>
          <div v-for="(s, i) in schedule" :key="i"
            style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
                   padding: 18px 0; border-top: 1px solid var(--line);">
            <span class="disp-a tnum" style="font-size: 32px; min-width: 90px; text-transform: uppercase;">{{ s.time }}</span>
            <span style="font-size: 16px; font-weight: 500; flex: 1; min-width: 140px;">{{ s.round }}</span>
            <Chip :variant="s.status === 'live' ? 'live' : ''" style="width: fit-content;">{{ statusLabel(s.status) }}</Chip>
          </div>
        </div>
      </div>
    </section>

    <PublicFooter />
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  section:first-of-type {
    padding: 28px 0 24px !important;
  }
  /* Hero: stack columns */
  div[style*="grid-template-columns: 1.4fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
  /* Stats bar: 2 columns */
  section[style*="grid-template-columns: repeat(5, 1fr)"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  /* Pool leaders: 2 columns */
  div[style*="grid-template-columns: repeat(4, 1fr)"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  section[style*="padding: 56px 0"] {
    padding: 28px 0 !important;
  }
  span[style*="font-size: 32px"][style*="min-width"] {
    font-size: 22px !important;
    min-width: 60px !important;
  }
}

@media (max-width: 480px) {
  section[style*="grid-template-columns: repeat(5, 1fr)"],
  section[style*="grid-template-columns: repeat(2, 1fr)"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  div.disp-a[style*="font-size: 44px"] {
    font-size: 30px !important;
  }
  div[style*="grid-template-columns: repeat(4, 1fr)"] {
    grid-template-columns: 1fr !important;
  }
}
</style>
