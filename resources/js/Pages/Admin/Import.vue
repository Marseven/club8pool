<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Chip from '@/Components/Chip.vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  preview: Object,
});

const form = useForm({ file: null });
const confirming = ref(false);
const cancelling = ref(false);

const submit = () => form.post('/admin/import', { forceFormData: true });

const confirmImport = () => {
  if (confirming.value) return;
  confirming.value = true;
  router.post('/admin/import/confirm', {}, {
    onFinish: () => { confirming.value = false; },
  });
};

const cancelImport = () => {
  if (cancelling.value) return;
  cancelling.value = true;
  router.post('/admin/import/annuler', {}, {
    onFinish: () => { cancelling.value = false; },
  });
};

const fileLabel = ref('');
const onFileChange = (e) => {
  const f = e.target.files?.[0];
  form.file = f ?? null;
  fileLabel.value = f?.name ?? '';
};

const grouped = computed(() => {
  if (!props.preview?.stats?.matches?.length) return {};
  const g = {};
  for (const m of props.preview.stats.matches) {
    const pool = m.label.match(/^([A-Z])/)?.[1] ?? '?';
    g[pool] ??= [];
    g[pool].push(m);
  }
  return g;
});

const totalCounts = computed(() => {
  const s = props.preview?.stats;
  return {
    matches: s?.matches?.length ?? 0,
    errors: s?.errors?.length ?? 0,
    skipped: s?.skipped?.length ?? 0,
  };
});
</script>

