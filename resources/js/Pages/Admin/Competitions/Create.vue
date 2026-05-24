<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Ball8 from '@/Components/Ball8.vue';

const step = ref(0);
const steps = ['Informations', 'Format & règles', 'Joueurs', 'Calendrier & tables', 'Publication'];

const form = useForm({
  name: 'Icone Pool Championship — Édition 2',
  discipline: '8-ball',
  format: 'single_elim',
  player_slots: 16,
  race_to: 7,
  shot_clock: 30,
  alternate_break: true,
  push_out: false,
  frame_pause: 60,
  tiebreak_race: 9,
  venue: 'Le Cadre, Libreville',
  city: 'Libreville',
  entry_fee: 25000,
  deposit: 10000,
  prize_pool: 1400000,
  starts_on: '',
  ends_on: '',
  registration_closes_at: '',
});

const disciplines = [
  { l: '8-Ball', sub: 'WPA · 56 billes · race to N', v: '8-ball' },
  { l: '10-Ball', sub: 'Push out, ball-in-hand', v: '10-ball' },
  { l: 'Snooker', sub: '22 billes · frames', v: 'snooker' },
  { l: 'Blackball', sub: 'Règles européennes', v: 'blackball' },
];

const formats = [
  ['single_elim', 'Élimination directe', '16 joueurs, tableau classique. Une défaite = élimination.'],
  ['double_elim', 'Double élimination', 'Tableau perdant. Plus long mais plus équitable.'],
  ['pools', 'Poules + finales', '4 poules de 4. Les 2 meilleurs en quart de finale.'],
  ['round_robin', 'Round-robin', 'Tout le monde joue contre tout le monde.'],
  ['simple', 'Match simple', 'Un seul match 1v1. Pour démos ou finales isolées.'],
  ['teams', 'Tournoi équipes', 'Équipes de 2-4 joueurs, manches alternées.'],
];

const submit = () => form.post('/admin/competitions');
</script>

