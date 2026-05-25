<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import Ball8 from '@/Components/Ball8.vue';
import Chip from '@/Components/Chip.vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({
  competition: Object,
  tables: Array,
  pools: Array,
  kpis: Object,
  recentRegistrations: Array,
  schedule: Array,
});

const tick = ref(0);
let id;
onMounted(() => { id = setInterval(() => tick.value++, 1000); });
onUnmounted(() => clearInterval(id));

const liveMatch = (table) => table.live_match;

const adjust = (match, side, delta) => {
  router.patch(`/admin/matchs/${match.id}`, {
    [side]: Math.max(0, match[side] + delta),
  }, { preserveScroll: true, only: ['tables', 'kpis'] });
};
</script>

<template>
  <Head title="Tableau de bord" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="dashboard" />
    <main style="flex: 1; display: flex; flex-direction: column;">
      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">COMPÉTITION ACTIVE</div>
          <div style="display: flex; align-items: center; gap: 14px; margin-top: 8px;">
            <span class="disp-a" style="font-size: 22px;">{{ competition?.name }}</span>
            <Chip variant="live">PHASE QUARTS</Chip>
          </div>
        </div>
        <div style="display: flex; gap: 10px;">
          <Link href="/competitions" class="btn">Aperçu public ↗</Link>
          <Link href="/admin/competitions/nouvelle" class="btn btn-felt">+ Nouvelle compétition</Link>
        </div>
      </header>

      <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
                      background: var(--line); border-bottom: 1px solid var(--line);">
        <div v-for="(kpi, i) in [
          [`${kpis.players}/${kpis.slots}`, 'JOUEURS INSCRITS', `${competition?.pool_count ?? 0} poules de ${competition?.pool_size ?? 7}`, 'check'],
          [`${kpis.matches_done}/${kpis.matches_total}`, 'MATCHS JOUÉS', `${kpis.matches_live} en cours`, ''],
          [`${kpis.tables_active}/${kpis.tables_total}`, 'TABLES ACTIVES', '1 réservée · 1 maintenance', ''],
          ['00:42', 'MATCH LE PLUS LONG', kpis.longest_live ? `${kpis.longest_live.player_a?.last_name} vs ${kpis.longest_live.player_b?.last_name}` : '—', 'LIVE'],
        ]" :key="i" style="padding: 24px; background: var(--ink-2);">
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">{{ kpi[1] }}</div>
          <div style="display: flex; align-items: baseline; gap: 10px; margin-top: 12px;">
            <span class="disp-a tnum" style="font-size: 42px;">{{ kpi[0] }}</span>
            <Check v-if="kpi[3] === 'check'" :size="10" style="color: var(--felt-2);" />
            <span v-else-if="kpi[3]" class="mono tnum" style="font-size: 11px; color: var(--felt-2);">{{ kpi[3] }}</span>
          </div>
          <div style="font-size: 12px; color: var(--mute); margin-top: 6px;">{{ kpi[2] }}</div>
        </div>
      </section>

      <section v-if="pools?.length" style="padding: 24px 32px; border-bottom: 1px solid var(--line);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
          <h3 class="disp-a" style="font-size: 22px;">Avancement des poules</h3>
          <Link href="/competitions" class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.14em;">
            VUE PUBLIQUE →
          </Link>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <div v-for="p in pools" :key="p.id" style="border: 1px solid var(--line); padding: 14px; background: var(--ink-2);">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
              <span class="disp-a" style="font-size: 20px;">POULE {{ p.name }}</span>
              <span class="mono" style="font-size: 10px; color: var(--mute);">{{ p.matches_done }}/{{ p.matches_total }}</span>
            </div>
            <div style="margin-top: 10px; height: 4px; background: var(--ink-4);">
              <div :style="{ height: '100%', width: p.progress + '%', background: 'var(--felt-2)' }" />
            </div>
            <div style="margin-top: 12px;">
              <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.18em;">LEADER</div>
              <div style="font-size: 13px; font-weight: 600; margin-top: 4px;">
                {{ p.leader ? (p.leader.player.first_name + ' ' + (p.leader.player.last_name ?? '')).trim() : '—' }}
              </div>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 4px;">
                V {{ p.leader?.v ?? 0 }} · Diff {{ (p.leader?.diff ?? 0) > 0 ? '+' : '' }}{{ p.leader?.diff ?? 0 }}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section style="display: grid; grid-template-columns: 1.4fr 1fr; flex: 1; border-bottom: 1px solid var(--line);">
        <div style="padding: 24px 32px; border-right: 1px solid var(--line);">
          <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 18px;">
            <h3 class="disp-a" style="font-size: 28px;">Tables en temps réel</h3>
            <span class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.14em;">
              RAFRAÎCHI · IL Y A {{ tick % 60 }}s
            </span>
          </div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
            <div v-for="t in tables" :key="t.id" :style="{
              border: '1px solid ' + (t.status === 'live' ? 'rgba(229,72,77,0.4)' : 'var(--line)'),
              background: t.status === 'live' ? 'rgba(229,72,77,0.03)' : 'var(--ink-2)',
              padding: '16px'
            }">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <span class="mono" style="font-size: 10px; letter-spacing: 0.2em; color: var(--mute);">
                  {{ t.name?.toUpperCase() }} · {{ t.location?.toUpperCase() }}
                </span>
                <Chip :variant="t.status === 'live' ? 'live' : ''" style="padding: 1px 6px; font-size: 9px;">
                  {{ t.status === 'live' ? 'LIVE' : t.status === 'maint' ? 'MAINT.' : 'LIBRE' }}
                </Chip>
              </div>
              <template v-if="t.status === 'live' && liveMatch(t)">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="font-size: 13px; font-weight: 600;">
                    {{ liveMatch(t).player_a?.first_name?.[0] }}. {{ liveMatch(t).player_a?.last_name }}
                  </span>
                  <span class="disp-a tnum" style="font-size: 26px; color: var(--felt-2);">{{ liveMatch(t).score_a }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                  <span style="font-size: 13px; color: var(--mute);">
                    {{ liveMatch(t).player_b?.first_name?.[0] }}. {{ liveMatch(t).player_b?.last_name }}
                  </span>
                  <span class="disp-a tnum" style="font-size: 26px; color: var(--mute);">{{ liveMatch(t).score_b }}</span>
                </div>
                <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 12px;
                                          padding-top: 10px; border-top: 1px solid var(--line);
                                          display: flex; justify-content: space-between;">
                  <span>FRAME {{ (liveMatch(t).score_a ?? 0) + (liveMatch(t).score_b ?? 0) + 1 }}/{{ competition?.race_to }}</span>
                  <span>ARB. {{ liveMatch(t).referee?.name?.toUpperCase() ?? '—' }}</span>
                </div>
              </template>
              <template v-else>
                <div style="font-size: 13px; color: var(--chalk-2); margin-top: 4px;">{{ t.status === 'maint' ? 'Tapis remplacé' : 'En attente' }}</div>
                <div class="mono tnum" style="font-size: 11px; color: var(--mute); margin-top: 12px;">{{ t.status === 'maint' ? 'OUT' : '—' }}</div>
              </template>
            </div>
          </div>

          <div v-if="kpis.longest_live"
               style="margin-top: 32px; border: 1px solid var(--line-strong); padding: 20px; background: var(--ink-2);">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 18px;">
              <h4 class="disp-a" style="font-size: 22px;">Contrôleur match · {{ kpis.longest_live.table?.name }}</h4>
              <span class="mono" style="font-size: 11px; color: var(--mute);">QUART DE FINALE</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: center;">
              <div style="text-align: left;">
                <div style="font-size: 12px; color: var(--mute); margin-bottom: 6px;">SEED #{{ kpis.longest_live.player_a?.id }}</div>
                <div style="font-size: 18px; font-weight: 600;">{{ kpis.longest_live.player_a?.first_name }} {{ kpis.longest_live.player_a?.last_name }}</div>
                <div class="disp-a tnum" style="font-size: 72px; color: var(--felt-2);">{{ kpis.longest_live.score_a }}</div>
                <div style="display: flex; gap: 6px;">
                  <button class="btn" style="padding: 6px 10px; font-size: 11px;" @click="adjust(kpis.longest_live, 'score_a', -1)">− point</button>
                  <button class="btn btn-felt" style="padding: 6px 10px; font-size: 11px;" @click="adjust(kpis.longest_live, 'score_a', 1)">+ frame</button>
                </div>
              </div>
              <div style="text-align: center; display: flex; flex-direction: column; align-items: center; gap: 12px;">
                <div class="mono" style="font-size: 10px; letter-spacing: 0.2em; color: var(--mute);">RACE TO {{ competition?.race_to }}</div>
                <Ball8 :size="48" />
                <div class="mono tnum" style="font-size: 24px; color: var(--live);">00:42:18</div>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--mute); margin-bottom: 6px;">SEED #{{ kpis.longest_live.player_b?.id }}</div>
                <div style="font-size: 18px; font-weight: 600;">{{ kpis.longest_live.player_b?.first_name }} {{ kpis.longest_live.player_b?.last_name }}</div>
                <div class="disp-a tnum" style="font-size: 72px; color: var(--chalk-2);">{{ kpis.longest_live.score_b }}</div>
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <button class="btn" style="padding: 6px 10px; font-size: 11px;" @click="adjust(kpis.longest_live, 'score_b', -1)">− point</button>
                  <button class="btn btn-felt" style="padding: 6px 10px; font-size: 11px;" @click="adjust(kpis.longest_live, 'score_b', 1)">+ frame</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div style="padding: 24px 28px; display: flex; flex-direction: column; gap: 24px;">
          <div>
            <h3 class="disp-a" style="font-size: 24px; margin-bottom: 6px;">Calendrier</h3>
            <p style="font-size: 11px; color: var(--mute); margin-bottom: 12px;" class="mono">HORAIRES SOUPLES · TIMING NON STRICT</p>
            <div v-for="(s, i) in schedule" :key="i"
                 :style="{ display: 'grid', gridTemplateColumns: '90px 1fr auto', alignItems: 'center',
                          padding: '12px 0', borderTop: i ? '1px solid var(--line)' : '1px solid var(--line-strong)',
                          opacity: s.kind === 'rest' ? 0.5 : 1 }">
              <span class="disp-a tnum" style="font-size: 16px; text-transform: uppercase;">{{ s.label }}</span>
              <span :style="{ fontSize: '13px', fontStyle: s.kind === 'rest' ? 'italic' : 'normal' }">
                {{ s.kind === 'rest' ? 'Journée de repos' : 'Journée de compétition' }}
              </span>
              <Chip :variant="s.status === 'live' ? 'live' : s.status === 'done' ? 'felt' : ''" style="padding: 1px 6px; font-size: 9px;">
                <Check v-if="s.status === 'done'" :size="10" style="vertical-align:middle;" />
                <template v-else>{{ s.status === 'live' ? 'EN COURS' : s.status === 'rest' ? 'REPOS' : 'À VENIR' }}</template>
              </Chip>
            </div>
          </div>

          <div>
            <h3 class="disp-a" style="font-size: 24px; margin-bottom: 14px;">Inscriptions récentes</h3>
            <div v-for="r in recentRegistrations" :key="r.id"
                 style="display: flex; justify-content: space-between; align-items: center;
                        padding: 10px 0; border-top: 1px solid var(--line);">
              <div style="display: flex; align-items: center; gap: 10px;">
                <span class="mono" style="font-size: 10px; color: var(--mute);">#{{ String(r.player.id).padStart(2,'0') }}</span>
                <div>
                  <div style="font-size: 13px; font-weight: 600;">{{ r.player.first_name }} {{ r.player.last_name }}</div>
                  <div style="font-size: 11px; color: var(--mute);">{{ r.player.club?.name }} · {{ r.player.club?.city }}</div>
                </div>
              </div>
              <span class="mono tnum" style="font-size: 11px; color: var(--felt-2);">{{ r.player.rating }}</span>
            </div>
          </div>

          <div style="margin-top: auto; display: flex; flex-direction: column; gap: 8px;">
            <Link href="/admin/joueurs" class="btn">+ Ajouter un joueur</Link>
            <Link href="/admin/tirage" class="btn">Tirage au sort des demi-finales</Link>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
