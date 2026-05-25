<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import Logo from './Logo.vue';
import GabonFlag from './GabonFlag.vue';
import { X, Menu } from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const open = ref(false);

const links = [
  { label: 'Compétitions', href: '/competitions', external: false },
  { label: 'Joueurs', href: '/joueurs', external: false },
  { label: 'Inscription', href: '/inscription', external: false },
  { label: 'Live ↗', href: '/live', external: true },
];

watch(open, (v) => {
  document.body.classList.toggle('mobile-drawer-open', v);
});

onMounted(() => {
  const mq = window.matchMedia('(min-width: 769px)');
  mq.addEventListener('change', (e) => { if (e.matches) open.value = false; });
});
</script>

<template>
  <nav style="display: flex; align-items: center; justify-content: space-between;
              padding: 14px 24px; border-bottom: 1px solid var(--line);
              background: rgba(10,10,11,0.85); backdrop-filter: blur(8px);
              position: sticky; top: 0; z-index: 50;">
    <Link href="/" @click="open = false"><Logo :size="32" /></Link>

    <div class="mobile-hidden" style="display: flex; gap: 24px; font-size: 13px; font-weight: 600;
                letter-spacing: 0.04em; text-transform: uppercase;">
      <template v-for="l in links" :key="l.href">
        <a v-if="l.external" :href="l.href" target="_blank" rel="noopener"
           :style="{ color: l.label.includes('Live') ? 'var(--live)' : 'var(--mute)' }">
          {{ l.label }}
        </a>
        <Link v-else :href="l.href" style="color: var(--mute);">{{ l.label }}</Link>
      </template>
    </div>

    <div class="mobile-hidden" style="display: flex; gap: 8px; align-items: center;">
      <GabonFlag :width="20" :height="14" />
      <template v-if="auth">
        <Link :href="auth.role === 'referee' ? '/arbitre' : '/admin'" class="btn">Mon espace</Link>
        <Link as="button" method="post" href="/logout" class="btn">↪</Link>
      </template>
      <template v-else>
        <Link href="/login" class="btn">Se connecter</Link>
      </template>
    </div>

    <button class="mobile-hamburger" @click="open = !open" aria-label="Menu">
      <X v-if="open" :size="18" />
      <Menu v-else :size="18" />
    </button>
  </nav>

  <div v-if="open" class="mobile-drawer">
    <header style="display: flex; align-items: center; justify-content: space-between;
                   padding: 14px 24px; border-bottom: 1px solid var(--line);">
      <Logo :size="32" />
      <button class="mobile-hamburger" @click="open = false" aria-label="Fermer">
        <X :size="18" />
      </button>
    </header>

    <div style="padding: 24px; display: flex; flex-direction: column;">
      <template v-for="l in links" :key="l.href">
        <a v-if="l.external" :href="l.href" target="_blank" rel="noopener" @click="open = false"
           :style="{
             display: 'flex', alignItems: 'center', justifyContent: 'space-between',
             padding: '18px 4px',
             borderBottom: '1px solid var(--line)',
             color: l.label.includes('Live') ? 'var(--live)' : 'var(--chalk)',
             fontFamily: 'var(--font-display-a)',
             fontSize: '24px', fontWeight: 700, letterSpacing: '0.02em',
           }">
          <span>{{ l.label }}</span>
          <span style="font-size: 14px; color: var(--mute);">↗</span>
        </a>
        <Link v-else :href="l.href" @click="open = false"
              :style="{
                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                padding: '18px 4px',
                borderBottom: '1px solid var(--line)',
                color: 'var(--chalk)',
                fontFamily: 'var(--font-display-a)',
                fontSize: '24px', fontWeight: 700, letterSpacing: '0.02em',
              }">
          <span>{{ l.label }}</span>
          <span style="font-size: 14px; color: var(--mute);">→</span>
        </Link>
      </template>
    </div>

    <div style="margin-top: auto; padding: 24px; border-top: 1px solid var(--line); display: flex; gap: 10px;">
      <template v-if="auth">
        <Link :href="auth.role === 'referee' ? '/arbitre' : '/admin'" class="btn btn-felt" @click="open = false"
              style="flex: 1; justify-content: center;">Mon espace</Link>
        <Link as="button" method="post" href="/logout" class="btn" style="flex: 1; justify-content: center;">Déconnexion</Link>
      </template>
      <template v-else>
        <Link href="/login" class="btn btn-felt" @click="open = false" style="flex: 1; justify-content: center;">Se connecter</Link>
      </template>
    </div>

    <div style="padding: 18px 24px; display: flex; align-items: center; gap: 10px; color: var(--mute); border-top: 1px solid var(--line);">
      <GabonFlag :width="20" :height="14" />
      <span class="mono" style="font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase;">Icone Pool · Libreville</span>
    </div>
  </div>
</template>
