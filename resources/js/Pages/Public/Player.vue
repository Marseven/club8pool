<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({
  player: Object,
  competition: Object,
  registration: Object,
  standing: Object,
  qualified: Boolean,
  totalPlayersInPool: Number,
  journey: Array,
  history: Array,
});

const initials = computed(() => `${props.player.first_name?.[0] ?? ''}${props.player.last_name?.[0] ?? ''}`);

const winRate = computed(() => {
  if (!props.standing) return 0;
  const total = props.standing.v + (props.standing.l > 0 ? props.journey?.filter(j => j.status === 'done' && !j.win && !j.is_draw).length ?? 0 : 0);
  const done = props.journey?.filter(j => j.status === 'done').length ?? 0;
  return done ? Math.round((props.standing.v / done) * 100) : 0;
});

const phaseLabel = (m) => {
  if (m.phase === 'pool') return `Poule ${m.pool_name}`;
  return ({ R32: '32e', R16: '8e', QF: 'Quart', SF: 'Demi', F: 'Finale', GF: 'Grande finale', EXH: 'Exhib.' }[m.round] || m.round);
};

const fmtDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' });
};

const fmtTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}h${d.getUTCMinutes().toString().padStart(2, '0')}`;
};
</script>

<template>
  <Head :title="`${player.first_name} ${player.last_name}`">
    <meta name="description" :content="`Fiche joueur ${player.first_name} ${player.last_name} (${player.club?.name}) — parcours dans ${competition?.name}, classement de poule, historique des matchs.`" head-key="description" />
    <meta property="og:type" content="profile" head-key="og:type" />
  </Head>

  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <div style="padding: 20px 48px; border-bottom: 1px solid var(--line); display: flex; gap: 12px; align-items: center;">
      <Link href="/joueurs" class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--mute);">JOUEURS</Link>
      <span style="color: var(--mute-2);">/</span>
      <span class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--chalk-2);">
        {{ player.first_name?.toUpperCase() }} {{ player.last_name }}
      </span>
    </div>

    <!-- Hero identité -->
    <section style="display: grid; grid-template-columns: 280px 1fr 320px; border-bottom: 1px solid var(--line);">
      <div style="height: 360px; border-right: 1px solid var(--line); background: var(--ink-2);
                  display: flex; align-items: center; justify-content: center;">
        <div style="width: 180px; height: 180px; border-radius: 50%; background: var(--ink-4);
                    display: flex; align-items: center; justify-content: center;
                    font-family: var(--font-display-a); font-weight: 700; font-size: 64px; color: var(--chalk);">
          {{ initials }}
        </div>
      </div>

      <div style="padding: 36px 40px; border-right: 1px solid var(--line);">
        <div style="display: flex; gap: 10px; margin-bottom: 18px;">
          <Chip v-if="registration" variant="felt">POULE {{ registration.pool_name }} · {{ registration.pool_slot ? registration.pool_name + registration.pool_slot : '—' }}</Chip>
          <Chip v-if="qualified" variant="felt" style="display:inline-flex;align-items:center;gap:4px;"><Check :size="10" /> QUALIFIÉ</Chip>
          <Chip><GabonFlag :width="14" :height="10" /> {{ player.club?.city?.toUpperCase() ?? 'GABON' }}</Chip>
        </div>
        <h1 class="disp-a" style="font-size: 84px; line-height: 0.88;">
          {{ player.first_name }}<br />{{ player.last_name }}
        </h1>
        <div style="display: flex; gap: 32px; margin-top: 28px; flex-wrap: wrap;">
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">CLUB</div>
            <div style="font-size: 16px; margin-top: 6px; font-weight: 600;">{{ player.club?.name ?? '—' }}</div>
          </div>
          <div v-if="player.fgb_card">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">CARTE FGB</div>
            <div class="mono" style="font-size: 14px; margin-top: 6px;">{{ player.fgb_card }}</div>
          </div>
          <div v-if="player.cue">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">CUE</div>
            <div style="font-size: 16px; margin-top: 6px; font-weight: 600;">{{ player.cue }}</div>
          </div>
        </div>
      </div>

      <!-- KPI dans la compétition courante -->
      <div v-if="standing" style="display: flex; flex-direction: column;">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--line); flex: 1;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">RANG · POULE {{ registration?.pool_name }}</div>
          <div class="disp-a tnum" :style="{ fontSize: '64px', marginTop: '6px', color: qualified ? 'var(--felt-2)' : 'var(--chalk)' }">
            {{ standing.rank }}<span style="font-size: 24px; color: var(--mute);">/{{ totalPlayersInPool }}</span>
          </div>
        </div>
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--line); flex: 1; display: flex; gap: 16px;">
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">V</div>
            <div class="disp-a tnum" style="font-size: 32px; margin-top: 4px;">{{ standing.v }}</div>
          </div>
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">W</div>
            <div class="disp-a tnum" style="font-size: 32px; margin-top: 4px; color: var(--felt-2);">{{ standing.w }}</div>
          </div>
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">L</div>
            <div class="disp-a tnum" style="font-size: 32px; margin-top: 4px; color: var(--mute);">{{ standing.l }}</div>
          </div>
        </div>
        <div style="padding: 18px 22px; flex: 1;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">DIFFÉRENCE</div>
          <div class="disp-a tnum" :style="{ fontSize: '38px', marginTop: '6px',
                color: standing.diff > 0 ? 'var(--felt-2)' : standing.diff < 0 ? 'var(--live)' : 'var(--chalk-2)' }">
            {{ standing.diff > 0 ? '+' : '' }}{{ standing.diff }}
          </div>
        </div>
      </div>

      <!-- KPI alternatif si pas inscrit -->
      <div v-else style="padding: 36px 32px; display: flex; flex-direction: column; gap: 12px;">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ELO</div>
          <div class="disp-a tnum" style="font-size: 56px; margin-top: 6px;">{{ player.rating }}</div>
        </div>
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">VICTOIRES / DÉFAITES</div>
          <div class="disp-a tnum" style="font-size: 28px; margin-top: 6px;">
            <span style="color: var(--felt-2);">{{ player.wins }}</span> / <span style="color: var(--mute);">{{ player.losses }}</span>
          </div>
        </div>
        <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 8px;">
          Pas inscrit à {{ competition?.name }}.
        </div>
      </div>
    </section>

    <!-- Parcours dans la compétition -->
    <section v-if="journey?.length" style="padding: 32px 48px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 class="disp-a" style="font-size: 32px;">Parcours · {{ competition?.name }}</h2>
        <span class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.14em;">
          {{ journey.filter(j => j.status === 'done').length }}/{{ journey.length }} MATCHS JOUÉS
        </span>
      </div>

      <!-- Frise -->
      <div style="display: flex; gap: 6px; margin-bottom: 28px; flex-wrap: wrap;">
        <div v-for="m in journey" :key="m.id"
             :style="{
               width: '44px', height: '44px',
               background: m.win ? 'rgba(45,168,118,0.18)' : m.loss ? 'rgba(229,72,77,0.12)' : m.status === 'live' ? 'rgba(229,72,77,0.04)' : m.is_draw ? 'var(--ink-3)' : 'transparent',
               border: '1px solid ' + (m.win ? 'rgba(45,168,118,0.5)' : m.loss ? 'rgba(229,72,77,0.4)' : m.status === 'live' ? 'rgba(229,72,77,0.5)' : 'var(--line-strong)'),
               display: 'flex', alignItems: 'center', justifyContent: 'center', flexDirection: 'column',
               fontFamily: 'var(--font-mono)', fontSize: '11px', fontWeight: 700,
               color: m.win ? 'var(--felt-2)' : m.loss ? 'var(--live)' : m.status === 'live' ? 'var(--live)' : 'var(--mute)',
             }"
             :title="`${phaseLabel(m)} vs ${m.opponent?.name ?? '—'}`">
          <span v-if="m.status === 'done'">{{ m.my_score }}–{{ m.opp_score }}</span>
          <span v-else-if="m.status === 'live'" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;vertical-align:middle;"></span>
          <span v-else>·</span>
        </div>
      </div>

      <!-- Détail des matchs -->
      <table class="tbl">
        <thead>
          <tr>
            <th>Phase</th>
            <th>Adversaire</th>
            <th style="text-align: right;">Score</th>
            <th>Résultat</th>
            <th>Quand</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in journey" :key="m.id">
            <td>{{ phaseLabel(m) }}</td>
            <td style="font-weight: 600;">
              <Link v-if="m.opponent" :href="`/joueurs/${m.opponent.id}`">{{ m.opponent.name }}</Link>
              <span v-else style="color: var(--mute);">À déterminer</span>
            </td>
            <td class="mono tnum" style="text-align: right; font-weight: 700;">
              <template v-if="m.status === 'done' || m.status === 'live'">{{ m.my_score }} — {{ m.opp_score }}</template>
              <template v-else>—</template>
            </td>
            <td>
              <Chip v-if="m.status === 'live'" variant="live">EN COURS</Chip>
              <Chip v-else-if="m.win" variant="felt">VICTOIRE</Chip>
              <Chip v-else-if="m.loss">DÉFAITE</Chip>
              <span v-else-if="m.is_draw" class="mono" style="color: var(--mute);">NUL</span>
              <span v-else class="mono" style="color: var(--mute);">À VENIR</span>
            </td>
            <td class="mono" style="font-size: 11px; color: var(--mute);">
              <template v-if="m.scheduled_at">{{ fmtTime(m.scheduled_at) }}</template>
              <template v-else>—</template>
              <template v-if="m.table"> · {{ m.table }}</template>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Historique hors compétition courante -->
    <section v-if="history?.length" style="padding: 32px 48px;">
      <h3 class="disp-a" style="font-size: 24px; margin-bottom: 16px;">Historique précédent</h3>
      <table class="tbl">
        <thead>
          <tr>
            <th>Date</th>
            <th>Compétition</th>
            <th>Tour</th>
            <th>Adversaire</th>
            <th style="text-align: right;">Score</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in history" :key="m.id">
            <td class="mono" style="color: var(--mute);">{{ fmtDate(m.ended_at) }}</td>
            <td>{{ m.competition?.name }}</td>
            <td style="color: var(--mute);">{{ m.round }}</td>
            <td style="font-weight: 600;">
              {{ (m.player_a_id === player.id ? m.player_b : m.player_a)?.last_name }}
            </td>
            <td class="mono tnum" style="text-align: right;">{{ m.score_a }}–{{ m.score_b }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
