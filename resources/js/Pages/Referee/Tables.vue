<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import RefereeNav from '@/Components/RefereeNav.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({ tables: Array });

const live = computed(() => props.tables.filter(t => t.status === 'live').length);
const idle = computed(() => props.tables.filter(t => t.status === 'idle').length);
const maint = computed(() => props.tables.filter(t => t.status === 'maint').length);
</script>

<template>
  <Head title="Tables" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">
    <header style="padding: 18px 22px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <div>
        <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">ICONE POOL CHAMPIONSHIP</div>
        <div class="disp-a" style="font-size: 28px; margin-top: 6px;">TABLES</div>
      </div>
      <Ball8 :size="32" />
    </header>

    <div style="padding: 14px 22px; display: flex; gap: 16px;">
      <div><span class="disp-a tnum" style="font-size: 24px; color: var(--live);">{{ String(live).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">EN COURS</span></div>
      <div><span class="disp-a tnum" style="font-size: 24px;">{{ String(idle).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">LIBRES</span></div>
      <div v-if="maint"><span class="disp-a tnum" style="font-size: 24px; color: var(--mute);">{{ String(maint).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">MAINT.</span></div>
    </div>

    <div style="flex: 1; padding: 6px 16px 16px;">
      <div v-for="t in tables" :key="t.id" :style="{
        border: '1px solid ' + (t.status === 'live' ? 'rgba(229,72,77,0.4)' : 'var(--line)'),
        background: t.status === 'live' ? 'rgba(229,72,77,0.04)' : 'var(--ink-2)',
        padding: '16px', marginTop: '10px', borderRadius: '3px',
        opacity: t.status === 'maint' ? 0.55 : 1,
      }">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <div>
            <div class="disp-a" style="font-size: 22px;">{{ t.name?.toUpperCase() }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 4px; letter-spacing: 0.18em;">
              {{ t.location?.toUpperCase() }}
            </div>
          </div>
          <Chip :variant="t.status === 'live' ? 'live' : t.status === 'maint' ? '' : 'felt'"
                style="padding: 4px 10px;">
            {{ t.status === 'live' ? 'EN COURS' : t.status === 'maint' ? 'MAINTENANCE' : 'LIBRE' }}
          </Chip>
        </div>

        <template v-if="t.status === 'live' && t.live_match">
          <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-bottom: 8px;">
            POULE {{ t.live_match.pool?.name ?? '—' }}
            <span v-if="t.live_match.referee"> · ARB. {{ t.live_match.referee?.name?.toUpperCase() }}</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div style="min-width: 0; flex: 1;">
              <div :style="{ fontSize: '14px', fontWeight: 700, color: t.live_match.score_a > t.live_match.score_b ? 'var(--chalk)' : 'var(--chalk-2)' }">
                {{ t.live_match.player_a?.first_name }} {{ t.live_match.player_a?.last_name }}
              </div>
              <div :style="{ fontSize: '13px', marginTop: '2px', color: t.live_match.score_b > t.live_match.score_a ? 'var(--chalk)' : 'var(--mute)' }">
                {{ t.live_match.player_b?.first_name }} {{ t.live_match.player_b?.last_name }}
              </div>
            </div>
            <div class="disp-a tnum" style="font-size: 28px;">
              <span :style="{ color: t.live_match.score_a > t.live_match.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ t.live_match.score_a }}</span>
              <span style="color: var(--mute-2); margin: 0 4px;">—</span>
              <span :style="{ color: t.live_match.score_b > t.live_match.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ t.live_match.score_b }}</span>
            </div>
          </div>
          <Link :href="`/arbitre/match/${t.live_match.id}/live`" class="btn btn-felt"
                style="width: 100%; margin-top: 12px; justify-content: center;">
            Aller au match →
          </Link>
        </template>
      </div>

      <div v-if="!tables.length" style="padding: 40px 0; text-align: center;">
        <div class="disp-a" style="font-size: 22px; color: var(--mute);">AUCUNE TABLE</div>
        <p style="font-size: 12px; color: var(--mute); margin-top: 10px;">
          Demande à l'organisateur d'ajouter au moins une table.
        </p>
      </div>
    </div>

    <RefereeNav active="tables" />
  </div>
</template>
