<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import PoolStandings from '@/Components/PoolStandings.vue';
import Chip from '@/Components/Chip.vue';
import { Play, Square, RotateCcw, Plus, Minus, Trophy } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  pools: Array,
  tables: Array,
  referees: Array,
});

const selectedPool = ref(props.pools[0]?.id);
const editingMatch = ref(null);
const startingMatch = ref(null);

const editor = ref({ score_a: 0, score_b: 0, is_draw: false, warning_a: false, warning_b: false, note: '' });
const starter = ref({ pool_table_id: null, referee_id: null });

const currentPool = computed(() => props.pools.find(p => p.id === selectedPool.value));

const startEdit = (m) => {
  editingMatch.value = m;
  editor.value = {
    score_a: m.score_a,
    score_b: m.score_b,
    is_draw: m.is_draw,
    warning_a: m.warning_a,
    warning_b: m.warning_b,
    note: m.note ?? '',
  };
};

const saveScore = () => {
  router.patch(`/admin/poules/matchs/${editingMatch.value.id}`, editor.value, {
    preserveScroll: true,
    onSuccess: () => { editingMatch.value = null; },
  });
};

const cancel = () => { editingMatch.value = null; };

const startStart = (m) => {
  startingMatch.value = m;
  starter.value = {
    pool_table_id: props.tables.find(t => t.status !== 'maint')?.id ?? null,
    referee_id: props.referees[0]?.id ?? null,
  };
};

const launch = () => {
  router.post(`/admin/poules/matchs/${startingMatch.value.id}/lancer`, starter.value, {
    preserveScroll: true,
    onSuccess: () => { startingMatch.value = null; },
  });
};

const cancelStart = () => { startingMatch.value = null; };

// Live scoring
const scoringMatchId = ref(null);
const scoringMatch = computed(() =>
  props.pools.flatMap(p => p.matches).find(m => m.id === scoringMatchId.value) ?? null
);
const openScoring = (m) => { scoringMatchId.value = m.id; };
const closeScoring = () => { scoringMatchId.value = null; };

const frame = (matchId, player) => {
  router.post(`/admin/poules/matchs/${matchId}/frame`, { player }, { preserveScroll: true });
};
const undo = (matchId, player) => {
  router.post(`/admin/poules/matchs/${matchId}/undo`, { player }, { preserveScroll: true });
};
const closeMatch = (m) => {
  scoringMatchId.value = null;
  startEdit(m);
};

const confirmingReset = ref(null);
const resetMatch = (m) => {
  if (confirmingReset.value === m.id) {
    router.post(`/admin/poules/matchs/${m.id}/reset`, {}, { preserveScroll: true });
    confirmingReset.value = null;
  } else {
    confirmingReset.value = m.id;
    setTimeout(() => { if (confirmingReset.value === m.id) confirmingReset.value = null; }, 3000);
  }
};

let pollInterval;
onMounted(() => {
  pollInterval = setInterval(() => {
    if (editingMatch.value || startingMatch.value || scoringMatchId.value) return;
    router.reload({ only: ['pools'], preserveScroll: true });
  }, 15000);
});
onUnmounted(() => clearInterval(pollInterval));

const playerLabel = (pool, playerId) => {
  const idx = pool.players.findIndex(p => p.id === playerId);
  return idx >= 0 ? `${pool.name}${idx + 1}` : '?';
};
</script>

