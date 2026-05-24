<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';

defineProps({ competitions: Array });
</script>

<template>
  <Head title="Compétitions" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="comps" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">COMPÉTITIONS</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">Toutes les éditions</div>
        </div>
        <Link href="/admin/competitions/nouvelle" class="btn btn-felt">+ Nouvelle compétition</Link>
      </header>

      <section style="padding: 32px;">
        <table class="tbl">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Discipline</th>
              <th>Format</th>
              <th>Inscrits</th>
              <th>Matchs</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in competitions" :key="c.id">
              <td style="font-weight: 600;">{{ c.name }}</td>
              <td>{{ c.discipline }}</td>
              <td style="color: var(--mute);">{{ c.format }}</td>
              <td class="mono tnum">{{ c.registrations_count }} / {{ c.player_slots }}</td>
              <td class="mono tnum">{{ c.matches_count }}</td>
              <td>
                <Chip :variant="c.status === 'in_progress' ? 'live' : c.status === 'finished' ? 'felt' : ''">
                  {{ c.status }}
                </Chip>
              </td>
              <td style="text-align: right;">
                <Link :href="`/admin/competitions/${c.id}`" class="btn" style="padding: 4px 10px; font-size: 11px;">Voir →</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>
