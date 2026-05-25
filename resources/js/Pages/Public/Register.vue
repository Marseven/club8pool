<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  clubs: Array,
  slots: Number,
  registered: Number,
  isOpen: Boolean,
  closedReason: String,
});

const step = ref(0);
const steps = ['Identité', 'Club & cue', 'Paiement', 'Validation'];

const form = useForm({
  first_name: '',
  last_name: '',
  birthdate: '',
  fgb_card: '',
  phone: '',
  email: '',
  address: '',
  club_id: null,
  cue: '',
  competition_id: props.competition?.id,
});

const remaining = computed(() => Math.max(0, props.slots - props.registered));

const submit = () => form.post('/inscription', {
  onSuccess: () => step.value = 3,
});

const fmtFcfa = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
</script>

<template>
  <Head title="Inscription">
    <meta name="description" :content="`Inscrivez-vous au ${competition?.name}. ${slots - registered} places restantes. Frais d'inscription ${competition?.entry_fee} FCFA. Dotation ${competition?.prize_pool} FCFA.`" head-key="description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />
    <section style="display: grid; grid-template-columns: 1fr 1fr; min-height: calc(100vh - 73px);">
      <div style="padding: 48px 56px; border-right: 1px solid var(--line);
                  background: var(--ink-2); display: flex; flex-direction: column;">
        <Chip v-if="isOpen" style="width: fit-content;" variant="felt">INSCRIPTIONS OUVERTES</Chip>
        <Chip v-else style="width: fit-content;" variant="live">INSCRIPTIONS FERMÉES</Chip>
        <h1 class="disp-a" style="font-size: 80px; line-height: 0.92; margin-top: 24px;">
          {{ competition?.name?.split(' — ')[0] }}<br />
          <span style="color: var(--felt-2);">2026</span>
        </h1>
        <p style="font-size: 15px; color: var(--chalk-2); max-width: 460px; line-height: 1.6; margin-top: 28px;">
          {{ slots }} places. Élimination directe. Race to {{ competition?.race_to }}. Du 05 au 07 juin au Cadre, Libreville.
          Inscriptions closes le 04/06 à 18h.
        </p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px;">
          <div v-for="(item, i) in [
            ['Frais d\'inscription', fmtFcfa(competition?.entry_fee)],
            ['Caution caisse', fmtFcfa(competition?.deposit)],
            ['Dotation totale', fmtFcfa(competition?.prize_pool)],
            ['Format', 'Tableau ' + slots + ' · race to ' + competition?.race_to],
          ]" :key="i">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); text-transform: uppercase;">{{ item[0] }}</div>
            <div style="font-size: 18px; font-weight: 600; margin-top: 6px;">{{ item[1] }}</div>
          </div>
        </div>
        <div style="margin-top: auto; padding: 20px 0 0; border-top: 1px solid var(--line);">
          <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; color: var(--mute);">PLACES RESTANTES</div>
          <div style="display: flex; gap: 3px; margin-top: 12px;">
            <span v-for="i in slots" :key="i"
                  :style="{ flex: 1, height: '10px',
                           background: i <= (slots - registered) ? 'var(--felt)' : 'var(--ink-4)' }" />
          </div>
          <div style="display: flex; justify-content: space-between; margin-top: 10px;">
            <span class="disp-a tnum" style="font-size: 28px;">
              {{ String(remaining).padStart(2, '0') }}<span style="color: var(--mute);">/{{ slots }}</span>
            </span>
            <span class="mono" style="font-size: 11px; color: var(--mute);">{{ registered }} INSCRITS</span>
          </div>
        </div>
      </div>

      <div style="padding: 48px 56px;">
        <!-- État : inscriptions fermées -->
        <div v-if="!isOpen" style="display: flex; flex-direction: column; align-items: flex-start; gap: 22px; padding: 40px 0;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(229,72,77,0.12);
                        border: 1px solid rgba(229,72,77,0.4); display: flex; align-items: center; justify-content: center;
                        font-family: var(--font-display-a); font-size: 28px; color: var(--live);">⊘</div>
            <div>
              <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--live);">INSCRIPTIONS FERMÉES</div>
              <div class="disp-a" style="font-size: 26px; margin-top: 6px;">
                {{ competition?.status === 'in_progress' ? 'La compétition a démarré' : competition?.status === 'finished' ? 'Compétition terminée' : 'Tableau complet' }}
              </div>
            </div>
          </div>
          <p style="font-size: 14px; color: var(--chalk-2); max-width: 480px; line-height: 1.6;">
            {{ closedReason }}
          </p>
          <p style="font-size: 13px; color: var(--mute); line-height: 1.6;">
            Vous pouvez suivre la compétition en direct ou consulter le bracket et les classements de poules.
          </p>
          <div style="display: flex; gap: 10px; margin-top: 8px;">
            <a href="/live" target="_blank" class="btn btn-felt">Suivre le live →</a>
            <a href="/competitions" class="btn">Voir la compétition</a>
            <a href="/joueurs" class="btn">Liste des joueurs</a>
          </div>
        </div>

        <!-- Formulaire d'inscription -->
        <div v-if="isOpen" style="display: flex; gap: 0; margin-bottom: 36px;">
          <div v-for="(s, i) in steps" :key="i" style="flex: 1; display: flex; align-items: center; gap: 10px;">
            <span :style="{
              width: '28px', height: '28px', borderRadius: '50%',
              background: i === step ? 'var(--felt-2)' : i < step ? 'var(--felt)' : 'transparent',
              border: '1px solid ' + (i === step ? 'var(--felt-2)' : 'var(--line-strong)'),
              color: i === step ? 'var(--ink)' : 'var(--mute)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontFamily: 'var(--font-mono)', fontSize: '12px', fontWeight: 700,
            }">{{ i + 1 }}</span>
            <span class="mono" :style="{ fontSize: '11px', letterSpacing: '0.14em',
                  color: i === step ? 'var(--chalk)' : 'var(--mute)' }">{{ s.toUpperCase() }}</span>
            <span v-if="i < 3" style="flex: 1; height: 1px; background: var(--line); margin: 0 8px;" />
          </div>
        </div>

        <form v-if="isOpen && step !== 3" @submit.prevent="submit">
          <template v-if="step === 0">
            <h2 class="disp-a" style="font-size: 40px;">Identité du joueur</h2>
            <p style="font-size: 13px; color: var(--mute); margin-top: 8px;">
              Tels qu'inscrits sur la carte FGB. Ces informations seront affichées publiquement.
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 28px;">
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">PRÉNOM</div>
                <input v-model="form.first_name" required />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">NOM</div>
                <input v-model="form.last_name" required />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">DATE DE NAISSANCE</div>
                <input v-model="form.birthdate" type="date" required />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">CARTE FGB</div>
                <input v-model="form.fgb_card" required />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">TÉLÉPHONE</div>
                <input v-model="form.phone" required />
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">E-MAIL</div>
                <input v-model="form.email" type="email" required />
              </label>
              <label style="grid-column: 1 / -1;">
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">ADRESSE</div>
                <input v-model="form.address" required />
              </label>
            </div>
          </template>

          <template v-else-if="step === 1">
            <h2 class="disp-a" style="font-size: 40px;">Club &amp; cue</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 28px;">
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">CLUB</div>
                <select v-model="form.club_id">
                  <option :value="null">— sélectionner —</option>
                  <option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.name }} · {{ c.city }}</option>
                </select>
              </label>
              <label>
                <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px;">CUE</div>
                <input v-model="form.cue" placeholder="Predator 12.4mm" />
              </label>
            </div>
          </template>

          <template v-else-if="step === 2">
            <h2 class="disp-a" style="font-size: 40px;">Paiement</h2>
            <div style="margin-top: 28px; padding: 24px; border: 1px solid var(--line-strong); background: var(--ink-2);">
              <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute);">FRAIS</div>
              <div class="disp-a tnum" style="font-size: 48px; margin-top: 8px;">{{ fmtFcfa((competition?.entry_fee ?? 0) + (competition?.deposit ?? 0)) }}</div>
              <p style="font-size: 13px; color: var(--mute); margin-top: 12px;">
                Mobile Money · Espèces FGB · Virement BICIG
              </p>
            </div>
          </template>

          <label style="display: flex; gap: 12px; align-items: flex-start; margin-top: 28px;">
            <input type="checkbox" required style="width: auto; margin: 0;" />
            <span style="font-size: 12px; color: var(--mute); line-height: 1.6;">
              J'accepte le règlement intérieur FGB et la charte du joueur (équipement, ponctualité, anti-dopage).
            </span>
          </label>

          <div style="display: flex; gap: 12px; margin-top: 36px;">
            <button v-if="step > 0" type="button" class="btn" @click="step--">← Précédent</button>
            <button v-if="step < 2" type="button" class="btn btn-felt" style="margin-left: auto;" @click="step++">Étape suivante →</button>
            <button v-else type="submit" class="btn btn-felt" style="margin-left: auto;" :disabled="form.processing">
              {{ form.processing ? 'Envoi…' : 'Confirmer l\'inscription →' }}
            </button>
          </div>
        </form>

        <div v-if="isOpen && step === 3" style="padding: 60px 0; text-align: center;">
          <div class="disp-a" style="font-size: 64px; color: var(--felt-2);">✓</div>
          <h2 class="disp-a" style="font-size: 40px; margin-top: 18px;">Inscription envoyée</h2>
          <p style="font-size: 14px; color: var(--mute); margin-top: 12px;">
            Vous serez contacté par la FGB sous 48h pour la validation.
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
