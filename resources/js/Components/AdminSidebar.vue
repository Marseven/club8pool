<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import Logo from './Logo.vue';
import { X, Menu } from 'lucide-vue-next';

defineProps({ active: { type: String, default: 'dashboard' } });

const page = usePage();
const user = computed(() => page.props.auth?.user);
const open = ref(false);

const items = [
  ['dashboard', 'Tableau de bord', '◰', '/admin'],
  ['comps', 'Compétitions', '◇', '/admin/competitions'],
  ['pools', 'Poules', '▤', '/admin/poules'],
  ['knockout', 'Phase finale', '⊙', '/admin/phase-finale'],
  ['scoring', 'Live Scoring', '⊕', '/admin/scoring'],
  ['import', 'Import Excel', '↓', '/admin/import'],
  ['export', 'Exports', '⇩', '/admin/exports'],
  ['players', 'Joueurs', '○', '/admin/joueurs'],
  ['referees', 'Arbitres', '△', '/admin/arbitres'],
  ['stats', 'Statistiques', '◈', '/admin/statistiques'],
  ['rating', 'Classement Elo', '≋', '/admin/classement'],
];

const initials = (s) => (s || '??').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();

watch(open, (v) => {
  document.body.classList.toggle('mobile-drawer-open', v);
});

onMounted(() => {
  const mq = window.matchMedia('(min-width: 769px)');
  mq.addEventListener('change', (e) => { if (e.matches) open.value = false; });
});
</script>

<template>
  <!-- Mobile topbar with hamburger (mobile only — display géré 100% en CSS) -->
  <header class="admin-mobile-topbar" style="align-items: center; justify-content: space-between;
                 padding: 12px 16px; border-bottom: 1px solid var(--line);
                 background: var(--ink-2); position: sticky; top: 0; z-index: 40;">
    <Link href="/admin"><Logo :size="28" /></Link>
    <button class="mobile-hamburger" @click="open = !open" aria-label="Menu">
      <X v-if="open" :size="18" />
      <Menu v-else :size="18" />
    </button>
  </header>

  <!-- Desktop sidebar -->
  <aside class="mobile-hidden" style="width: 240px; border-right: 1px solid var(--line); padding: 24px 0;
                display: flex; flex-direction: column; gap: 4px; background: var(--ink-2);
                min-height: 100vh; flex-shrink: 0;">
    <div style="padding: 0 22px 22px; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
      <Logo :size="28" />
      <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-top: 14px;">
        ESPACE ORGANISATEUR
      </div>
    </div>
    <Link v-for="[k, l, ic, href] in items" :key="k" :href="href" :style="{
      display: 'flex', alignItems: 'center', gap: '14px', padding: '10px 22px',
      fontSize: '13px',
      color: active === k ? 'var(--chalk)' : 'var(--mute)',
      background: active === k ? 'var(--ink-3)' : 'transparent',
      borderLeft: '2px solid ' + (active === k ? 'var(--felt-2)' : 'transparent'),
      cursor: 'pointer',
    }">
      <span class="mono" :style="{ fontSize: '14px', width: '16px', textAlign: 'center',
                                    color: active === k ? 'var(--felt-2)' : 'var(--mute-2)' }">{{ ic }}</span>
      <span :style="{ fontWeight: active === k ? 600 : 400 }">{{ l }}</span>
    </Link>
    <div style="margin-top: auto; padding: 20px 22px; border-top: 1px solid var(--line);
                display: flex; align-items: center; gap: 10px;">
      <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--felt);
                  display: flex; align-items: center; justify-content: center;
                  font-weight: 700; font-size: 12px;">{{ initials(user?.name) }}</div>
      <div style="min-width: 0; flex: 1;">
        <div style="font-size: 12px; font-weight: 600;">{{ user?.name }}</div>
        <div style="font-size: 10px; color: var(--mute);">{{ user?.title || 'Administrateur' }}</div>
      </div>
      <Link as="button" method="post" href="/logout" style="background: transparent; border: 1px solid var(--line-strong);
                          color: var(--mute); padding: 4px 8px; font-size: 10px; cursor: pointer;">↪</Link>
    </div>
  </aside>

  <!-- Mobile drawer -->
  <div v-if="open" class="mobile-drawer">
    <header style="display: flex; align-items: center; justify-content: space-between;
                   padding: 14px 24px; border-bottom: 1px solid var(--line);">
      <Logo :size="28" />
      <button class="mobile-hamburger" @click="open = false" aria-label="Fermer">
        <X :size="18" />
      </button>
    </header>

    <div style="padding: 8px 0;">
      <Link v-for="[k, l, ic, href] in items" :key="k" :href="href" @click="open = false" :style="{
        display: 'flex', alignItems: 'center', gap: '14px',
        padding: '18px 24px',
        borderBottom: '1px solid var(--line)',
        borderLeft: '3px solid ' + (active === k ? 'var(--felt-2)' : 'transparent'),
        color: active === k ? 'var(--chalk)' : 'var(--chalk-2)',
        background: active === k ? 'var(--ink-3)' : 'transparent',
        fontFamily: 'var(--font-display-a)',
        fontSize: '20px', fontWeight: 700, letterSpacing: '0.02em',
      }">
        <span class="mono" :style="{ fontSize: '18px', width: '20px', textAlign: 'center',
                                      color: active === k ? 'var(--felt-2)' : 'var(--mute-2)' }">{{ ic }}</span>
        <span>{{ l }}</span>
      </Link>
    </div>

    <div style="margin-top: auto; padding: 24px; border-top: 1px solid var(--line);
                display: flex; align-items: center; gap: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--felt);
                  display: flex; align-items: center; justify-content: center;
                  font-weight: 700; font-size: 14px;">{{ initials(user?.name) }}</div>
      <div style="min-width: 0; flex: 1;">
        <div style="font-size: 14px; font-weight: 600;">{{ user?.name }}</div>
        <div style="font-size: 11px; color: var(--mute);">{{ user?.title || 'Administrateur' }}</div>
      </div>
      <Link as="button" method="post" href="/logout" class="btn">Déconnexion</Link>
    </div>
  </div>
</template>
