<script setup>
import { computed } from 'vue';
import BracketCard from './BracketCard.vue';
import Ball8 from './Ball8.vue';

const props = defineProps({ matches: Object });

// ── Layout ────────────────────────────────────────────────────────────────────
const MW   = 190;      // card width
const MH   = 94;       // card height (32+1+32+1+28)
const GX   = 48;       // connector gap
const CX   = MW + GX; // column pitch = 238
const UNIT = 110;      // vertical spacing per R16 slot

// ── Data slices ───────────────────────────────────────────────────────────────
const r16L  = computed(() => (props.matches?.R16 ?? []).slice(0, 4));
const r16R  = computed(() => (props.matches?.R16 ?? []).slice(4, 8));
const qfL   = computed(() => (props.matches?.QF  ?? []).slice(0, 2));
const qfR   = computed(() => (props.matches?.QF  ?? []).slice(2, 4));
const sfL   = computed(() => (props.matches?.SF  ?? [])[0] ?? null);
const sfR   = computed(() => (props.matches?.SF  ?? [])[1] ?? null);
const fin   = computed(() => (props.matches?.F   ?? [])[0] ?? null);
const third = computed(() => (props.matches?.['3P'] ?? [])[0] ?? null);

// ── Position helpers ──────────────────────────────────────────────────────────
const cx    = (col)  => col * CX;
const r16Y  = (i)    => i * UNIT;
const r16CY = (i)    => r16Y(i) + MH / 2;
const qfCY  = (pair) => (r16CY(pair * 2) + r16CY(pair * 2 + 1)) / 2;
const qfY   = (pair) => qfCY(pair) - MH / 2;
const sfCY  = ()     => (qfCY(0) + qfCY(1)) / 2;
const sfY   = ()     => sfCY() - MH / 2;

const SVG_W = computed(() => 6 * CX + MW);
const SVG_H = computed(() => 3 * UNIT + MH + 20);

// ── Winner ────────────────────────────────────────────────────────────────────
const winner = computed(() => {
  const m = fin.value;
  if (!m || m.status !== 'done') return null;
  const p = m.score_a > m.score_b ? m.player_a : m.player_b;
  if (!p) return null;
  return p.first_name + (p.last_name?.trim() ? ' ' + p.last_name : '');
});

// ── SVG connector paths ───────────────────────────────────────────────────────
const connPath = computed(() => {
  const h = GX / 2;
  const p = [];

  // Left arm: from right edge, elbow right
  const bL = (rX, y1, y2, tY) => {
    const mX = rX + h;
    p.push(`M${rX},${y1}H${mX}`, `M${rX},${y2}H${mX}`, `M${mX},${y1}V${y2}`, `M${mX},${tY}H${rX + GX}`);
  };
  // Right arm: from left edge, elbow left
  const bR = (lX, y1, y2, tY) => {
    const mX = lX - h;
    p.push(`M${lX},${y1}H${mX}`, `M${lX},${y2}H${mX}`, `M${mX},${y1}V${y2}`, `M${mX},${tY}H${lX - GX}`);
  };

  // Left half
  bL(cx(0) + MW, r16CY(0), r16CY(1), qfCY(0));
  bL(cx(0) + MW, r16CY(2), r16CY(3), qfCY(1));
  bL(cx(1) + MW, qfCY(0),  qfCY(1),  sfCY());
  p.push(`M${cx(2) + MW},${sfCY()}H${cx(3)}`);

  // Right half
  bR(cx(6),   r16CY(0), r16CY(1), qfCY(0));
  bR(cx(6),   r16CY(2), r16CY(3), qfCY(1));
  bR(cx(5),   qfCY(0),  qfCY(1),  sfCY());
  p.push(`M${cx(4)},${sfCY()}H${cx(3) + MW}`);

  return p.join(' ');
});

// ── Column headers ────────────────────────────────────────────────────────────
const COL_LABELS = [
  { col: 0, label: '8ES DE FINALE', sub: 'RACE TO 9'  },
  { col: 1, label: 'QUARTS',        sub: 'RACE TO 9'  },
  { col: 2, label: 'DEMI-FINALES',  sub: 'RACE TO 9'  },
  { col: 3, label: 'FINALE',        sub: 'RACE TO 11' },
  { col: 4, label: 'DEMI-FINALES',  sub: 'RACE TO 9'  },
  { col: 5, label: 'QUARTS',        sub: 'RACE TO 9'  },
  { col: 6, label: '8ES DE FINALE', sub: 'RACE TO 9'  },
];
</script>

