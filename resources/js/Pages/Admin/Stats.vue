<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
  competition: Object,
  statistics: Array,
  overview: { type: Object, default: () => ({}) },
  leaders: { type: Object, default: () => ({}) },
  active: { type: String, default: 'comps' },
});

const recalculating = ref(false);

const leaderBoards = [
  { key: 'frames_won',     title: 'Manches gagnées', unit: '' },
  { key: 'matches_won',    title: 'Matchs gagnés',   unit: '' },
  { key: 'break_and_runs', title: 'Break & runs',    unit: '' },
];
const medals = ['🥇', '🥈', '🥉'];

const recalculate = () => {
  if (recalculating.value) return;
  recalculating.value = true;
  const url = props.active === 'stats'
    ? '/admin/statistiques/recalculer'
    : `/admin/competitions/${props.competition.id}/stats/recalculate`;
  router.post(url, {}, { onFinish: () => { recalculating.value = false; } });
};

const winRate = (s) => {
  if (!s.matches_played) return '—';
  return Math.round((s.matches_won / s.matches_played) * 100) + '%';
};
</script>

<template>
  <Head :title="`Stats · ${competition.name}`" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar :active="active" />
    <main style="flex: 1; display: flex; flex-direction: column; min-width: 0;">

      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">STATISTIQUES · COMPÉTITION</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">{{ competition.name }}</div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <Link :href="`/admin/competitions/${competition.id}`" class="btn">← Compétition</Link>
          <a :href="`/admin/competitions/${competition.id}/rapport`" target="_blank" rel="noopener" class="btn">Rapport ↗</a>
          <button
            class="btn btn-felt"
            :disabled="recalculating"
            @click="recalculate"
            style="cursor: pointer;"
          >
            {{ recalculating ? 'Recalcul…' : '⟳ Recalculer' }}
          </button>
        </div>
      </header>

      <!-- ── Synthèse de la compétition ── -->
      <section v-if="overview && overview.matches_total"
               style="padding: 24px 32px; border-bottom: 1px solid var(--line);">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 14px;">
          SYNTHÈSE
        </div>
        <div class="kpi-grid">
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.players }}</div>
            <div class="kpi-lbl">Joueurs</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.matches_done }}<span class="kpi-sub">/{{ overview.matches_total }}</span></div>
            <div class="kpi-lbl">Matchs joués</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.frames_total }}</div>
            <div class="kpi-lbl">Manches jouées</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.frames_avg }}</div>
            <div class="kpi-lbl">Manches / match</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.avg_duration ?? '—' }}<span v-if="overview.avg_duration" class="kpi-sub">min</span></div>
            <div class="kpi-lbl">Durée moy.</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val">{{ overview.total_hours ?? '—' }}<span v-if="overview.total_hours" class="kpi-sub">h</span></div>
            <div class="kpi-lbl">Temps de jeu</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val" style="color: var(--felt-2);">{{ overview.break_and_runs }}</div>
            <div class="kpi-lbl">Break & runs</div>
          </div>
          <div class="kpi">
            <div class="disp-a tnum kpi-val" style="color: var(--live);">{{ overview.matches_live }}</div>
            <div class="kpi-lbl">En direct</div>
          </div>
        </div>

        <!-- Faits marquants + classements -->
        <div class="synth-grid" style="margin-top: 18px;">
          <!-- Leaderboards -->
          <div v-for="b in leaderBoards" :key="b.key" class="lead-card">
            <div class="mono lead-head">{{ b.title }}</div>
            <template v-if="leaders[b.key] && leaders[b.key].length">
              <div v-for="(r, i) in leaders[b.key]" :key="i" class="lead-row">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                  <span style="font-size: 13px;">{{ medals[i] }}</span>
                  <div style="min-width: 0;">
                    <div style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ r.name }}</div>
                    <div v-if="r.club" class="mono" style="font-size: 9px; color: var(--mute);">{{ r.club }}</div>
                  </div>
                </div>
                <div class="disp-a tnum" style="font-size: 18px; color: var(--felt-2);">{{ r.value }}</div>
              </div>
            </template>
            <div v-else class="lead-empty">Aucune donnée</div>
          </div>
        </div>

        <!-- Records -->
        <div v-if="overview.longest_match || overview.widest_match"
             style="display: flex; gap: 14px; flex-wrap: wrap; margin-top: 14px;">
          <div v-if="overview.longest_match" class="record-card">
            <div class="mono record-lbl">⏱ MATCH LE PLUS LONG</div>
            <div class="record-main">{{ overview.longest_match.players }}</div>
            <div class="mono record-sub">{{ overview.longest_match.score }} · {{ overview.longest_match.duration }}</div>
          </div>
          <div v-if="overview.widest_match" class="record-card">
            <div class="mono record-lbl">▲ ÉCART LE PLUS LARGE</div>
            <div class="record-main">{{ overview.widest_match.players }}</div>
            <div class="mono record-sub">{{ overview.widest_match.score }} · +{{ overview.widest_match.gap }} manches</div>
          </div>
        </div>
      </section>

      <!-- Empty state -->
      <div v-if="!statistics.length"
           style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
                  gap: 12px; color: var(--mute); padding: 80px 32px;">
        <div class="disp-a" style="font-size: 32px; color: var(--mute-2);">—</div>
        <div class="mono" style="font-size: 11px; letter-spacing: 0.18em;">AUCUNE STATISTIQUE DISPONIBLE</div>
        <div style="font-size: 13px; max-width: 380px; text-align: center; margin-top: 4px;">
          Les statistiques sont générées après la fin de chaque match.
          Cliquez sur Recalculer pour forcer un recalcul.
        </div>
        <button class="btn btn-felt" @click="recalculate" :disabled="recalculating"
                style="margin-top: 16px; cursor: pointer;">
          {{ recalculating ? 'Recalcul en cours…' : '⟳ Recalculer les statistiques' }}
        </button>
      </div>

      <!-- Stats table -->
      <div v-else style="flex: 1; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: var(--ink-2); border-bottom: 1px solid var(--line);">
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">JOUEUR</th>
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">CLUB</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500; white-space: nowrap;">FRAMES V/D</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500; white-space: nowrap;">MATCHS V/D</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">% V</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">FAUTES</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">SÉCURITÉS</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500; white-space: nowrap;">B&R</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">MISS</th>
              <th class="mono" style="padding: 10px 14px; text-align: center; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">ÉTAT</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in statistics" :key="s.id"
                :style="{
                  borderBottom: '1px solid var(--line)',
                  background: s.is_stale ? 'rgba(229,192,72,0.03)' : 'transparent',
                }"
                onmouseover="this.style.filter='brightness(1.06)'" onmouseout="this.style.filter='none'">

              <td style="padding: 12px 14px; white-space: nowrap;">
                <div style="font-size: 13px; font-weight: 600;">
                  {{ s.player?.first_name }} {{ s.player?.last_name }}
                </div>
              </td>

              <td style="padding: 12px 14px; font-size: 12px; color: var(--mute); white-space: nowrap;">
                {{ s.player?.club?.name ?? '—' }}
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px; white-space: nowrap;">
                <span style="color: var(--felt-2);">{{ s.frames_won ?? 0 }}</span>
                <span style="color: var(--mute);"> / </span>
                <span style="color: var(--mute);">{{ s.frames_lost ?? 0 }}</span>
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px; white-space: nowrap;">
                <span style="color: var(--chalk);">{{ s.matches_won ?? 0 }}</span>
                <span style="color: var(--mute);"> / </span>
                <span style="color: var(--mute);">{{ (s.matches_played ?? 0) - (s.matches_won ?? 0) }}</span>
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px; color: var(--mute);">
                {{ winRate(s) }}
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                          color: var(--mute);">
                {{ s.fouls ?? 0 }}
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                          color: var(--mute);">
                {{ s.safeties ?? 0 }}
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                          color: var(--felt-2);">
                {{ s.break_and_runs ?? 0 }}
              </td>

              <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                          color: var(--mute);">
                {{ s.misses ?? 0 }}
              </td>

              <td style="padding: 12px 14px; text-align: center;">
                <span v-if="s.is_stale"
                      class="mono"
                      style="font-size: 9px; letter-spacing: 0.12em; color: #e5c048;
                             border: 1px solid #e5c048; padding: 2px 6px; opacity: 0.85;">
                  STALE
                </span>
                <span v-else style="color: var(--felt-2); font-size: 14px;">✓</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Summary bar -->
      <div v-if="statistics.length"
           style="padding: 14px 32px; border-top: 1px solid var(--line); background: var(--ink-2);
                  display: flex; gap: 32px; align-items: center;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.16em; color: var(--mute);">
          {{ statistics.length }} JOUEUR{{ statistics.length > 1 ? 'S' : '' }}
        </div>
        <div v-if="statistics.some(s => s.is_stale)"
             class="mono" style="font-size: 10px; letter-spacing: 0.14em; color: #e5c048;">
          {{ statistics.filter(s => s.is_stale).length }} LIGNE(S) STALE — RECALCUL RECOMMANDÉ
        </div>
      </div>

    </main>
  </div>
