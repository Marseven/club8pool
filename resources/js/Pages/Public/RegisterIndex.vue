<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import Chip from '@/Components/Chip.vue';
import { competitionStatus, competitionStatusChip, label } from '@/utils/labels.js';

defineProps({
  open: Array,
  others: Array,
});

const fmtFcfa = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long' }) : '';

const structureLabel = {
  knockout:       'Élimination directe',
  pools_knockout: 'Poules + phase finale',
  pools_only:     'Phase de poules',
  round_robin:    'Round-robin',
};
</script>

<template>
  <Head title="Inscriptions">
    <meta name="description" content="Toutes les compétitions de billard Club 8 Pool — inscrivez-vous aux éditions ouvertes." head-key="description" />
  </Head>

  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <section style="padding: 32px 24px; border-bottom: 1px solid var(--line);">
      <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">INSCRIPTIONS</div>
      <h1 class="disp-a" style="font-size: clamp(40px, 9vw, 80px); margin-top: 14px; line-height: 0.92;">
        Choisissez<br /><span style="color: var(--felt-2);">votre compétition</span>
      </h1>
    </section>

    <section v-if="open.length === 0" style="padding: 32px 24px;">
      <div style="padding: 32px; border: 1px dashed var(--line-strong); text-align: center; max-width: 560px; margin: 0 auto;">
        <div class="disp-a" style="font-size: clamp(24px, 6vw, 32px); color: var(--mute);">Aucune inscription ouverte</div>
        <p style="font-size: 13px; color: var(--mute); margin-top: 14px; line-height: 1.6;">
          Aucune compétition n'accepte actuellement de nouvelles inscriptions. Revenez bientôt
          ou suivez les compétitions en cours.
        </p>
      </div>
    </section>

    <section v-else style="padding: 32px 24px;">
      <div class="comps-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; max-width: 1280px; margin: 0 auto;">
        <Link v-for="c in open" :key="c.id" :href="`/inscription/${c.slug}`"
              style="border: 1px solid var(--felt-2); background: rgba(45,168,118,0.05); padding: 24px; display: block;">
          <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px;">
            <Chip variant="felt">INSCRIPTIONS OUVERTES</Chip>
            <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">
              J-{{ c.registration_closes_at ? Math.max(0, Math.ceil((new Date(c.registration_closes_at) - Date.now()) / 86400000)) : '?' }}
            </span>
          </div>
          <h3 class="disp-a" style="font-size: clamp(24px, 5vw, 36px); margin-top: 14px; line-height: 0.9;">{{ c.name }}</h3>
          <p class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.18em; margin-top: 12px; text-transform: uppercase;">
            {{ structureLabel[c.structure] }} · RACE TO {{ c.race_to }}
          </p>
          <div style="display: flex; gap: 24px; margin-top: 18px; flex-wrap: wrap;">
            <div>
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">DATES</div>
              <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">
                {{ fmtDate(c.starts_on) }}{{ c.ends_on && c.ends_on !== c.starts_on ? ' → ' + fmtDate(c.ends_on) : '' }}
              </div>
            </div>
            <div>
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">LIEU</div>
              <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">{{ c.venue }}</div>
            </div>
            <div>
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">FRAIS</div>
              <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">{{ fmtFcfa(c.entry_fee) }}</div>
            </div>
          </div>
          <div style="margin-top: 22px; display: flex; gap: 6px; align-items: baseline;">
            <span class="disp-a tnum" style="font-size: 36px; color: var(--felt-2);">{{ String(c.remaining).padStart(2, '0') }}</span>
            <span style="font-size: 12px; color: var(--mute);">/{{ c.player_slots }} places restantes</span>
            <span style="margin-left: auto; color: var(--mute);">→</span>
          </div>
        </Link>
      </div>
    </section>

    <section v-if="others.length" style="padding: 32px 24px; border-top: 1px solid var(--line);">
      <h2 class="disp-a" style="font-size: clamp(24px, 6vw, 40px); margin-bottom: 18px;">Autres éditions</h2>
      <div class="comps-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; max-width: 1280px; margin: 0 auto;">
        <Link v-for="c in others" :key="c.id" :href="`/inscription/${c.slug}`"
              style="border: 1px solid var(--line); background: var(--ink-2); padding: 20px; display: block; opacity: 0.85;">
          <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px;">
            <Chip :variant="label(competitionStatusChip, c.status)">{{ label(competitionStatus, c.status) }}</Chip>
            <span class="mono" style="font-size: 10px; color: var(--mute);">{{ c.registered }}/{{ c.player_slots }}</span>
          </div>
          <div style="font-size: 18px; font-weight: 600; margin-top: 10px;">{{ c.name }}</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 8px;">
            {{ fmtDate(c.starts_on) }}{{ c.ends_on && c.ends_on !== c.starts_on ? ' → ' + fmtDate(c.ends_on) : '' }} · {{ c.venue }}
          </div>
        </Link>
      </div>
    </section>
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  /* Competition cards: 1 col on mobile */
  .comps-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
