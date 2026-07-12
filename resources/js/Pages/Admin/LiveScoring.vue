<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';
import { Plus, Minus, Play, Square, Trophy, X } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  liveMatches: { type: Array, default: () => [] },
  upcomingMatches: { type: Array, default: () => [] },
  tables: { type: Array, default: () => [] },
  referees: { type: Array, default: () => [] },
});

// ── Route helpers (phase decides the endpoint namespace) ────────────────
const base = (m) => m.phase === 'knockout' ? '/admin/phase-finale/matchs' : '/admin/poules/matchs';

const frame = (m, player) => {
  router.post(`${base(m)}/${m.id}/frame`, { player }, { preserveScroll: true, preserveState: true });
};
const undo = (m, player) => {
  router.post(`${base(m)}/${m.id}/undo`, { player }, { preserveScroll: true, preserveState: true });
};

// ── Close match (pre-filled with the live score — the bug we fixed) ─────
const closingMatch = ref(null);
const closeScore = ref({ score_a: 0, score_b: 0 });
const closing = ref(false);
const openClose = (m) => {
  closingMatch.value = m;
  closeScore.value = { score_a: m.score_a ?? 0, score_b: m.score_b ?? 0 };
};
const cancelClose = () => { closingMatch.value = null; };
const isDrawInvalid = computed(() =>
  closingMatch.value?.phase === 'knockout' && closeScore.value.score_a === closeScore.value.score_b
);
const submitClose = () => {
  const m = closingMatch.value;
  if (!m || closing.value) return;
  closing.value = true;
  const payload = { score_a: closeScore.value.score_a, score_b: closeScore.value.score_b };
  const opts = {
    preserveScroll: true,
    onSuccess: () => { closingMatch.value = null; },
    onFinish: () => { closing.value = false; },
  };
  if (m.phase === 'knockout') {
    router.post(`/admin/phase-finale/matchs/${m.id}/clore`, payload, opts);
  } else {
    router.patch(`/admin/poules/matchs/${m.id}`, payload, opts);
  }
};

// ── Start match (table + referee) ───────────────────────────────────────
const startingMatch = ref(null);
const starter = ref({ pool_table_id: null, referee_id: null });
const launching = ref(false);
const openStart = (m) => {
  startingMatch.value = m;
  starter.value = {
    pool_table_id: props.tables?.find(t => t.status !== 'maint')?.id ?? null,
    referee_id: props.referees?.[0]?.id ?? null,
  };
};
const cancelStart = () => { startingMatch.value = null; };
const tableRequired = computed(() => startingMatch.value?.phase === 'knockout');
const launch = () => {
  const m = startingMatch.value;
  if (!m || launching.value) return;
  if (tableRequired.value && !starter.value.pool_table_id) return;
  launching.value = true;
  router.post(`${base(m)}/${m.id}/lancer`, starter.value, {
    preserveScroll: true,
    onSuccess: () => { startingMatch.value = null; },
    onFinish: () => { launching.value = false; },
  });
};

// ── Auto-refresh ────────────────────────────────────────────────────────
const lastRefresh = ref(new Date());
const tick = ref(0);
let pollInterval, tickInterval;
const secondsAgo = computed(() => { void tick.value; return Math.floor((Date.now() - lastRefresh.value.getTime()) / 1000); });

onMounted(() => {
  pollInterval = setInterval(() => {
    router.reload({
      only: ['liveMatches', 'upcomingMatches', 'tables'],
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => { lastRefresh.value = new Date(); },
    });
  }, 5000);
  tickInterval = setInterval(() => { tick.value++; }, 1000);
});
onUnmounted(() => { clearInterval(pollInterval); clearInterval(tickInterval); });

// ── Display helpers ─────────────────────────────────────────────────────
const name = (p) => p ? `${p.first_name} ${p.last_name ?? ''}`.trim() : '—';
const atRace = (m, side) => (side === 'a' ? m.score_a : m.score_b) >= m.race_to;
const elapsed = (m) => {
  void tick.value;
  if (!m.started_at) return null;
  const s = Math.floor((Date.now() - new Date(m.started_at).getTime()) / 1000);
  const min = Math.floor(s / 60);
  return `${min}min`;
};
</script>

