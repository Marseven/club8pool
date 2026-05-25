<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import RefereeNav from '@/Components/RefereeNav.vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({ matches: Array });
const page = usePage();
const user = computed(() => page.props.auth?.user);

const fmtTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}h${d.getUTCMinutes().toString().padStart(2, '0')}`;
};

const stats = computed(() => ({
  total: props.matches.length,
  live: props.matches.filter(m => m.status === 'live').length,
  done: props.matches.filter(m => m.status === 'done').length,
}));

const initials = (s) => (s || '').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
</script>

<template>
  <Head title="Mes matchs" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">
    <header style="padding: 18px 22px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; gap: 10px; align-items: center;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--felt);
                    display: flex; align-items: center; justify-content: center; font-weight: 800;">{{ initials(user?.name) }}</div>
        <div>
          <div style="font-size: 13px; font-weight: 700;">{{ user?.name }}</div>
          <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.14em;">{{ user?.title?.toUpperCase() }}</div>
        </div>
      </div>
      <Ball8 :size="32" />
    </header>

    <div style="padding: 16px 22px;">
      <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <h2 class="disp-a" style="font-size: 36px;">AUJOURD'HUI</h2>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">SAM. 06 JUIN</span>
      </div>
      <div style="display: flex; gap: 16px; margin-top: 12px;">
        <div><span class="disp-a" style="font-size: 22px;">{{ String(stats.total).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">MATCHS</span></div>
        <div><span class="disp-a" style="font-size: 22px; color: var(--live);">{{ String(stats.live).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">EN COURS</span></div>
        <div><span class="disp-a" style="font-size: 22px; color: var(--felt-2);">{{ String(stats.done).padStart(2, '0') }}</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">TERMINÉS</span></div>
      </div>
    </div>

    <div style="flex: 1; padding: 0 16px 16px;">
      <div v-for="m in matches" :key="m.id" :style="{
        border: '1px solid ' + (m.status === 'live' ? 'rgba(229,72,77,0.4)' : 'var(--line)'),
        background: m.status === 'live' ? 'rgba(229,72,77,0.04)' : 'var(--ink-2)',
        padding: '16px', marginTop: '10px', borderRadius: '3px',
        opacity: m.status === 'done' ? 0.55 : 1,
      }">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <span class="disp-a tnum" style="font-size: 24px;">{{ fmtTime(m.scheduled_at) }}</span>
          <span :style="{
            padding: '2px 8px', fontSize: '9px', fontFamily: 'var(--font-mono)',
            fontWeight: 700, letterSpacing: '0.14em', borderRadius: '2px',
            background: m.status === 'live' ? 'rgba(229,72,77,0.15)' : m.status === 'done' ? 'rgba(45,168,118,0.12)' : 'rgba(255,255,255,0.08)',
            color: m.status === 'live' ? 'var(--live)' : m.status === 'done' ? 'var(--felt-2)' : 'var(--chalk-2)',
          }">
            <template v-if="m.status === 'live'">
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;vertical-align:middle;margin-right:4px;"></span>LIVE
            </template>
            <template v-else-if="m.status === 'done'">
              <Check :size="10" style="vertical-align:middle;margin-right:2px;" /> TERMINÉ
            </template>
            <template v-else>{{ m.status === 'scheduled' ? 'PROCHAIN' : 'EN ATTENTE' }}</template>
          </span>
        </div>
        <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-bottom: 8px;">
          {{ m.round }} · {{ m.table?.name?.toUpperCase() ?? 'TABLE —' }}
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
          <div style="min-width: 0; flex: 1;">
            <div style="font-size: 14px; font-weight: 700;">{{ m.player_a?.first_name?.[0] }}. {{ m.player_a?.last_name }}</div>
            <div style="font-size: 13px; color: var(--mute); margin-top: 2px;">{{ m.player_b?.first_name?.[0] }}. {{ m.player_b?.last_name }}</div>
          </div>
          <div v-if="m.status === 'live'" style="text-align: right; flex-shrink: 0;">
            <div class="disp-a tnum" style="font-size: 26px; color: var(--felt-2); line-height: 1;">
              {{ m.score_a }}<span style="color: var(--mute-2); margin: 0 4px;">—</span><span style="color: var(--chalk-2);">{{ m.score_b }}</span>
            </div>
          </div>
        </div>
        <Link v-if="m.status === 'live'" :href="`/arbitre/match/${m.id}/live`" class="btn btn-felt"
              style="width: 100%; margin-top: 12px; justify-content: center;">Reprendre →</Link>
        <Link v-else-if="m.status === 'scheduled'" :href="`/arbitre/match/${m.id}/pre`" class="btn"
              style="width: 100%; margin-top: 12px; justify-content: center;">Démarrer →</Link>
      </div>
    </div>

    <RefereeNav active="queue" />
  </div>
</template>
