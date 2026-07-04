<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Play, Pause, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
  match:          Object,
  raceTo:         Number,
  extensionUsedA: Boolean,
  extensionUsedB: Boolean,
});

// ── Config compétition ─────────────────────────────────────
const competition    = computed(() => props.match.competition ?? {});
const shotEnabled    = computed(() => competition.value.shot_clock_enabled !== false);
const shotMax        = computed(() => competition.value.shot_clock ?? 30);
const shotFirst      = computed(() => competition.value.shot_clock_first_shot ?? shotMax.value);
const lateThreshold  = computed(() => competition.value.shot_clock_late_seconds ?? 15);
const extAllowed     = computed(() => competition.value.shot_clock_extensions_per_player ?? 0);

// ── État compteur ──────────────────────────────────────────
const shotClock    = ref(shotFirst.value);
const clockRunning = ref(false);
const matchTime    = ref(0);
let shotI  = null;
let matchI = null;

const startClock = () => {
  if (shotI || !shotEnabled.value) return;
  clockRunning.value = true;
  shotI = setInterval(() => {
    if (shotClock.value > 0) shotClock.value--;
  }, 1000);
};

const pauseClock = () => {
  clearInterval(shotI);
  shotI = null;
  clockRunning.value = false;
};

const resetClock = (startAfter = null) => {
  shotClock.value = shotMax.value;
  if (startAfter ?? clockRunning.value) {
    clearInterval(shotI);
    shotI = null;
    startClock();
  }
};

onMounted(() => {
  if (shotEnabled.value) startClock();
  matchI = setInterval(() => matchTime.value++, 1000);
});
onUnmounted(() => { clearInterval(shotI); clearInterval(matchI); });

// ── Score local ────────────────────────────────────────────
const localScore = ref({ a: props.match.score_a, b: props.match.score_b });

// ── Main (hand) ────────────────────────────────────────────
const currentHand = ref(null); // null | 'a' | 'b'

const setHand = (side) => {
  currentHand.value = side;
  resetClock(true); // toujours redémarrer après changement de main
};

const handName = computed(() => {
  if (currentHand.value === 'a') return props.match.player_a?.first_name ?? 'Joueur A';
  if (currentHand.value === 'b') return props.match.player_b?.first_name ?? 'Joueur B';
  return null;
});

// ── Extension ──────────────────────────────────────────────
const extUsedA   = ref(props.extensionUsedA);
const extUsedB   = ref(props.extensionUsedB);
const extLoading = ref(false);

const useExtension = (side) => {
  if (extLoading.value) return;
  extLoading.value = true;
  router.post(`/arbitre/match/${props.match.id}/extension`, { player: side.toUpperCase() }, {
    preserveScroll: true,
    preserveState:  true,
    onSuccess: () => {
      if (side === 'a') extUsedA.value = true;
      else              extUsedB.value = true;
      resetClock(true);
    },
    onFinish: () => { extLoading.value = false; },
  });
};

// ── Scoring ────────────────────────────────────────────────
const raceReached = computed(() =>
  localScore.value.a >= props.raceTo || localScore.value.b >= props.raceTo
);

const winFrame = (side) => {
  localScore.value[side]++;
  resetClock(true);
  router.post(`/arbitre/match/${props.match.id}/frame`, {
    winner: side.toUpperCase(),
  }, { preserveScroll: true, preserveState: true });
};

const undoFrame = (side) => {
  if (localScore.value[side] <= 0) return;
  localScore.value[side]--;
  router.post(`/arbitre/match/${props.match.id}/undo-frame`, {
    player: side.toUpperCase(),
  }, { preserveScroll: true, preserveState: true });
};

// ── Clôture ────────────────────────────────────────────────
const showConfirm  = ref(false);
const closePending = ref(false);

const confirmClose = () => {
  closePending.value = true;
  pauseClock();
  router.post(`/arbitre/match/${props.match.id}/clore`, {}, {
    onFinish: () => { closePending.value = false; },
  });
};

// ── Panel événements ───────────────────────────────────────
const eventsOpen   = ref(false);
const eventLoading = ref(false);
const toast        = ref(null);
let toastTimer     = null;

