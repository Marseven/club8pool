<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';
import { competitionStatus, competitionStatusChip, registrationStatus, registrationStatusChip, label } from '@/utils/labels.js';

defineProps({ competition: Object });

const structureLabel = {
  knockout:       'Élimination directe',
  pools_knockout: 'Poules + phase finale',
  pools_only:     'Poules uniquement',
  round_robin:    'Round-robin général',
};

const fmtFcfa = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
</script>

<template>
  <Head :title="competition.name" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="comps" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div style="display: flex; align-items: center; gap: 14px;">
          <img v-if="competition.logo_url" :src="competition.logo_url" :alt="competition.name + ' logo'"
               style="height: 48px; width: 48px; object-fit: contain;" />
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">COMPÉTITION</div>
            <div class="disp-a" style="font-size: 24px; margin-top: 6px;">{{ competition.name }}</div>
          </div>
        </div>
        <div style="display: flex; gap: 10px;">
          <Link :href="`/competitions/${competition.slug}`" class="btn">Aperçu public ↗</Link>
          <Link :href="`/admin/competitions/${competition.id}/edit`" class="btn btn-felt">Éditer</Link>
        </div>
      </header>

      <section style="padding: 32px;
                      display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;
                      border-bottom: 1px solid var(--line);">
        <div style="padding: 20px; border: 1px solid var(--line); background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">STRUCTURE</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 10px;">{{ structureLabel[competition.structure] }}</div>
          <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 10px;">
            <template v-if="competition.structure === 'pools_knockout'">
              {{ competition.pool_count }} POULES · {{ competition.player_slots }} JOUEURS · {{ competition.qualifiers_per_pool }} QUALIFIÉS/POULE
            </template>
            <template v-else>
              {{ competition.player_slots }} JOUEURS
            </template>
          </div>
        </div>

        <div style="padding: 20px; border: 1px solid var(--felt-2); background: rgba(45,168,118,0.04);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">RACE</div>
          <template v-if="competition.structure === 'pools_knockout'">
            <div style="display: flex; gap: 32px; margin-top: 10px; align-items: baseline;">
              <div>
                <div class="disp-a tnum" style="font-size: 44px; color: var(--felt-2);">{{ competition.pool_race_to ?? competition.race_to }}</div>
                <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.2em; margin-top: 4px;">POULES</div>
              </div>
              <div style="font-family: var(--font-display-a); font-size: 32px; color: var(--mute-2);">·</div>
              <div>
                <div class="disp-a tnum" style="font-size: 44px; color: var(--felt-2);">{{ competition.knockout_race_to ?? competition.race_to }}</div>
                <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.2em; margin-top: 4px;">FINALE</div>
              </div>
            </div>
          </template>
          <template v-else>
            <div class="disp-a tnum" style="font-size: 56px; margin-top: 10px; color: var(--felt-2);">RACE TO {{ competition.race_to }}</div>
            <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 8px;">
              PREMIER À {{ competition.race_to }} MANCHES GAGNÉES
            </div>
          </template>
        </div>

        <div style="padding: 20px; border: 1px solid var(--line); background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">RÈGLES</div>
          <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 6px;">
            <div style="font-size: 13px;">Shot clock · <strong>{{ competition.shot_clock }} sec</strong></div>
            <div style="font-size: 13px;">Pause frames · <strong>{{ competition.frame_pause }} sec</strong></div>
            <div style="font-size: 13px;">Race départage · <strong>{{ competition.tiebreak_race }}</strong></div>
          </div>
          <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px;">
            <Chip v-if="competition.alternate_break">Break alterné</Chip>
            <Chip v-if="competition.allow_draw" variant="felt">Nuls autorisés</Chip>
            <Chip v-if="competition.enable_warnings">Avertissements</Chip>
          </div>
        </div>
      </section>

      <section style="padding: 32px; display: grid; grid-template-columns: 1.3fr 1fr; gap: 32px;">
        <div>
          <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Informations</h3>
          <table class="tbl">
            <tr><th>Discipline</th><td>{{ competition.discipline }}</td></tr>
            <tr><th>Lieu</th><td>{{ competition.venue }}</td></tr>
            <tr><th>Ville</th><td>{{ competition.city }}</td></tr>
            <tr><th>Dates</th><td>{{ competition.starts_on?.slice(0, 10) }} → {{ competition.ends_on?.slice(0, 10) }}</td></tr>
            <tr><th>Inscriptions</th><td>Closes le {{ competition.registration_closes_at?.slice(0, 16)?.replace('T', ' à ') }}</td></tr>
            <tr><th>Inscription</th><td>{{ fmtFcfa(competition.entry_fee ?? 0) }}</td></tr>
            <tr><th>Dotation</th><td><strong style="color: var(--felt-2);">{{ fmtFcfa(competition.prize_pool ?? 0) }}</strong></td></tr>
            <tr><th>Statut</th><td><Chip :variant="label(competitionStatusChip, competition.status)">{{ label(competitionStatus, competition.status) }}</Chip></td></tr>
          </table>
        </div>

        <div v-if="competition.pools?.length">
          <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Poules ({{ competition.pools.length }})</h3>
          <div v-for="p in competition.pools" :key="p.id"
               style="padding: 14px 16px; border: 1px solid var(--line); background: var(--ink-2); margin-bottom: 10px;
                      display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div class="disp-a" style="font-size: 18px;">POULE {{ p.name }}</div>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 4px;">{{ p.size }} JOUEURS</div>
            </div>
            <Link href="/admin/poules" class="btn" style="padding: 4px 10px; font-size: 11px;">Gérer →</Link>
          </div>
        </div>

        <div v-else>
          <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Joueurs inscrits ({{ competition.registrations?.length ?? 0 }})</h3>
          <div v-for="r in competition.registrations" :key="r.id"
               style="display: flex; justify-content: space-between; align-items: center;
                      padding: 10px 0; border-top: 1px solid var(--line);">
            <div>
              <div style="font-size: 13px; font-weight: 600;">{{ r.player.first_name }} {{ r.player.last_name }}</div>
              <div style="font-size: 11px; color: var(--mute);">{{ r.player.club?.name }}</div>
            </div>
            <Chip :variant="label(registrationStatusChip, r.status)">{{ label(registrationStatus, r.status) }}</Chip>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
