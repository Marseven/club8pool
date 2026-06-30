<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  player: { type: Object, required: true },
  competition: { type: Object, required: true },
  journey: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Stage labels ──────────────────────────────────────────────────────────────
const STAGE_LABELS = {
  champion: 'CHAMPION',
  finalist: 'FINALISTE',
  runner_up: 'FINALISTE (2ÈME)',
  pool_stage: 'PHASE DE POULES',
  qualified_to_knockout: 'QUALIFIÉ',
  eliminated_in_pool: 'ÉLIMINÉ — POULES',
  third_place: '3ÈME PLACE',
  third_place_match: 'MATCH POUR LA 3ÈME',
  knockout_sf: 'DEMI-FINALE',
  eliminated_sf: 'ÉLIMINÉ — SF',
  knockout_qf: 'QUART DE FINALE',
  eliminated_qf: 'ÉLIMINÉ — QF',
  knockout_r16: 'HUITIÈME',
  eliminated_r16: 'ÉLIMINÉ — HDF',
  knockout_r32: '32ÈME',
  eliminated_r32: 'ÉLIMINÉ — R32',
  registered: 'INSCRIT',
  waiting_pool_assignment: 'EN ATTENTE DE TIRAGE',
};

const ROUND_LABELS = {
  R32: '32ème',
  R16: 'Huitièmes',
  QF: 'Quarts',
  SF: 'Demi-finales',
  '3P': '3ème place',
  F: 'Finale',
};

const stageLabel = computed(() => STAGE_LABELS[props.journey.stage] ?? props.journey.stage?.toUpperCase() ?? '—');

// Chip variant for stage
const stageChipVariant = computed(() => {
  const s = props.journey.stage ?? '';
  if (s === 'champion' || s === 'finalist' || s === 'runner_up' || s === 'qualified_to_knockout' || s === 'third_place') {
    return 'felt';
  }
  return '';
});

const stageIsEliminated = computed(() => (props.journey.stage ?? '').startsWith('eliminated'));

function roundLabel(round) {
  return ROUND_LABELS[round] ?? round ?? '—';
}

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}

function resultVariant(result, isDraw) {
  if (isDraw) return '';
  if (result === 'win') return 'felt';
  return '';
}

function resultLabel(result, isDraw) {
  if (isDraw) return 'NUL';
  if (result === 'win') return 'VICTOIRE';
  if (result === 'loss') return 'DÉFAITE';
  return '—';
}

const hasPoolMatches = computed(() => (props.journey.pool_matches ?? []).length > 0);
const hasKoMatches = computed(() => (props.journey.ko_matches ?? []).length > 0);
</script>

