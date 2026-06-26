<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
  byDiscipline: Object,
  disciplines: Array,
});

const active = ref(props.disciplines?.[0] ?? null);

const rows = computed(() => {
  if (!active.value) return [];
  return props.byDiscipline[active.value] ?? [];
});

const fmtDiscipline = (d) =>
  ({ '8-ball': '8-Ball', '9-ball': '9-Ball', 'straight-pool': 'Straight Pool' })[d] ?? d.replace(/_/g, ' ').toUpperCase();

const fmtDate = (d) => d
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  : '—';

const winRate = (r) => {
  const total = r.frames_won + r.frames_lost;
  if (!total) return '—';
  return Math.round((r.frames_won / total) * 100) + '%';
};

const medals = ['🥇', '🥈', '🥉'];
</script>

<template>
  <Head title="Classement Elo">
    <meta name="description" content="Classement Elo des joueurs Club 8 Pool — 8-ball et 9-ball, Libreville Gabon." head-key="description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh; display: flex; flex-direction: column;">
    <PublicNav />

    <!-- Hero -->
    <section style="padding: 32px 0; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">CLASSEMENT ELO</div>
        <h1 class="disp-a" style="font-size: clamp(40px, 9vw, 80px); margin-top: 14px; line-height: 0.92;">
          Joueurs<br /><span style="color: var(--mute);">classés</span>
        </h1>
        <p style="font-size: 13px; color: var(--mute); max-width: 560px; margin-top: 16px; line-height: 1.7;">
          Le classement Elo est mis à jour après chaque match officiel. Un joueur devient confirmé après 10 matchs joués. Plus le nombre est élevé, plus le joueur est fort.
        </p>
      </div>
    </section>

    <!-- Discipline tabs -->
    <div v-if="disciplines.length > 1"
         style="border-bottom: 1px solid var(--line); overflow-x: auto;">
      <div class="container" style="display: flex;">
        <button
          v-for="d in disciplines" :key="d"
          @click="active = d"
          :style="{
            padding: '12px 18px', background: 'transparent', border: 'none',
            borderBottom: active === d ? '2px solid var(--felt-2)' : '2px solid transparent',
            color: active === d ? 'var(--chalk)' : 'var(--mute)',
            cursor: 'pointer', fontFamily: 'var(--font-mono)',
            fontSize: '11px', letterSpacing: '0.16em', whiteSpace: 'nowrap',
          }"
        >{{ fmtDiscipline(d) }}</button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!rows.length"
         style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--mute); padding: 80px 24px;">
      <div style="text-align: center;">
        <div class="disp-a" style="font-size: 28px; color: var(--mute-2);">—</div>
        <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; margin-top: 10px;">AUCUN JOUEUR CLASSÉ</div>
        <p style="font-size: 13px; margin-top: 10px; max-width: 360px; line-height: 1.6;">
          Le classement sera disponible après les premières compétitions.
        </p>
      </div>
    </div>

    <!-- Ranking table -->
    <div v-else style="flex: 1; overflow-x: auto; padding: 24px 0;">
      <div class="container">

        <!-- Top 3 cards -->
        <div v-if="rows.length >= 3"
             style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px;">
          <div
            v-for="(r, i) in rows.slice(0, 3)" :key="r.player_id"
            :style="{
              border: '1px solid ' + (i === 0 ? 'var(--felt-2)' : 'var(--line)'),
              background: i === 0 ? 'rgba(46,125,94,0.08)' : 'var(--ink-2)',
              padding: '20px', textAlign: 'center',
            }"
          >
            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">{{ medals[i] }}</div>
            <div style="font-size: 13px; font-weight: 700; color: var(--chalk); line-height: 1.2;">
              {{ r.first_name }} {{ r.last_name }}
            </div>
            <div style="font-size: 11px; color: var(--mute); margin-top: 4px;">{{ r.club ?? '—' }}</div>
            <div class="disp-a tnum"
                 :style="{ fontSize: '32px', marginTop: '10px', color: i === 0 ? 'var(--felt-2)' : 'var(--chalk)' }">
              {{ r.rating }}
            </div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.16em; color: var(--mute); margin-top: 2px;">ELO</div>
            <div v-if="r.provisional"
                 class="mono"
                 style="font-size: 9px; letter-spacing: 0.12em; color: #e5c048; border: 1px solid #e5c048;
                        padding: 2px 6px; margin-top: 8px; display: inline-block;">PROVISOIRE</div>
          </div>
        </div>

        <!-- Full table -->
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: var(--ink-2); border-bottom: 1px solid var(--line);">
              <th class="mono" style="padding: 9px 12px; text-align: center; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500; width: 44px;">#</th>
              <th class="mono" style="padding: 9px 12px; text-align: left; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500;">JOUEUR</th>
              <th class="mono" style="padding: 9px 12px; text-align: left; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500;">CLUB</th>
              <th class="mono" style="padding: 9px 12px; text-align: right; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500;">ELO</th>
              <th class="mono" style="padding: 9px 12px; text-align: right; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500; white-space: nowrap;">MATCHS</th>
              <th class="mono" style="padding: 9px 12px; text-align: right; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500; white-space: nowrap;">% FRAMES</th>
              <th class="mono" style="padding: 9px 12px; text-align: center; font-size: 9px; letter-spacing: 0.16em; color: var(--mute); font-weight: 500; white-space: nowrap;">DERNIER</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="r in rows" :key="r.player_id"
              style="border-bottom: 1px solid var(--line);"
              onmouseover="this.style.filter='brightness(1.06)'" onmouseout="this.style.filter='none'"
            >
              <td class="tnum" style="padding: 11px 12px; text-align: center; font-size: 13px; font-weight: 700;"
                  :style="{ color: r.rank <= 3 ? 'var(--felt-2)' : 'var(--mute)' }">
                {{ r.rank }}
              </td>
              <td style="padding: 11px 12px;">
                <Link :href="`/joueurs/${r.player_id}`" style="text-decoration: none;">
                  <div style="font-size: 13px; font-weight: 600; color: var(--chalk);">
                    {{ r.first_name }} {{ r.last_name }}
                  </div>
                  <div v-if="r.provisional" class="mono"
                       style="font-size: 9px; color: #e5c048; letter-spacing: 0.1em; margin-top: 2px;">PROVISOIRE</div>
                </Link>
              </td>
              <td style="padding: 11px 12px; font-size: 12px; color: var(--mute);">{{ r.club ?? '—' }}</td>
              <td class="mono tnum" style="padding: 11px 12px; text-align: right; font-size: 16px; font-weight: 700; color: var(--chalk);">
                {{ r.rating }}
              </td>
              <td class="mono tnum" style="padding: 11px 12px; text-align: right; font-size: 12px; color: var(--mute);">
                {{ r.games_played }}
              </td>
              <td class="mono tnum" style="padding: 11px 12px; text-align: right; font-size: 12px; color: var(--mute);">
                {{ winRate(r) }}
              </td>
              <td class="mono" style="padding: 11px 12px; text-align: center; font-size: 11px; color: var(--mute);">
                {{ fmtDate(r.last_match_at) }}
              </td>
            </tr>
          </tbody>
        </table>

        <div style="padding: 12px 0; font-size: 11px; color: var(--mute); border-top: 1px solid var(--line); margin-top: -1px;">
          {{ rows.length }} joueur{{ rows.length > 1 ? 's' : '' }} classé{{ rows.length > 1 ? 's' : '' }}
          · Système Elo · Mis à jour après chaque match officiel
        </div>
      </div>
    </div>

    <PublicFooter />
  </div>
</template>

<style scoped>
@media (max-width: 640px) {
  div[style*="grid-template-columns: repeat(3, 1fr)"] {
    grid-template-columns: 1fr !important;
  }
  th:nth-child(n+5), td:nth-child(n+5) { display: none; }
}
</style>
