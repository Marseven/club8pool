<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';
import { AlertTriangle, RotateCcw, Check, Pencil, Play, Square, Plus, Minus, Trophy } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  qualifiers: Object,
  ties: Array,
  pairs: Array,
  existing: Object,
  tables: Array,
  referees: Array,
  progress: Object,
});

// Local mutable copy so the admin can reorder ties before submitting.
const localQualifiers = ref(JSON.parse(JSON.stringify(props.qualifiers)));

// Reseed pairs whenever localQualifiers changes (mirrors the backend logic)
const localPairs = computed(() => {
  const A = localQualifiers.value.A ?? [];
  const B = localQualifiers.value.B ?? [];
  const C = localQualifiers.value.C ?? [];
  const D = localQualifiers.value.D ?? [];
  if (A.length === 4 && B.length === 4 && C.length === 4 && D.length === 4) {
    // A vs C, B vs D — interleaved pour QF cross-group
    return [
      [A[0], C[3]], [B[0], D[3]],  // pos 0-1 : A1vC4, B1vD4 → QF 0
      [A[1], C[2]], [B[1], D[2]],  // pos 2-3 : A2vC3, B2vD3 → QF 1
      [A[2], C[1]], [B[2], D[1]],  // pos 4-5 : A3vC2, B3vD2 → QF 2
      [A[3], C[0]], [B[3], D[0]],  // pos 6-7 : A4vC1, B4vD1 → QF 3
    ];
  }
  // fallback: pair from props
  return props.pairs;
});

