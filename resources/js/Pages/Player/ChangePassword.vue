<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '@/Components/Logo.vue';

const props = defineProps({
  forced: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const submit = () => form.post('/joueur/password/change');
</script>

<template>
  <Head title="Changer mon mot de passe · Espace Joueur" />

  <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;
              background: var(--ink); padding: 24px;">
    <div style="width: 100%; max-width: 440px;">
      <!-- Brand -->
      <div style="display: flex; justify-content: center; margin-bottom: 36px;">
        <Link href="/"><Logo :size="40" /></Link>
      </div>

      <!-- Forced notice -->
      <div v-if="forced"
           style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #F59E0B;
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px; line-height: 1.5;">
        <strong class="mono" style="font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; display: block; margin-bottom: 4px;">
          CHANGEMENT OBLIGATOIRE
        </strong>
        Vous devez définir un nouveau mot de passe avant d'accéder à votre espace.
      </div>

      <!-- Flash success -->
      <div v-if="flash.success"
           style="background: rgba(31,138,91,0.15); border: 1px solid rgba(31,138,91,0.35); color: var(--felt-2);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.success }}
      </div>

      <!-- Flash error -->
      <div v-if="flash.error"
           style="background: rgba(229,72,77,0.1); border: 1px solid rgba(229,72,77,0.3); color: var(--live);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.error }}
      </div>

      <!-- Card -->
      <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 12px; padding: 40px;">
        <!-- Header -->
        <div style="margin-bottom: 32px;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); text-transform: uppercase; margin-bottom: 10px;">
            ESPACE JOUEUR
          </div>
          <h1 class="disp-a" style="font-size: 28px; margin: 0;">CHANGER MON MOT DE PASSE</h1>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" style="display: flex; flex-direction: column; gap: 18px;">
          <!-- Mot de passe actuel -->
          <label>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
              Mot de passe actuel
            </div>
            <input
              v-model="form.current_password"
              type="password"
              required
              autocomplete="current-password"
              :style="{ borderColor: form.errors.current_password ? 'var(--live)' : '' }"
            />
            <div v-if="form.errors.current_password" class="mono" style="color: var(--live); font-size: 11px; margin-top: 6px;">
              {{ form.errors.current_password }}
            </div>
          </label>

          <!-- Nouveau mot de passe -->
          <label>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
              Nouveau mot de passe
            </div>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              :style="{ borderColor: form.errors.password ? 'var(--live)' : '' }"
            />
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 5px; letter-spacing: 0.06em;">
              min. 8 caractères
            </div>
            <div v-if="form.errors.password" class="mono" style="color: var(--live); font-size: 11px; margin-top: 4px;">
              {{ form.errors.password }}
            </div>
          </label>

          <!-- Confirmer -->
          <label>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
              Confirmer le mot de passe
            </div>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              :style="{ borderColor: form.errors.password_confirmation ? 'var(--live)' : '' }"
            />
            <div v-if="form.errors.password_confirmation" class="mono" style="color: var(--live); font-size: 11px; margin-top: 6px;">
              {{ form.errors.password_confirmation }}
            </div>
          </label>

          <!-- Submit -->
          <button
            type="submit"
            class="btn btn-felt"
            :disabled="form.processing"
            style="width: 100%; justify-content: center; margin-top: 4px; font-family: var(--font-display-a);
                   font-size: 14px; letter-spacing: 0.06em; font-weight: 700;"
          >
            {{ form.processing ? 'Enregistrement…' : 'ENREGISTRER →' }}
          </button>
        </form>
      </div>

      <!-- Back link (only when not forced) -->
      <div v-if="!forced" style="text-align: center; margin-top: 24px;">
        <Link href="/joueur/dashboard" style="color: var(--mute); font-size: 13px; text-decoration: none;">← Retour</Link>
      </div>
    </div>
  </div>
</template>