<template>
  <Head title="Poules" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="pools" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">
            PHASE DE POULES · RACE TO {{ competition.pool_race_to ?? competition.race_to }}
          </div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">{{ competition.name }}</div>
        </div>
        <div style="display: flex; gap: 8px;">
          <button v-for="p in pools" :key="p.id" @click="selectedPool = p.id" :style="{
            padding: '8px 16px',
            border: '1px solid ' + (selectedPool === p.id ? 'var(--felt-2)' : 'var(--line-strong)'),
            background: selectedPool === p.id ? 'rgba(45,168,118,0.08)' : 'transparent',
            color: 'var(--chalk)', cursor: 'pointer',
            fontFamily: 'var(--font-display-a)', fontWeight: 700, fontSize: '14px', letterSpacing: '0.02em',
          }">POULE {{ p.name }}</button>
        </div>
      </header>

      <section v-if="currentPool" style="display: grid; grid-template-columns: 420px 1fr; min-height: calc(100vh - 76px);">
        <aside style="border-right: 1px solid var(--line); padding: 24px;">
          <PoolStandings :pool="currentPool" :qualifiers-per-pool="competition.qualifiers_per_pool" />
          <div style="margin-top: 24px; padding: 14px; background: var(--ink-2); border: 1px solid var(--line);">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">AVANCEMENT</div>
            <div class="disp-a tnum" style="font-size: 36px; margin-top: 6px;">{{ currentPool.progress }}%</div>
            <div style="margin-top: 10px; height: 4px; background: var(--ink-4);">
              <div :style="{ height: '100%', width: currentPool.progress + '%', background: 'var(--felt-2)' }" />
            </div>
          </div>
        </aside>

        <div style="padding: 24px 32px;">
          <h3 class="disp-a" style="font-size: 22px; margin-bottom: 16px;">Matchs · POULE {{ currentPool.name }}</h3>
          <table class="tbl" style="font-size: 12px;">
            <thead>
              <tr>
                <th style="width: 80px;">Match</th>
                <th>Joueur 1</th>
                <th style="text-align: center;">Score</th>
                <th>Joueur 2</th>
                <th>Vainqueur</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in currentPool.matches" :key="m.id" :style="{ background: m.id === editingMatch?.id ? 'var(--ink-3)' : 'transparent' }">
                <td class="mono" style="color: var(--mute);">
                  {{ playerLabel(currentPool, m.player_a_id) }} vs {{ playerLabel(currentPool, m.player_b_id) }}
                </td>
                <td :style="{ fontWeight: m.score_a > m.score_b && !m.is_draw ? 700 : 500 }">
                  {{ m.player_a?.first_name }} {{ m.player_a?.last_name }}
                </td>
                <td class="mono tnum" style="text-align: center; font-weight: 700;">
                  <template v-if="m.status === 'done'">{{ m.score_a }} — {{ m.score_b }}</template>
                  <template v-else>— · —</template>
                </td>
                <td :style="{ fontWeight: m.score_b > m.score_a && !m.is_draw ? 700 : 500 }">
                  {{ m.player_b?.first_name }} {{ m.player_b?.last_name }}
                </td>
                <td>
                  <Chip v-if="m.status === 'live'" variant="live">EN COURS</Chip>
                  <span v-else-if="m.is_draw" class="mono" style="color: var(--mute);">NUL</span>
                  <span v-else-if="m.status === 'done' && m.score_a > m.score_b">{{ m.player_a?.first_name }}</span>
                  <span v-else-if="m.status === 'done' && m.score_b > m.score_a">{{ m.player_b?.first_name }}</span>
                  <span v-else style="color: var(--mute-2);">·</span>
                  <span v-if="m.warning_a || m.warning_b" style="color: var(--live); margin-left: 8px;">!</span>
                </td>
                <td style="text-align: right; white-space: nowrap; display: flex; gap: 4px; justify-content: flex-end; align-items: center;">
                  <button v-if="m.status === 'scheduled'" class="btn btn-felt"
                          style="padding: 4px 10px; font-size: 11px; display:inline-flex; align-items:center; gap:4px;" @click="startStart(m)">
                    <Play :size="12" /> Démarrer
                  </button>
                  <button v-if="m.status === 'scheduled'" class="btn"
                          style="padding: 4px 10px; font-size: 11px;"
                          @click="startEdit(m)" title="Saisir directement sans live">
                    Saisir
                  </button>
                  <button v-if="m.status === 'live'" class="btn btn-felt"
                          style="padding: 4px 10px; font-size: 11px; display:inline-flex; align-items:center; gap:4px;"
                          @click="openScoring(m)">
                    <Plus :size="12" /> Scorer
                  </button>
                  <button v-if="m.status === 'live'" class="btn"
                          style="padding: 4px 10px; font-size: 11px; border-color: var(--live); color: var(--live); display:inline-flex; align-items:center; gap:4px;"
                          @click="startEdit(m)">
                    <Square :size="12" /> Clore
                  </button>
                  <button v-if="m.status === 'done'" class="btn"
                          style="padding: 4px 10px; font-size: 11px;" @click="startEdit(m)">
                    Modifier
                  </button>
                  <button class="btn"
                          @click="resetMatch(m)"
                          :style="{
                            padding: '4px 10px', fontSize: '11px',
                            display: 'inline-flex', alignItems: 'center', gap: '4px',
                            borderColor: confirmingReset === m.id ? 'var(--live)' : undefined,
                            color: confirmingReset === m.id ? 'var(--live)' : undefined,
                          }"
                          :title="confirmingReset === m.id ? 'Cliquer encore pour confirmer' : 'Remettre à zéro'">
                    <RotateCcw :size="11" />
                    {{ confirmingReset === m.id ? 'Confirmer ?' : 'Reset' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="editingMatch" @click.self="cancel"
           style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center;
                  justify-content: center; z-index: 50;">
        <div style="background: var(--ink-2); border: 1px solid var(--line-strong); padding: 32px;
                    width: 520px; max-width: 90vw;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">SAISIE SCORE</div>
          <div class="disp-a" style="font-size: 22px; margin-top: 6px;">
            {{ editingMatch.player_a?.first_name }} <span style="color: var(--mute);">vs</span> {{ editingMatch.player_b?.first_name }}
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 24px;">
            <label>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SCORE {{ editingMatch.player_a?.first_name?.toUpperCase() }}</div>
              <input v-model.number="editor.score_a" type="number" min="0" :max="competition.pool_race_to ?? competition.race_to" />
            </label>
            <label>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SCORE {{ editingMatch.player_b?.first_name?.toUpperCase() }}</div>
              <input v-model.number="editor.score_b" type="number" min="0" :max="competition.pool_race_to ?? competition.race_to" />
            </label>
          </div>

          <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px;">
            <label v-if="competition.allow_draw" style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
              <input v-model="editor.is_draw" type="checkbox" style="width: auto; margin: 0;" />
              Match nul
            </label>
            <template v-if="competition.enable_warnings">
              <label style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                <input v-model="editor.warning_a" type="checkbox" style="width: auto; margin: 0;" />
                Avertissement · {{ editingMatch.player_a?.first_name }}
              </label>
              <label style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                <input v-model="editor.warning_b" type="checkbox" style="width: auto; margin: 0;" />
                Avertissement · {{ editingMatch.player_b?.first_name }}
              </label>
            </template>
          </div>

          <label style="display: block; margin-top: 16px;">
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">NOTE</div>
            <textarea v-model="editor.note" rows="2" />
          </label>

          <div style="display: flex; gap: 10px; margin-top: 24px;">
            <button class="btn" @click="cancel">Annuler</button>
            <button class="btn btn-felt" style="margin-left: auto;" @click="saveScore">Enregistrer</button>
          </div>
        </div>
      </div>

      <!-- Modale : scoring en direct -->
      <div v-if="scoringMatchId && scoringMatch" @click.self="closeScoring"
           style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center;
                  justify-content: center; z-index: 60;">
        <div style="background: var(--ink-2); border: 1px solid var(--line-strong); padding: 36px;
                    width: 600px; max-width: 95vw;">

          <!-- Header -->
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px;">
            <div>
              <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--live); margin-bottom: 6px;
                                        display:flex; align-items:center; gap:6px;">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;"></span>
                EN DIRECT · POULE {{ currentPool?.name }}
              </div>
              <div class="disp-a" style="font-size: 22px;">
                {{ scoringMatch.player_a?.first_name }} {{ scoringMatch.player_a?.last_name }}
                <span style="color:var(--mute);">vs</span>
                {{ scoringMatch.player_b?.first_name }} {{ scoringMatch.player_b?.last_name }}
              </div>
            </div>
            <div class="mono" style="font-size: 10px; color: var(--mute);">
              RACE TO {{ competition.pool_race_to ?? competition.race_to }}
            </div>
          </div>

          <!-- Scores -->
          <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: center; margin-bottom: 28px;">

            <!-- Joueur A -->
            <div style="text-align: center;">
              <div style="font-size: 13px; font-weight: 700; margin-bottom: 12px; color: var(--chalk-2);">
                {{ scoringMatch.player_a?.first_name?.toUpperCase() }}
              </div>
              <div class="disp-a tnum" :style="{
                fontSize: '80px', lineHeight: 1,
                color: scoringMatch.score_a >= (competition.pool_race_to ?? competition.race_to) ? 'var(--felt-2)' : 'var(--chalk)'
              }">{{ scoringMatch.score_a }}</div>
              <div style="display: flex; gap: 8px; justify-content: center; margin-top: 16px;">
                <button class="btn btn-felt" style="padding: 10px 22px; font-size: 16px; display:inline-flex; align-items:center; gap:6px;"
                        @click="frame(scoringMatch.id, 'A')">
                  <Plus :size="16" /> +1
                </button>
                <button class="btn" style="padding: 10px 14px; font-size: 13px; display:inline-flex; align-items:center; gap:4px;"
                        :disabled="scoringMatch.score_a === 0" @click="undo(scoringMatch.id, 'A')">
                  <Minus :size="13" />
                </button>
              </div>
              <label style="display:flex; align-items:center; gap:8px; justify-content:center; margin-top:14px; font-size:12px; color:var(--live); cursor:pointer;"
                     v-if="competition.enable_warnings">
                <input type="checkbox" :checked="scoringMatch.warning_a"
                       @change="router.patch(`/admin/poules/matchs/${scoringMatch.id}`, { ...scoringMatch, warning_a: $event.target.checked, status: scoringMatch.status }, { preserveScroll: true })"
                       style="width:auto;margin:0;" />
                Avertissement
              </label>
            </div>

            <!-- Séparateur -->
            <div class="disp-a" style="font-size: 42px; color: var(--mute-2);">—</div>

            <!-- Joueur B -->
            <div style="text-align: center;">
              <div style="font-size: 13px; font-weight: 700; margin-bottom: 12px; color: var(--chalk-2);">
                {{ scoringMatch.player_b?.first_name?.toUpperCase() }}
              </div>
              <div class="disp-a tnum" :style="{
                fontSize: '80px', lineHeight: 1,
                color: scoringMatch.score_b >= (competition.pool_race_to ?? competition.race_to) ? 'var(--felt-2)' : 'var(--chalk)'
              }">{{ scoringMatch.score_b }}</div>
              <div style="display: flex; gap: 8px; justify-content: center; margin-top: 16px;">
                <button class="btn btn-felt" style="padding: 10px 22px; font-size: 16px; display:inline-flex; align-items:center; gap:6px;"
                        @click="frame(scoringMatch.id, 'B')">
                  <Plus :size="16" /> +1
                </button>
                <button class="btn" style="padding: 10px 14px; font-size: 13px; display:inline-flex; align-items:center; gap:4px;"
                        :disabled="scoringMatch.score_b === 0" @click="undo(scoringMatch.id, 'B')">
                  <Minus :size="13" />
                </button>
              </div>
              <label style="display:flex; align-items:center; gap:8px; justify-content:center; margin-top:14px; font-size:12px; color:var(--live); cursor:pointer;"
                     v-if="competition.enable_warnings">
                <input type="checkbox" :checked="scoringMatch.warning_b"
                       @change="router.patch(`/admin/poules/matchs/${scoringMatch.id}`, { ...scoringMatch, warning_b: $event.target.checked, status: scoringMatch.status }, { preserveScroll: true })"
                       style="width:auto;margin:0;" />
                Avertissement
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div style="display: flex; gap: 10px; padding-top: 20px; border-top: 1px solid var(--line);">
            <button class="btn" @click="closeScoring">Fermer</button>
            <button class="btn btn-felt" style="margin-left: auto; display:inline-flex; align-items:center; gap:6px;"
                    @click="closeMatch(scoringMatch)">
              <Trophy :size="13" /> Terminer &amp; saisir score final
            </button>
          </div>
        </div>
      </div>

      <!-- Modale : démarrer un match -->
      <div v-if="startingMatch" @click.self="cancelStart"
           style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center;
                  justify-content: center; z-index: 50;">
        <div style="background: var(--ink-2); border: 1px solid var(--felt-2); padding: 32px;
                    width: 520px; max-width: 90vw;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2); display:flex; align-items:center; gap:4px;"><Play :size="12" /> DÉMARRER LE MATCH</div>
          <div class="disp-a" style="font-size: 26px; margin-top: 8px;">
            {{ startingMatch.player_a?.first_name }} {{ startingMatch.player_a?.last_name }}
            <span style="color: var(--mute);">vs</span>
            {{ startingMatch.player_b?.first_name }} {{ startingMatch.player_b?.last_name }}
          </div>
          <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 8px; letter-spacing: 0.14em;">
            POULE {{ currentPool.name }} · RACE TO {{ competition.pool_race_to ?? competition.race_to }}
          </div>

          <div style="margin-top: 24px;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 10px;">TABLE</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
              <button v-for="t in tables" :key="t.id" type="button" @click="starter.pool_table_id = t.id" :disabled="t.status === 'maint'"
                :style="{
                  padding: '14px', cursor: t.status === 'maint' ? 'not-allowed' : 'pointer',
                  border: '1px solid ' + (starter.pool_table_id === t.id ? 'var(--felt-2)' : 'var(--line-strong)'),
                  background: starter.pool_table_id === t.id ? 'rgba(45,168,118,0.08)' : 'var(--ink)',
                  textAlign: 'left', opacity: t.status === 'maint' ? 0.4 : 1,
                }">
                <div class="disp-a" style="font-size: 18px;">{{ t.name }}</div>
                <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.14em; margin-top: 4px;">
                  {{ t.location?.toUpperCase() }} · {{ t.status === 'maint' ? 'MAINTENANCE' : t.status === 'live' ? '! OCCUPÉE' : 'LIBRE' }}
                </div>
              </button>
            </div>
          </div>

          <div style="margin-top: 22px;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 10px;">ARBITRE</div>
            <select v-model="starter.referee_id">
              <option :value="null">— sans arbitre —</option>
              <option v-for="r in referees" :key="r.id" :value="r.id">{{ r.name }}{{ r.title ? ' · ' + r.title : '' }}</option>
            </select>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 28px;">
            <button class="btn" @click="cancelStart">Annuler</button>
            <button class="btn btn-felt" style="margin-left: auto; display:inline-flex; align-items:center; gap:6px;" @click="launch" :disabled="!starter.pool_table_id">
              <Play :size="14" /> Lancer le match
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