<template>
  <div style="overflow-x: auto; padding: 20px 0 28px;">

    <!-- Empty state -->
    <div v-if="!matches?.R16?.length && !matches?.QF?.length"
         style="padding: 60px 24px; text-align: center; font-family: var(--font-mono);
                font-size: 11px; letter-spacing: .18em; color: var(--mute);">
      PHASE FINALE NON GÉNÉRÉE
    </div>

    <template v-else>
      <!-- Column headers -->
      <div :style="{ position: 'relative', width: SVG_W + 'px', height: '40px', marginBottom: '10px' }">
        <div v-for="h in COL_LABELS" :key="h.col"
             :style="{ position: 'absolute', left: cx(h.col) + 'px', width: MW + 'px', textAlign: 'center' }">
          <div class="mono" style="font-size: 9px; letter-spacing: .22em; color: var(--felt-2);">{{ h.label }}</div>
          <div class="mono" style="font-size: 8px; letter-spacing: .12em; color: var(--mute); margin-top: 3px;">{{ h.sub }}</div>
        </div>
      </div>

      <!-- Bracket canvas -->
      <div :style="{ position: 'relative', width: SVG_W + 'px', height: SVG_H + 'px' }">

        <!-- SVG connector lines -->
        <svg :width="SVG_W" :height="SVG_H"
             style="position:absolute;top:0;left:0;overflow:visible;pointer-events:none;">
          <path :d="connPath" fill="none" stroke="var(--line-strong)" stroke-width="1"/>
        </svg>

        <!-- R16 LEFT (pos 0–3) -->
        <div v-for="(m, i) in r16L" :key="'rl' + i"
             :style="{ position: 'absolute', left: cx(0) + 'px', top: r16Y(i) + 'px' }">
          <BracketCard :match="m" />
        </div>

        <!-- QF LEFT (pos 0–1) -->
        <div v-for="(m, i) in qfL" :key="'ql' + i"
             :style="{ position: 'absolute', left: cx(1) + 'px', top: qfY(i) + 'px' }">
          <BracketCard :match="m" />
        </div>

        <!-- SF LEFT -->
        <div :style="{ position: 'absolute', left: cx(2) + 'px', top: sfY() + 'px' }">
          <BracketCard :match="sfL" />
        </div>

        <!-- FINALE -->
        <div :style="{ position: 'absolute', left: cx(3) + 'px', top: sfY() + 'px' }">
          <BracketCard :match="fin" :is-final="true" />
        </div>

        <!-- MATCH 3e PLACE (petite finale) — sous la finale -->
        <div v-if="third"
             :style="{ position: 'absolute', left: cx(3) + 'px', top: (sfY() + MH + 34) + 'px' }">
          <div class="mono" style="font-size: 9px; letter-spacing: .18em; color: #b87333; text-align: center; margin-bottom: 6px;">
            🥉 MATCH 3e PLACE
          </div>
          <BracketCard :match="third" />
        </div>

        <!-- SF RIGHT -->
        <div :style="{ position: 'absolute', left: cx(4) + 'px', top: sfY() + 'px' }">
          <BracketCard :match="sfR" />
        </div>

        <!-- QF RIGHT (pos 2–3) -->
        <div v-for="(m, i) in qfR" :key="'qr' + i"
             :style="{ position: 'absolute', left: cx(5) + 'px', top: qfY(i) + 'px' }">
          <BracketCard :match="m" />
        </div>

        <!-- R16 RIGHT (pos 4–7) -->
        <div v-for="(m, i) in r16R" :key="'rr' + i"
             :style="{ position: 'absolute', left: cx(6) + 'px', top: r16Y(i) + 'px' }">
          <BracketCard :match="m" />
        </div>

      </div>

      <!-- Winner banner -->
      <div v-if="winner"
           :style="{ width: SVG_W + 'px', marginTop: '18px', display: 'flex', justifyContent: 'center' }">
        <div style="display: flex; align-items: center; gap: 14px;
                    border: 1px solid var(--felt); padding: 12px 28px;
                    background: rgba(45,168,118,.06);">
          <Ball8 :size="36" />
          <div>
            <div class="mono" style="font-size: 9px; letter-spacing: .22em; color: var(--mute);">VAINQUEUR</div>
            <div class="disp-a" style="font-size: 22px; color: var(--felt-2); line-height: 1.1; margin-top: 3px;">
              {{ winner }}
            </div>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>
