<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  competition: Object,
  pools: Array,
  unassigned: Array,
  totalPlayers: Number,
});

const search = ref('');
const filterPool = ref('all');

const filteredPools = computed(() => {
  const term = search.value.trim().toLowerCase();
  return props.pools
    .filter(p => filterPool.value === 'all' || filterPool.value === p.name)
    .map(p => ({
      ...p,
      players: term
        ? p.players.filter(pl => pl.name.toLowerCase().includes(term) || (pl.club ?? '').toLowerCase().includes(term))
        : p.players,
    }))
    .filter(p => p.players.length > 0);
});

const initials = (name) => name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
</script>

<template>
  <Head :title="`Joueurs · ${competition?.name ?? 'Club 8 Pool'}`">
    <meta name="description" :content="`${totalPlayers} joueurs inscrits au ${competition?.name}. Classement, club, parcours dans la compétition.`" head-key="description" />
  </Head>

  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />

    <section style="padding: 32px 0; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--mute);">JOUEURS · {{ competition?.name?.toUpperCase() }}</div>
        <h1 class="disp-a" style="font-size: 72px; margin-top: 14px;">
          {{ String(totalPlayers ?? 0).padStart(2, '0') }} <span style="color: var(--felt-2);">JOUEURS</span> EN LICE
        </h1>

        <div class="filter-bar" style="display: flex; gap: 14px; margin-top: 28px; flex-wrap: wrap;">
          <input v-model="search" placeholder="Rechercher un joueur, un club…" class="search-input" style="max-width: 360px;" />
          <div class="pool-filter-btns" style="display: flex; gap: 6px;">
            <button :class="filterPool === 'all' ? 'btn btn-primary' : 'btn'" style="padding: 8px 14px;" @click="filterPool = 'all'">Toutes</button>
            <button v-for="p in pools" :key="p.id" :class="filterPool === p.name ? 'btn btn-primary' : 'btn'"
                    style="padding: 8px 14px; white-space: nowrap;" @click="filterPool = p.name">
              Poule {{ p.name }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <section style="padding: 32px 0;">
      <div class="container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
      <div v-for="pool in filteredPools" :key="pool.id"
           style="border: 1px solid var(--line); background: var(--ink-2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--line);
                    display: flex; justify-content: space-between; align-items: baseline;">
          <div>
            <div class="disp-a" style="font-size: 26px;">POULE {{ pool.name }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-top: 4px;">
              {{ pool.players.length }} JOUEURS
            </div>
          </div>
          <Chip variant="felt">{{ competition?.qualifiers_per_pool }} QUAL.</Chip>
        </div>

        <div>
          <Link v-for="pl in pool.players" :key="pl.id" :href="`/joueurs/${pl.id}`"
                :style="{
                  display: 'grid', gridTemplateColumns: '28px 36px 1fr auto', alignItems: 'center', gap: '12px',
                  padding: '14px 20px',
                  borderTop: '1px solid var(--line)',
                  background: pl.qualified ? 'rgba(45,168,118,0.04)' : 'transparent',
                  borderLeft: pl.qualified ? '2px solid var(--felt-2)' : '2px solid transparent',
                  textDecoration: 'none',
                  transition: 'background .15s',
                }"
                class="hover-row">
            <span class="mono tnum" :style="{ fontWeight: 700, color: pl.qualified ? 'var(--felt-2)' : 'var(--mute)', fontSize: '14px' }">
              {{ pl.rank ?? '—' }}
            </span>
            <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--ink-4);
                         display: flex; align-items: center; justify-content: center;
                         font-family: var(--font-display-a); font-weight: 700; font-size: 11px;">
              {{ initials(pl.name) }}
            </span>
            <div style="min-width: 0;">
              <div style="font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ pl.name }}
              </div>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 2px;">
                {{ pl.club ?? '—' }}{{ pl.fgb_card ? ' · ' + pl.fgb_card : '' }}
              </div>
            </div>
            <div style="text-align: right; display: flex; gap: 14px; align-items: baseline;">
              <span class="mono tnum" style="font-size: 14px; font-weight: 700;">{{ pl.v }}V</span>
              <span class="mono tnum" :style="{ fontSize: '12px', color: pl.diff > 0 ? 'var(--felt-2)' : pl.diff < 0 ? 'var(--mute)' : 'var(--chalk-2)' }">
                {{ pl.diff > 0 ? '+' : '' }}{{ pl.diff }}
              </span>
              <span style="color: var(--mute);">→</span>
            </div>
          </Link>

          <div v-if="pool.players.length === 0" style="padding: 24px; text-align: center; color: var(--mute);">
            Aucun joueur ne correspond.
          </div>
        </div>
      </div>
      </div>
    </section>

    <section v-if="unassigned?.length" style="padding: 0 0 48px;">
      <div class="container">
        <h3 class="disp-a" style="font-size: 22px; margin-bottom: 14px;">En attente d'affectation</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <Link v-for="pl in unassigned" :key="pl.id" :href="`/joueurs/${pl.id}`"
                style="border: 1px solid var(--line); background: var(--ink-2); padding: 14px;">
            <div style="font-size: 13px; font-weight: 600;">{{ pl.name }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 4px;">{{ pl.club }} · ELO {{ pl.rating }}</div>
          </Link>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hover-row:hover {
  background: var(--ink-3) !important;
}

@media (max-width: 768px) {
  /* Filter bar: search full width, pool buttons scroll */
  .filter-bar {
    flex-direction: column;
    gap: 10px;
  }
  .search-input {
    max-width: 100% !important;
    width: 100%;
  }
  .pool-filter-btns {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 2px;
  }
  .pool-filter-btns::-webkit-scrollbar { display: none; }

  /* Player row: tighter grid on mobile */
  .player-row {
    grid-template-columns: 22px 30px 1fr auto !important;
    gap: 8px !important;
    padding: 12px 14px !important;
  }

  /* Stats on right: reduce gap */
  .player-stats {
    gap: 8px !important;
  }
}

@media (max-width: 480px) {
  /* Hide club text in player row to save space */
  .player-club {
    display: none;
  }
}
</style>
