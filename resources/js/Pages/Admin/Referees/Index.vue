<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({ referees: Array });

const showForm = ref(false);
const form = useForm({
  name: '',
  email: '',
  fgb_card: '',
  pin: '',
  title: 'Arbitre',
});

const submit = () => form.post('/admin/arbitres', { onSuccess: () => { showForm.value = false; form.reset(); } });
</script>

<template>
  <Head title="Arbitres" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="referees" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ARBITRES</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">Arbitres certifiés ({{ referees.length }})</div>
        </div>
        <button class="btn btn-felt" @click="showForm = !showForm">+ Ajouter un arbitre</button>
      </header>

      <div v-if="showForm" style="padding: 24px 32px; border-bottom: 1px solid var(--line); background: var(--ink-2);">
        <form @submit.prevent="submit" class="referee-form" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; align-items: end;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">NOM COMPLET</div>
            <input v-model="form.name" required />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">EMAIL</div>
            <input v-model="form.email" type="email" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">CARTE FGB</div>
            <input v-model="form.fgb_card" required />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">PIN (4-6 chiffres)</div>
            <input v-model="form.pin" required minlength="4" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 4px;">TITRE</div>
            <input v-model="form.title" />
          </label>
          <button type="submit" class="btn btn-felt" :disabled="form.processing">Enregistrer</button>
        </form>
      </div>

      <section style="padding: 32px;">
        <table class="tbl">
          <thead>
            <tr><th>Nom</th><th>Titre</th><th>Carte FGB</th><th>Email</th></tr>
          </thead>
          <tbody>
            <tr v-for="r in referees" :key="r.id">
              <td style="font-weight: 600;">{{ r.name }}</td>
              <td style="color: var(--mute);">{{ r.title }}</td>
              <td class="mono">{{ r.fgb_card }}</td>
              <td class="mono" style="color: var(--mute);">{{ r.email ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 12px !important;
  }
  header .btn {
    width: 100%;
    justify-content: center;
  }
  .referee-form {
    grid-template-columns: 1fr !important;
  }
}
</style>
