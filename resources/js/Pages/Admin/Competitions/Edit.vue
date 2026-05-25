<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({ competition: Object });

const logoForm = useForm({ logo: null });
const logoPreview = ref(null);

const onLogoChange = (e) => {
  const file = e.target.files?.[0] ?? null;
  logoForm.logo = file;
  logoPreview.value = file ? URL.createObjectURL(file) : null;
};

const uploadLogo = () => {
  if (! logoForm.logo) return;
  logoForm.post(`/admin/competitions/${props.competition.id}/logo`, {
    forceFormData: true,
    onSuccess: () => {
      logoForm.reset();
      logoPreview.value = null;
    },
  });
};

const removeLogo = () => {
  if (! confirm('Retirer le logo actuel ?')) return;
  router.delete(`/admin/competitions/${props.competition.id}/logo`);
};

const form = useForm({
  name: props.competition.name,
  venue: props.competition.venue,
  city: props.competition.city,
  discipline: props.competition.discipline,
  format: props.competition.format,
  structure: props.competition.structure,
  player_slots: props.competition.player_slots,
  pool_count: props.competition.pool_count,
  pool_size: props.competition.pool_size,
  qualifiers_per_pool: props.competition.qualifiers_per_pool,
  race_to: props.competition.race_to,
  pool_race_to: props.competition.pool_race_to ?? props.competition.race_to,
  knockout_race_to: props.competition.knockout_race_to ?? props.competition.race_to,
  shot_clock: props.competition.shot_clock,
  alternate_break: !!props.competition.alternate_break,
  allow_draw: !!props.competition.allow_draw,
  enable_warnings: !!props.competition.enable_warnings,
  push_out: !!props.competition.push_out,
  frame_pause: props.competition.frame_pause,
  tiebreak_race: props.competition.tiebreak_race,
  entry_fee: props.competition.entry_fee,
  deposit: props.competition.deposit,
  prize_pool: props.competition.prize_pool,
  status: props.competition.status,
  starts_on: props.competition.starts_on?.slice(0, 10),
  ends_on: props.competition.ends_on?.slice(0, 10),
  registration_closes_at: props.competition.registration_closes_at?.slice(0, 16),
});

const racePresets = [3, 5, 7, 9, 11];

const submit = () => {
  if (form.structure === 'pools_knockout' || form.structure === 'pools_only') {
    form.race_to = form.pool_race_to;
  } else if (form.structure === 'knockout') {
    form.race_to = form.knockout_race_to;
  }
  form.patch(`/admin/competitions/${props.competition.id}`);
};
</script>

