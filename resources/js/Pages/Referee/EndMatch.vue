<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ match: Object });

const signed = computed(() => new Set(props.match.signatures?.map(s => s.player_id) ?? []));

const sign = (playerId) => {
  router.post(`/arbitre/match/${props.match.id}/signer`, {
    player_id: playerId,
    signature_data: '✓',
  }, { preserveScroll: true });
};

const allSigned = computed(() => signed.value.has(props.match.player_a?.id) && signed.value.has(props.match.player_b?.id));
</script>

<template>
  <Head title="Fin de match" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink); display: flex; flex-direction: column;">
    <header style="padding: 8px 22px 14px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <Link href="/arbitre" style="color: var(--mute); font-size: 16px;">←</Link>
      <div style="text-align: center;">
        <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--felt-2);">✓ MATCH TERMINÉ</div>
        <div style="font-size: 13px; font-weight: 700;">Validation</div>
      </div>
      <span style="color: var(--mute); font-size: 16px;">⋮</span>
    </header>

    <div style="padding: 22px 22px 12px; border-bottom: 1px solid var(--line);">
      <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">
        {{ match.round }} · {{ match.table?.name?.toUpperCase() }}
      </div>
      <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: end; gap: 12px; margin-top: 14px;">
        <div style="text-align: left;">
          <div style="font-size: 13px; font-weight: 700;">{{ match.player_a?.last_name }}</div>
          <div class="disp-a tnum" :style="{ fontSize: '72px', lineHeight: 0.9,
                color: match.score_a > match.score_b ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ match.score_a }}</div>
        </div>
        <div style="padding-bottom: 18px; font-family: var(--font-display-a); font-size: 36px; color: var(--mute-2);">—</div>
        <div style="text-align: right;">
          <div style="font-size: 13px; font-weight: 700; color: var(--chalk-2);">{{ match.player_b?.last_name }}</div>
          <div class="disp-a tnum" :style="{ fontSize: '72px', lineHeight: 0.9,
                color: match.score_b > match.score_a ? 'var(--felt-2)' : 'var(--chalk-2)' }">{{ match.score_b }}</div>
        </div>
      </div>
      <div style="display: flex; justify-content: space-between; margin-top: 10px;">
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">DURÉE 01:08:42</span>
        <span class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em;">{{ match.score_a + match.score_b }} FRAMES</span>
      </div>
    </div>

    <div style="flex: 1; padding: 18px 22px; overflow: auto;">
      <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 10px;">SIGNATURES DES JOUEURS</div>
      <div style="display: flex; flex-direction: column; gap: 10px;">
        <div v-for="p in [match.player_a, match.player_b]" :key="p?.id" :style="{
          border: '1px solid ' + (signed.has(p?.id) ? 'rgba(45,168,118,0.4)' : 'var(--line-strong)'),
          background: signed.has(p?.id) ? 'rgba(45,168,118,0.04)' : 'var(--ink-2)',
          borderRadius: '3px', padding: '14px',
        }">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 12px; font-weight: 700;">{{ p?.first_name }} {{ p?.last_name }}</span>
            <span class="mono" :style="{ fontSize: '9px', letterSpacing: '0.14em',
                  color: signed.has(p?.id) ? 'var(--felt-2)' : 'var(--live)' }">
              {{ signed.has(p?.id) ? '✓ SIGNÉ' : '⚠ EN ATTENTE' }}
            </span>
          </div>
          <button @click="sign(p.id)" :disabled="signed.has(p?.id)" :style="{
            height: '60px', width: '100%', background: 'var(--ink)', borderRadius: '2px',
            border: '1px dashed var(--line)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: '24px', color: signed.has(p?.id) ? 'var(--felt-2)' : 'var(--mute-2)',
            fontFamily: 'cursive', cursor: signed.has(p?.id) ? 'default' : 'pointer',
          }">
            {{ signed.has(p?.id) ? '✓' : 'Toucher pour signer' }}
          </button>
        </div>
      </div>
    </div>

    <div style="padding: 12px 22px; border-top: 1px solid var(--line);
                display: flex; flex-direction: column; gap: 8px; background: var(--ink-2);">
      <Link :href="`/arbitre`" class="btn btn-felt" :disabled="!allSigned" style="justify-content: center;">
        {{ allSigned ? 'Valider · retourner à la file' : 'En attente des signatures' }}
      </Link>
    </div>
  </div>
</template>
