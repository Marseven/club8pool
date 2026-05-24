<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Ball8 from '@/Components/Ball8.vue';

const step = ref(0);
const steps = ['Informations', 'Format', 'Règles & race', 'Frais & dotation', 'Publication'];

const form = useForm({
  name: 'Icone Pool Championship — Édition 2',
  venue: 'Salle Icone, Libreville',
  city: 'Libreville',
  discipline: '8-ball',
  format: 'pools',
  structure: 'pools_knockout',
  player_slots: 28,
  pool_count: 4,
  pool_size: 7,
  qualifiers_per_pool: 2,
  race_to: 3,
  shot_clock: 30,
  alternate_break: true,
  allow_draw: true,
  enable_warnings: true,
  push_out: false,
  frame_pause: 60,
  tiebreak_race: 5,
  entry_fee: 10000,
  deposit: 5000,
  prize_pool: 600000,
  starts_on: '',
  ends_on: '',
  registration_closes_at: '',
});

const structures = [
  { v: 'knockout', l: 'Élimination directe', sub: 'Tableau classique. Une défaite = élimination.', defaultSlots: 16, hint: 'Bracket 8 / 16 / 32 joueurs' },
  { v: 'pools_knockout', l: 'Poules + phase finale', sub: 'Round-robin par poule. Les meilleurs de chaque poule se qualifient en phase finale (bracket).', defaultSlots: 28, hint: '4 poules × 7 → bracket de 8' },
  { v: 'pools_only', l: 'Poules uniquement', sub: 'Round-robin par poule, sans phase finale. Le classement de poule décide.', defaultSlots: 28 },
  { v: 'round_robin', l: 'Round-robin général', sub: 'Tout le monde joue contre tout le monde. Pas de poules ni de bracket.', defaultSlots: 8 },
];

const disciplines = [
  { v: '8-ball', l: '8-Ball', sub: 'WPA · 56 billes' },
  { v: '10-ball', l: '10-Ball', sub: 'Push out, ball-in-hand' },
  { v: 'snooker', l: 'Snooker', sub: '22 billes · frames' },
  { v: 'blackball', l: 'Blackball', sub: 'Règles européennes' },
];

const racePresets = [3, 5, 7, 9, 11];

const optionsList = [
  ['alternate_break', 'Break alterné', 'Le break alterne à chaque manche'],
  ['allow_draw', 'Autoriser les nuls', 'Les matchs peuvent finir à égalité (ex. 1-1 en race to 3)'],
  ['enable_warnings', 'Avertissements', "L'arbitre peut donner un avertissement à un joueur"],
  ['push_out', 'Push out', 'Autorisé après le break (10-ball)'],
];

const usesPools = computed(() => ['pools_knockout', 'pools_only'].includes(form.structure));

watch(() => form.structure, (val) => {
  const s = structures.find(s => s.v === val);
  if (s) form.player_slots = s.defaultSlots;
  if (!['pools_knockout', 'pools_only'].includes(val)) {
    form.pool_count = 0;
    form.pool_size = 0;
    form.qualifiers_per_pool = 0;
  } else if (form.pool_count === 0) {
    form.pool_count = 4;
    form.pool_size = 7;
    form.qualifiers_per_pool = 2;
  }
});

watch([() => form.pool_count, () => form.pool_size], () => {
  if (usesPools.value) form.player_slots = form.pool_count * form.pool_size;
});

const submit = () => form.post('/admin/competitions');

