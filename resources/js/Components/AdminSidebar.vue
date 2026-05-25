<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from './Logo.vue';

defineProps({ active: { type: String, default: 'dashboard' } });

const page = usePage();
const user = computed(() => page.props.auth?.user);

const items = [
  ['dashboard', 'Tableau de bord', '◰', '/admin'],
  ['comps', 'Compétitions', '◇', '/admin/competitions'],
  ['pools', 'Poules', '▤', '/admin/poules'],
  ['players', 'Joueurs', '○', '/admin/joueurs'],
  ['referees', 'Arbitres', '△', '/admin/arbitres'],
  ['draw', 'Tirage final', '◉', '/admin/tirage'],
];

const initials = (s) => (s || '??').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
</script>

<template>
  <aside style="width: 240px; border-right: 1px solid var(--line); padding: 24px 0;
                display: flex; flex-direction: column; gap: 4px; background: var(--ink-2);
                min-height: 100vh;">
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
</template>
