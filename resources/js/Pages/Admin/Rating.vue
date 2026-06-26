<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
  byDiscipline: Object,
  disciplines: Array,
});

const activeDiscipline = ref(props.disciplines?.[0] ?? null);

const rows = computed(() => {
  if (!activeDiscipline.value) return [];
  return props.byDiscipline[activeDiscipline.value] ?? [];
});

const fmtDate = (d) => {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const fmtDiscipline = (d) => (d ?? '').replace(/_/g, ' ').toUpperCase();
</script>

<template>
  <Head title="Classement Elo" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="rating" />
    <main style="flex: 1; display: flex; flex-direction: column; min-width: 0;">

      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ADMIN</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">Classement Elo</div>
        </div>
        <Link href="/admin" class="btn">← Tableau de bord</Link>
      </header>

      <!-- Discipline tabs -->
      <div v-if="disciplines.length" style="display: flex; gap: 0; border-bottom: 1px solid var(--line);
                                            padding: 0 32px; overflow-x: auto;">
        <button
          v-for="d in disciplines" :key="d"
          @click="activeDiscipline = d"
          :style="{
            padding: '12px 18px',
            background: 'transparent',
            border: 'none',
            borderBottom: activeDiscipline === d ? '2px solid var(--felt-2)' : '2px solid transparent',
            color: activeDiscipline === d ? 'var(--chalk)' : 'var(--mute)',
            cursor: 'pointer',
            fontFamily: 'var(--font-mono)',
            fontSize: '11px',
            letterSpacing: '0.16em',
            whiteSpace: 'nowrap',
          }"
        >
          {{ fmtDiscipline(d) }}
          <span style="margin-left: 6px; opacity: 0.6;">({{ (byDiscipline[d] ?? []).length }})</span>
        </button>
      </div>

      <!-- Empty state: no disciplines at all -->
      <div v-if="!disciplines.length"
           style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
                  gap: 12px; color: var(--mute); padding: 80px 32px;">
        <div class="disp-a" style="font-size: 32px; color: var(--mute-2);">—</div>
        <div class="mono" style="font-size: 11px; letter-spacing: 0.18em;">AUCUN CLASSEMENT DISPONIBLE</div>
        <div style="font-size: 13px; max-width: 380px; text-align: center; margin-top: 4px;">
          Les classements Elo sont générés automatiquement après chaque match joué.
        </div>
      </div>

      <!-- Table -->
      <div v-else style="flex: 1; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: var(--ink-2); border-bottom: 1px solid var(--line);">
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500; white-space: nowrap;">#</th>
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">JOUEUR</th>
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">CLUB</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">RATING</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">PARTIES</th>
              <th class="mono" style="padding: 10px 14px; text-align: right; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">V / D</th>
              <th class="mono" style="padding: 10px 14px; text-align: left; font-size: 10px; letter-spacing: 0.16em;
                                      color: var(--mute); font-weight: 500;">DERNIER MATCH</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="rows.length">
              <tr v-for="r in rows" :key="r.id"
                  style="border-bottom: 1px solid var(--line); transition: background 0.1s;"
                  onmouseover="this.style.background='var(--ink-2)'" onmouseout="this.style.background='transparent'">
                <td class="mono tnum" style="padding: 12px 14px; font-size: 12px; color: var(--mute); white-space: nowrap;">
                  {{ String(r.rank).padStart(2, '0') }}
                </td>
                <td style="padding: 12px 14px; white-space: nowrap;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 600;">
                      {{ r.player?.first_name }} {{ r.player?.last_name }}
                    </span>
                    <span v-if="r.provisional"
                          class="mono"
                          style="font-size: 9px; letter-spacing: 0.12em; color: var(--felt-2);
                                 border: 1px solid var(--felt-2); padding: 1px 5px; opacity: 0.85;">
                      P
                    </span>
                  </div>
                </td>
                <td style="padding: 12px 14px; font-size: 12px; color: var(--mute); white-space: nowrap;">
                  {{ r.player?.club?.name ?? '—' }}
                </td>
                <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 18px;
                                            font-weight: 700; color: var(--chalk); white-space: nowrap;">
                  {{ r.rating ?? '—' }}
                </td>
                <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                            color: var(--mute); white-space: nowrap;">
                  {{ r.games_played ?? 0 }}
                </td>
                <td class="mono tnum" style="padding: 12px 14px; text-align: right; font-size: 12px;
                                            white-space: nowrap;">
                  <span style="color: var(--felt-2);">{{ r.frames_won ?? 0 }}</span>
                  <span style="color: var(--mute);"> / </span>
                  <span style="color: var(--mute);">{{ r.frames_lost ?? 0 }}</span>
                </td>
                <td class="mono tnum" style="padding: 12px 14px; font-size: 11px; color: var(--mute); white-space: nowrap;">
                  {{ fmtDate(r.last_match_at) }}
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="7" style="padding: 60px 14px; text-align: center; color: var(--mute);">
                <div class="disp-a" style="font-size: 20px; margin-bottom: 8px;">—</div>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em;">
                  AUCUN JOUEUR CLASSÉ DANS CETTE DISCIPLINE
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
  header > a {
    width: 100%;
    justify-content: center;
  }
}
</style>