<template>
  <Head title="Import Excel" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="import" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">
            IMPORT DES RÉSULTATS
          </div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">
            {{ competition.name }}
          </div>
        </div>
        <Link href="/admin/poules" class="btn">Gérer les poules →</Link>
      </header>

      <!-- ÉTAPE 1 : upload -->
      <section v-if="!preview" style="padding: 32px;">
        <div style="max-width: 720px;">
          <h3 class="disp-a" style="font-size: 28px;">Étape 1 · Charger le fichier Excel</h3>
          <p style="font-size: 13px; color: var(--mute); margin-top: 8px; line-height: 1.6;">
            Format attendu : un classeur `.xlsx` avec une feuille par poule (<span class="mono">Poules_A</span>,
            <span class="mono">Poules_B</span>, <span class="mono">Poules_C</span>, <span class="mono">Poules_D</span>),
            chaque ligne au format <span class="mono">A1 vs A2 | Joueur 1 | Score | Joueur 2 | Score | Vainqueur</span>.
            Les lignes avec score vide sont ignorées (matchs non joués).
          </p>

          <form @submit.prevent="submit" style="margin-top: 28px;">
            <label style="display: block; cursor: pointer;">
              <input type="file" accept=".xlsx,.xls" @change="onFileChange" style="display: none;" />
              <div style="border: 1px dashed var(--line-strong); padding: 36px 24px; text-align: center;
                          background: var(--ink-2); transition: background .15s;">
                <div class="disp-a" style="font-size: 24px;">↓ Choisir un fichier .xlsx</div>
                <div v-if="fileLabel" class="mono" style="font-size: 12px; color: var(--felt-2); margin-top: 14px;">
                  {{ fileLabel }}
                </div>
                <div v-else class="mono" style="font-size: 11px; color: var(--mute); margin-top: 14px; letter-spacing: 0.18em;">
                  GLISSEZ OU CLIQUEZ POUR SÉLECTIONNER · MAX 5 MO
                </div>
              </div>
            </label>

            <div v-if="form.errors.file" style="color: var(--live); font-size: 12px; margin-top: 10px;" class="mono">
              {{ form.errors.file }}
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
              <button type="submit" class="btn btn-felt" :disabled="!form.file || form.processing">
                {{ form.processing ? 'Analyse…' : 'Analyser le fichier →' }}
              </button>
              <Link href="/admin/poules" class="btn">Annuler</Link>
            </div>
          </form>

          <div style="margin-top: 36px; padding: 18px; border: 1px solid var(--line); background: var(--ink-2);">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">RAPPEL</div>
            <p style="font-size: 12px; color: var(--chalk-2); margin-top: 8px; line-height: 1.6;">
              L'import écrase les scores existants pour les matchs trouvés dans le fichier. Les matchs non listés
              sont laissés intacts. Un aperçu sera présenté avant validation finale.
            </p>
          </div>
        </div>
      </section>

      <!-- ÉTAPE 2 : preview + confirm -->
      <section v-else style="padding: 32px;">
        <h3 class="disp-a" style="font-size: 28px; margin-bottom: 6px;">Étape 2 · Aperçu &amp; validation</h3>
        <p style="font-size: 12px; color: var(--mute);">
          Fichier : <span class="mono">{{ preview.filename }}</span>
        </p>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 24px;">
          <div style="padding: 16px; border: 1px solid var(--felt-2); background: rgba(45,168,118,0.06);">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">À IMPORTER</div>
            <div class="disp-a tnum" style="font-size: 40px; color: var(--felt-2); margin-top: 6px;">{{ totalCounts.matches }}</div>
          </div>
          <div style="padding: 16px; border: 1px solid var(--line); background: var(--ink-2);">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">IGNORÉS</div>
            <div class="disp-a tnum" style="font-size: 40px; margin-top: 6px;">{{ totalCounts.skipped }}</div>
          </div>
          <div style="padding: 16px; border: 1px solid var(--line); background: var(--ink-2);">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ERREURS</div>
            <div class="disp-a tnum" :style="{ fontSize: '40px', marginTop: '6px', color: totalCounts.errors ? 'var(--live)' : 'var(--chalk)' }">{{ totalCounts.errors }}</div>
          </div>
        </div>

        <div v-if="preview.stats.errors?.length" style="margin-top: 20px; padding: 14px;
             border: 1px solid var(--live); background: rgba(229,72,77,0.06);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--live);">ERREURS</div>
          <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 12px; color: var(--chalk-2); line-height: 1.6;">
            <li v-for="(e, i) in preview.stats.errors" :key="i">{{ e }}</li>
          </ul>
        </div>

        <div v-if="preview.stats.skipped?.length" style="margin-top: 14px; padding: 14px;
             border: 1px solid var(--line); background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">IGNORÉS</div>
          <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 12px; color: var(--mute); line-height: 1.6;">
            <li v-for="(e, i) in preview.stats.skipped" :key="i">{{ e }}</li>
          </ul>
        </div>

        <div v-for="(matches, pool) in grouped" :key="pool" style="margin-top: 32px;">
          <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <h4 class="disp-a" style="font-size: 22px;">POULE {{ pool }}</h4>
            <span class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.14em;">
              {{ matches.length }} MATCHS
            </span>
          </div>
          <table class="tbl" style="font-size: 12px;">
            <thead>
              <tr>
                <th style="width: 90px;">Match</th>
                <th>Joueur 1</th>
                <th style="text-align: center; width: 60px;">Score</th>
                <th>Joueur 2</th>
                <th style="text-align: right;">État</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in matches" :key="m.match_id">
                <td class="mono" style="color: var(--mute);">{{ m.label }}</td>
                <td :style="{ fontWeight: m.score_a > m.score_b && !m.is_draw ? 700 : 500 }">{{ m.player_a_name }}</td>
                <td class="mono tnum" style="text-align: center; font-weight: 700;">{{ m.score_a }} — {{ m.score_b }}</td>
                <td :style="{ fontWeight: m.score_b > m.score_a && !m.is_draw ? 700 : 500 }">{{ m.player_b_name }}</td>
                <td style="text-align: right;">
                  <Chip v-if="m.is_draw" style="padding: 2px 6px;">NUL</Chip>
                  <Chip v-else-if="m.currently_done" style="padding: 2px 6px;">ÉCRASE</Chip>
                  <Chip v-else variant="felt" style="padding: 2px 6px;">NOUVEAU</Chip>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--line); flex-wrap: wrap;">
          <button class="btn" @click="cancelImport" :disabled="cancelling || confirming">
            {{ cancelling ? 'Annulation…' : 'Annuler' }}
          </button>
          <button class="btn btn-felt" style="margin-left: auto; display:inline-flex; align-items:center; gap:6px;"
                  @click="confirmImport" :disabled="totalCounts.matches === 0 || confirming || cancelling">
            <Check :size="12" /> {{ confirming ? 'Import en cours…' : `Valider l'import · ${totalCounts.matches} matchs` }}
          </button>
        </div>
      </section>
    </main>
  </div>
</template>
