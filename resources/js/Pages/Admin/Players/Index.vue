<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({ players: Array, clubs: Array });

const showForm = ref(false);
const form = useForm({
  first_name: '',
  last_name: '',
  club_id: null,
  fgb_card: '',
  phone: '',
  email: '',
  rating: 1500,
});

const submit = () => form.post('/admin/joueurs', { onSuccess: () => { showForm.value = false; form.reset(); } });
</script>

<template>
  <Head title="Joueurs" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="players" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">JOUEURS</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">Tous les joueurs ({{ players.length }})</div>
        </div>
        <button class="btn btn-felt" @click="showForm = !showForm">+ Ajouter un joueur</button>
      </header>

      <div v-if="showForm" style="padding: 24px 32px; border-bottom: 1px solid var(--line); background: var(--ink-2);">
        <form @submit.prevent="submit" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; align-items: end;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">PRÉNOM</div>
            <input v-model="form.first_name" required />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">NOM</div>
            <input v-model="form.last_name" required />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">CLUB</div>
            <select v-model="form.club_id">
              <option :value="null">— sélectionner —</option>
              <option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">CARTE FGB</div>
            <input v-model="form.fgb_card" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">ELO</div>
            <input v-model.number="form.rating" type="number" />
          </label>
          <button type="submit" class="btn btn-felt" :disabled="form.processing">Enregistrer</button>
        </form>
      </div>

      <section style="padding: 32px;">
        <table class="tbl">
          <thead>
            <tr><th>#</th><th>Joueur</th><th>Club</th><th style="text-align: right;">Elo</th><th style="text-align: right;">V/D</th><th>Carte FGB</th></tr>
          </thead>
          <tbody>
            <tr v-for="(p, i) in players" :key="p.id">
              <td class="mono tnum" style="color: var(--mute);">{{ String(i + 1).padStart(2, '0') }}</td>
              <td style="font-weight: 600;">{{ p.first_name }} {{ p.last_name }}</td>
              <td style="color: var(--mute);">{{ p.club?.name }} · {{ p.club?.city }}</td>
              <td class="mono tnum" style="text-align: right;">{{ p.rating }}</td>
              <td class="mono tnum" style="text-align: right;">{{ p.wins }}/{{ p.losses }}</td>
              <td class="mono" style="font-size: 11px; color: var(--mute);">{{ p.fgb_card }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>
