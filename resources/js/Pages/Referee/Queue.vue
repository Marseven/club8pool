<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Ball8 from '@/Components/Ball8.vue';
import RefereeNav from '@/Components/RefereeNav.vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({ matches: Array, available: Array });
const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});
const claiming = ref(null);

const fmtTime = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return `${d.getUTCHours().toString().padStart(2, '0')}h${d.getUTCMinutes().toString().padStart(2, '0')}`;
};

const matchLabel = (m) => m.pool?.name ? `Poule ${m.pool.name}` : (m.round ?? '—');

const stats = computed(() => ({
  total: props.matches.length,
  live: props.matches.filter(m => m.status === 'live').length,
  done: props.matches.filter(m => m.status === 'done').length,
}));

const initials = (s) => (s || '').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();

const today = () => {
  const d = new Date();
  const days = ['DIM.', 'LUN.', 'MAR.', 'MER.', 'JEU.', 'VEN.', 'SAM.'];
  const months = ['JAN', 'FÉV', 'MAR', 'AVR', 'MAI', 'JUN', 'JUL', 'AOÛ', 'SEP', 'OCT', 'NOV', 'DÉC'];
  return `${days[d.getDay()]} ${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]}`;
};

const claim = (m) => {
  claiming.value = m.id;
  router.post(`/arbitre/match/${m.id}/claim`, {}, {
    onFinish: () => { claiming.value = null; },
  });
};
</script>

