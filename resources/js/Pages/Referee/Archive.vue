<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ matches: Array });

const fmtDate = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  const months = ['JAN','FÉV','MAR','AVR','MAI','JUN','JUL','AOÛ','SEP','OCT','NOV','DÉC'];
  return `${String(d.getUTCDate()).padStart(2,'0')} ${months[d.getUTCMonth()]}`;
};

const fmtTime = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return `${String(d.getUTCHours()).padStart(2,'0')}h${String(d.getUTCMinutes()).padStart(2,'0')}`;
};

const fmtDuration = (s) => {
  if (!s) return null;
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${m}m${String(sec).padStart(2,'0')}s`;
};

const phaseLabel = (m) => {
  if (m.pool) return `Poule ${m.pool.name}`;
  const map = { R32: '1/32e', R16: '1/16e', QF: 'Quart', SF: 'Demi', F: 'Finale', '3P': '3e place' };
  return map[m.round] ?? m.round ?? '—';
};

const winner = (m) => {
  if (m.score_a > m.score_b) return `${m.player_a?.first_name} ${m.player_a?.last_name}`;
  if (m.score_b > m.score_a) return `${m.player_b?.first_name} ${m.player_b?.last_name}`;
  return null;
};
</script>

<template>
  <Head title="Mes matchs archivés" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">

    <!-- En-tête -->
    <header style="padding: 12px 22px 14px; border-bottom: 1px solid var(--line);
                   display: flex; align-items: center; gap: 14px;">
      <Link href="/arbitre" style="color: var(--mute); font-size: 18px; line-height: 1;">←</Link>
      <div>
        <div style="font-size: 15px; font-weight: 700;">Matchs archivés</div>
        <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; margin-top: 1px;">
          {{ matches?.length ?? 0 }} MATCH{{ (matches?.length ?? 0) !== 1 ? 'S' : '' }}
        </div>
      </div>
    </header>

    <!-- Liste vide -->
    <div v-if="!matches?.length"
         style="flex: 1; display: flex; flex-direction: column; align-items: center;
                justify-content: center; text-align: center; padding: 40px 32px; color: var(--mute);">
      <div class="disp-a" style="font-size: 22px; margin-bottom: 12px;">AUCUN MATCH</div>
      <p style="font-size: 12px; line-height: 1.6;">
        Vous n'avez pas encore clôturé de match.
      </p>
    </div>

    <!-- Liste des matchs terminés -->
    <div v-else style="flex: 1; padding: 12px 16px 24px; overflow-y: auto;">
      <div v-for="m in matches" :key="m.id" :style="{
        border: '1px solid var(--line)',
        background: 'var(--ink-2)',
        borderRadius: '3px',
        padding: '14px',
        marginBottom: '8px',
      }">
        <!-- Ligne supérieure : date + phase + table -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.12em;">
            {{ fmtDate(m.ended_at) }} · {{ fmtTime(m.ended_at) }}
          </span>
          <span class="mono" style="font-size: 9px; padding: 2px 8px; border-radius: 2px;
                background: rgba(255,255,255,0.06); color: var(--chalk-2); letter-spacing: 0.12em;">
            {{ phaseLabel(m) }} · {{ m.table?.name?.toUpperCase() ?? '—' }}
          </span>
        </div>

        <!-- Joueurs et score -->
        <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px;">
          <div>
            <div :style="{
              fontSize: '13px', fontWeight: 700,
              color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk)',
            }">{{ m.player_a?.first_name }} {{ m.player_a?.last_name }}</div>
            <div class="disp-a tnum" :style="{
              fontSize: '42px', lineHeight: 0.9,
              color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)',
            }">{{ m.score_a }}</div>
          </div>
          <div style="font-family: var(--font-display-a); font-size: 22px; color: var(--mute-2); padding-bottom: 4px;">—</div>
          <div style="text-align: right;">
            <div :style="{
              fontSize: '13px', fontWeight: 700,
              color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk)',
            }">{{ m.player_b?.first_name }} {{ m.player_b?.last_name }}</div>
            <div class="disp-a tnum" :style="{
              fontSize: '42px', lineHeight: 0.9,
              color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk-2)',
            }">{{ m.score_b }}</div>
          </div>
        </div>

        <!-- Pied : compétition + durée -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;
                    padding-top: 8px; border-top: 1px solid var(--line);">
          <span style="font-size: 11px; color: var(--chalk-2);">
            {{ m.competition?.name?.split(' — ')[0] ?? '—' }}
          </span>
          <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.1em;">
            {{ fmtDuration(m.duration_seconds) ?? '—' }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>