</template>

<style scoped>
/* KPI grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 10px;
}
.kpi {
  border: 1px solid var(--line);
  background: var(--ink-2);
  padding: 12px 14px;
}
.kpi-val { font-size: 26px; line-height: 1; }
.kpi-sub { font-size: 13px; color: var(--mute); margin-left: 2px; }
.kpi-lbl {
  font-family: var(--font-mono);
  font-size: 8px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--mute);
  margin-top: 8px;
}

/* Leaderboards */
.synth-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
.lead-card { border: 1px solid var(--line); background: var(--ink-2); }
.lead-head {
  font-size: 9px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--felt-2);
  padding: 8px 12px;
  border-bottom: 1px solid var(--line);
}
.lead-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 12px;
  border-bottom: 1px solid var(--line);
}
.lead-row:last-child { border-bottom: none; }
.lead-empty {
  padding: 14px 12px;
  font-size: 11px;
  color: var(--mute);
  text-align: center;
}

/* Records */
.record-card {
  flex: 1;
  min-width: 220px;
  border: 1px solid var(--line);
  background: var(--ink-2);
  padding: 12px 16px;
}
.record-lbl { font-size: 8px; letter-spacing: 0.16em; color: var(--mute); }
.record-main { font-size: 14px; font-weight: 600; margin-top: 6px; }
.record-sub { font-size: 10px; color: var(--felt-2); margin-top: 4px; letter-spacing: 0.06em; }

@media (max-width: 1100px) {
  .kpi-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 768px) {
  header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 12px !important;
  }
  header > div:last-child {
    width: 100%;
    flex-wrap: wrap;
  }
  header .btn {
    flex: 1;
    justify-content: center;
  }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .synth-grid { grid-template-columns: 1fr; }
}
</style>
