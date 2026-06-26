<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const search = ref('');
const activeCategory = ref('all');
const openId = ref(null);

const categories = [
  { id: 'all', label: 'Toutes les questions' },
  { id: 'inscription', label: 'Inscription' },
  { id: 'competition', label: 'Compétition' },
  { id: 'regles', label: 'Règles' },
  { id: 'classement', label: 'Classement' },
  { id: 'pratique', label: 'Pratique' },
];

const faq = [
  // Inscription
  { id: 'ins-1', cat: 'inscription', q: 'Comment m\'inscrire à un tournoi ?', a: 'Rendez-vous sur la page Inscription de ce site et remplissez le formulaire avant la date limite. Vous recevrez une confirmation par téléphone ou e-mail. Si les places sont limitées, les inscriptions sont validées selon l\'ordre d\'arrivée.' },
  { id: 'ins-2', cat: 'inscription', q: 'Quel est le montant des frais d\'inscription ?', a: 'Les frais varient selon la compétition et sont indiqués sur la page de l\'événement. Le paiement peut s\'effectuer en espèces auprès de l\'organisateur Dimitri (077 79 10 57) ou via Mobile Money. Aucune inscription n\'est confirmée sans paiement.' },
  { id: 'ins-3', cat: 'inscription', q: 'Puis-je annuler mon inscription ?', a: 'Les annulations sont acceptées jusqu\'à 48h avant le début de la compétition. Au-delà de ce délai, les frais d\'inscription ne sont pas remboursables. Contactez l\'organisateur directement pour tout cas exceptionnel.' },
  { id: 'ins-4', cat: 'inscription', q: 'Quelles sont les conditions d\'éligibilité ?', a: 'Tout joueur résidant au Gabon peut s\'inscrire. Un joueur junior (moins de 18 ans) doit fournir l\'autorisation écrite d\'un parent ou tuteur. Aucune licence fédérale n\'est obligatoire sauf mention contraire dans le règlement de l\'édition.' },
  { id: 'ins-5', cat: 'inscription', q: 'Combien de temps avant la compétition les inscriptions ferment-elles ?', a: 'Les inscriptions ferment généralement 3 à 7 jours avant le début du tournoi pour permettre l\'organisation du tirage au sort. La date exacte est affichée sur la page de la compétition. Aucune inscription tardive ne sera acceptée après la clôture officielle.' },

  // Compétition
  { id: 'comp-1', cat: 'competition', q: 'Comment se déroule la compétition ?', a: 'La compétition se déroule en deux phases. D\'abord les poules (round-robin) : chaque joueur affronte tous les adversaires de son groupe. Les meilleurs de chaque groupe se qualifient pour la phase finale (élimination directe) jusqu\'à la grande finale.' },
  { id: 'comp-2', cat: 'competition', q: 'Comment est formé le classement en poule ?', a: 'Chaque victoire rapporte 1 point, chaque défaite 0. En cas d\'égalité, on regarde d\'abord le résultat du face-à-face direct, puis la différence de frames gagnées/perdues. Si l\'égalité persiste, un match de barrage ou un tirage au sort tranche.' },
  { id: 'comp-3', cat: 'competition', q: 'Qu\'est-ce qu\'un « bye » en phase finale ?', a: 'Un bye est une qualification automatique au tour suivant sans jouer de match, accordée lorsque le nombre de qualifiés n\'est pas une puissance de 2 (8, 16, 32…). Les byes sont attribués aux têtes de série les mieux classées selon le résultat des poules.' },
  { id: 'comp-4', cat: 'competition', q: 'Y a-t-il une petite finale (match pour la 3e place) ?', a: 'Oui, un match pour la 3e et 4e place oppose les deux demi-finalistes éliminés. Ce match est obligatoire et comptabilisé au classement officiel. Le refus de jouer ce match entraîne un classement à la 4e place d\'office.' },
  { id: 'comp-5', cat: 'competition', q: 'Combien de temps dure une journée de compétition ?', a: 'Une journée de poules dure généralement 6 à 8 heures. Les journées de phase finale sont plus courtes (4 à 6 heures). Le programme exact est publié sur ce site plusieurs jours avant la compétition. Les horaires peuvent être ajustés selon la cadence des matchs.' },

  // Règles
  { id: 'reg-1', cat: 'regles', q: 'Quelles règles officielles sont utilisées ?', a: 'Les compétitions Club 8 Pool se disputent selon les règles de la World Pool-Billiard Association (WPA), version 2022. Ces règles sont disponibles sur le site officiel de la WPA. Consultez aussi la page Règles de ce site pour un résumé en français.' },
  { id: 'reg-2', cat: 'regles', q: 'Comment fonctionne la balle en main ?', a: 'En cas de faute, l\'adversaire reçoit la balle en main complète : il peut placer la bille blanche n\'importe où sur la table avant de jouer. Il n\'est pas obligatoire de jouer derrière la ligne de fond (ligne de départ). Cette règle s\'applique à toutes les phases de la compétition.' },
  { id: 'reg-3', cat: 'regles', q: 'Qu\'est-ce qu\'une sécurité (safety) ?', a: 'Une sécurité est un tir défensif joué délibérément sans empocher de bille, tout en respectant les règles du tir légal (toucher sa bille de tête en premier, mettre une bille en bande). Le joueur doit annoncer sa sécurité à l\'arbitre avant de jouer. Son tour passe alors à l\'adversaire.' },
  { id: 'reg-4', cat: 'regles', q: 'Qui arbitre les matchs ?', a: 'Un arbitre de table est présent pour chaque match officiel en phase finale. En phase de poules, un arbitre circulant supervise plusieurs tables. Les joueurs sont responsables d\'appliquer les règles honnêtement et d\'appeler l\'arbitre en cas de litige avant de jouer le tir suivant.' },
  { id: 'reg-5', cat: 'regles', q: 'Que se passe-t-il si je touche accidentellement une bille avec ma queue ?', a: 'Toucher une bille avec la queue (sauf lors du tir) est une faute. L\'adversaire reçoit la balle en main. Si la bille déplacée est la bille blanche, la faute est également déclarée. En cas de doute, appelez immédiatement l\'arbitre sans toucher à quoi que ce soit.' },

  // Classement
  { id: 'cla-1', cat: 'classement', q: 'Comment fonctionne le système Elo ?', a: 'Le classement Elo attribue à chaque joueur un nombre de points qui évolue après chaque match. Une victoire contre un joueur plus fort rapporte plus de points qu\'une victoire contre un joueur plus faible. Une défaite en fait perdre. Le système récompense la régularité sur la durée.' },
  { id: 'cla-2', cat: 'classement', q: 'Comment mon score Elo est-il calculé après un match ?', a: 'Le calcul prend en compte votre Elo actuel, celui de votre adversaire et le résultat du match. La formule standard : nouveau Elo = Elo actuel + K × (résultat – probabilité de victoire). La valeur K (facteur de correction) est plus élevée pour les joueurs débutants, diminuant avec l\'expérience.' },
  { id: 'cla-3', cat: 'classement', q: 'Qu\'est-ce qu\'un classement provisoire ?', a: 'Un joueur est classé "provisoire" tant qu\'il n\'a pas disputé 10 matchs officiels. Son Elo existe mais est moins fiable. Après 10 matchs, le classement devient définitif et le joueur apparaît pleinement dans le classement public.' },
  { id: 'cla-4', cat: 'classement', q: 'Comment progresser dans le classement ?', a: 'Pour progresser, jouez régulièrement et cherchez des adversaires de niveau supérieur. Une victoire contre un joueur très bien classé fait monter rapidement. Même en perdant contre un joueur bien classé, la perte de points est limitée. La régularité sur plusieurs tournois est la clé.' },
  { id: 'cla-5', cat: 'classement', q: 'Où puis-je voir mon classement en temps réel ?', a: 'Le classement est disponible sur la page Classement de ce site, accessible sans connexion. Il est mis à jour après la saisie de chaque match officiel par l\'organisateur. Vous pouvez aussi consulter la fiche de chaque joueur sur la page Joueurs.' },

  // Pratique
  { id: 'pra-1', cat: 'pratique', q: 'Où se déroule la compétition ?', a: 'Les compétitions Club 8 Pool se tiennent à l\'Icone Pool à Libreville, Gabon. L\'adresse exacte et les indications d\'accès sont disponibles sur la page de chaque compétition. Contactez l\'organisateur pour tout renseignement sur le lieu.' },
  { id: 'pra-2', cat: 'pratique', q: 'À quelle heure commencent les matchs ?', a: 'Les horaires sont publiés sur ce site avant chaque événement. En général, les poules commencent en matinée ou en début d\'après-midi. Le programme exact de chaque journée est affiché sur la page de la compétition active. Soyez présent au moins 20 minutes avant votre premier match.' },
  { id: 'pra-3', cat: 'pratique', q: 'Comment suivre les matchs en direct si je ne peux pas me déplacer ?', a: 'Rendez-vous sur la page Live de ce site pour suivre en temps réel les scores, la table de chaque match, les résultats des frames et le bracket de la phase finale. La page Live est mise à jour automatiquement et ne nécessite pas de connexion spéciale.' },
  { id: 'pra-4', cat: 'pratique', q: 'Comment contacter les organisateurs ?', a: 'Contactez Dimitri, responsable de l\'organisation, au 077 79 10 57 (appel ou WhatsApp). Pour toute question relative aux inscriptions, aux résultats ou à la logistique, c\'est le contact principal. Vous pouvez aussi écrire à mebodoaristide@gmail.com.' },
  { id: 'pra-5', cat: 'pratique', q: 'Où puis-je consulter les résultats après la compétition ?', a: 'Les résultats complets sont disponibles sur la page de la compétition concernée. Une fois le tournoi terminé, celui-ci est archivé et consultable via la page Tournois. Les statistiques individuelles (frames, victoires, fautes) sont accessibles sur la fiche de chaque joueur.' },
];

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase();
  return faq.filter(item => {
    const matchCat = activeCategory.value === 'all' || item.cat === activeCategory.value;
    const matchSearch = !q
      || item.q.toLowerCase().includes(q)
      || item.a.toLowerCase().includes(q);
    return matchCat && matchSearch;
  });
});

