<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Check, Play } from 'lucide-vue-next';
defineProps({ match: Object });
</script>

<template>
  <Head title="Pré-match" />
  <div style="max-width: 480px; margin: 0 auto; min-height: 100vh; background: var(--ink); display: flex; flex-direction: column;">
    <header style="padding: 8px 22px 14px; border-bottom: 1px solid var(--line);
                   display: flex; justify-content: space-between; align-items: center;">
      <Link href="/arbitre" style="color: var(--mute); font-size: 16px;">←</Link>
      <div style="text-align: center;">
        <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">
          {{ match.table?.name?.toUpperCase() }} · {{ match.round }}
        </div>
        <div style="font-size: 13px; font-weight: 700;">Pré-match</div>
      </div>
      <span style="color: var(--mute); font-size: 16px;">⋮</span>
    </header>

    <div style="flex: 1; padding: 22px 22px 0; overflow: auto;">
      <div style="border: 1px solid var(--line); background: var(--ink-2); border-radius: 3px; overflow: hidden;">
        <div v-for="(p, i) in [
          { player: match.player_a, breaking: true },
          { player: match.player_b, breaking: false },
        ]" :key="i" :style="{
          padding: '18px',
          display: 'grid', gridTemplateColumns: '44px 1fr auto', alignItems: 'center', gap: '12px',
          borderBottom: i === 0 ? '1px solid var(--line)' : 'none',
          background: p.breaking ? 'rgba(45,168,118,0.05)' : 'transparent',
        }">
          <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--ink-4);
                      display: flex; align-items: center; justify-content: center;
                      font-family: var(--font-display-a); font-weight: 700; font-size: 16px;">
            {{ p.player?.first_name?.[0] }}{{ p.player?.last_name?.[0] }}
          </div>
          <div>
            <div style="font-size: 14px; font-weight: 700;">{{ p.player?.first_name }} {{ p.player?.last_name }}</div>
            <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; margin-top: 3px;">
              SEED #{{ p.player?.id }} · ELO {{ p.player?.rating }}
            </div>
          </div>
          <span v-if="p.breaking" style="padding: 4px 8px; font-size: 9px; font-family: var(--font-mono);
                                          font-weight: 700; background: var(--felt-2); color: var(--ink);
                                          border-radius: 2px; letter-spacing: 0.1em;">BREAK</span>
        </div>
      </div>

      <div style="margin-top: 22px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">QUI CASSE EN PREMIER ?</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <button style="padding: 14px; background: rgba(45,168,118,0.08); border: 1px solid var(--felt-2);
                         color: var(--chalk); border-radius: 3px; text-align: left; cursor: pointer;">
            <div class="mono" style="font-size: 9px; color: var(--felt-2); letter-spacing: 0.14em; display:flex; align-items:center; gap:4px;"><Check :size="9" style="vertical-align:middle;" /> CHOIX ARBITRE</div>
            <div style="font-size: 13px; font-weight: 700; margin-top: 6px;">{{ match.player_a?.last_name }}</div>
            <div class="mono" style="font-size: 9px; color: var(--mute); margin-top: 2px;">SEED HAUT</div>
          </button>
          <button style="padding: 14px; background: transparent; border: 1px solid var(--line-strong);
                         color: var(--chalk-2); border-radius: 3px; text-align: left; cursor: pointer;">
            <div class="mono" style="font-size: 9px; color: var(--mute); letter-spacing: 0.14em;">OU</div>
            <div style="font-size: 13px; font-weight: 700; margin-top: 6px;">{{ match.player_b?.last_name }}</div>
            <div class="mono" style="font-size: 9px; color: var(--mute); margin-top: 2px;">SEED BAS</div>
          </button>
        </div>
      </div>

      <div style="margin-top: 22px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">RÉGLAGES</div>
        <div style="border: 1px solid var(--line); border-radius: 3px; overflow: hidden;">
          <div v-for="([k, v], i) in [
            ['Race to', match.competition?.race_to],
            ['Shot clock', (match.competition?.shot_clock ?? 30) + ' sec'],
            ['Break', match.competition?.alternate_break ? 'Alterné' : 'Vainqueur'],
            ['Pause frames', (match.competition?.frame_pause ?? 60) + ' sec'],
          ]" :key="k" :style="{
            display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '12px',
            padding: '12px 14px', background: 'var(--ink-2)',
            borderTop: i ? '1px solid var(--line)' : 'none',
          }">
            <span style="font-size: 12px; color: var(--chalk-2); white-space: nowrap;">{{ k }}</span>
            <span class="mono" style="font-size: 13px; font-weight: 700; white-space: nowrap;">{{ v }}</span>
          </div>
        </div>
      </div>
    </div>

    <div style="padding: 14px 22px; border-top: 1px solid var(--line); background: var(--ink-2);">
      <Link :href="`/arbitre/match/${match.id}/live`" class="btn btn-felt"
            style="width: 100%; padding: 16px; justify-content: center; display:flex; align-items:center; gap:6px;"><Play :size="14" /> Démarrer le match</Link>
    </div>
  </div>
</template>
