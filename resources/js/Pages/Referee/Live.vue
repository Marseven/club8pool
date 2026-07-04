<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Pause } from 'lucide-vue-next';

const props = defineProps({ match: Object });

// ── Shot clock config ──────────────────────────────────────
const competition = computed(() => props.match.competition ?? {});
const shotClockEnabled  = computed(() => competition.value.shot_clock_enabled !== false);
const shotClockMax      = computed(() => competition.value.shot_clock ?? 30);
const shotClockFirstShot = computed(() => competition.value.shot_clock_first_shot ?? shotClockMax.value);
const lateThreshold     = computed(() => competition.value.shot_clock_late_seconds ?? 15);

const shotClock  = ref(shotClockFirstShot.value);
const matchTime  = ref(0);
const localScore = ref({ a: props.match.score_a, b: props.match.score_b });

const resetShotClock = () => { shotClock.value = shotClockMax.value; };

let shotI, matchI;
onMounted(() => {
  if (shotClockEnabled.value) {
    shotI = setInterval(() => {
      shotClock.value = shotClock.value <= 0 ? shotClockMax.value : shotClock.value - 1;
    }, 1000);
  }
  matchI = setInterval(() => matchTime.value++, 1000);
});
onUnmounted(() => { clearInterval(shotI); clearInterval(matchI); });

const fmt = (s) => {
  const m = Math.floor(s / 60), sec = s % 60;
  return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
};

const danger = computed(() => shotClockEnabled.value && shotClock.value <= 10);
const late   = computed(() => shotClockEnabled.value && shotClock.value <= lateThreshold.value);

// ── Frame scoring ──────────────────────────────────────────
const winFrame = (side) => {
  localScore.value[side]++;
  resetShotClock();
  router.post(`/arbitre/match/${props.match.id}/frame`, {
    winner: side === 'a' ? 'A' : 'B',
  }, { preserveScroll: true, preserveState: true });
};

// ── Pro Events panel ───────────────────────────────────────
const eventsOpen    = ref(false);
const toast         = ref(null);
const toastTimer    = ref(null);
const eventLoading  = ref(false);

const showToast = (msg, isError = false) => {
  clearTimeout(toastTimer.value);
  toast.value = { msg, isError };
  toastTimer.value = setTimeout(() => { toast.value = null; }, 2000);
};

const currentFrame = computed(() => localScore.value.a + localScore.value.b + 1);

const sendEvent = async (event_type, player = null) => {
  if (eventLoading.value) return;
  eventLoading.value = true;

  const payload = { event_type, frame_number: currentFrame.value };
  if (player) payload.player = player;

  try {
    await window.axios.post(`/api/referee/matches/${props.match.id}/events`, payload);

    const labels = {
      foul:                  'Foul',
      safety:                'Safety',
      miss:                  'Missed shot',
      shot_clock_extension:  'Extension',
      shot_clock_violation:  'Clock violation',
      break_and_run:         'Break & Run',
      re_rack:               'Re-rack',
    };
    const playerLabel = player ? ` · ${player === 'a' ? props.match.player_a?.last_name : props.match.player_b?.last_name}` : '';
    showToast(`✓ ${labels[event_type] ?? event_type}${playerLabel}`);
  } catch (e) {
    showToast('Error — could not record event', true);
  } finally {
    eventLoading.value = false;
  }
};

// Per-player events that need A or B
const perPlayerEvents = computed(() => {
  const base = [
    { type: 'foul',   label: 'FOUL' },
    { type: 'safety', label: 'SAFETY' },
    { type: 'miss',   label: 'MISS' },
  ];
  if (shotClockEnabled.value) {
    base.push({ type: 'shot_clock_extension', label: 'EXTENSION' });
    base.push({ type: 'shot_clock_violation', label: 'CLK VIOLATION' });
  }
  return base;
});
</script>