const showToast = (msg, isError = false) => {
  clearTimeout(toastTimer);
  toast.value = { msg, isError };
  toastTimer = setTimeout(() => { toast.value = null; }, 2500);
};

const sendEvent = async (type, player = null) => {
  if (eventLoading.value) return;
  eventLoading.value = true;
  const payload = { event_type: type, frame_number: localScore.value.a + localScore.value.b + 1 };
  if (player) payload.player = player;
  try {
    await window.axios.post(`/api/referee/matches/${props.match.id}/events`, payload);
    const labels = { foul: 'Faute', safety: 'Safety', miss: 'Tir raté',
                     break_and_run: 'Break & Run', re_rack: 'Re-rack',
                     shot_clock_violation: 'Violation horloge' };
    const pName = player === 'A' ? props.match.player_a?.first_name
                : player === 'B' ? props.match.player_b?.first_name : null;
    showToast(`✓ ${labels[type] ?? type}${pName ? ' · ' + pName : ''}`);
  } catch {
    showToast('Erreur — événement non enregistré', true);
  } finally {
    eventLoading.value = false;
  }
};

// ── Phase label ────────────────────────────────────────────
const phaseLabel = computed(() => {
  if (props.match.pool) return `Poule ${props.match.pool.name}`;
  const map = { R32: '1/32e', R16: '1/16e', QF: 'Quart', SF: 'Demi', F: 'Finale', '3P': '3e place' };
  return map[props.match.round] ?? props.match.round ?? '—';
});

const fmt = (s) => {
  const m = Math.floor(s / 60), sec = s % 60;
  return String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
};

const danger = computed(() => shotEnabled.value && shotClock.value <= 10);
const late   = computed(() => shotEnabled.value && shotClock.value <= lateThreshold.value);
</script>

