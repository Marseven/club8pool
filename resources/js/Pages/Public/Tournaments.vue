<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import Chip from '@/Components/Chip.vue';

defineProps({
  competitions: Array,
});

const fmtFcfa = (n) => n ? new Intl.NumberFormat('fr-FR').format(n) + ' FCFA' : '—';

const fmtDate = (d) => d
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
  : '';

const structureLabel = {
  knockout:       'Élimination directe',
  pools_knockout: 'Poules + phase finale',
  pools_only:     'Phase de poules',
  round_robin:    'Round-robin',
};
</script>

<template>
  <Head title="Tournois">
    <meta name="description" content="Historique des compétitions Club 8 Pool — résultats et archives." head-key="description" />
  </Head>

  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <section style="padding: 32px 24px; border-bottom: 1px solid var(--line);">
      <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">ARCHIVES</div>
      <h1 class="disp-a" style="font-size: clamp(40px, 9vw, 80px); margin-top: 14px; line-height: 0.92;">
        Tournois<br /><span style="color: var(--mute);">terminés</span>
      </h1>
    </section>

    <section v-if="competitions.length === 0" style="padding: 48px 24px;">
      <div style="padding: 40px; border: 1px dashed var(--line-strong); text-align: center; max-width: 560px; margin: 0 auto;">
        <div class="disp-a" style="font-size: clamp(20px, 5vw, 28px); color: var(--mute);">Aucun tournoi archivé</div>
        <p style="font-size: 13px; color: var(--mute); margin-top: 14px; line-height: 1.6;">
          Les compétitions terminées apparaîtront ici une fois archivées.
        </p>
      </div>
    </section>

    <section v-else style="padding: 32px 24px;">
      <div class="t-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; max-width: 1280px; margin: 0 auto;">
        <Link
          v-for="c in competitions"
          :key="c.id"
          :href="`/competitions/${c.slug}`"
          style="border: 1px solid var(--line); background: var(--ink-2); padding: 24px; display: block; text-decoration: none; position: relative; overflow: hidden;"
        >
          <!-- Logo en filigrane -->
          <img
            v-if="c.logo_url"
            :src="c.logo_url"
            alt=""
            style="position: absolute; right: -10px; top: -10px; width: 80px; height: 80px; object-fit: contain; opacity: 0.07; pointer-events: none;"
          />

          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <Chip variant="">TERMINÉE</Chip>
            <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.16em;">
              {{ c.discipline?.toUpperCase() }}
            </span>
          </div>

          <h3 class="disp-a" style="font-size: clamp(20px, 4vw, 30px); margin-top: 14px; line-height: 0.95; color: var(--chalk);">
            {{ c.name }}
          </h3>

          <p class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.16em; margin-top: 8px; text-transform: uppercase;">
            {{ structureLabel[c.structure] ?? c.structure }}
          </p>

          <div style="display: flex; gap: 24px; margin-top: 16px; flex-wrap: wrap;">
            <div>
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">DATES</div>
              <div style="font-size: 13px; font-weight: 600; margin-top: 4px; color: var(--chalk);">
                {{ fmtDate(c.starts_on) }}
                <template v-if="c.ends_on && c.ends_on !== c.starts_on">
                  <span style="color: var(--mute);"> → </span>{{ fmtDate(c.ends_on) }}
                </template>
              </div>
            </div>
            <div v-if="c.venue">
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">LIEU</div>
              <div style="font-size: 13px; font-weight: 600; margin-top: 4px; color: var(--chalk);">{{ c.venue }}</div>
            </div>
            <div v-if="c.prize_pool">
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;">DOTATION</div>
              <div style="font-size: 13px; font-weight: 600; margin-top: 4px; color: var(--felt-2);">{{ fmtFcfa(c.prize_pool) }}</div>
            </div>
          </div>

          <div style="display: flex; gap: 20px; margin-top: 16px; border-top: 1px solid var(--line); padding-top: 14px;">
            <div>
              <span class="disp-a tnum" style="font-size: 24px; color: var(--chalk);">{{ c.registrations_count }}</span>
              <span style="font-size: 11px; color: var(--mute); margin-left: 4px;">joueurs</span>
            </div>
            <div>
              <span class="disp-a tnum" style="font-size: 24px; color: var(--chalk);">{{ c.matches_count }}</span>
              <span style="font-size: 11px; color: var(--mute); margin-left: 4px;">matchs</span>
            </div>
            <div style="margin-left: auto; color: var(--mute); align-self: center;">→</div>
          </div>
        </Link>
      </div>
    </section>

    <PublicFooter />
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  .t-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