<template>
  <Head title="Nouvelle compétition" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="comps" />
    <main style="flex: 1;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">NOUVELLE COMPÉTITION</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">
            Étape {{ step + 1 }} sur {{ steps.length }} · {{ steps[step] }}
          </div>
        </div>
        <div style="display: flex; gap: 10px;">
          <button class="btn">Brouillon</button>
        </div>
      </header>

      <div style="display: flex; border-bottom: 1px solid var(--line);">
        <div v-for="(s, i) in steps" :key="i" :style="{
          flex: 1, padding: '18px 24px',
          borderRight: i < steps.length - 1 ? '1px solid var(--line)' : 'none',
          background: i === step ? 'var(--ink-2)' : 'transparent',
          borderBottom: i === step ? '2px solid var(--felt-2)' : '2px solid transparent',
          marginBottom: '-1px',
          cursor: 'pointer',
        }" @click="step = i">
          <div class="mono" :style="{ fontSize: '10px', letterSpacing: '0.2em',
                                       color: i <= step ? 'var(--felt-2)' : 'var(--mute)' }">
            ÉTAPE {{ String(i + 1).padStart(2, '0') }}
          </div>
          <div :style="{ fontSize: '14px', fontWeight: 600, marginTop: '6px',
                color: i === step ? 'var(--chalk)' : i < step ? 'var(--chalk-2)' : 'var(--mute)' }">{{ s }}</div>
        </div>
      </div>

      <section style="display: grid; grid-template-columns: 1fr 360px;">
        <div style="padding: 32px; border-right: 1px solid var(--line);">
          <template v-if="step === 0">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Informations</h3>
            <label style="display: block; margin-bottom: 16px;">
              <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">NOM</div>
              <input v-model="form.name" />
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">LIEU</div>
                <input v-model="form.venue" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">VILLE</div>
                <input v-model="form.city" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">DÉBUT</div>
                <input v-model="form.starts_on" type="date" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">FIN</div>
                <input v-model="form.ends_on" type="date" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">CLÔTURE INSCRIPTIONS</div>
                <input v-model="form.registration_closes_at" type="datetime-local" />
              </label>
            </div>
          </template>

          <template v-else-if="step === 1">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Discipline</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 32px;">
              <div v-for="d in disciplines" :key="d.v" @click="form.discipline = d.v" :style="{
                padding: '14px', cursor: 'pointer',
                border: '1px solid ' + (form.discipline === d.v ? 'var(--felt-2)' : 'var(--line-strong)'),
                background: form.discipline === d.v ? 'rgba(45,168,118,0.06)' : 'var(--ink-2)',
              }">
                <div style="display: flex; justify-content: space-between;">
                  <Ball8 :size="24" />
                  <span v-if="form.discipline === d.v" class="mono" style="font-size: 9px; color: var(--felt-2);">✓ SÉLECTIONNÉ</span>
                </div>
                <div class="disp-a" style="font-size: 22px; margin-top: 14px;">{{ d.l }}</div>
                <div style="font-size: 11px; color: var(--mute); margin-top: 6px;">{{ d.sub }}</div>
              </div>
            </div>

            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Format de compétition</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 32px;">
              <div v-for="[v, l, sub] in formats" :key="v" @click="form.format = v" :style="{
                padding: '16px', cursor: 'pointer',
                border: '1px solid ' + (form.format === v ? 'var(--felt-2)' : 'var(--line-strong)'),
                background: form.format === v ? 'rgba(45,168,118,0.06)' : 'var(--ink-2)',
              }">
                <div class="disp-a" style="font-size: 18px;">{{ l }}</div>
                <div style="font-size: 11px; color: var(--mute); margin-top: 8px; line-height: 1.5;">{{ sub }}</div>
              </div>
            </div>

            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Règles spécifiques</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">RACE TO</div>
                <input v-model.number="form.race_to" type="number" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SHOT CLOCK (sec)</div>
                <input v-model.number="form.shot_clock" type="number" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">PAUSE FRAMES</div>
                <input v-model.number="form.frame_pause" type="number" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">RACE TIEBREAK</div>
                <input v-model.number="form.tiebreak_race" type="number" />
              </label>
            </div>
          </template>

          <template v-else-if="step === 2">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Joueurs · {{ form.player_slots }} places</h3>
            <label>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">PLACES</div>
              <input v-model.number="form.player_slots" type="number" />
            </label>
          </template>

          <template v-else-if="step === 3">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Frais & dotation</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
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
          </template>

          <template v-else>
            <h3 class="disp-a" style="font-size: 22px;">Récapitulatif &amp; publication</h3>
            <p style="color: var(--mute); margin-top: 12px;">Vérifiez les informations avant publication.</p>
            <pre style="margin-top: 16px; padding: 16px; background: var(--ink-2); border: 1px solid var(--line); overflow: auto;"
                 class="mono">{{ JSON.stringify(form.data(), null, 2) }}</pre>
          </template>
        </div>

        <aside style="padding: 32px 24px; display: flex; flex-direction: column; gap: 24px;">
          <h4 class="disp-a" style="font-size: 20px;">Aperçu</h4>
          <div style="border: 1px solid var(--line-strong); background: var(--ink-2); padding: 18px;">
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">NOM</div>
            <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ form.name }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-top: 14px;">FORMAT</div>
            <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ form.format }} · {{ form.player_slots }} j.</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-top: 14px;">RACE</div>
            <div class="disp-a tnum" style="font-size: 28px; margin-top: 4px;">RACE TO {{ form.race_to }}</div>
          </div>

          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">CHECKLIST</div>
            <div v-for="(s, i) in steps" :key="i"
                 style="display: flex; gap: 12px; align-items: center; padding: 10px 0; border-top: 1px solid var(--line);">
              <span :style="{
                width: '18px', height: '18px', borderRadius: '2px',
                background: i < step ? 'var(--felt-2)' : 'transparent',
                border: '1px solid ' + (i === step ? 'var(--felt-2)' : 'var(--line-strong)'),
                color: i < step ? 'var(--ink)' : 'var(--felt-2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontFamily: 'var(--font-mono)', fontSize: '10px',
              }">{{ i < step ? '✓' : i === step ? '·' : '' }}</span>
              <span :style="{ fontSize: '13px', color: i > step ? 'var(--mute)' : 'var(--chalk)' }">{{ s }}</span>
            </div>
          </div>

          <div style="margin-top: auto; display: flex; flex-direction: column; gap: 8px;">
            <button v-if="step < steps.length - 1" class="btn btn-felt" @click="step++">Étape suivante →</button>
            <button v-else class="btn btn-felt" @click="submit" :disabled="form.processing">
              {{ form.processing ? 'Création…' : 'Publier la compétition' }}
            </button>
            <button v-if="step > 0" class="btn" @click="step--">← Étape précédente</button>
          </div>
        </aside>
      </section>
    </main>
  </div>
</template>