<template>
  <Head title="Arbitrage du match" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">

    <!-- ── En-tête ─────────────────────────────────────────── -->
    <header style="padding: 8px 18px 12px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="/arbitre" style="font-size: 18px; color: var(--mute); line-height: 1;">←</a>
        <div style="text-align: center;">
          <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute);">
            {{ phaseLabel }} · {{ match.table?.name?.toUpperCase() ?? '—' }}
          </div>
          <div style="font-size: 13px; font-weight: 700; margin-top: 2px;">
            {{ match.competition?.name?.split(' — ')[0] }}
          </div>
        </div>
        <span class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.12em;">
          RACE À {{ raceTo }}
        </span>
      </div>
    </header>

    <!-- ── Compteur shot clock ─────────────────────────────── -->
    <div v-if="shotEnabled" :style="{
      padding: '12px 18px 10px',
      background: danger ? 'rgba(229,72,77,0.06)' : 'transparent',
      borderBottom: '1px solid var(--line)',
      transition: 'background .3s',
    }">
      <!-- Indicateur main -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
        <span class="mono" style="font-size: 9px; letter-spacing: 0.18em;"
              :style="{ color: handName ? 'var(--chalk-2)' : 'var(--mute)' }">
          {{ handName ? `MAIN : ${handName.toUpperCase()}` : 'MAIN NON DÉFINIE' }}
        </span>
        <span class="mono" :style="{
          fontSize: '9px', letterSpacing: '0.18em',
          color: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--mute)',
        }">{{ danger ? 'ATTENTION' : late ? 'LATE' : 'NORMAL' }}</span>
      </div>

      <!-- Grand compteur -->
      <div class="disp-a tnum" :style="{
        fontSize: '120px', lineHeight: 0.88, textAlign: 'center',
        color: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--chalk)',
        userSelect: 'none',
      }">{{ String(shotClock).padStart(2,'0') }}</div>

      <!-- Barre de progression -->
      <div style="height: 3px; background: var(--line); overflow: hidden; margin-top: 6px;">
        <div :style="{
          width: (shotClock / shotMax) * 100 + '%', height: '100%',
          background: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--felt-2)',
          transition: 'width .8s linear',
        }" />
      </div>

      <!-- Contrôles Play / Pause / Reset -->
      <div style="display: flex; gap: 8px; margin-top: 10px;">
        <button v-if="!clockRunning" @click="startClock"
                class="mono" style="flex: 1; min-height: 40px; border-radius: 2px; cursor: pointer;
                  background: rgba(45,168,118,0.12); border: 1px solid rgba(45,168,118,0.4);
                  color: var(--felt-2); font-size: 10px; letter-spacing: 0.14em;
                  display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
          <Play :size="12" /> PLAY
        </button>
        <button v-else @click="pauseClock"
                class="mono" style="flex: 1; min-height: 40px; border-radius: 2px; cursor: pointer;
                  background: rgba(229,72,77,0.1); border: 1px solid rgba(229,72,77,0.4);
                  color: var(--live); font-size: 10px; letter-spacing: 0.14em;
                  display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
          <Pause :size="12" /> PAUSE
        </button>
        <button @click="resetClock()"
                class="mono" style="min-height: 40px; padding: 0 16px; border-radius: 2px; cursor: pointer;
                  background: rgba(255,255,255,0.06); border: 1px solid var(--line-strong);
                  color: var(--chalk-2); font-size: 10px; letter-spacing: 0.14em;
                  display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
          <RotateCcw :size="12" /> RESET
        </button>
      </div>

      <!-- Temps match + manche -->
      <div style="display: flex; justify-content: space-between; margin-top: 8px;">
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.12em;">
          MANCHE {{ localScore.a + localScore.b + 1 }}
        </span>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.12em;">
          MATCH {{ fmt(matchTime) }}
        </span>
      </div>
    </div>

    <!-- Sans shot clock -->
    <div v-else style="padding: 10px 18px; border-bottom: 1px solid var(--line);
                        display: flex; justify-content: space-between;">
      <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.12em;">
        MANCHE {{ localScore.a + localScore.b + 1 }} · RACE À {{ raceTo }}
      </span>
      <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.12em;">
        MATCH {{ fmt(matchTime) }}
      </span>
    </div>

    <!-- ── Deux colonnes joueurs ──────────────────────────── -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; flex: 1;">

      <div v-for="(p, i) in [
        { player: match.player_a, score: localScore.a, side: 'a', extUsed: extUsedA },
        { player: match.player_b, score: localScore.b, side: 'b', extUsed: extUsedB },
      ]" :key="i" :style="{
        padding: '14px 12px',
        borderRight: i === 0 ? '1px solid var(--line)' : 'none',
        borderBottom: '1px solid var(--line)',
        display: 'flex', flexDirection: 'column', gap: '8px',
      }">
        <!-- Nom -->
        <div style="font-size: 13px; font-weight: 700; line-height: 1.2; min-height: 32px;">
          {{ p.player?.first_name }}<br />
          <span style="font-size: 11px; font-weight: 400; color: var(--chalk-2);">
            {{ p.player?.last_name }}
          </span>
        </div>

        <!-- Score -->
        <div class="disp-a tnum" :style="{
          fontSize: '72px', lineHeight: 0.9, textAlign: 'center',
          color: p.score > (i === 0 ? localScore.b : localScore.a) ? 'var(--felt-2)' : 'var(--chalk)',
        }">{{ p.score }}</div>

        <!-- + Manche -->
        <button @click="winFrame(p.side)"
                class="btn btn-felt" style="width: 100%; justify-content: center;
                  min-height: 48px; font-size: 13px; font-weight: 700;">
          + Manche
        </button>

        <!-- - Manche -->
        <button @click="undoFrame(p.side)" :disabled="p.score <= 0"
                class="btn" style="width: 100%; justify-content: center;
                  min-height: 40px; font-size: 12px;"
                :style="{ opacity: p.score <= 0 ? 0.35 : 1 }">
          − Manche
        </button>

        <!-- Main -->
        <button @click="setHand(p.side)"
                :class="['btn', currentHand === p.side ? 'btn-felt' : '']"
                :style="{
                  width: '100%', justifyContent: 'center', minHeight: '40px',
                  fontSize: '11px', fontFamily: 'var(--font-mono)', letterSpacing: '0.14em',
                  background: currentHand === p.side ? 'rgba(45,168,118,0.15)' : 'transparent',
                  borderColor: currentHand === p.side ? 'var(--felt-2)' : 'var(--line-strong)',
                  color: currentHand === p.side ? 'var(--felt-2)' : 'var(--chalk-2)',
                }">
          MAIN
        </button>

        <!-- Extension -->
        <button v-if="extAllowed >= 1"
                @click="useExtension(p.side)"
                :disabled="p.extUsed || extLoading"
                :style="{
                  width: '100%', minHeight: '36px',
                  padding: '6px', borderRadius: '2px', cursor: p.extUsed ? 'default' : 'pointer',
                  background: p.extUsed ? 'rgba(255,255,255,0.04)' : 'rgba(255,190,0,0.08)',
                  border: '1px solid ' + (p.extUsed ? 'var(--line)' : 'rgba(255,190,0,0.4)'),
                  color: p.extUsed ? 'var(--mute)' : 'rgba(255,190,0,0.9)',
                  fontSize: '9px', fontFamily: 'var(--font-mono)', letterSpacing: '0.14em',
                  opacity: p.extUsed ? 0.5 : 1,
                }">
          {{ p.extUsed ? 'EXT. UTILISÉE' : 'EXTENSION' }}
        </button>
      </div>
    </div>

    <!-- ── Panel événements (masqué par défaut) ──────────── -->
    <div style="padding: 10px 16px; border-top: 1px solid var(--line); background: var(--ink-2);">
      <button @click="eventsOpen = !eventsOpen"
              class="mono" style="width: 100%; min-height: 38px; border-radius: 2px; cursor: pointer;
                background: transparent; border: 1px solid var(--line);
                color: var(--mute); font-size: 9px; letter-spacing: 0.18em;
                display: flex; justify-content: space-between; align-items: center; padding: 0 12px;">
        <span>ÉVÉNEMENTS (FOUL · SAFETY · …)</span>
        <span>{{ eventsOpen ? '▲' : '▼' }}</span>
      </button>

      <div v-if="eventsOpen" style="margin-top: 8px; border: 1px solid var(--line); border-radius: 2px;
                                     overflow: hidden; background: var(--ink);">
        <!-- Toast -->
        <div v-if="toast" :style="{
          padding: '8px 14px', fontSize: '11px', fontFamily: 'var(--font-mono)',
          textAlign: 'center', letterSpacing: '0.1em',
          background: toast.isError ? 'rgba(229,72,77,0.12)' : 'rgba(45,168,118,0.12)',
          color: toast.isError ? 'var(--live)' : 'var(--felt-2)',
          borderBottom: '1px solid var(--line)',
        }">{{ toast.msg }}</div>

        <!-- Événements par joueur -->
        <div v-for="ev in [
          { type: 'foul',   label: 'FAUTE' },
          { type: 'safety', label: 'SAFETY' },
          { type: 'miss',   label: 'TIR RATÉ' },
          { type: 'shot_clock_violation', label: 'VIOLATION HORLOGE' },
        ]" :key="ev.type"
             style="display: grid; grid-template-columns: 1fr auto auto; align-items: center;
                    border-bottom: 1px solid var(--line); padding: 8px 12px; gap: 6px;">
          <span class="mono" style="font-size: 10px; color: var(--chalk-2); letter-spacing: 0.1em;">{{ ev.label }}</span>
          <button @click="sendEvent(ev.type, 'A')" :disabled="eventLoading"
                  style="min-height: 40px; min-width: 52px; background: rgba(45,168,118,0.08);
                         border: 1px solid rgba(45,168,118,0.3); color: var(--felt-2);
                         font-size: 10px; font-weight: 700; border-radius: 2px; cursor: pointer;
                         font-family: var(--font-mono);">
            {{ match.player_a?.first_name?.slice(0,3)?.toUpperCase() ?? 'A' }}
          </button>
          <button @click="sendEvent(ev.type, 'B')" :disabled="eventLoading"
                  style="min-height: 40px; min-width: 52px; background: rgba(255,255,255,0.04);
                         border: 1px solid var(--line-strong); color: var(--chalk-2);
                         font-size: 10px; font-weight: 700; border-radius: 2px; cursor: pointer;
                         font-family: var(--font-mono);">
            {{ match.player_b?.first_name?.slice(0,3)?.toUpperCase() ?? 'B' }}
          </button>
        </div>

        <!-- Break & Run + Re-rack -->
        <div style="display: grid; grid-template-columns: 1fr auto auto; align-items: center;
                    border-bottom: 1px solid var(--line); padding: 8px 12px; gap: 6px;">
          <span class="mono" style="font-size: 10px; color: var(--chalk-2); letter-spacing: 0.1em;">BREAK &amp; RUN</span>
          <button @click="sendEvent('break_and_run','A')" :disabled="eventLoading"
                  style="min-height: 40px; min-width: 52px; background: rgba(45,168,118,0.08);
                         border: 1px solid rgba(45,168,118,0.3); color: var(--felt-2);
                         font-size: 10px; font-weight: 700; border-radius: 2px; cursor: pointer;
                         font-family: var(--font-mono);">
            {{ match.player_a?.first_name?.slice(0,3)?.toUpperCase() ?? 'A' }}
          </button>
          <button @click="sendEvent('break_and_run','B')" :disabled="eventLoading"
                  style="min-height: 40px; min-width: 52px; background: rgba(255,255,255,0.04);
                         border: 1px solid var(--line-strong); color: var(--chalk-2);
                         font-size: 10px; font-weight: 700; border-radius: 2px; cursor: pointer;
                         font-family: var(--font-mono);">
            {{ match.player_b?.first_name?.slice(0,3)?.toUpperCase() ?? 'B' }}
          </button>
        </div>
        <div style="padding: 8px 12px;">
          <button @click="sendEvent('re_rack')" :disabled="eventLoading"
                  style="width: 100%; min-height: 40px; background: rgba(255,255,255,0.04);
                         border: 1px solid var(--line-strong); color: var(--chalk-2); font-size: 10px;
                         font-weight: 700; border-radius: 2px; cursor: pointer;
                         font-family: var(--font-mono); letter-spacing: 0.14em;">
            RE-RACK
          </button>
        </div>
      </div>
    </div>

    <!-- ── Clôture du match ────────────────────────────────── -->
    <div style="padding: 12px 16px; border-top: 1px solid var(--line); background: var(--ink-2);">

      <!-- Confirmation -->
      <template v-if="showConfirm">
        <div class="mono" style="font-size: 11px; color: var(--chalk-2); text-align: center;
                                   margin-bottom: 10px; letter-spacing: 0.12em;">
          CONFIRMER LA CLÔTURE DU MATCH ?
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <button @click="confirmClose" :disabled="closePending"
                  class="btn btn-felt" style="justify-content: center; min-height: 48px;
                    border-color: var(--felt-2); color: var(--felt-2); font-weight: 700;">
            {{ closePending ? 'Clôture…' : 'Confirmer' }}
          </button>
          <button @click="showConfirm = false"
                  class="btn" style="justify-content: center; min-height: 48px;">
            Annuler
          </button>
        </div>
      </template>

      <!-- Bouton clôture (si race atteint) -->
      <template v-else-if="raceReached">
        <button @click="showConfirm = true"
                class="btn" style="width: 100%; justify-content: center; min-height: 52px;
                  font-size: 14px; font-weight: 700; border-color: var(--felt-2);
                  color: var(--felt-2); background: rgba(45,168,118,0.08);">
          Clôturer le match
        </button>
      </template>

      <!-- Race non atteint -->
      <template v-else>
        <div class="mono" style="text-align: center; font-size: 10px; color: var(--mute);
                                   letter-spacing: 0.14em; padding: 8px 0;">
          CLÔTURE DISPONIBLE AU RACE À {{ raceTo }}
          <span style="display: block; font-size: 18px; margin-top: 4px; font-family: var(--font-display-a);
                       color: var(--chalk-2);">
            {{ localScore.a }} — {{ localScore.b }}
          </span>
        </div>
      </template>
    </div>
  </div>
</template>
