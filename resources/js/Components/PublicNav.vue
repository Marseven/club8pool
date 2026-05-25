<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from './Logo.vue';
import GabonFlag from './GabonFlag.vue';

const page = usePage();
const auth = computed(() => page.props.auth?.user);

const links = [
  { label: 'Compétitions', href: '/competitions', external: false },
  { label: 'Classement', href: '/competitions#ranking', external: false },
  { label: 'Joueurs', href: '/joueurs', external: false },
  { label: 'Inscription', href: '/inscription', external: false },
  { label: 'Live ↗', href: '/live', external: true },
];
</script>

<template>
  <nav style="display: flex; align-items: center; justify-content: space-between;
              padding: 18px 48px; border-bottom: 1px solid var(--line);
              background: rgba(10,10,11,0.6); backdrop-filter: blur(8px);
              position: sticky; top: 0; z-index: 10;">
    <Link href="/"><Logo :size="36" /></Link>
    <div style="display: flex; gap: 28px; font-size: 13px; font-weight: 600;
                letter-spacing: 0.04em; text-transform: uppercase;">
      <template v-for="l in links" :key="l.href">
        <a v-if="l.external" :href="l.href" target="_blank" rel="noopener" :style="{ color: l.label.includes('Live') ? 'var(--live)' : 'var(--mute)' }">
          {{ l.label }}
        </a>
        <Link v-else :href="l.href" style="color: var(--mute);">{{ l.label }}</Link>
      </template>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <GabonFlag :width="22" :height="15" />
      <template v-if="auth">
        <Link :href="auth.role === 'referee' ? '/arbitre' : '/admin'" class="btn">Mon espace</Link>
        <Link as="button" method="post" href="/logout" class="btn">Déconnexion</Link>
      </template>
      <template v-else>
        <Link href="/login" class="btn">Se connecter</Link>
        <Link href="/inscription" class="btn btn-primary">S'inscrire</Link>
      </template>
    </div>
  </nav>
</template>
