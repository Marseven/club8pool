<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Logo from '@/Components/Logo.vue';

const form = useForm({
  login_name: '',
  password: '',
  remember: false,
});

const submit = () => form.post('/joueur/login');
</script>

<template>
  <Head title="Connexion · Espace Joueur" />

  <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;
              background: var(--ink); padding: 24px;">
    <div style="width: 100%; max-width: 440px;">
      <!-- Brand -->
      <div style="display: flex; justify-content: center; margin-bottom: 36px;">
        <Link href="/"><Logo :size="40" /></Link>
      </div>

      <!-- Card -->
      <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 12px; padding: 40px;">
        <!-- Header -->
        <div style="margin-bottom: 32px; text-align: center;">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--felt-2); text-transform: uppercase; margin-bottom: 10px;">
            ESPACE JOUEUR
          </div>
          <h1 class="disp-a" style="font-size: 34px; margin: 0 0 10px;">ESPACE JOUEUR</h1>
          <p style="color: var(--mute); font-size: 13px; margin: 0;">
            Connectez-vous avec votre identifiant
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" style="display: flex; flex-direction: column; gap: 18px;">
          <!-- Identifiant -->
          <label>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
              Identifiant
            </div>
            <input
              v-model="form.login_name"
              type="text"
              required
              autocomplete="username"
              placeholder="Votre prénom"
              :style="{ borderColor: form.errors.login_name ? 'var(--live)' : '' }"
            />
            <div v-if="form.errors.login_name" class="mono" style="color: var(--live); font-size: 11px; margin-top: 6px;">
              {{ form.errors.login_name }}
            </div>
          </label>

          <!-- Mot de passe -->
          <label>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
              Mot de passe
            </div>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              :style="{ borderColor: form.errors.password ? 'var(--live)' : '' }"
            />
            <div v-if="form.errors.password" class="mono" style="color: var(--live); font-size: 11px; margin-top: 6px;">
              {{ form.errors.password }}
            </div>
          </label>

          <!-- Se souvenir -->
          <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--mute); cursor: pointer;">
            <input v-model="form.remember" type="checkbox" style="width: auto; margin: 0;" />
            <span class="mono" style="font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase;">SE SOUVENIR DE MOI</span>
          </label>

          <!-- Error général -->
          <div v-if="form.errors.credentials" class="mono"
               style="background: rgba(229,72,77,0.1); border: 1px solid rgba(229,72,77,0.3); color: var(--live);
                      font-size: 11px; padding: 10px 14px; border-radius: 6px;">
            {{ form.errors.credentials }}
          </div>

          <!-- Submit -->
          <button
            type="submit"
            class="btn btn-felt"
            :disabled="form.processing"
            style="width: 100%; justify-content: center; margin-top: 4px; font-family: var(--font-display-a);
                   font-size: 14px; letter-spacing: 0.06em; font-weight: 700;"
          >
            {{ form.processing ? 'Connexion…' : 'SE CONNECTER →' }}
          </button>
        </form>
      </div>

      <!-- Back link -->
      <div style="text-align: center; margin-top: 24px;">
        <Link href="/" style="color: var(--mute); font-size: 13px; text-decoration: none;">← Retour à l'accueil</Link>
      </div>
    </div>
  </div>
</template>
