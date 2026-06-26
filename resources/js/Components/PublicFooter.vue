<script setup>
import { Link } from '@inertiajs/vue3';
import GabonFlag from './GabonFlag.vue';
</script>

<template>
  <footer style="background: var(--ink-2); border-top: 1px solid var(--line); padding: 40px 24px 28px; margin-top: auto;">
    <div style="max-width: 1280px; margin: 0 auto;">
      <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px; margin-bottom: 32px;">

        <!-- Brand -->
        <div>
          <div class="disp-a" style="font-size: 22px; color: var(--chalk); margin-bottom: 8px;">Club 8 Pool</div>
          <div style="font-size: 12px; color: var(--mute); line-height: 1.7; max-width: 300px;">
            Compétitions de billard 8-ball &amp; 9-ball à Libreville, Gabon.<br />
            Icone Pool · Libreville
          </div>
          <div style="display: flex; align-items: center; gap: 8px; margin-top: 14px;">
            <GabonFlag :width="22" :height="15" />
            <span class="mono" style="font-size: 10px; letter-spacing: 0.18em; color: var(--mute);">LIBREVILLE · GABON</span>
          </div>
        </div>

        <!-- Navigation -->
        <div>
          <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 14px;">NAVIGATION</div>
          <nav style="display: flex; flex-direction: column; gap: 8px;">
            <Link href="/competitions" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Compétitions</Link>
            <Link href="/tournois" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Tournois archivés</Link>
            <Link href="/classement" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Classement Elo</Link>
            <Link href="/joueurs" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Joueurs</Link>
            <Link href="/inscription" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Inscription</Link>
            <Link href="/regles" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Règles</Link>
          </nav>
        </div>

        <!-- Legal & Info -->
        <div>
          <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 14px;">INFORMATIONS</div>
          <nav style="display: flex; flex-direction: column; gap: 8px;">
            <Link href="/faq" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">FAQ</Link>
            <a href="/live" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Suivi en direct ↗</a>
            <Link href="/cgu" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Conditions d'utilisation</Link>
            <Link href="/confidentialite" style="font-size: 13px; color: var(--mute-2); text-decoration: none;">Confidentialité</Link>
          </nav>
        </div>

      </div>

      <div style="display: flex; justify-content: space-between; align-items: center;
                  border-top: 1px solid var(--line); padding-top: 20px; flex-wrap: wrap; gap: 10px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.14em; color: var(--mute);">
          © {{ new Date().getFullYear() }} Club 8 Pool · Tous droits réservés
        </div>
        <div style="display: flex; gap: 18px;">
          <Link href="/cgu" style="font-size: 11px; color: var(--mute); text-decoration: none;">CGU</Link>
          <Link href="/confidentialite" style="font-size: 11px; color: var(--mute); text-decoration: none;">Confidentialité</Link>
          <Link href="/faq" style="font-size: 11px; color: var(--mute); text-decoration: none;">FAQ</Link>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
@media (max-width: 768px) {
  footer > div > div:first-child {
    grid-template-columns: 1fr !important;
    gap: 28px !important;
  }
}
</style>
