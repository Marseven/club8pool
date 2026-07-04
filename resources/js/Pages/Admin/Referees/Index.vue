<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({ referees: Array });

const showForm  = ref(false);
const editingId = ref(null);
const deleteId  = ref(null);

const form = useForm({
  name: '', email: '', fgb_card: '', pin: '', title: 'Arbitre',
});

const editForm = useForm({
  name: '', email: '', fgb_card: '', pin: '', title: '',
});

const submit = () => form.post('/admin/arbitres', {
  onSuccess: () => { showForm.value = false; form.reset(); },
});

function startEdit(r) {
  editingId.value = r.id;
  editForm.name     = r.name;
  editForm.email    = r.email ?? '';
  editForm.fgb_card = r.fgb_card;
  editForm.pin      = '';
  editForm.title    = r.title ?? 'Arbitre';
}

function cancelEdit() {
  editingId.value = null;
  editForm.reset();
}

function saveEdit(id) {
  editForm.put(`/admin/arbitres/${id}`, {
    onSuccess: () => { editingId.value = null; },
  });
}

function confirmDelete(id) {
  deleteId.value = id;
}

function doDelete() {
  router.delete(`/admin/arbitres/${deleteId.value}`, {
    onSuccess: () => { deleteId.value = null; },
    onError:   () => { deleteId.value = null; },
  });
}
</script>

<template>
  <Head title="Arbitres" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="referees" />
    <main style="flex: 1;">

      <!-- Header -->
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ARBITRES</div>
          <div class="disp-a" style="font-size: 28px; margin-top: 6px;">Arbitres certifiés ({{ referees.length }})</div>
        </div>
        <button class="btn btn-felt" @click="showForm = !showForm">+ Ajouter un arbitre</button>
      </header>

      <!-- Add form -->
      <div v-if="showForm" style="padding: 24px 32px; border-bottom: 1px solid var(--line); background: var(--ink-2);">
        <form @submit.prevent="submit" class="referee-form"
              style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; align-items: end;">
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

      <!-- Table -->
      <section style="padding: 32px;">
        <table class="tbl">
          <thead>
            <tr><th>Nom</th><th>Titre</th><th>Carte FGB</th><th>Email</th><th></th></tr>
          </thead>
          <tbody>
            <template v-for="r in referees" :key="r.id">
              <!-- Normal row -->
              <tr v-if="editingId !== r.id">
                <td style="font-weight: 600;">{{ r.name }}</td>
                <td style="color: var(--mute);">{{ r.title }}</td>
                <td class="mono">{{ r.fgb_card }}</td>
                <td class="mono" style="color: var(--mute);">{{ r.email ?? '—' }}</td>
                <td style="text-align: right; white-space: nowrap;">
                  <button class="btn-action" @click="startEdit(r)">Modifier</button>
                  <button class="btn-action btn-danger" @click="confirmDelete(r.id)">Supprimer</button>
                </td>
              </tr>
              <!-- Edit row -->
              <tr v-else style="background: var(--ink-2);">
                <td>
                  <input v-model="editForm.name" required style="width: 100%;" />
                </td>
                <td>
                  <input v-model="editForm.title" style="width: 100%;" />
                </td>
                <td>
                  <input v-model="editForm.fgb_card" required class="mono" style="width: 100%;" />
                </td>
                <td>
                  <input v-model="editForm.email" type="email" class="mono" style="width: 100%;" placeholder="email" />
                </td>
                <td style="white-space: nowrap; vertical-align: middle;">
                  <input v-model="editForm.pin" placeholder="Nouveau PIN" minlength="4"
                         style="width: 110px; margin-right: 8px;" />
                  <button class="btn-action btn-save" :disabled="editForm.processing" @click="saveEdit(r.id)">Sauvegarder</button>
                  <button class="btn-action" @click="cancelEdit">Annuler</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </section>

    </main>
  </div>

  <!-- Delete confirmation modal -->
  <Teleport to="body">
    <div v-if="deleteId" class="modal-backdrop" @click.self="deleteId = null">
      <div class="modal-box">
        <div class="disp-a" style="font-size: 20px; margin-bottom: 12px;">Supprimer l'arbitre ?</div>
        <p style="color: var(--mute); margin-bottom: 24px; font-size: 14px;">
          Cette action est irréversible. Les matchs arbitrés par cet arbitre ne seront pas supprimés.
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
          <button class="btn btn-ghost" @click="deleteId = null">Annuler</button>
          <button class="btn btn-danger" @click="doDelete">Supprimer</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.btn-action {
  background: transparent;
  border: 1px solid var(--line);
  color: var(--mute);
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 4px;
  cursor: pointer;
  margin-left: 6px;
  transition: border-color 0.15s, color 0.15s;
}
.btn-action:hover    { border-color: var(--felt); color: var(--felt); }
.btn-action.btn-danger:hover { border-color: #e05252; color: #e05252; }
.btn-action.btn-save:hover   { border-color: var(--felt); color: var(--felt); }

.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  display: flex; align-items: center; justify-content: center;
  z-index: 999;
}
.modal-box {
  background: var(--ink-2);
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 32px;
  max-width: 420px;
  width: 90%;
}
.btn-danger {
  background: #e05252 !important;
  border-color: #e05252 !important;
  color: #fff !important;
}
.btn-ghost {
  background: transparent;
  border-color: var(--line);
  color: var(--mute);
}

@media (max-width: 768px) {
  header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 12px !important;
  }
  header .btn { width: 100%; justify-content: center; }
  .referee-form { grid-template-columns: 1fr !important; }
}
</style>
