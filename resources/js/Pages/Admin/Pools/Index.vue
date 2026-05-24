<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import PoolStandings from '@/Components/PoolStandings.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  pools: Array,
});

const selectedPool = ref(props.pools[0]?.id);
const editingMatch = ref(null);

const editor = ref({ score_a: 0, score_b: 0, is_draw: false, warning_a: false, warning_b: false, note: '' });

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
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">PHASE DE POULES</div>
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
                  <span v-if="m.warning_a || m.warning_b" style="color: var(--live); margin-left: 8px;">⚠</span>
                </td>
                <td style="text-align: right;">
                  <button class="btn" style="padding: 4px 10px; font-size: 11px;" @click="startEdit(m)">
                    {{ m.status === 'done' ? 'Modifier' : 'Saisir' }}
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
              <input v-model.number="editor.score_a" type="number" min="0" :max="competition.race_to" />
            </label>
            <label>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SCORE {{ editingMatch.player_b?.first_name?.toUpperCase() }}</div>
              <input v-model.number="editor.score_b" type="number" min="0" :max="competition.race_to" />
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
    </main>
  </div>
</template>
