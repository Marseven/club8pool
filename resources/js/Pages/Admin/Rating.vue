<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
  byDiscipline: Object,
  disciplines: Array,
});

const activeDiscipline = ref(props.disciplines?.[0] ?? null);
const confirmAction    = ref(null); // 'reset' | 'recalculate' | null
const processing       = ref(false);

const rows = computed(() => {
  if (!activeDiscipline.value) return [];
  return props.byDiscipline[activeDiscipline.value] ?? [];
});

const fmtDate = (d) => {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const fmtDiscipline = (d) => (d ?? '').replace(/_/g, ' ').toUpperCase();

const totalPlayers = computed(() =>
  Object.values(props.byDiscipline ?? {}).reduce((s, arr) => s + arr.length, 0)
);

function doConfirm() {
  processing.value = true;
  router.post(`/admin/classement/${confirmAction.value}`, {}, {
    onFinish: () => { processing.value = false; confirmAction.value = null; },
  });
}

const confirmLabels = {
  reset:       { title: 'Réinitialiser le classement ELO ?', text: 'Toutes les notes ELO et l\'historique de calcul seront supprimés. Les matchs ne sont pas effacés.', btn: 'Réinitialiser' },
  recalculate: { title: 'Recalculer le classement ELO ?',    text: 'Le classement sera entièrement recalculé depuis les matchs terminés. Cette opération peut prendre quelques secondes.', btn: 'Recalculer' },
};
</script>

<template>
  <Head title="Classement Elo" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="rating" />
    <main style="flex: 1; display: flex; flex-direction: column; min-width: 0;">

      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line); flex-wrap: wrap; gap: 12px;">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ADMIN</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">
            Classement Elo
            <span class="mono" style="font-size: 13px; color: var(--mute); margin-left: 10px;">{{ totalPlayers }} joueurs</span>
          </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <button class="btn btn-outline-warn" @click="confirmAction = 'recalculate'">↻ Recalculer</button>
          <button class="btn btn-outline-danger" @click="confirmAction = 'reset'">⊘ Réinitialiser</button>
          <Link href="/admin" class="btn">← Tableau de bord</Link>
        </div>
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

  <!-- Confirmation modal -->
  <Teleport to="body">
    <div v-if="confirmAction" class="modal-backdrop" @click.self="confirmAction = null">
      <div class="modal-box">
        <div class="disp-a" style="font-size: 20px; margin-bottom: 12px;">
          {{ confirmLabels[confirmAction].title }}
        </div>
        <p style="color: var(--mute); margin-bottom: 24px; font-size: 14px; line-height: 1.6;">
          {{ confirmLabels[confirmAction].text }}
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
          <button class="btn btn-ghost" :disabled="processing" @click="confirmAction = null">Annuler</button>
          <button class="btn btn-danger" :disabled="processing" @click="doConfirm">
            {{ processing ? '...' : confirmLabels[confirmAction].btn }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.btn-outline-warn {
  background: transparent;
  border: 1px solid #c9a035;
  color: #c9a035;
  transition: background 0.15s;
}
.btn-outline-warn:hover { background: rgba(201,160,53,.1); }

.btn-outline-danger {
  background: transparent;
  border: 1px solid #e05252;
  color: #e05252;
  transition: background 0.15s;
}
.btn-outline-danger:hover { background: rgba(224,82,82,.1); }

.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  display: flex; align-items: center; justify-content: center;
  z-index: 999;
}
.modal-box {
  background: var(--ink-2);
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 32px;
  max-width: 440px;
  width: 90%;
}
.btn-danger  { background: #e05252 !important; border-color: #e05252 !important; color: #fff !important; }
.btn-ghost   { background: transparent; border-color: var(--line); color: var(--mute); }

@media (max-width: 768px) {
  header { flex-direction: column !important; align-items: flex-start !important; }
}
</style>