<template>
  <Head title="Match en cours" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">

    <!-- Header -->
    <header style="padding: 8px 18px 14px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <a href="/arbitre" style="font-size: 18px; color: var(--mute);">←</a>
      <div>
        <div class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute);">
          {{ match.round }} · {{ match.table?.name?.toUpperCase() }}
        </div>
        <div style="font-size: 13px; font-weight: 700;">{{ match.competition?.name?.split(' — ')[0] }}</div>
      </div>
      <span class="mono" style="padding: 3px 7px; border: 1px solid rgba(45,168,118,0.4);
                                background: rgba(45,168,118,0.08); border-radius: 2px;
                                font-size: 9px; color: var(--felt-2); letter-spacing: 0.14em;
                                display:inline-flex; align-items:center; gap:4px;">
        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0;"></span>EN LIGNE
      </span>
    </header>

    <!-- Shot clock (hidden if disabled) -->
    <div v-if="shotClockEnabled" :style="{
      padding: '16px 18px 8px',
      background: danger ? 'rgba(229,72,77,0.06)' : 'transparent',
      borderBottom: '1px solid var(--line)',
      transition: 'background .3s',
    }">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span class="mono" style="font-size: 9px; letter-spacing: 0.2em; color: var(--mute);">SHOT CLOCK</span>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="mono" :style="{ fontSize: '9px', letterSpacing: '0.18em',
                color: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--mute)' }">
            {{ danger ? 'WARNING' : late ? 'LATE' : 'NORMAL' }}
          </span>
          <!-- RESET button -->
          <button @click="resetShotClock"
                  class="mono"
                  style="background: rgba(255,255,255,0.06); border: 1px solid var(--line-strong);
                         color: var(--chalk-2); font-size: 9px; letter-spacing: 0.14em;
                         padding: 2px 7px; border-radius: 2px; cursor: pointer; line-height: 1.6;">
            RESET
          </button>
        </div>
      </div>
      <!-- Tappable clock display also resets -->
      <div class="disp-a tnum"
           @click="resetShotClock"
           :style="{
             fontSize: '124px', lineHeight: 0.9, textAlign: 'center', marginTop: '4px',
             color: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--chalk)',
             cursor: 'pointer', userSelect: 'none',
           }">{{ String(shotClock).padStart(2, '0') }}</div>
      <div style="height: 4px; background: var(--line); overflow: hidden;">
        <div :style="{
          width: (shotClock / shotClockMax) * 100 + '%', height: '100%',
          background: danger ? 'var(--live)' : late ? 'var(--chalk-2)' : 'var(--felt-2)',
          transition: 'width .8s linear',
        }" />
      </div>
      <div style="display: flex; justify-content: space-between; margin-top: 8px;">
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">
          FRAME {{ localScore.a + localScore.b + 1 }} · RACE TO {{ match.competition?.race_to }}
        </span>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">MATCH {{ fmt(matchTime) }}</span>
      </div>
    </div>

    <!-- Frame + match time row when shot clock is hidden -->
    <div v-else style="padding: 10px 18px; border-bottom: 1px solid var(--line);
                        display: flex; justify-content: space-between;">
      <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">
        FRAME {{ localScore.a + localScore.b + 1 }} · RACE TO {{ match.competition?.race_to }}
      </span>
      <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">MATCH {{ fmt(matchTime) }}</span>
    </div>

    <!-- Scores -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid var(--line);">
      <div v-for="(p, i) in [
        { player: match.player_a, score: localScore.a, side: 'a' },
        { player: match.player_b, score: localScore.b, side: 'b' },
      ]" :key="i" :style="{ padding: '18px 16px 16px',
            borderRight: i === 0 ? '1px solid var(--line)' : 'none' }">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="mono" style="font-size: 9px; color: var(--mute);">SEED #{{ p.player?.id }}</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; line-height: 1.1; min-height: 28px;">
          {{ p.player?.first_name }}<br />{{ p.player?.last_name }}
        </div>
        <div class="disp-a tnum" :style="{ fontSize: '80px', lineHeight: 0.9, marginTop: '4px',
              color: p.score > (i === 0 ? localScore.b : localScore.a) ? 'var(--felt-2)' : 'var(--chalk)' }">{{ p.score }}</div>
      </div>
    </div>

    <!-- Frame scoring buttons -->
    <div style="padding: 14px 16px; display: flex; flex-direction: column; gap: 10px;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <button class="btn btn-felt" style="padding: 16px 12px; justify-content: center; min-height: 52px;"
                @click="winFrame('a')">+ Frame · {{ match.player_a?.last_name }}</button>
        <button class="btn" style="padding: 16px 12px; justify-content: center; min-height: 52px;"
                @click="winFrame('b')">+ Frame · {{ match.player_b?.last_name }}</button>
      </div>

      <!-- EVENTS toggle button -->
      <button @click="eventsOpen = !eventsOpen"
              class="mono"
              :style="{
                background: eventsOpen ? 'rgba(255,255,255,0.06)' : 'transparent',
                border: '1px solid ' + (eventsOpen ? 'var(--line-strong)' : 'var(--line)'),
                color: eventsOpen ? 'var(--chalk)' : 'var(--mute)',
                fontSize: '10px', letterSpacing: '0.18em', padding: '9px 12px',
                borderRadius: '2px', cursor: 'pointer', width: '100%',
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                minHeight: '44px',
              }">
        <span>EVENTS</span>
        <span style="opacity: 0.6; font-size: 11px;">{{ eventsOpen ? '▲' : '▼' }}</span>
      </button>

      <!-- Pro Events panel -->
      <div v-if="eventsOpen"
           style="border: 1px solid var(--line); border-radius: 2px; overflow: hidden; background: var(--ink-2);">

        <!-- Toast notification -->
        <div v-if="toast"
             :style="{
               padding: '8px 14px', fontSize: '11px', fontFamily: 'var(--font-mono)',
               letterSpacing: '0.1em', textAlign: 'center',
               background: toast.isError ? 'rgba(229,72,77,0.12)' : 'rgba(45,168,118,0.12)',
               color: toast.isError ? 'var(--live)' : 'var(--felt-2)',
               borderBottom: '1px solid var(--line)',
             }">
          {{ toast.msg }}
        </div>

        <!-- Per-player events header -->
        <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;
                                  padding: 10px 14px 6px; border-bottom: 1px solid var(--line);">
          PER PLAYER — SELECT A OR B
        </div>

        <div v-for="ev in perPlayerEvents" :key="ev.type"
             style="display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 0;
                    border-bottom: 1px solid var(--line); padding: 8px 12px;">
          <span class="mono" style="font-size: 10px; color: var(--chalk-2); letter-spacing: 0.1em;">{{ ev.label }}</span>
          <button @click="sendEvent(ev.type, 'a')" :disabled="eventLoading"
                  style="min-height: 44px; min-width: 56px; padding: 8px 10px; margin-left: 8px;
                         background: rgba(45,168,118,0.08); border: 1px solid rgba(45,168,118,0.3);
                         color: var(--felt-2); font-size: 11px; font-weight: 700;
                         border-radius: 2px; cursor: pointer; font-family: var(--font-mono);
                         opacity: eventLoading ? 0.5 : 1;">
            {{ match.player_a?.last_name?.slice(0, 3)?.toUpperCase() ?? 'A' }}
          </button>
          <button @click="sendEvent(ev.type, 'b')" :disabled="eventLoading"
                  style="min-height: 44px; min-width: 56px; padding: 8px 10px; margin-left: 6px;
                         background: rgba(255,255,255,0.04); border: 1px solid var(--line-strong);
                         color: var(--chalk-2); font-size: 11px; font-weight: 700;
                         border-radius: 2px; cursor: pointer; font-family: var(--font-mono);
                         opacity: eventLoading ? 0.5 : 1;">
            {{ match.player_b?.last_name?.slice(0, 3)?.toUpperCase() ?? 'B' }}
          </button>
        </div>

        <!-- Match-level events -->
        <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;
                                  padding: 10px 14px 6px; border-bottom: 1px solid var(--line);">
          MATCH EVENTS
        </div>

        <!-- Break & Run — needs player selection -->
        <div style="display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 0;
                    border-bottom: 1px solid var(--line); padding: 8px 12px;">
          <span class="mono" style="font-size: 10px; color: var(--chalk-2); letter-spacing: 0.1em;">BREAK &amp; RUN</span>
          <button @click="sendEvent('break_and_run', 'a')" :disabled="eventLoading"
                  style="min-height: 44px; min-width: 56px; padding: 8px 10px; margin-left: 8px;
                         background: rgba(45,168,118,0.08); border: 1px solid rgba(45,168,118,0.3);
                         color: var(--felt-2); font-size: 11px; font-weight: 700;
                         border-radius: 2px; cursor: pointer; font-family: var(--font-mono);">
            {{ match.player_a?.last_name?.slice(0, 3)?.toUpperCase() ?? 'A' }}
          </button>
          <button @click="sendEvent('break_and_run', 'b')" :disabled="eventLoading"
                  style="min-height: 44px; min-width: 56px; padding: 8px 10px; margin-left: 6px;
                         background: rgba(255,255,255,0.04); border: 1px solid var(--line-strong);
                         color: var(--chalk-2); font-size: 11px; font-weight: 700;
                         border-radius: 2px; cursor: pointer; font-family: var(--font-mono);">
            {{ match.player_b?.last_name?.slice(0, 3)?.toUpperCase() ?? 'B' }}
          </button>
        </div>

        <!-- Re-rack — no player -->
        <div style="padding: 8px 12px;">
          <button @click="sendEvent('re_rack')" :disabled="eventLoading"
                  style="min-height: 44px; width: 100%; padding: 10px 12px;
                         background: rgba(255,255,255,0.04); border: 1px solid var(--line-strong);
                         color: var(--chalk-2); font-size: 10px; font-weight: 700;
                         border-radius: 2px; cursor: pointer; font-family: var(--font-mono);
                         letter-spacing: 0.14em;">
            RE-RACK
          </button>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div style="margin-top: auto; padding: 12px 16px; border-top: 1px solid var(--line);
                display: flex; justify-content: space-between; align-items: center; background: var(--ink-2);">
      <button class="mono" style="background: transparent; border: none; color: var(--mute);
                                   font-size: 11px; letter-spacing: 0.14em; cursor: pointer;
                                   display:inline-flex; align-items:center; gap:4px;">
        <Pause :size="14" /> PAUSE
      </button>
      <a :href="`/arbitre/match/${match.id}/fin`" class="btn" style="border-color: var(--felt-2); color: var(--felt-2);">
        FIN DE MATCH
      </a>
    </div>
  </div>
</template>