<template>
  <Head title="Live Scoring · Admin" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="scoring" />
    <main style="flex: 1; min-width: 0;">

      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line); position: sticky; top: 0;
                     background: var(--ink); z-index: 30;">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">LIVE SCORING</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">{{ competition?.name }}</div>
        </div>
        <div style="display: flex; gap: 14px; align-items: center;">
          <Chip v-if="liveMatches.length" variant="live">{{ liveMatches.length }} EN DIRECT</Chip>
          <Chip v-else>AUCUN MATCH LIVE</Chip>
          <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">MAJ · {{ secondsAgo }}s</span>
        </div>
      </header>

      <!-- Live matches -->
      <section style="padding: 24px 32px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--live); margin-bottom: 16px;">
          MATCHS EN DIRECT
        </div>

        <div v-if="!liveMatches.length"
             style="padding: 48px; border: 1px dashed var(--line); text-align: center; color: var(--mute);">
          <div class="disp-a" style="font-size: 22px; color: var(--mute-2);">—</div>
          <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; margin-top: 10px;">AUCUN MATCH EN COURS</div>
          <p style="font-size: 13px; margin-top: 8px;">Démarre un match depuis « À venir » ci-dessous.</p>
        </div>

        <div v-else class="score-grid">
          <div v-for="m in liveMatches" :key="m.id" class="score-card">
            <!-- Meta -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
              <div>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.16em; color: var(--felt-2);">
                  {{ m.context }} · RACE TO {{ m.race_to }}
                </div>
                <div class="mono" style="font-size: 9px; color: var(--mute); margin-top: 4px;">
                  {{ m.table?.name ?? 'Sans table' }}<template v-if="m.referee"> · {{ m.referee.name }}</template>
                  <template v-if="elapsed(m)"> · {{ elapsed(m) }}</template>
                </div>
              </div>
              <Chip variant="live">LIVE</Chip>
            </div>

            <!-- Scoring -->
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px;">
              <!-- Player A -->
              <div style="text-align: center; min-width: 0;">
                <div class="score-name" :style="{ color: m.score_a > m.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">{{ name(m.player_a) }}</div>
                <div class="disp-a tnum score-num" :style="{ color: atRace(m, 'a') ? 'var(--felt-2)' : 'var(--chalk)' }">{{ m.score_a }}</div>
                <div style="display: flex; gap: 8px; justify-content: center; margin-top: 10px;">
                  <button class="btn btn-felt sc-btn" @click="frame(m, 'A')" :disabled="atRace(m, 'a')"><Plus :size="18" /></button>
                  <button class="btn sc-btn" @click="undo(m, 'A')" :disabled="m.score_a <= 0"><Minus :size="18" /></button>
                </div>
              </div>
              <div class="mono" style="font-size: 13px; color: var(--mute);">vs</div>
              <!-- Player B -->
              <div style="text-align: center; min-width: 0;">
                <div class="score-name" :style="{ color: m.score_b > m.score_a ? 'var(--chalk)' : 'var(--chalk-2)' }">{{ name(m.player_b) }}</div>
                <div class="disp-a tnum score-num" :style="{ color: atRace(m, 'b') ? 'var(--felt-2)' : 'var(--chalk)' }">{{ m.score_b }}</div>
                <div style="display: flex; gap: 8px; justify-content: center; margin-top: 10px;">
                  <button class="btn btn-felt sc-btn" @click="frame(m, 'B')" :disabled="atRace(m, 'b')"><Plus :size="18" /></button>
                  <button class="btn sc-btn" @click="undo(m, 'B')" :disabled="m.score_b <= 0"><Minus :size="18" /></button>
                </div>
              </div>
            </div>

            <!-- Close -->
            <div style="margin-top: 18px; border-top: 1px solid var(--line); padding-top: 14px;">
              <button class="btn btn-felt" @click="openClose(m)"
                      style="width: 100%; padding: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <Square :size="14" /> Clôturer le match
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Upcoming matches -->
      <section v-if="upcomingMatches.length" style="padding: 0 32px 40px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 14px;">
          À VENIR · PRÊTS À DÉMARRER
        </div>
        <div class="up-grid">
          <div v-for="m in upcomingMatches" :key="m.id" class="up-card">
            <div style="min-width: 0;">
              <div class="mono" style="font-size: 9px; color: var(--felt-2); letter-spacing: 0.14em;">{{ m.context }}</div>
              <div style="font-size: 13px; font-weight: 600; margin-top: 6px;">{{ name(m.player_a) }}</div>
              <div style="font-size: 13px; color: var(--mute); margin-top: 2px;">vs {{ name(m.player_b) }}</div>
            </div>
            <button class="btn btn-felt" @click="openStart(m)"
                    style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
              <Play :size="12" /> Démarrer
            </button>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- ── Close modal ── -->
  <div v-if="closingMatch" @click.self="cancelClose"
       style="position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:200;padding:16px;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);padding:28px;width:400px;max-width:100%;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
        <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--mute);">SCORE FINAL · {{ closingMatch.context }}</div>
        <button @click="cancelClose" style="background:transparent;border:none;color:var(--mute);font-size:18px;cursor:pointer;line-height:1;"><X :size="16" /></button>
      </div>
      <div style="font-size:15px;font-weight:700;margin-bottom:18px;">
        {{ name(closingMatch.player_a) }} <span style="color:var(--mute);font-weight:400;">vs</span> {{ name(closingMatch.player_b) }}
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">{{ closingMatch.player_a?.first_name?.toUpperCase() }}</div>
          <input v-model.number="closeScore.score_a" type="number" min="0"
                 style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);color:var(--chalk);font-size:20px;text-align:center;" />
        </div>
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">{{ closingMatch.player_b?.first_name?.toUpperCase() }}</div>
          <input v-model.number="closeScore.score_b" type="number" min="0"
                 style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);color:var(--chalk);font-size:20px;text-align:center;" />
        </div>
      </div>
      <div v-if="isDrawInvalid"
           style="margin-bottom:14px;padding:10px 12px;border:1px solid rgba(229,72,77,.4);background:rgba(229,72,77,.04);">
        <div class="mono" style="font-size:10px;color:var(--live);">⚠ ÉGALITÉ INTERDITE EN PHASE FINALE</div>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn" @click="cancelClose" style="flex:1;padding:10px;">Annuler</button>
        <button class="btn btn-felt" @click="submitClose" :disabled="closing || isDrawInvalid"
                style="flex:2;padding:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <Trophy :size="13" /> {{ closing ? 'Enregistrement…' : 'Valider le résultat' }}
        </button>
      </div>
    </div>
  </div>

  <!-- ── Start modal ── -->
  <div v-if="startingMatch" @click.self="cancelStart"
       style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:200;padding:16px;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);padding:28px;width:400px;max-width:100%;">
      <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--mute);margin-bottom:6px;">DÉMARRER · {{ startingMatch.context }}</div>
      <div style="font-size:15px;font-weight:700;margin-bottom:18px;">
        {{ name(startingMatch.player_a) }} <span style="color:var(--mute);font-weight:400;">vs</span> {{ name(startingMatch.player_b) }}
      </div>
      <div style="margin-bottom:14px;">
        <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">TABLE <span v-if="tableRequired" style="color:var(--live);">*</span></div>
        <select v-model.number="starter.pool_table_id"
                style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);color:var(--chalk);font-size:14px;">
          <option :value="null">— Aucune —</option>
          <option v-for="t in tables" :key="t.id" :value="t.id" :disabled="t.status === 'maint'">{{ t.name }}<template v-if="t.status === 'maint'"> (maintenance)</template></option>
        </select>
      </div>
      <div style="margin-bottom:20px;">
        <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">ARBITRE</div>
        <select v-model.number="starter.referee_id"
                style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);color:var(--chalk);font-size:14px;">
          <option :value="null">— Aucun —</option>
          <option v-for="r in referees" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn" @click="cancelStart" style="flex:1;padding:10px;">Annuler</button>
        <button class="btn btn-felt" @click="launch" :disabled="launching || (tableRequired && !starter.pool_table_id)"
                style="flex:2;padding:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <Play :size="13" /> {{ launching ? 'Lancement…' : 'Lancer le match' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.score-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 16px;
}
.score-card {
  border: 1px solid rgba(229,72,77,0.4);
  background: rgba(229,72,77,0.03);
  padding: 20px;
}
.score-name {
  font-size: 14px; font-weight: 600; line-height: 1.2;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  min-height: 34px; display: flex; align-items: center; justify-content: center;
}
.score-num { font-size: 64px; line-height: 1; }
.sc-btn {
  width: 46px; height: 46px; padding: 0;
  display: flex; align-items: center; justify-content: center;
}

.up-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 12px;
}
.up-card {
  border: 1px solid var(--line);
  background: var(--ink-2);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

@media (max-width: 640px) {
  .score-grid { grid-template-columns: 1fr; }
  header { flex-wrap: wrap; gap: 10px; }
}
</style>