<template>
  <Head :title="`${competition.name} · Espace Joueur`" />
  <PublicNav />

  <div style="background: var(--ink); min-height: 100vh; padding-bottom: 64px; color: var(--chalk);">
    <div class="container" style="padding-top: 40px;">

      <!-- Back link -->
      <Link href="/joueur/dashboard"
            style="display: inline-flex; align-items: center; gap: 6px; color: var(--mute); font-size: 13px;
                   text-decoration: none; margin-bottom: 28px;">
        ← Tableau de bord
      </Link>

      <!-- Flash messages -->
      <div v-if="flash.success"
           style="background: rgba(31,138,91,0.15); border: 1px solid rgba(31,138,91,0.35); color: var(--felt-2);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.success }}
      </div>
      <div v-if="flash.error"
           style="background: rgba(229,72,77,0.1); border: 1px solid rgba(229,72,77,0.3); color: var(--live);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.error }}
      </div>

      <!-- ─── HEADER ─── -->
      <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 12px;
                  padding: 32px; margin-bottom: 32px;">
        <div style="display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px;">
          <div>
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; text-transform: uppercase; margin-bottom: 8px;">
              Compétition
            </div>
            <h1 class="disp-a" style="font-size: 32px; margin: 0 0 10px; line-height: 1;">{{ competition.name }}</h1>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
              <Chip>{{ competition.status?.toUpperCase() }}</Chip>
              <span class="mono" style="font-size: 11px; color: var(--mute);">{{ player.name }}</span>
            </div>
          </div>
          <div v-if="competition.race_to" style="text-align: right;">
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Race to</div>
            <div class="disp-a" style="font-size: 28px; color: var(--chalk-2);">{{ competition.race_to }}</div>
          </div>
        </div>
      </div>

      <!-- ─── MON PARCOURS ─── -->
      <section style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">MON PARCOURS</h2>

        <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px; padding: 24px 32px;">
          <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: center;">

            <!-- Stage chip -->
            <div>
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 8px;">Statut</div>
              <Chip
                :variant="stageChipVariant"
                :style="stageIsEliminated ? { color: 'var(--live)' } : {}"
              >
                {{ stageLabel }}
              </Chip>
            </div>

            <!-- Pool info -->
            <div v-if="journey.registration?.pool">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Poule</div>
              <div style="font-weight: 600;">{{ journey.registration.pool.name }}</div>
            </div>

            <!-- Seed -->
            <div v-if="journey.registration?.seed">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Tête de série</div>
              <div class="disp-a" style="font-size: 18px;">#{{ journey.registration.seed }}</div>
            </div>

            <!-- Pool rank -->
            <div v-if="journey.pool_rank != null">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Classement poule</div>
              <div class="disp-a" style="font-size: 18px;">{{ journey.pool_rank }}</div>
            </div>

            <!-- Pool record -->
            <div v-if="hasPoolMatches && journey.pool_record">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Bilan poule</div>
              <div class="mono" style="font-size: 14px;">
                <span style="color: var(--felt-2);">{{ journey.pool_record.w }}V</span>
                &nbsp;{{ journey.pool_record.l }}D
                &nbsp;{{ journey.pool_record.d }}N
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- ─── MATCHS DE POULE ─── -->
      <section style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">MATCHS DE POULE</h2>

        <div v-if="!hasPoolMatches"
             style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px;
                    padding: 24px; color: var(--mute); font-size: 14px;">
          Aucun match de poule.
        </div>

        <div v-else style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px; overflow: hidden;">
          <div
            v-for="(m, i) in journey.pool_matches"
            :key="m.id"
            :style="{
              display: 'flex', alignItems: 'center', gap: '16px',
              padding: '14px 24px',
              borderTop: i > 0 ? '1px solid var(--line)' : 'none',
              flexWrap: 'wrap',
            }"
          >
            <!-- Opponent -->
            <div style="flex: 1; min-width: 100px;">
              <div style="font-size: 14px; font-weight: 600;">{{ m.opponent?.name ?? '—' }}</div>
            </div>
            <!-- Score -->
            <div class="mono" style="font-size: 16px; font-weight: 700; letter-spacing: 0.05em; min-width: 60px; text-align: center;">
              {{ m.my_score ?? '—' }} — {{ m.op_score ?? '—' }}
            </div>
            <!-- Result chip -->
            <div style="min-width: 90px; text-align: center;">
              <Chip
                :variant="resultVariant(m.result, m.is_draw)"
                :style="m.result === 'loss' ? { color: 'var(--live)' } : {}"
              >
                {{ resultLabel(m.result, m.is_draw) }}
              </Chip>
            </div>
            <!-- Status -->
            <div class="mono" style="font-size: 10px; color: var(--mute); text-transform: uppercase; letter-spacing: 0.08em;">
              {{ m.status ?? '' }}
            </div>
          </div>
        </div>
      </section>

      <!-- ─── PHASE FINALE ─── -->
      <section v-if="hasKoMatches" style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">PHASE FINALE</h2>

        <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px; overflow: hidden;">
          <div
            v-for="(m, i) in journey.ko_matches"
            :key="m.id"
            :style="{
              display: 'flex', alignItems: 'center', gap: '16px',
              padding: '14px 24px',
              borderTop: i > 0 ? '1px solid var(--line)' : 'none',
              flexWrap: 'wrap',
            }"
          >
            <!-- Round -->
            <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.1em; text-transform: uppercase; min-width: 80px;">
              {{ roundLabel(m.round) }}
            </div>
            <!-- Opponent -->
            <div style="flex: 1; min-width: 100px; font-size: 14px; font-weight: 600;">
              {{ m.opponent?.name ?? '—' }}
            </div>
            <!-- Score -->
            <div class="mono" style="font-size: 16px; font-weight: 700; letter-spacing: 0.05em; min-width: 60px; text-align: center;">
              {{ m.my_score ?? '—' }} — {{ m.op_score ?? '—' }}
            </div>
            <!-- Result chip -->
            <div style="min-width: 90px; text-align: center;">
              <Chip
                :variant="resultVariant(m.result, m.is_draw)"
                :style="m.result === 'loss' ? { color: 'var(--live)' } : {}"
              >
                {{ resultLabel(m.result, m.is_draw) }}
              </Chip>
            </div>
          </div>
        </div>
      </section>

      <!-- ─── PROCHAIN MATCH ─── -->
      <section v-if="journey.next_match" style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">PROCHAIN MATCH</h2>

        <div style="background: var(--ink-3); border: 1px solid var(--line-strong); border-radius: 10px; padding: 24px 32px;">
          <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: center;">
            <div>
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Adversaire</div>
              <div style="font-size: 18px; font-weight: 600;">{{ journey.next_match.opponent?.name ?? '—' }}</div>
            </div>
            <div v-if="journey.next_match.table">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Table</div>
              <div style="font-size: 18px; font-weight: 600;">{{ journey.next_match.table }}</div>
            </div>
            <div v-if="journey.next_match.round">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Tour</div>
              <div class="mono" style="font-size: 14px;">{{ roundLabel(journey.next_match.round) }}</div>
            </div>
            <div v-if="journey.next_match.scheduled_at" style="margin-left: auto; text-align: right;">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Heure prévue</div>
              <div class="mono" style="font-size: 14px; color: var(--chalk-2);">{{ formatDate(journey.next_match.scheduled_at) }}</div>
            </div>
          </div>
        </div>
      </section>

    </div><!-- .container -->
  </div>
</template>