<template>
  <Head :title="`Éditer · ${competition.name}`" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="comps" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">ÉDITION COMPÉTITION</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">{{ competition.name }}</div>
        </div>
        <Link :href="`/admin/competitions/${competition.id}`" class="btn">← Retour</Link>
      </header>

      <section style="max-width: 920px; padding: 32px 32px 0;">
        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 14px;">Logo du tournoi</h3>
        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 20px; align-items: start;
                    padding: 18px; border: 1px solid var(--line); background: var(--ink-2); margin-bottom: 28px;">
          <div style="width: 140px; height: 140px; border: 1px solid var(--line-strong);
                      display: flex; align-items: center; justify-content: center;
                      background: var(--ink); overflow: hidden;">
            <img v-if="logoPreview" :src="logoPreview" alt="Aperçu"
                 style="max-width: 100%; max-height: 100%; object-fit: contain;" />
            <img v-else-if="competition.logo_url" :src="competition.logo_url" alt="Logo"
                 style="max-width: 100%; max-height: 100%; object-fit: contain;" />
            <span v-else class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">SANS LOGO</span>
          </div>
          <div>
            <p style="font-size: 12px; color: var(--mute); margin-bottom: 12px; line-height: 1.5;">
              PNG, JPG, SVG ou WebP · 2 Mo max · Format carré recommandé.
              Affiché sur la home, la page compétition et le scoreboard live.
            </p>
            <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                   @change="onLogoChange"
                   style="display: block; margin-bottom: 12px; font-size: 12px; color: var(--chalk-2);" />
            <div v-if="logoForm.errors.logo" style="font-size: 11px; color: var(--live); margin-bottom: 10px;">
              {{ logoForm.errors.logo }}
            </div>
            <div style="display: flex; gap: 10px;">
              <button type="button" class="btn btn-felt" :disabled="!logoForm.logo || logoForm.processing"
                      @click="uploadLogo">
                {{ logoForm.processing ? 'Envoi…' : 'Téléverser' }}
              </button>
              <button type="button" v-if="competition.logo_url" class="btn"
                      @click="removeLogo"
                      style="border-color: rgba(229,72,77,0.4); color: var(--live);">
                Retirer le logo
              </button>
            </div>
          </div>
        </div>
      </section>

      <form @submit.prevent="submit" style="max-width: 920px; padding: 0 32px 32px;">
        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 16px;">Identité</h3>
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; margin-bottom: 24px;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">NOM</div>
            <input v-model="form.name" required />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">LIEU</div>
            <input v-model="form.venue" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">VILLE</div>
            <input v-model="form.city" />
          </label>
        </div>

        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 6px;">Statut de la compétition</h3>
        <p style="font-size: 12px; color: var(--mute); margin-bottom: 14px;">
          Quand le statut est <strong style="color: var(--chalk);">« En cours »</strong> ou <strong style="color: var(--chalk);">« Terminée »</strong>, les inscriptions publiques sont automatiquement fermées.
        </p>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 28px;">
          <button v-for="opt in [
            { v: 'draft', l: 'Brouillon', sub: 'Non visible' },
            { v: 'registration', l: 'Inscriptions', sub: 'Ouvertes' },
            { v: 'in_progress', l: 'En cours', sub: 'Inscriptions fermées' },
            { v: 'finished', l: 'Terminée', sub: 'Archive' },
          ]" :key="opt.v" type="button" @click="form.status = opt.v" :style="{
            padding: '14px', cursor: 'pointer', textAlign: 'left',
            border: '1px solid ' + (form.status === opt.v ? 'var(--felt-2)' : 'var(--line-strong)'),
            background: form.status === opt.v ? 'rgba(45,168,118,0.08)' : 'var(--ink-2)',
          }">
            <div class="disp-a" :style="{ fontSize: '16px', color: form.status === opt.v ? 'var(--felt-2)' : 'var(--chalk)' }">{{ opt.l }}</div>
            <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.14em; margin-top: 6px;">{{ opt.sub.toUpperCase() }}</div>
          </button>
        </div>

        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 14px;">Race to · Manches gagnantes</h3>

        <div v-if="['pools_knockout', 'pools_only'].includes(form.structure)" style="margin-bottom: 20px;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2); margin-bottom: 10px;">▤ PHASE DE POULES</div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button v-for="n in racePresets" :key="'p'+n" type="button" @click="form.pool_race_to = n" :style="{
              width: '64px', height: '64px', cursor: 'pointer',
              border: '1px solid ' + (form.pool_race_to === n ? 'var(--felt-2)' : 'var(--line-strong)'),
              background: form.pool_race_to === n ? 'rgba(45,168,118,0.08)' : 'var(--ink-2)',
              display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
            }">
              <div class="mono" style="font-size: 8px; letter-spacing: 0.2em; color: var(--mute);">RACE</div>
              <div class="disp-a tnum" :style="{ fontSize: '24px', color: form.pool_race_to === n ? 'var(--felt-2)' : 'var(--chalk)' }">{{ n }}</div>
            </button>
            <input v-model.number="form.pool_race_to" type="number" min="1" max="25"
                   style="width: 70px; text-align: center; font-family: var(--font-display-a); font-size: 18px;" />
          </div>
        </div>

        <div v-if="['pools_knockout', 'knockout'].includes(form.structure)" style="margin-bottom: 28px;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2); margin-bottom: 10px;">◇ PHASE FINALE</div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button v-for="n in racePresets" :key="'k'+n" type="button" @click="form.knockout_race_to = n" :style="{
              width: '64px', height: '64px', cursor: 'pointer',
              border: '1px solid ' + (form.knockout_race_to === n ? 'var(--felt-2)' : 'var(--line-strong)'),
              background: form.knockout_race_to === n ? 'rgba(45,168,118,0.08)' : 'var(--ink-2)',
              display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
            }">
              <div class="mono" style="font-size: 8px; letter-spacing: 0.2em; color: var(--mute);">RACE</div>
              <div class="disp-a tnum" :style="{ fontSize: '24px', color: form.knockout_race_to === n ? 'var(--felt-2)' : 'var(--chalk)' }">{{ n }}</div>
            </button>
            <input v-model.number="form.knockout_race_to" type="number" min="1" max="25"
                   style="width: 70px; text-align: center; font-family: var(--font-display-a); font-size: 18px;" />
          </div>
        </div>

        <div v-if="form.structure === 'round_robin'" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px;">
          <button v-for="n in racePresets" :key="n" type="button" @click="form.race_to = n" :style="{
            width: '72px', height: '72px', cursor: 'pointer',
            border: '1px solid ' + (form.race_to === n ? 'var(--felt-2)' : 'var(--line-strong)'),
            background: form.race_to === n ? 'rgba(45,168,118,0.08)' : 'var(--ink-2)',
            display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
          }">
            <div class="mono" style="font-size: 8px; letter-spacing: 0.2em; color: var(--mute);">RACE TO</div>
            <div class="disp-a tnum" :style="{ fontSize: '30px', color: form.race_to === n ? 'var(--felt-2)' : 'var(--chalk)' }">{{ n }}</div>
          </button>
        </div>

        <h3 v-if="['pools_knockout', 'pools_only'].includes(form.structure)" class="disp-a" style="font-size: 20px; margin-bottom: 14px;">Poules</h3>
        <div v-if="['pools_knockout', 'pools_only'].includes(form.structure)" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">NB POULES</div>
            <input v-model.number="form.pool_count" type="number" min="2" max="16" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">JOUEURS / POULE</div>
            <input v-model.number="form.pool_size" type="number" min="3" max="12" />
          </label>
          <label v-if="form.structure === 'pools_knockout'">
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">QUALIFIÉS / POULE</div>
            <input v-model.number="form.qualifiers_per_pool" type="number" min="1" :max="form.pool_size - 1" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">JOUEURS TOTAL</div>
            <input v-model.number="form.player_slots" type="number" min="2" max="256" />
          </label>
        </div>

        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 14px;">Réglages</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SHOT CLOCK</div>
            <input v-model.number="form.shot_clock" type="number" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">PAUSE FRAMES</div>
            <input v-model.number="form.frame_pause" type="number" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">RACE DÉPARTAGE</div>
            <input v-model.number="form.tiebreak_race" type="number" />
          </label>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 28px;">
          <label v-for="opt in [
            ['alternate_break', 'Break alterné'],
            ['allow_draw', 'Autoriser les nuls'],
            ['enable_warnings', 'Avertissements'],
            ['push_out', 'Push out'],
          ]" :key="opt[0]" :style="{
            display: 'flex', gap: '12px', alignItems: 'center',
            padding: '12px 14px', cursor: 'pointer',
            border: '1px solid ' + (form[opt[0]] ? 'var(--felt-2)' : 'var(--line-strong)'),
            background: form[opt[0]] ? 'rgba(45,168,118,0.05)' : 'var(--ink-2)',
          }">
            <input v-model="form[opt[0]]" type="checkbox" style="width: auto; margin: 0;" />
            <span style="font-size: 13px; font-weight: 600;">{{ opt[1] }}</span>
          </label>
        </div>

        <h3 class="disp-a" style="font-size: 20px; margin-bottom: 14px;">Frais &amp; dotation</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 28px;">
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">INSCRIPTION (FCFA)</div>
            <input v-model.number="form.entry_fee" type="number" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">CAUTION (FCFA)</div>
            <input v-model.number="form.deposit" type="number" />
          </label>
          <label>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">DOTATION (FCFA)</div>
            <input v-model.number="form.prize_pool" type="number" />
          </label>
        </div>

        <div style="display: flex; gap: 10px;">
          <Link :href="`/admin/competitions/${competition.id}`" class="btn">Annuler</Link>
          <button type="submit" class="btn btn-felt" :disabled="form.processing" style="margin-left: auto;">
            {{ form.processing ? 'Enregistrement…' : 'Enregistrer les modifications' }}
          </button>
        </div>
      </form>
    </main>
  </div>
</template>