const highlight = (text) => {
  const q = search.value.trim();
  if (!q) return text;
  return text.replace(
    new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'),
    '<mark style="background:rgba(46,125,94,0.35);color:var(--chalk);border-radius:2px;padding:0 1px;">$1</mark>'
  );
};

const toggle = (id) => {
  openId.value = openId.value === id ? null : id;
};
</script>

<template>
  <Head title="FAQ — Club 8 Pool">
    <meta name="description" content="Questions fréquentes sur les compétitions Club 8 Pool — inscription, règles, classement, pratique." head-key="description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh; display: flex; flex-direction: column;">
    <PublicNav />

    <!-- Hero + search -->
    <section style="padding: 32px 0 28px; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">FAQ</div>
        <h1 class="disp-a" style="font-size: clamp(40px, 9vw, 80px); margin-top: 14px; line-height: 0.92;">
          Questions<br /><span style="color: var(--mute);">fréquentes</span>
        </h1>

        <!-- Search -->
        <div style="margin-top: 24px; max-width: 540px; position: relative;">
          <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--mute); font-size: 14px; pointer-events: none;">◎</span>
          <input
            v-model="search"
            type="search"
            placeholder="Rechercher une question…"
            style="width: 100%; padding: 12px 14px 12px 40px;
                   background: var(--ink-2); border: 1px solid var(--line-strong);
                   color: var(--chalk); font-size: 14px; outline: none;
                   font-family: var(--font-body);"
            @focus="$event.target.style.borderColor = 'var(--felt-2)'"
            @blur="$event.target.style.borderColor = 'var(--line-strong)'"
          />
        </div>
      </div>
    </section>

    <!-- Category chips -->
    <section style="border-bottom: 1px solid var(--line);">
      <div class="container" style="padding-top: 0; padding-bottom: 0;">
        <div style="display: flex; gap: 8px; padding: 16px 0; overflow-x: auto; flex-wrap: nowrap;">
          <button
            v-for="cat in categories" :key="cat.id"
            @click="activeCategory = cat.id; openId = null"
            :style="{
              padding: '6px 14px',
              border: '1px solid ' + (activeCategory === cat.id ? 'var(--felt-2)' : 'var(--line)'),
              background: activeCategory === cat.id ? 'var(--felt)' : 'transparent',
              color: activeCategory === cat.id ? '#fff' : 'var(--mute)',
              cursor: 'pointer', fontSize: '11px', fontFamily: 'var(--font-mono)',
              letterSpacing: '0.12em', whiteSpace: 'nowrap',
            }"
          >{{ cat.label.toUpperCase() }}</button>
        </div>
      </div>
    </section>

    <!-- Results -->
    <div class="container" style="flex: 1; width: 100%; max-width: 840px;">

      <!-- No results -->
      <div v-if="filtered.length === 0"
           style="padding: 48px; text-align: center; color: var(--mute);">
        <div class="disp-a" style="font-size: 28px; color: var(--mute-2);">?</div>
        <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; margin-top: 10px;">AUCUN RÉSULTAT</div>
        <p style="font-size: 13px; margin-top: 8px;">Essayez d'autres mots-clés ou contactez-nous directement.</p>
      </div>

      <!-- Count when searching -->
      <div v-if="search && filtered.length > 0"
           class="mono"
           style="font-size: 10px; letter-spacing: 0.16em; color: var(--mute); margin-bottom: 16px;">
        {{ filtered.length }} résultat{{ filtered.length > 1 ? 's' : '' }} pour « {{ search }} »
      </div>

      <!-- FAQ accordion -->
      <div style="display: flex; flex-direction: column; gap: 0;">
        <div
          v-for="item in filtered" :key="item.id"
          style="border-bottom: 1px solid var(--line);"
        >
          <button
            @click="toggle(item.id)"
            style="width: 100%; display: flex; justify-content: space-between; align-items: flex-start;
                   gap: 16px; padding: 18px 0; background: transparent; border: none;
                   cursor: pointer; text-align: left;"
          >
            <span
              style="font-size: 14px; font-weight: 600; color: var(--chalk); line-height: 1.4; flex: 1;"
              v-html="highlight(item.q)"
            />
            <span
              :style="{
                color: 'var(--felt-2)', fontSize: '18px', lineHeight: '1',
                transform: openId === item.id ? 'rotate(45deg)' : 'none',
                transition: 'transform 0.2s', flexShrink: '0', marginTop: '2px',
              }"
            >+</span>
          </button>
          <div
            v-show="openId === item.id"
            style="padding: 0 0 18px 0; font-size: 13px; color: var(--mute); line-height: 1.75; max-width: 680px;"
            v-html="highlight(item.a)"
          />
        </div>
      </div>

      <!-- CTA contact -->
      <div v-if="!search"
           style="margin-top: 40px; padding: 24px; border: 1px dashed var(--line); text-align: center;">
        <div style="font-size: 13px; color: var(--mute); margin-bottom: 12px; line-height: 1.6;">
          Vous ne trouvez pas la réponse à votre question ?
        </div>
        <a href="tel:+24177791057"
           style="display: inline-block; padding: 10px 24px;
                  border: 1px solid var(--felt-2); color: var(--felt-2);
                  text-decoration: none; font-size: 13px; font-weight: 600;">
          ☎ Contacter Dimitri · 077 79 10 57
        </a>
      </div>
    </div>

    <PublicFooter />
  </div>
</template>
