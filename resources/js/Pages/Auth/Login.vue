<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import GabonFlag from '@/Components/GabonFlag.vue';

const mode = ref('admin'); // 'admin' | 'referee'

const adminForm = useForm({ mode: 'admin', email: '', password: '', remember: false });
const refForm = useForm({ mode: 'referee', name: '', pin: '', remember: false });

watch(mode, () => {
  adminForm.clearErrors();
  refForm.clearErrors();
});

const submitAdmin = () => adminForm.post('/login');
const submitRef = () => refForm.post('/login');
</script>

<template>
  <Head title="Connexion" />
  <div style="min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; background: var(--ink);">
    <!-- Left visual panel -->
    <div style="padding: 56px 64px; border-right: 1px solid var(--line); background: var(--ink-2);
                display: flex; flex-direction: column; justify-content: space-between;">
      <Link href="/" style="display: flex; align-items: center; gap: 12px;">
        <Ball8 :size="42" />
        <span class="disp-a" style="font-size: 24px;">Club 8 Pool</span>
      </Link>
      <div>
        <span class="chip felt">ESPACE PRIVÉ</span>
        <h1 class="disp-a" style="font-size: 72px; line-height: 0.9; margin-top: 24px;">
          <template v-if="mode === 'admin'">Connexion<br/><span style="color: var(--felt-2);">administration</span></template>
          <template v-else>Connexion<br/><span style="color: var(--felt-2);">arbitre</span></template>
        </h1>
        <p style="margin-top: 24px; max-width: 460px; color: var(--chalk-2); font-size: 15px; line-height: 1.5;">
          <template v-if="mode === 'admin'">
            Configurez la compétition, pilotez les poules et démarrez les matchs depuis votre interface organisateur.
          </template>
          <template v-else>
            Rejoignez les matchs qui vous sont assignés. Identifiez-vous avec votre prénom et votre code PIN.
          </template>
        </p>
      </div>
      <div style="display: flex; align-items: center; gap: 12px; color: var(--mute);" class="mono">
        <GabonFlag :width="22" :height="15" />
        <span style="font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase;">
          Icone Pool · Libreville
        </span>
      </div>
    </div>

    <!-- Right form panel -->
    <div style="padding: 64px; display: flex; flex-direction: column; justify-content: center;">
      <!-- Mode toggle -->
      <div style="display: flex; gap: 0; margin-bottom: 36px; max-width: 420px; border: 1px solid var(--line-strong);">
        <button type="button" @click="mode = 'admin'" :style="{
          flex: 1, padding: '14px', cursor: 'pointer',
          background: mode === 'admin' ? 'var(--chalk)' : 'transparent',
          color: mode === 'admin' ? 'var(--ink)' : 'var(--mute)',
          border: 'none',
          fontFamily: 'var(--font-display-a)', fontWeight: 700, fontSize: '13px', letterSpacing: '0.08em', textTransform: 'uppercase',
        }">Administrateur</button>
        <button type="button" @click="mode = 'referee'" :style="{
          flex: 1, padding: '14px', cursor: 'pointer',
          background: mode === 'referee' ? 'var(--felt-2)' : 'transparent',
          color: mode === 'referee' ? 'var(--ink)' : 'var(--mute)',
          border: 'none',
          fontFamily: 'var(--font-display-a)', fontWeight: 700, fontSize: '13px', letterSpacing: '0.08em', textTransform: 'uppercase',
        }">Arbitre</button>
      </div>

      <!-- Admin form -->
      <form v-if="mode === 'admin'" @submit.prevent="submitAdmin" style="max-width: 420px; display: flex; flex-direction: column; gap: 18px;">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">ADMINISTRATEUR · ORGANISATEUR</div>
        <h2 class="disp-a" style="font-size: 48px;">Se connecter</h2>

        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
            Email
          </div>
          <input v-model="adminForm.email" type="email" required autocomplete="username" placeholder="admin@club8pool.ga" />
          <div v-if="adminForm.errors.email" style="color: var(--live); font-size: 11px; margin-top: 6px;" class="mono">{{ adminForm.errors.email }}</div>
        </label>
        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
            Mot de passe
          </div>
          <input v-model="adminForm.password" type="password" required autocomplete="current-password" />
        </label>
        <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--mute);">
          <input v-model="adminForm.remember" type="checkbox" style="width: auto; margin: 0;" />
          Se souvenir de moi
        </label>
        <button type="submit" class="btn btn-felt" :disabled="adminForm.processing" style="margin-top: 12px;">
          {{ adminForm.processing ? 'Connexion…' : 'Se connecter →' }}
        </button>
      </form>

      <!-- Referee form -->
      <form v-else @submit.prevent="submitRef" style="max-width: 420px; display: flex; flex-direction: column; gap: 18px;">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--felt-2);">ARBITRE · PRÉNOM + PIN</div>
        <h2 class="disp-a" style="font-size: 48px;">Connexion arbitre</h2>

        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
            Prénom
          </div>
          <input v-model="refForm.name" type="text" required autocomplete="username"
                 placeholder="Votre prénom" />
          <div v-if="refForm.errors.name" style="color: var(--live); font-size: 11px; margin-top: 6px;" class="mono">{{ refForm.errors.name }}</div>
        </label>
        <label>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute); margin-bottom: 8px; text-transform: uppercase;">
            Code PIN
          </div>
          <input v-model="refForm.pin" type="password" inputmode="numeric" required maxlength="8" autocomplete="current-password"
                 placeholder="•••••"
                 style="font-family: var(--font-mono); letter-spacing: 0.5em; font-size: 22px; text-align: center;" />
        </label>
        <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--mute);">
          <input v-model="refForm.remember" type="checkbox" style="width: auto; margin: 0;" />
          Garder ma session
        </label>
        <button type="submit" class="btn btn-felt" :disabled="refForm.processing" style="margin-top: 12px;">
          {{ refForm.processing ? 'Connexion…' : 'Se connecter →' }}
        </button>
      </form>
    </div>
  </div>
</template>
