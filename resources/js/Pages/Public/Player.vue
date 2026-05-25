<script setup>
import { Head } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import GabonFlag from '@/Components/GabonFlag.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  player: Object,
  matches: Array,
  palmares: Array,
  form: Array,
});

const winRate = () => {
  const total = props.player.wins + props.player.losses;
  return total ? Math.round((props.player.wins / total) * 100) : 0;
};

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' }) : '';
</script>

<template>
  <Head :title="`${player.first_name} ${player.last_name}`">
    <meta name="description" :content="`Fiche joueur ${player.first_name} ${player.last_name} (${player.club?.name}) — palmarès, classement Elo, historique des matchs sur Club 8 Pool.`" head-key="description" />
    <meta property="og:type" content="profile" head-key="og:type" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh;">
    <PublicNav />
    <div style="padding: 20px 48px; border-bottom: 1px solid var(--line); display: flex; gap: 12px; align-items: center;">
      <span class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--mute);">JOUEURS</span>
      <span style="color: var(--mute-2);">/</span>
      <span class="mono" style="font-size: 11px; letter-spacing: 0.2em; color: var(--chalk-2);">
        {{ player.first_name.toUpperCase() }} {{ player.last_name }} · #{{ String(player.id).padStart(2,'0') }}
      </span>
    </div>

    <section style="display: grid; grid-template-columns: 420px 1fr 280px; border-bottom: 1px solid var(--line);">
      <div class="img-ph" style="height: 480px; border-right: 1px solid var(--line);">PORTRAIT · 4:5</div>
      <div style="padding: 40px; border-right: 1px solid var(--line);">
        <div style="display: flex; gap: 10px; margin-bottom: 18px;">
          <Chip variant="felt">CHAMPION 24 · 25</Chip>
          <Chip>SEED #{{ String(player.id).padStart(2,'0') }}</Chip>
          <Chip><GabonFlag :width="14" :height="10" /> {{ player.club?.city?.toUpperCase() }}</Chip>
        </div>
        <h1 class="disp-a" style="font-size: 96px; line-height: 0.88;">
          {{ player.first_name }}<br />{{ player.last_name }}
        </h1>
        <div style="display: flex; gap: 32px; margin-top: 32px;">
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">NÉ EN</div>
            <div class="disp-a tnum" style="font-size: 28px; margin-top: 6px;">{{ player.birthdate?.slice(0, 4) }}</div>
          </div>
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">CLUB</div>
            <div style="font-size: 18px; margin-top: 6px; font-weight: 600;">{{ player.club?.name }} · {{ player.club?.city }}</div>
          </div>
          <div>
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">CUE</div>
            <div style="font-size: 18px; margin-top: 6px; font-weight: 600;">{{ player.cue }}</div>
          </div>
        </div>
      </div>
      <div style="display: flex; flex-direction: column;">
        <div v-for="(kpi, i) in [
          [player.rating, 'ELO COURANT', '+24'],
          [winRate() + '%', 'TAUX VICTOIRE', `${player.wins + player.losses} matchs`],
          ['00:14', 'TEMPS / FRAME', 'MÉDIAN'],
          ['04', 'TITRES MAJ.', 'DEPUIS 22'],
        ]" :key="i" :style="{ padding: '20px 24px',
          borderBottom: i < 3 ? '1px solid var(--line)' : 'none', flex: 1 }">
          <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <span class="disp-a tnum" style="font-size: 36px;">{{ kpi[0] }}</span>
            <span class="mono tnum" style="font-size: 10px; color: var(--felt-2);">{{ kpi[2] }}</span>
          </div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-top: 4px;">{{ kpi[1] }}</div>
        </div>
      </div>
    </section>

    <section style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid var(--line);">
      <div style="padding: 32px 48px; border-right: 1px solid var(--line);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
          <h3 class="disp-a" style="font-size: 32px;">Forme · 12 derniers</h3>
          <span class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.14em;">VICTOIRES / DÉFAITES</span>
        </div>
        <div style="display: flex; gap: 4px; align-items: flex-end; height: 120px;">
          <div v-for="(v, i) in form" :key="i" style="flex: 1; display: flex; flex-direction: column;
                                                       justify-content: flex-end; align-items: center;">
            <div :style="{ width: '100%', height: ((v / 9) * 100) + '%',
                           background: v >= 7 ? 'var(--felt-2)' : 'var(--ink-4)' }" />
            <span class="mono tnum" style="font-size: 9px; color: var(--mute); margin-top: 6px;">{{ v }}</span>
          </div>
        </div>
        <div style="display: flex; gap: 20px; margin-top: 24px;">
          <div><span class="disp-a tnum" style="font-size: 32px;">9</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 8px;">V</span></div>
          <div><span class="disp-a tnum" style="font-size: 32px; color: var(--mute);">3</span><span class="mono" style="font-size: 10px; color: var(--mute); margin-left: 8px;">D</span></div>
          <div style="margin-left: auto;">
            <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">STREAK</div>
            <div class="disp-a tnum" style="font-size: 28px; color: var(--felt-2);">+04</div>
          </div>
        </div>
      </div>
      <div style="padding: 32px 48px;">
        <h3 class="disp-a" style="font-size: 32px; margin-bottom: 20px;">Palmarès</h3>
        <div v-for="(row, i) in palmares" :key="i"
             style="display: grid; grid-template-columns: 60px 1fr 60px; align-items: center;
                    padding: 12px 0; border-top: 1px solid var(--line);">
          <span class="mono tnum" style="font-size: 12px; color: var(--mute);">{{ row.year }}</span>
          <span style="font-size: 14px; font-weight: 500;">{{ row.title }}</span>
          <span class="disp-a" :style="{ fontSize: '18px',
                color: row.rank === '1ᵉʳ' ? 'var(--felt-2)' : 'var(--chalk-2)', textAlign: 'right' }">{{ row.rank }}</span>
        </div>
      </div>
    </section>

    <section style="padding: 32px 48px;">
      <h3 class="disp-a" style="font-size: 32px; margin-bottom: 20px;">Historique des matchs</h3>
      <table class="tbl">
        <thead>
          <tr>
            <th>Date</th>
            <th>Tour</th>
            <th>Adversaire</th>
            <th style="text-align: right;">Score</th>
            <th style="text-align: right;">Résultat</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in matches" :key="m.id">
            <td class="mono tnum" style="color: var(--mute);">{{ fmtDate(m.ended_at) }}</td>
            <td>{{ m.round }} · {{ m.competition?.name?.split(' — ')[0] }}</td>
            <td style="font-weight: 600;">
              {{ (m.player_a_id === player.id ? m.player_b : m.player_a)?.last_name }}
            </td>
            <td class="mono tnum" style="text-align: right; font-weight: 600;">{{ m.score_a }}-{{ m.score_b }}</td>
            <td style="text-align: right;">
              <Chip :variant="((m.player_a_id === player.id ? m.score_a : m.score_b) > (m.player_a_id === player.id ? m.score_b : m.score_a)) ? 'felt' : ''"
                    style="padding: 2px 8px;">
                {{ ((m.player_a_id === player.id ? m.score_a : m.score_b) > (m.player_a_id === player.id ? m.score_b : m.score_a)) ? 'VICTOIRE' : 'DÉFAITE' }}
              </Chip>
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
