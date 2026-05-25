<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Ball8 from '@/Components/Ball8.vue';
import Chip from '@/Components/Chip.vue';
import { Play, Pause, Check } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  players: Array,
});

const drawn = ref(0);
const paused = ref(false);
let interval;

onMounted(() => {
  interval = setInterval(() => {
    if (paused.value) return;
    if (drawn.value < props.players.length) drawn.value++;
  }, 1800);
});

onUnmounted(() => clearInterval(interval));

const current = computed(() => props.players[drawn.value] ?? null);

const pairings = computed(() => {
  const pairs = [
    [0, 15], [7, 8], [3, 12], [4, 11],
    [1, 14], [6, 9], [2, 13], [5, 10],
  ];
  return pairs;
});

const commit = () => {
  const data = pairings.value.map(([a, b]) => [props.players[a]?.id, props.players[b]?.id]);
  router.post('/admin/tirage', { pairings: data });
};

const reset = () => { drawn.value = 0; };
</script>

<template>
  <Head title="Tirage au sort" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="draw" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">
            TIRAGE AU SORT · {{ competition.name?.toUpperCase() }}
          </div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">Phase 1/8 · 8 matchs à composer</div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <Chip variant="live">EN COURS · {{ drawn }}/{{ players.length }}</Chip>
          <button class="btn" @click="paused = !paused" style="display:inline-flex; align-items:center; gap:4px;">
            <template v-if="paused"><Play :size="12" /> Reprendre</template>
            <template v-else><Pause :size="12" /> Pause</template>
          </button>
          <button class="btn" @click="reset">⟲ Re-tirer</button>
        </div>
      </header>

      <section style="display: grid; grid-template-columns: 300px 1fr 380px; min-height: calc(100vh - 86px);">
        <aside style="padding: 24px; border-right: 1px solid var(--line); background: var(--ink-2);">
          <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
            <h4 class="disp-a" style="font-size: 20px;">Pot · {{ players.length }} joueurs</h4>
            <span class="mono tnum" style="font-size: 11px; color: var(--felt-2);">{{ players.length - drawn }} REST.</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: 4px;">
            <div v-for="(p, i) in players" :key="p.id" :style="{
              display: 'flex', alignItems: 'center', gap: '10px', padding: '8px 10px',
              background: i < drawn ? 'transparent' : 'var(--ink)',
              border: '1px solid ' + (i < drawn ? 'transparent' : 'var(--line-strong)'),
              opacity: i < drawn ? 0.35 : 1,
              transition: 'all .3s',
            }">
              <span class="mono tnum" style="font-size: 11px; color: var(--mute); width: 24px;">{{ String(p.id).padStart(2,'0') }}</span>
              <span style="font-size: 12px; font-weight: 600; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ p.name }}
              </span>
              <Check v-if="i < drawn" :size="9" style="color: var(--felt-2);" />
              <span v-else class="mono tnum" style="font-size: 10px; color: var(--mute);">{{ p.rating }}</span>
            </div>
          </div>
        </aside>

        <div style="padding: 36px; display: flex; flex-direction: column; gap: 32px; align-items: center; justify-content: center;">
          <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">JOUEUR EN COURS</div>
          <div style="border: 1px solid var(--felt-2); background: rgba(45,168,118,0.06);
                      padding: 32px 48px; text-align: center; min-width: 380px;">
            <Ball8 :size="48" :mono="true" />
            <div class="disp-a" style="font-size: 48px; margin-top: 18px; line-height: 0.9;">
              {{ current ? current.name : 'TIRAGE TERMINÉ' }}
            </div>
            <div v-if="current" class="mono" style="font-size: 12px; color: var(--mute); margin-top: 12px; letter-spacing: 0.14em;">
              ELO {{ current.rating }} · {{ current.club?.toUpperCase() }}
            </div>
          </div>
          <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; max-width: 600px;">
            <div v-for="i in 8" :key="i" :style="{
              width: '64px', height: '64px', borderRadius: '50%',
              background: i - 1 < Math.floor(drawn / 2) ? 'var(--ink-3)' : 'transparent',
              border: '1px solid ' + (i - 1 === Math.floor(drawn / 2) ? 'var(--felt-2)' : 'var(--line-strong)'),
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontFamily: 'var(--font-mono)', fontSize: '12px', fontWeight: 700,
              color: i - 1 < Math.floor(drawn / 2) ? 'var(--mute)' : i - 1 === Math.floor(drawn / 2) ? 'var(--felt-2)' : 'var(--mute-2)',
              transition: 'all .4s',
            }">M{{ i }}</div>
          </div>
        </div>

        <aside style="padding: 24px; border-left: 1px solid var(--line);">
          <h4 class="disp-a" style="font-size: 20px; margin-bottom: 16px;">Tableau en cours</h4>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <div v-for="(pair, i) in pairings" :key="i" :style="{
              border: '1px solid ' + (drawn >= i * 2 + 2 ? 'var(--line)' : 'var(--line-strong)'),
              background: drawn >= i * 2 + 2 ? 'var(--ink-2)' : 'var(--ink)',
            }">
              <div v-for="(seat, j) in pair" :key="j" :style="{
                padding: '8px 12px',
                borderTop: j === 1 ? '1px solid var(--line)' : 'none',
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                background: (i * 2 + j) === drawn - 1 ? 'rgba(45,168,118,0.08)' : 'transparent',
              }">
                <span :style="{ fontSize: '12px', fontWeight: 600,
                      color: drawn > i * 2 + j ? 'var(--chalk)' : 'var(--mute-2)' }">
                  {{ drawn > i * 2 + j ? players[seat]?.name : '— à tirer —' }}
                </span>
                <span v-if="drawn > i * 2 + j" class="mono" style="font-size: 9px; color: var(--mute);">#{{ players[seat]?.id }}</span>
              </div>
            </div>
          </div>
          <button class="btn btn-felt" style="width: 100%; margin-top: 20px;" :disabled="drawn < players.length" @click="commit">
            {{ drawn < players.length ? `${drawn}/${players.length} placés` : 'Valider le tableau →' }}
          </button>
        </aside>
      </section>
    </main>
  </div>
</template>