const formatFcfa = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
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

          <!-- STEP 0: Infos -->
          <template v-if="step === 0">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Informations générales</h3>
            <label style="display: block; margin-bottom: 16px;">
              <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">NOM DE LA COMPÉTITION</div>
              <input v-model="form.name" placeholder="Icone Pool Championship — Édition 2" />
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
              <label style="grid-column: 1 / -1;">
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 6px;">CLÔTURE DES INSCRIPTIONS</div>
                <input v-model="form.registration_closes_at" type="datetime-local" />
              </label>
            </div>
          </template>

          <!-- STEP 1: Format / structure -->
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
                  <span v-if="form.discipline === d.v" class="mono" style="font-size: 9px; color: var(--felt-2);">✓</span>
                </div>
                <div class="disp-a" style="font-size: 22px; margin-top: 14px;">{{ d.l }}</div>
                <div style="font-size: 11px; color: var(--mute); margin-top: 6px;">{{ d.sub }}</div>
              </div>
            </div>

            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Structure du tournoi</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
              <div v-for="s in structures" :key="s.v" @click="form.structure = s.v" :style="{
                padding: '18px', cursor: 'pointer',
                border: '1px solid ' + (form.structure === s.v ? 'var(--felt-2)' : 'var(--line-strong)'),
                background: form.structure === s.v ? 'rgba(45,168,118,0.06)' : 'var(--ink-2)',
              }">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span class="disp-a" style="font-size: 18px;">{{ s.l }}</span>
                  <span v-if="form.structure === s.v" class="mono" style="font-size: 9px; color: var(--felt-2); letter-spacing: 0.14em;">✓ SÉLECTIONNÉ</span>
                </div>
                <div style="font-size: 12px; color: var(--mute); margin-top: 10px; line-height: 1.5;">{{ s.sub }}</div>
                <div v-if="s.hint" class="mono" style="font-size: 10px; color: var(--felt-2); letter-spacing: 0.14em; margin-top: 10px;">{{ s.hint }}</div>
              </div>
            </div>

            <template v-if="usesPools">
              <h4 class="disp-a" style="font-size: 18px; margin-bottom: 14px;">Configuration des poules</h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
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
              </div>
              <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 12px; letter-spacing: 0.12em;">
                = {{ form.pool_count * form.pool_size }} JOUEURS · {{ form.pool_count * (form.pool_size * (form.pool_size - 1) / 2) }} MATCHS DE POULE
              </div>
            </template>
            <template v-else>
              <label style="display: block; max-width: 200px;">
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">NOMBRE DE JOUEURS</div>
                <input v-model.number="form.player_slots" type="number" min="2" max="128" />
              </label>
            </template>
          </template>

          <!-- STEP 2: Race & règles -->
          <template v-else-if="step === 2">
            <div style="margin-bottom: 36px;">
              <h3 class="disp-a" style="font-size: 22px; margin-bottom: 6px;">Race to · Manches gagnantes</h3>
              <p style="font-size: 13px; color: var(--mute); margin-bottom: 18px;">
                Premier joueur à atteindre <strong style="color: var(--chalk); font-weight: 700;">{{ form.race_to }} manches gagnées</strong> remporte le match.
              </p>

              <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button v-for="n in racePresets" :key="n" type="button" @click="form.race_to = n" :style="{
                  width: '80px', height: '80px', cursor: 'pointer',
                  border: '1px solid ' + (form.race_to === n ? 'var(--felt-2)' : 'var(--line-strong)'),
                  background: form.race_to === n ? 'rgba(45,168,118,0.08)' : 'var(--ink-2)',
                  display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                }">
                  <div class="mono" style="font-size: 8px; letter-spacing: 0.2em; color: var(--mute);">RACE TO</div>
                  <div class="disp-a tnum" :style="{ fontSize: '36px', color: form.race_to === n ? 'var(--felt-2)' : 'var(--chalk)' }">{{ n }}</div>
                </button>
                <div style="width: 80px; height: 80px; border: 1px dashed var(--line-strong);
                            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                  <div class="mono" style="font-size: 8px; letter-spacing: 0.2em; color: var(--mute);">CUSTOM</div>
                  <input v-model.number="form.race_to" type="number" min="1" max="25"
                         style="width: 60px; padding: 4px; text-align: center; font-family: var(--font-display-a);
                                font-size: 22px; background: transparent; border: 1px solid var(--line); color: var(--chalk);" />
                </div>
              </div>
            </div>

            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Règles spécifiques</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">SHOT CLOCK (sec)</div>
                <input v-model.number="form.shot_clock" type="number" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">PAUSE FRAMES (sec)</div>
                <input v-model.number="form.frame_pause" type="number" />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-bottom: 6px;">RACE DÉPARTAGE</div>
                <input v-model.number="form.tiebreak_race" type="number" />
              </label>
            </div>

            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">OPTIONS</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
              <label v-for="opt in optionsList" :key="opt[0]" :style="{
                display: 'flex', gap: '12px', alignItems: 'flex-start',
                padding: '14px', cursor: 'pointer',
                border: '1px solid ' + (form[opt[0]] ? 'var(--felt-2)' : 'var(--line-strong)'),
                background: form[opt[0]] ? 'rgba(45,168,118,0.05)' : 'var(--ink-2)',
              }">
                <input v-model="form[opt[0]]" type="checkbox" style="width: auto; margin: 2px 0 0;" />
                <div>
                  <div style="font-size: 13px; font-weight: 600;">{{ opt[1] }}</div>
                  <div style="font-size: 11px; color: var(--mute); margin-top: 4px;">{{ opt[2] }}</div>
                </div>
              </label>
            </div>
          </template>

          <!-- STEP 3: Frais -->
          <template v-else-if="step === 3">
            <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">Frais &amp; dotation</h3>
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

          <!-- STEP 4: Recap -->
          <template v-else>
            <h3 class="disp-a" style="font-size: 22px;">Récapitulatif</h3>
            <p style="color: var(--mute); margin-top: 12px; font-size: 13px;">
              Vérifiez les paramètres ci-dessous. À la publication, les poules vides seront créées automatiquement.
            </p>
            <table class="tbl" style="margin-top: 20px;">
              <tr><th>Nom</th><td>{{ form.name }}</td></tr>
              <tr><th>Lieu</th><td>{{ form.venue }} · {{ form.city }}</td></tr>
              <tr><th>Discipline</th><td>{{ form.discipline }}</td></tr>
              <tr><th>Structure</th><td>{{ structures.find(s => s.v === form.structure)?.l }}</td></tr>
              <tr v-if="usesPools"><th>Poules</th><td>{{ form.pool_count }} × {{ form.pool_size }} joueurs · {{ form.qualifiers_per_pool }} qualifiés</td></tr>
              <tr v-else><th>Joueurs</th><td>{{ form.player_slots }}</td></tr>
              <tr><th>Race to</th><td><strong>{{ form.race_to }}</strong> manches gagnantes</td></tr>
              <tr><th>Shot clock</th><td>{{ form.shot_clock }} sec</td></tr>
              <tr><th>Options</th><td>
                <template v-if="form.alternate_break">Break alterné · </template>
                <template v-if="form.allow_draw">Nuls autorisés · </template>
                <template v-if="form.enable_warnings">Avertissements</template>
              </td></tr>
              <tr><th>Dotation</th><td>{{ formatFcfa(form.prize_pool) }}</td></tr>
            </table>
          </template>
        </div>

        <aside style="padding: 32px 24px; display: flex; flex-direction: column; gap: 24px;">
          <h4 class="disp-a" style="font-size: 20px;">Aperçu</h4>
          <div style="border: 1px solid var(--line-strong); background: var(--ink-2); padding: 18px;">
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">NOM</div>
            <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ form.name || '—' }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-top: 14px;">STRUCTURE</div>
            <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">{{ structures.find(s => s.v === form.structure)?.l }}</div>
            <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-top: 14px;">RACE</div>
            <div class="disp-a tnum" style="font-size: 36px; margin-top: 4px; color: var(--felt-2);">RACE TO {{ form.race_to }}</div>
            <div v-if="usesPools" class="mono" style="font-size: 10px; color: var(--mute); margin-top: 12px;">
              {{ form.pool_count }} poules · {{ form.player_slots }} joueurs
            </div>
            <div v-else class="mono" style="font-size: 10px; color: var(--mute); margin-top: 12px;">
              {{ form.player_slots }} joueurs
            </div>
          </div>

          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">PROGRESSION</div>
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
              {{ form.processing ? 'Création…' : '✓ Publier la compétition' }}
            </button>
            <button v-if="step > 0" class="btn" @click="step--">← Étape précédente</button>
          </div>
        </aside>
      </section>
    </main>
  </div>
</template>