const moveUp = (poolKey, idx) => {
  if (idx <= 0) return;
  const arr = localQualifiers.value[poolKey];
  [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
};

const moveDown = (poolKey, idx) => {
  const arr = localQualifiers.value[poolKey];
  if (idx >= arr.length - 1) return;
  [arr[idx + 1], arr[idx]] = [arr[idx], arr[idx + 1]];
};

const existing = computed(() => props.existing ?? {});
const hasExisting = computed(() => (existing.value.R16?.length ?? 0) > 0 || (existing.value.QF?.length ?? 0) > 0);

const submitting = ref(false);
const generate = () => {
  if (! confirm(hasExisting.value
    ? 'Cela écrasera le bracket existant. Continuer ?'
    : 'Générer le bracket avec ces qualifiés ?')) return;
  submitting.value = true;
  router.post(`/admin/competitions/${props.competition.id}/phase-finale/generer`, { pairs: localPairs.value }, {
    onFinish: () => { submitting.value = false; },
  });
};

const labelOf = (q) => q ? `${q.pool_name}${q.pool_slot}` : '—';
const roundLabel = (r) => ({ R16: '8e de finale', QF: 'Quart de finale', SF: 'Demi-finale', F: 'Finale' }[r] ?? r);

// ── Score correction modal (done matches) ─────────────────────────
const editingMatch = ref(null);
const scoreEdit = ref({ score_a: 0, score_b: 0 });
const saving = ref(false);

const openEdit = (m) => { editingMatch.value = m; scoreEdit.value = { score_a: m.score_a, score_b: m.score_b }; };
const closeEdit = () => { editingMatch.value = null; };
const saveEdit = () => {
  saving.value = true;
  router.patch(`/admin/matchs/${editingMatch.value.id}`, {
    score_a: scoreEdit.value.score_a, score_b: scoreEdit.value.score_b, status: 'done',
  }, { onSuccess: closeEdit, onFinish: () => { saving.value = false; } });
};

// ── Start match modal ──────────────────────────────────────────────
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
const closeStart = () => { startingMatch.value = null; };
const launch = () => {
  launching.value = true;
  router.post(`/admin/phase-finale/matchs/${startingMatch.value.id}/lancer`, starter.value, {
    onSuccess: closeStart,
    onFinish: () => { launching.value = false; },
  });
};

// ── Saisir score direct (scheduled → done sans passer live) ───────
const saisirMatch = ref(null);
const saisirScore = ref({ score_a: 0, score_b: 0 });
const saisirSaving = ref(false);

const openSaisir = (m) => { saisirMatch.value = m; saisirScore.value = { score_a: 0, score_b: 0 }; };
const closeSaisir = () => { saisirMatch.value = null; };
const saveSaisir = () => {
  saisirSaving.value = true;
  router.post(`/admin/phase-finale/matchs/${saisirMatch.value.id}/clore`, saisirScore.value, {
    onSuccess: closeSaisir,
    onFinish: () => { saisirSaving.value = false; },
  });
};

// ── Live scoring modal ─────────────────────────────────────────────
const scoringMatchId = ref(null);
const scoringMatch = computed(() => {
  if (!scoringMatchId.value) return null;
  for (const round of ['R16','QF','SF','F']) {
    const m = (props.existing?.[round] ?? []).find(m => m.id === scoringMatchId.value);
    if (m) return m;
  }
  return null;
});

const openScoring = (m) => { scoringMatchId.value = m.id; };
const closeScoring = () => { scoringMatchId.value = null; };
const frame = (id, player) => router.post(`/admin/phase-finale/matchs/${id}/frame`, { player }, { preserveScroll: true });
const undo = (id, player) => router.post(`/admin/phase-finale/matchs/${id}/undo`, { player }, { preserveScroll: true });
const closeMatchToEdit = (m) => { scoringMatchId.value = null; openSaisir(m); };
</script>

<template>
  <Head title="Phase finale" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="knockout" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">PHASE FINALE</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">{{ competition.name }}</div>
          <a :href="`/admin/competitions/${competition.id}`"
             style="font-size: 11px; color: var(--mute); text-decoration: none; margin-top: 4px; display: inline-block;">
            ← Compétition
          </a>
        </div>
        <Chip :variant="progress.pool_ready ? 'felt' : 'live'">
          {{ progress.pool_done }}/{{ progress.pool_total }} POULES JOUÉES
        </Chip>
      </header>

      <!-- Bandeau d'état -->
      <div v-if="!progress.pool_ready"
           style="margin: 24px 32px; padding: 16px 20px;
                  border: 1px solid rgba(229,72,77,0.4); background: rgba(229,72,77,0.04);">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--live);">PHASE DE POULES NON TERMINÉE</div>
        <p style="font-size: 13px; color: var(--chalk-2); margin-top: 8px; line-height: 1.5;">
          Il reste {{ progress.pool_total - progress.pool_done }} matchs de poule à jouer.
          Tu peux quand même prévisualiser le bracket ci-dessous mais sa génération définitive
          devrait attendre la fin des poules pour éviter les ré-écrasements.
        </p>
      </div>

      <!-- Existing knockout -->
      <section v-if="hasExisting" style="margin: 24px 32px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2); margin-bottom: 14px;">BRACKET ACTUEL</div>

        <div v-for="round in ['R16', 'QF', 'SF', 'F']" :key="round">
          <template v-if="(existing[round] || []).length">
            <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;
                                     padding: 8px 0 6px; border-bottom: 1px solid var(--line);">
              {{ roundLabel(round) }}
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
              <tbody>
                <tr v-for="m in (existing[round] || [])" :key="m.id"
                    style="border-bottom: 1px solid var(--line);">
                  <td style="padding: 10px 0; width: 40%;">
                    <span :style="{ fontWeight: m.score_a > m.score_b ? 700 : 400 }">
                      {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
                    </span>
                  </td>
                  <td style="padding: 10px 12px; text-align: center; width: 80px;">
                    <template v-if="m.status === 'done'">
                      <span class="disp-a tnum" style="font-size: 18px;">
                        {{ m.score_a }} — {{ m.score_b }}
                      </span>
                    </template>
                    <template v-else-if="m.status === 'live'">
                      <span class="disp-a tnum" style="font-size: 18px; color: var(--live);">
                        {{ m.score_a }} — {{ m.score_b }}
                      </span>
                      <div class="mono" style="font-size: 9px; color: var(--live); letter-spacing: .15em;">● EN COURS</div>
                    </template>
                    <template v-else>
                      <span class="mono" style="font-size: 10px; color: var(--mute);">
                        {{ m.status === 'scheduled' ? 'À JOUER' : 'ATTENTE' }}
                      </span>
                    </template>
                  </td>
                  <td style="padding: 10px 0; width: 40%; text-align: right;">
                    <span :style="{ fontWeight: m.score_b > m.score_a ? 700 : 400 }">
                      {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
                    </span>
                  </td>
                  <td style="padding: 10px 0 10px 14px; text-align: right; white-space: nowrap;">
                    <!-- scheduled → Démarrer + Saisir -->
                    <template v-if="m.status === 'scheduled' && m.player_a && m.player_b">
                      <button class="btn btn-felt" @click="openStart(m)"
                              style="padding: 4px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; margin-right: 6px;">
                        <Play :size="11" /> Démarrer
                      </button>
                      <button class="btn" @click="openSaisir(m)"
                              style="padding: 4px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                        <Trophy :size="11" /> Saisir
                      </button>
                    </template>
                    <!-- live → Scorer + Clore -->
                    <template v-else-if="m.status === 'live'">
                      <button class="btn btn-felt" @click="openScoring(m)"
                              style="padding: 4px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; margin-right: 6px;">
                        <Plus :size="11" /> Scorer
                      </button>
                      <button class="btn" @click="openSaisir(m)"
                              style="padding: 4px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                        <Square :size="11" /> Clore
                      </button>
                    </template>
                    <!-- done → Corriger -->
                    <template v-else-if="m.status === 'done'">
                      <button class="btn" @click="openEdit(m)"
                              style="padding: 4px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                        <Pencil :size="11" /> Corriger
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </div>

        <p style="font-size: 12px; color: var(--mute);">
          Régénérer le bracket écrasera tous ces matchs et leurs scores.
        </p>
      </section>

      <!-- Qualifiés -->
      <section style="padding: 24px 32px;">
        <h3 class="disp-a" style="font-size: 24px; margin-bottom: 14px;">Qualifiés par poule</h3>
        <div v-if="ties.length" style="margin-bottom: 18px; padding: 12px 16px;
             border: 1px solid var(--line-strong); background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--live); display:flex; align-items:center; gap:6px;">
            <AlertTriangle :size="12" /> {{ ties.length }} EX-AEQUO À TRANCHER
          </div>
          <ul style="margin: 8px 0 0; padding-left: 18px; font-size: 12px; color: var(--chalk-2); line-height: 1.6;">
            <li v-for="(t, i) in ties" :key="i">
              Poule {{ t.pool }}, rang {{ t.rank }} :
              <strong>{{ t.players.map(p => p.name).join(', ') }}</strong>
              (V {{ t.players[0].v }} · Diff {{ t.players[0].diff > 0 ? '+' : '' }}{{ t.players[0].diff }})
            </li>
          </ul>
          <p style="font-size: 11px; color: var(--mute); margin-top: 8px;">
            Réordonne les joueurs avec les flèches dans la liste ci-dessous pour décider qui
            entre dans le top {{ competition.qualifiers_per_pool }}.
          </p>
        </div>

        <div class="qualifiers-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
          <div v-for="(players, poolName) in localQualifiers" :key="poolName"
               style="border: 1px solid var(--line); background: var(--ink-2);">
            <div style="padding: 12px 16px; border-bottom: 1px solid var(--line);
                        display: flex; justify-content: space-between; align-items: baseline;">
              <h4 class="disp-a" style="font-size: 20px;">POULE {{ poolName }}</h4>
              <Chip variant="felt">{{ players.length }} QUAL.</Chip>
            </div>
            <div>
              <div v-for="(p, i) in players" :key="p.player_id"
                   style="display: grid; grid-template-columns: 28px 1fr auto auto; align-items: center; gap: 8px;
                          padding: 10px 16px; border-top: 1px solid var(--line);">
                <span class="mono tnum" style="color: var(--felt-2); font-weight: 700;">{{ poolName }}{{ i + 1 }}</span>
                <span>
                  <span style="font-weight: 600;">{{ p.name }}</span>
                  <span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 8px;">
                    V{{ p.v }} · {{ p.diff > 0 ? '+' : '' }}{{ p.diff }}
                  </span>
                </span>
                <button @click="moveUp(poolName, i)" :disabled="i === 0"
                        :style="{ background: 'transparent', border: '1px solid var(--line-strong)',
                                  width: '24px', height: '24px', cursor: i === 0 ? 'not-allowed' : 'pointer',
                                  color: i === 0 ? 'var(--mute-2)' : 'var(--chalk)' }">▲</button>
                <button @click="moveDown(poolName, i)" :disabled="i === players.length - 1"
                        :style="{ background: 'transparent', border: '1px solid var(--line-strong)',
                                  width: '24px', height: '24px', cursor: i === players.length - 1 ? 'not-allowed' : 'pointer',
                                  color: i === players.length - 1 ? 'var(--mute-2)' : 'var(--chalk)' }">▼</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Bracket préview -->
      <section style="padding: 24px 32px; border-top: 1px solid var(--line);">
        <h3 class="disp-a" style="font-size: 24px; margin-bottom: 14px;">
          Bracket proposé · {{ localPairs.length }} matchs
        </h3>
        <p style="font-size: 12px; color: var(--mute); margin-bottom: 18px;">
          Seeding cross-poules standard : les têtes de poule se rencontrent au plus tôt en demi-finale.
        </p>
        <div class="bracket-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <div v-for="(pair, i) in localPairs" :key="i"
               style="border: 1px solid var(--line); background: var(--ink-2); padding: 14px;">
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">
              MATCH {{ i + 1 }}
            </div>
            <div style="margin-top: 8px;">
              <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                <span class="mono" style="font-size: 10px; color: var(--felt-2);">{{ labelOf(pair[0]) }}</span>
                <span style="font-size: 13px; font-weight: 600;">{{ pair[0]?.name ?? '—' }}</span>
              </div>
              <div style="font-size: 11px; color: var(--mute); text-align: center; padding: 2px 0;">vs</div>
              <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                <span class="mono" style="font-size: 10px; color: var(--felt-2);">{{ labelOf(pair[1]) }}</span>
                <span style="font-size: 13px; font-weight: 600;">{{ pair[1]?.name ?? '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section style="padding: 24px 32px; border-top: 1px solid var(--line);
                      display: flex; justify-content: space-between; align-items: center;
                      gap: 14px; flex-wrap: wrap;">
        <p style="font-size: 12px; color: var(--mute); max-width: 520px;">
          Une fois validé : 8 matchs de 8e de finale créés + placeholders pour QF / SF / F.
          Les gagnants remontent automatiquement à chaque match clôturé.
        </p>
        <button class="btn btn-felt" @click="generate" :disabled="submitting"
                style="padding: 14px 22px;">
          <template v-if="submitting">Création…</template>
          <template v-else-if="hasExisting"><RotateCcw :size="12" style="vertical-align:middle;margin-right:4px;" /> Régénérer le bracket</template>
          <template v-else><Check :size="12" style="vertical-align:middle;margin-right:4px;" /> Générer le bracket</template>
        </button>
      </section>
    </main>
  </div>

  <!-- ── Start match modal ─────────────────────────────────────── -->
  <div v-if="startingMatch" @click.self="closeStart"
       style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;
              align-items:center;justify-content:center;z-index:200;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);
                padding:28px;width:400px;max-width:calc(100vw - 32px);">
      <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--mute);margin-bottom:4px;">
        LANCER LE MATCH
      </div>
      <div style="font-size:15px;font-weight:700;margin-bottom:20px;">
        {{ startingMatch.player_a?.first_name }} {{ startingMatch.player_a?.last_name }}
        <span style="color:var(--mute);font-weight:400;"> vs </span>
        {{ startingMatch.player_b?.first_name }} {{ startingMatch.player_b?.last_name }}
      </div>

      <div style="margin-bottom:14px;">
        <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">TABLE</div>
        <select v-model="starter.pool_table_id"
                style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);
                       color:var(--chalk);font-size:13px;">
          <option v-for="t in tables" :key="t.id" :value="t.id">{{ t.name ?? `Table ${t.id}` }}</option>
        </select>
      </div>

      <div style="margin-bottom:20px;">
        <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">ARBITRE (optionnel)</div>
        <select v-model="starter.referee_id"
                style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);
                       color:var(--chalk);font-size:13px;">
          <option :value="null">— Aucun —</option>
          <option v-for="r in referees" :key="r.id" :value="r.id">{{ r.title ? r.title + ' ' : '' }}{{ r.name }}</option>
        </select>
      </div>

      <div style="display:flex;gap:10px;">
        <button class="btn" @click="closeStart" style="flex:1;padding:10px;">Annuler</button>
        <button class="btn btn-felt" @click="launch" :disabled="launching || !starter.pool_table_id"
                style="flex:2;padding:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <Play :size="13" />{{ launching ? 'Lancement…' : 'Lancer le match' }}
        </button>
      </div>
    </div>
  </div>

  <!-- ── Live scoring modal ──────────────────────────────────────── -->
  <div v-if="scoringMatchId && scoringMatch" @click.self="closeScoring"
       style="position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;
              align-items:center;justify-content:center;z-index:200;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);
                padding:28px;width:440px;max-width:calc(100vw - 32px);">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
        <div>
          <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--live);">EN COURS · LIVE SCORING</div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-top:4px;">
            {{ roundLabel(scoringMatch.round) }}
          </div>
        </div>
        <button @click="closeScoring"
                style="background:transparent;border:none;color:var(--mute);font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <!-- Scores côte à côte -->
      <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;margin-bottom:24px;">
        <!-- Joueur A -->
        <div style="text-align:center;">
          <div style="font-size:13px;font-weight:600;margin-bottom:10px;min-height:36px;display:flex;align-items:center;justify-content:center;">
            {{ scoringMatch.player_a?.first_name }}<br><span style="font-size:11px;color:var(--mute);">{{ scoringMatch.player_a?.last_name }}</span>
          </div>
          <div class="disp-a tnum" style="font-size:80px;line-height:1;">{{ scoringMatch.score_a }}</div>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;">
            <button class="btn btn-felt" @click="frame(scoringMatch.id, 'A')"
                    style="width:44px;height:44px;padding:0;font-size:20px;display:flex;align-items:center;justify-content:center;">
              <Plus :size="18" />
            </button>
            <button class="btn" @click="undo(scoringMatch.id, 'A')" :disabled="scoringMatch.score_a <= 0"
                    style="width:44px;height:44px;padding:0;font-size:20px;display:flex;align-items:center;justify-content:center;">
              <Minus :size="18" />
            </button>
          </div>
        </div>
        <!-- Séparateur -->
        <div class="mono" style="font-size:14px;color:var(--mute);">vs</div>
        <!-- Joueur B -->
        <div style="text-align:center;">
          <div style="font-size:13px;font-weight:600;margin-bottom:10px;min-height:36px;display:flex;align-items:center;justify-content:center;">
            {{ scoringMatch.player_b?.first_name }}<br><span style="font-size:11px;color:var(--mute);">{{ scoringMatch.player_b?.last_name }}</span>
          </div>
          <div class="disp-a tnum" style="font-size:80px;line-height:1;">{{ scoringMatch.score_b }}</div>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;">
            <button class="btn btn-felt" @click="frame(scoringMatch.id, 'B')"
                    style="width:44px;height:44px;padding:0;font-size:20px;display:flex;align-items:center;justify-content:center;">
              <Plus :size="18" />
            </button>
            <button class="btn" @click="undo(scoringMatch.id, 'B')" :disabled="scoringMatch.score_b <= 0"
                    style="width:44px;height:44px;padding:0;font-size:20px;display:flex;align-items:center;justify-content:center;">
              <Minus :size="18" />
            </button>
          </div>
        </div>
      </div>

      <div style="border-top:1px solid var(--line);padding-top:16px;display:flex;gap:10px;">
        <button class="btn" @click="closeScoring" style="flex:1;padding:10px;">Fermer</button>
        <button class="btn btn-felt" @click="closeMatchToEdit(scoringMatch)"
                style="flex:2;padding:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <Square :size="13" /> Clore le match
        </button>
      </div>
    </div>
  </div>

  <!-- ── Saisir score modal (scheduled → done ou close live) ─────── -->
  <div v-if="saisirMatch" @click.self="closeSaisir"
       style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;
              align-items:center;justify-content:center;z-index:200;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);
                padding:28px;width:380px;max-width:calc(100vw - 32px);">
      <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--mute);margin-bottom:4px;">
        SAISIR LE SCORE FINAL
      </div>
      <div style="font-size:15px;font-weight:700;margin-bottom:20px;">
        {{ saisirMatch.player_a?.first_name }} {{ saisirMatch.player_a?.last_name }}
        <span style="color:var(--mute);font-weight:400;"> vs </span>
        {{ saisirMatch.player_b?.first_name }} {{ saisirMatch.player_b?.last_name }}
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">
            SCORE · {{ saisirMatch.player_a?.first_name?.toUpperCase() }}
          </div>
          <input v-model.number="saisirScore.score_a" type="number" min="0"
                 :max="competition.knockout_race_to ?? competition.race_to"
                 style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);
                        color:var(--chalk);font-size:18px;text-align:center;" />
        </div>
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">
            SCORE · {{ saisirMatch.player_b?.first_name?.toUpperCase() }}
          </div>
          <input v-model.number="saisirScore.score_b" type="number" min="0"
                 :max="competition.knockout_race_to ?? competition.race_to"
                 style="width:100%;padding:8px 10px;background:var(--ink);border:1px solid var(--line-strong);
                        color:var(--chalk);font-size:18px;text-align:center;" />
        </div>
      </div>

      <div v-if="saisirScore.score_a === saisirScore.score_b && (saisirScore.score_a > 0 || saisirScore.score_b > 0)"
           style="margin-bottom:14px;padding:10px 12px;border:1px solid rgba(229,72,77,.4);background:rgba(229,72,77,.04);">
        <div class="mono" style="font-size:10px;color:var(--live);">⚠ ÉGALITÉ — vérifier le score avant de valider</div>
      </div>

      <div style="display:flex;gap:10px;">
        <button class="btn" @click="closeSaisir" style="flex:1;padding:10px;">Annuler</button>
        <button class="btn btn-felt" @click="saveSaisir"
                :disabled="saisirSaving || saisirScore.score_a === saisirScore.score_b"
                style="flex:2;padding:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <Trophy :size="13" />{{ saisirSaving ? 'Enregistrement…' : 'Valider le résultat' }}
        </button>
      </div>
    </div>
  </div>

  <!-- ── Score correction modal ─────────────────────────────────── -->
  <div v-if="editingMatch" @click.self="closeEdit"
       style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;
              align-items:center;justify-content:center;z-index:200;">
    <div style="background:var(--ink-2);border:1px solid var(--line-strong);
                padding:28px;width:380px;max-width:calc(100vw - 32px);">
      <div class="mono" style="font-size:10px;letter-spacing:.22em;color:var(--mute);margin-bottom:4px;">
        CORRECTION DE SCORE
      </div>
      <div style="font-size:15px;font-weight:700;margin-bottom:20px;">
        {{ editingMatch.player_a?.first_name }}
        <span style="color:var(--mute);font-weight:400;"> vs </span>
        {{ editingMatch.player_b?.first_name }}
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">
            SCORE · {{ editingMatch.player_a?.first_name?.toUpperCase() }}
          </div>
          <input v-model.number="scoreEdit.score_a" type="number" min="0"
                 :max="competition.knockout_race_to ?? competition.race_to" />
        </div>
        <div>
          <div class="mono" style="font-size:10px;color:var(--mute);margin-bottom:6px;">
            SCORE · {{ editingMatch.player_b?.first_name?.toUpperCase() }}
          </div>
          <input v-model.number="scoreEdit.score_b" type="number" min="0"
                 :max="competition.knockout_race_to ?? competition.race_to" />
        </div>
      </div>

      <div style="display:flex;gap:10px;">
        <button class="btn" @click="closeEdit" style="flex:1;padding:10px;">Annuler</button>
        <button class="btn btn-felt" @click="saveEdit" :disabled="saving"
                style="flex:2;padding:10px;">
          {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  /* Header: stack title + chip */
  header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 10px !important;
  }

  /* Bracket preview: 2 cols on mobile, 1 col on small phones */
  .bracket-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }

  /* Qualifiers: 1 col on mobile */
  .qualifiers-grid {
    grid-template-columns: 1fr !important;
  }

  /* Existing bracket table: all widths fluid */
  section table {
    width: 100%;
  }
  section table td:first-child,
  section table td:nth-child(3) {
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

@media (max-width: 480px) {
  .bracket-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