<template>
  <Head title="Mes matchs" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink);
              display: flex; flex-direction: column;">

    <!-- Header -->
    <header style="padding: 18px 22px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; gap: 10px; align-items: center;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--felt);
                    display: flex; align-items: center; justify-content: center; font-weight: 800;">
          {{ initials(user?.name) }}
        </div>
        <div>
          <div style="font-size: 13px; font-weight: 700;">{{ user?.name }}</div>
          <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.14em;">
            {{ user?.title?.toUpperCase() ?? 'ARBITRE' }}
          </div>
        </div>
      </div>
      <Ball8 :size="32" />
    </header>

    <!-- Flash message -->
    <div v-if="flash.error" style="margin: 12px 22px 0; padding: 10px 14px;
         border: 1px solid rgba(229,72,77,0.4); background: rgba(229,72,77,0.06);
         font-size: 12px; color: var(--live); font-family: var(--font-mono);">
      {{ flash.error }}
    </div>
    <div v-if="flash.success" style="margin: 12px 22px 0; padding: 10px 14px;
         border: 1px solid rgba(45,168,118,0.4); background: rgba(45,168,118,0.06);
         font-size: 12px; color: var(--felt-2); font-family: var(--font-mono);">
      {{ flash.success }}
    </div>

    <!-- Stats -->
    <div style="padding: 16px 22px; border-bottom: 1px solid var(--line);">
      <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <h2 class="disp-a" style="font-size: 36px;">AUJOURD'HUI</h2>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em;">{{ today() }}</span>
      </div>
      <div style="display: flex; gap: 20px; margin-top: 12px;">
        <div>
          <span class="disp-a" style="font-size: 22px;">{{ String(stats.total).padStart(2, '0') }}</span>
          <span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">MES MATCHS</span>
        </div>
        <div>
          <span class="disp-a" style="font-size: 22px; color: var(--live);">{{ String(stats.live).padStart(2, '0') }}</span>
          <span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">EN COURS</span>
        </div>
        <div>
          <span class="disp-a" style="font-size: 22px; color: var(--felt-2);">{{ String(available?.length ?? 0).padStart(2, '0') }}</span>
          <span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 6px;">DISPONIBLES</span>
        </div>
      </div>
    </div>

    <!-- Match list -->
    <div style="flex: 1; padding: 0 16px 16px; overflow-y: auto;">

      <!-- ── Mes matchs ──────────────────────────────────── -->
      <template v-if="matches.length">
        <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.22em;
                                  padding: 16px 0 8px; display: flex; align-items: center; gap: 8px;">
          MES MATCHS
          <span style="background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 2px;">
            {{ matches.length }}
          </span>
        </div>

        <div v-for="m in matches" :key="m.id" :style="{
          border: '1px solid ' + (m.status === 'live' ? 'rgba(229,72,77,0.4)' : 'var(--line)'),
          background: m.status === 'live' ? 'rgba(229,72,77,0.04)' : 'var(--ink-2)',
          padding: '16px', marginBottom: '8px', borderRadius: '3px',
          opacity: m.status === 'done' ? 0.55 : 1,
        }">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span class="disp-a tnum" style="font-size: 24px;">{{ fmtTime(m.scheduled_at) }}</span>
            <span :style="{
              padding: '2px 8px', fontSize: '9px', fontFamily: 'var(--font-mono)',
              fontWeight: 700, letterSpacing: '0.14em', borderRadius: '2px',
              background: m.status === 'live' ? 'rgba(229,72,77,0.15)' : m.status === 'done' ? 'rgba(45,168,118,0.12)' : 'rgba(255,255,255,0.08)',
              color: m.status === 'live' ? 'var(--live)' : m.status === 'done' ? 'var(--felt-2)' : 'var(--chalk-2)',
            }">
              <template v-if="m.status === 'live'">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;
                             background:currentColor;vertical-align:middle;margin-right:4px;"></span>LIVE
              </template>
              <template v-else-if="m.status === 'done'">
                <Check :size="9" style="vertical-align:middle;margin-right:2px;" /> TERMINÉ
              </template>
              <template v-else>{{ m.status === 'scheduled' ? 'PROGRAMMÉ' : 'EN ATTENTE' }}</template>
            </span>
          </div>

          <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; margin-bottom: 8px;">
            {{ matchLabel(m) }} · {{ m.table?.name?.toUpperCase() ?? 'TABLE —' }}
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div style="min-width: 0; flex: 1;">
              <div style="font-size: 14px; font-weight: 700;">
                {{ m.player_a?.first_name?.[0] }}. {{ m.player_a?.last_name }}
              </div>
              <div style="font-size: 13px; color: var(--chalk-2); margin-top: 2px;">
                {{ m.player_b?.first_name?.[0] }}. {{ m.player_b?.last_name }}
              </div>
            </div>
            <div v-if="m.status === 'live' || m.status === 'done'" style="text-align: right; flex-shrink: 0;">
              <div class="disp-a tnum" style="font-size: 26px; line-height: 1;">
                <span :style="{ color: m.score_a > m.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_a }}</span>
                <span style="color: var(--mute-2); margin: 0 4px;">—</span>
                <span :style="{ color: m.score_b > m.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ m.score_b }}</span>
              </div>
            </div>
          </div>

          <Link v-if="m.status === 'live'" :href="`/arbitre/match/${m.id}/live`"
                class="btn btn-felt" style="width: 100%; margin-top: 12px; justify-content: center;">
            Reprendre →
          </Link>
          <Link v-else-if="['scheduled', 'pending'].includes(m.status)" :href="`/arbitre/match/${m.id}/pre`"
                class="btn" style="width: 100%; margin-top: 12px; justify-content: center;">
            Préparer →
          </Link>
          <Link v-else-if="m.status === 'done'" :href="`/arbitre/match/${m.id}/fin`"
                class="btn" style="width: 100%; margin-top: 12px; justify-content: center; opacity: 0.6;">
            Voir le résumé →
          </Link>
        </div>
      </template>

      <!-- ── Disponibles ─────────────────────────────────── -->
      <template v-if="available?.length">
        <div class="mono" style="font-size: 9px; color: var(--felt-2); letter-spacing: 0.22em;
                                  padding: 16px 0 8px; display: flex; align-items: center; gap: 8px;">
          DISPONIBLES
          <span style="background: rgba(45,168,118,0.12); padding: 1px 6px; border-radius: 2px;
                       color: var(--felt-2);">{{ available.length }}</span>
        </div>

        <div v-for="m in available" :key="m.id"
             style="border: 1px solid rgba(45,168,118,0.22); background: var(--ink-2);
                    padding: 16px; margin-bottom: 8px; border-radius: 3px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span class="disp-a tnum" style="font-size: 24px; color: var(--chalk-2);">
              {{ fmtTime(m.scheduled_at) }}
            </span>
            <span v-if="m.pool" style="padding: 2px 8px; font-size: 9px; font-family: var(--font-mono);
                  font-weight: 700; letter-spacing: 0.14em; border-radius: 2px;
                  background: rgba(45,168,118,0.1); border: 1px solid rgba(45,168,118,0.3);
                  color: var(--felt-2);">
              {{ m.pool.name }}
            </span>
          </div>

          <div style="font-size: 14px; font-weight: 700; margin-bottom: 2px;">
            {{ m.player_a?.first_name?.[0] }}. {{ m.player_a?.last_name }}
          </div>
          <div style="font-size: 13px; color: var(--chalk-2); margin-bottom: 12px;">
            {{ m.player_b?.first_name?.[0] }}. {{ m.player_b?.last_name }}
          </div>

          <button class="btn" @click="claim(m)" :disabled="claiming === m.id"
                  style="width: 100%; justify-content: center;
                         background: rgba(45,168,118,0.1); border-color: rgba(45,168,118,0.4);
                         color: var(--felt-2);">
            {{ claiming === m.id ? 'En cours…' : 'PRENDRE EN CHARGE' }}
          </button>
        </div>
      </template>

      <!-- Empty state -->
      <div v-if="!matches.length && !available?.length"
           style="text-align: center; padding: 60px 32px; color: var(--mute);">
        <div class="disp-a" style="font-size: 22px; margin-bottom: 12px;">AUCUN MATCH</div>
        <p style="font-size: 12px; line-height: 1.6;">
          Aucun match ne t'est assigné et aucun n'est disponible pour le moment.
        </p>
      </div>
    </div>

    <RefereeNav active="queue" />
  </div>
</template>
