<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  qualifiers: Object,    // { A: [...], B: [...], C: [...], D: [...] }
  ties: Array,           // [{ pool, rank, players: [...] }]
  pairs: Array,          // [[playerObj|null, playerObj|null], ...]
  existing: Object,      // { R16: [...], QF: [...], SF: [...], F: [...] }
  progress: Object,      // { pool_done, pool_total, pool_ready }
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
    return [
      [A[0], B[3]], [C[0], D[3]], [A[1], B[2]], [C[1], D[2]],
      [B[0], A[3]], [D[0], C[3]], [B[1], A[2]], [D[1], C[2]],
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
  router.post('/admin/phase-finale', { pairs: localPairs.value }, {
    onFinish: () => { submitting.value = false; },
  });
};

const labelOf = (q) => q ? `${q.pool_name}${q.pool_slot}` : '—';
const roundLabel = (r) => ({ R16: '8e de finale', QF: 'Quart de finale', SF: 'Demi-finale', F: 'Finale' }[r] ?? r);
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
      <section v-if="hasExisting" style="margin: 24px 32px; padding: 16px 20px;
           border: 1px solid var(--felt-2); background: rgba(45,168,118,0.04);">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2);">BRACKET ACTUEL</div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 14px;">
          <div v-for="round in ['R16', 'QF', 'SF', 'F']" :key="round">
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">
              {{ roundLabel(round) }}
            </div>
            <div class="disp-a tnum" style="font-size: 28px; margin-top: 6px;">
              {{ (existing[round] || []).length }}<span style="color: var(--mute); font-size: 14px;"> match{{ (existing[round] || []).length > 1 ? 'es' : '' }}</span>
            </div>
          </div>
        </div>
        <p style="font-size: 12px; color: var(--mute); margin-top: 12px;">
          Le bracket a déjà été généré. Régénérer écrasera tous les matchs knockout (les scores
          existants seront perdus). Tu peux aussi continuer à jouer le bracket actuel via
          /admin/poules ou directement dans les matchs.
        </p>
      </section>

      <!-- Qualifiés -->
      <section style="padding: 24px 32px;">
        <h3 class="disp-a" style="font-size: 24px; margin-bottom: 14px;">Qualifiés par poule</h3>
        <div v-if="ties.length" style="margin-bottom: 18px; padding: 12px 16px;
             border: 1px solid var(--line-strong); background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--live);">
            ⚠ {{ ties.length }} EX-AEQUO À TRANCHER
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

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
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
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
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
          {{ submitting ? 'Création…' : (hasExisting ? '↻ Régénérer le bracket' : '✓ Générer le bracket') }}
        </button>
      </section>
    </main>
  </div>
</template>
