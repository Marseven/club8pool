<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';

defineProps({ competition: Object });
</script>

<template>
  <Head :title="competition.name" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="comps" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">COMPÉTITION</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">{{ competition.name }}</div>
        </div>
        <Link :href="`/competitions/${competition.slug}`" class="btn">Aperçu public ↗</Link>
      </header>

      <section style="padding: 32px; display: grid; grid-template-columns: 1.3fr 1fr; gap: 32px;">
        <div>
          <h3 class="disp-a" style="font-size: 28px; margin-bottom: 18px;">Configuration</h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div v-for="[k, v] in [
              ['Discipline', competition.discipline],
              ['Format', competition.format],
              ['Race', 'Race to ' + competition.race_to],
              ['Slots', competition.player_slots],
              ['Lieu', competition.venue],
              ['Dates', competition.starts_on?.slice(0,10) + ' → ' + competition.ends_on?.slice(0,10)],
              ['Dotation', new Intl.NumberFormat('fr-FR').format(competition.prize_pool) + ' FCFA'],
              ['Statut', competition.status],
            ]" :key="k">
              <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">{{ k.toUpperCase() }}</div>
              <div style="font-size: 18px; margin-top: 6px; font-weight: 600;">{{ v }}</div>
            </div>
          </div>
        </div>

        <div>
          <h3 class="disp-a" style="font-size: 28px; margin-bottom: 18px;">Joueurs inscrits ({{ competition.registrations?.length }})</h3>
          <div v-for="r in competition.registrations" :key="r.id"
               style="display: flex; justify-content: space-between; align-items: center;
                      padding: 10px 0; border-top: 1px solid var(--line);">
            <div style="display: flex; align-items: center; gap: 10px;">
              <span class="mono" style="font-size: 10px; color: var(--mute);">#{{ String(r.seed).padStart(2,'0') }}</span>
              <div>
                <div style="font-size: 13px; font-weight: 600;">{{ r.player.first_name }} {{ r.player.last_name }}</div>
                <div style="font-size: 11px; color: var(--mute);">{{ r.player.club?.name }}</div>
              </div>
            </div>
            <Chip :variant="r.status === 'paid' ? 'felt' : ''">{{ r.status }}</Chip>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
