<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
  competition: Object,
  statistics: Array,
  active: { type: String, default: 'comps' },
});

const recalculating = ref(false);

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
}
</style>
