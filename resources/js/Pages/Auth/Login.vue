<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Ball8 from '@/Components/Ball8.vue';
import GabonFlag from '@/Components/GabonFlag.vue';

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login');
</script>

<template>
  <Head title="Connexion" />
  <div style="min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; background: var(--ink);">
    <div style="padding: 56px 64px; border-right: 1px solid var(--line); background: var(--ink-2);
                display: flex; flex-direction: column; justify-content: space-between;">
      <Link href="/" style="display: flex; align-items: center; gap: 12px;">
        <Ball8 :size="42" />
        <span class="disp-a" style="font-size: 24px;">Club 8 Pool</span>
      </Link>
      <div>
        <span class="chip felt">ESPACE ORGANISATEUR · FGB</span>
        <h1 class="disp-a" style="font-size: 80px; line-height: 0.9; margin-top: 24px;">
          Connexion<br /><span style="color: var(--felt-2);">administration</span>
        </h1>
        <p style="margin-top: 24px; max-width: 460px; color: var(--chalk-2); font-size: 15px; line-height: 1.5;">
          Configurez les compétitions, suivez les tables en temps réel, et orchestrez les tirages au sort
          depuis votre interface.
        </p>
      </div>
      <div style="display: flex; align-items: center; gap: 12px; color: var(--mute);" class="mono">
        <GabonFlag :width="22" :height="15" />
        <span style="font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase;">
          Fédération Gabonaise de Billard
        </span>
      </div>
    </div>

    <div style="padding: 64px; display: flex; flex-direction: column; justify-content: center;">
      <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">ESPACE PRIVÉ</div>
      <h2 class="disp-a" style="font-size: 56px; margin-top: 14px;">Se connecter</h2>

      <form @submit.prevent="submit" style="margin-top: 36px; max-width: 420px; display: flex; flex-direction: column; gap: 18px;">
        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); text-transform: uppercase; margin-bottom: 8px;">
            Email
          </div>
          <input v-model="form.email" type="email" required autocomplete="username" placeholder="admin@club8pool.ga" />
          <div v-if="form.errors.email" style="color: var(--live); font-size: 11px; margin-top: 6px;" class="mono">{{ form.errors.email }}</div>
        </label>
        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); text-transform: uppercase; margin-bottom: 8px;">
            Mot de passe
          </div>
          <input v-model="form.password" type="password" required autocomplete="current-password" />
        </label>
        <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--mute);">
          <input v-model="form.remember" type="checkbox" style="width: auto; margin: 0;" />
          Se souvenir de moi
        </label>
        <button type="submit" class="btn btn-felt" :disabled="form.processing" style="margin-top: 12px;">
          {{ form.processing ? 'Connexion…' : 'Se connecter →' }}
        </button>
      </form>

      <div style="margin-top: 48px; padding: 18px; border: 1px solid var(--line); background: var(--ink-2); max-width: 420px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.2em; color: var(--mute);">DÉMO</div>
        <div style="font-size: 12px; color: var(--chalk-2); margin-top: 8px; line-height: 1.5;">
          Admin · <span class="mono">admin@club8pool.ga</span> / <span class="mono">password</span><br/>
          Arbitre · <span class="mono">olivier@club8pool.ga</span> / <span class="mono">password</span>
        </div>
      </div>
    </div>
  </div>
</template>
