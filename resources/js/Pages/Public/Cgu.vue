<script setup>
import { Head } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const sections = [
  {
    title: '1. Présentation de l\'éditeur',
    content: `Le site Club 8 Pool (ci-après « le Site ») est édité par l'association Club 8 Pool, dont le siège social est établi à Libreville, République Gabonaise, et qui opère au sein de la salle Icone Pool, Libreville, Gabon.\n\nContact : mebodoaristide@gmail.com — Téléphone : 077 79 10 57 (Dimitri)\n\nConformément à l'article 37 de la Loi n°025/2018 du 25 juin 2018 relative à l'économie numérique en République Gabonaise, les présentes Conditions Générales d'Utilisation (CGU) sont rendues accessibles à tout utilisateur avant toute utilisation du Site.`,
  },
  {
    title: '2. Objet et acceptation des CGU',
    content: `Les présentes CGU ont pour objet de définir les modalités et conditions d'utilisation du Site ainsi que les droits et obligations de l'éditeur et des utilisateurs.\n\nL'accès au Site implique l'acceptation pleine et entière des présentes CGU. Si l'utilisateur n'accepte pas ces conditions, il doit s'abstenir d'utiliser le Site.\n\nL'éditeur se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs sont invités à les consulter régulièrement. Toute modification entre en vigueur dès sa publication sur le Site.`,
  },
  {
    title: '3. Accès au Site et utilisation',
    content: `Le Site est accessible gratuitement depuis tout appareil connecté à Internet. L'éditeur met en œuvre tous les moyens raisonnables pour assurer un accès continu au Site, mais ne saurait être tenu responsable d'une interruption temporaire pour des raisons de maintenance, de force majeure ou de défaillance technique.\n\nL'utilisateur s'engage à utiliser le Site de manière licite et conforme aux présentes CGU. Il est notamment interdit de tenter d'accéder de manière non autorisée aux systèmes informatiques du Site, de perturber le fonctionnement du Site ou de publier des contenus illicites.`,
  },
  {
    title: '4. Propriété intellectuelle',
    content: `L'ensemble des contenus du Site (textes, images, logos, données, graphiques, vidéos, sons) est protégé par le droit de la propriété intellectuelle applicable en République Gabonaise, et notamment par la Loi n°039/2000 relative à la protection de la propriété intellectuelle au Gabon.\n\nToute reproduction, représentation, modification, publication, transmission ou dénaturation, totale ou partielle, du contenu du Site est interdite sans l'accord préalable et écrit de l'éditeur. Seule une utilisation à des fins strictement personnelles et non commerciales est autorisée.`,
  },
  {
    title: '5. Responsabilité',
    content: `L'éditeur met tout en œuvre pour fournir des informations exactes et à jour sur le Site. Toutefois, il ne saurait garantir l'exactitude, la complétude ou l'actualité des informations diffusées. L'utilisateur est seul responsable de l'utilisation qu'il fait des informations publiées.\n\nL'éditeur ne pourra être tenu responsable des dommages directs ou indirects résultant de l'accès au Site ou de l'utilisation des informations qui y sont publiées, notamment en cas d'interruption de service, d'erreur ou d'omission dans les contenus.\n\nLes liens hypertextes vers d'autres sites web sont fournis à titre indicatif. L'éditeur n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu.`,
  },
  {
    title: '6. Données personnelles',
    content: `Les informations personnelles collectées sur ce Site sont traitées conformément à la Loi n°001/2011 du 25 septembre 2011 relative à la protection des données à caractère personnel en République Gabonaise et sous le contrôle de la Commission Nationale pour la Protection des Données à Caractère Personnel (CNPDCP).\n\nPour toute question relative au traitement de vos données personnelles, consultez notre Politique de confidentialité accessible depuis ce Site.`,
  },
  {
    title: '7. Cookies',
    content: `Le Site peut utiliser des cookies techniques strictement nécessaires à son fonctionnement (session, préférences d'affichage). Ces cookies ne stockent aucune donnée personnelle identifiable.\n\nConformément à la réglementation gabonaise applicable, l'utilisateur est informé de l'utilisation de ces cookies lors de sa première visite. Il peut désactiver les cookies depuis les paramètres de son navigateur, ce qui peut affecter certaines fonctionnalités du Site.`,
  },
  {
    title: '8. Droit applicable et juridiction compétente',
    content: `Les présentes CGU sont régies par le droit gabonais, et notamment par la Loi n°025/2018 relative à l'économie numérique et le Code civil gabonais.\n\nEn cas de litige relatif à l'interprétation ou à l'exécution des présentes CGU, les parties conviennent de rechercher une solution amiable. À défaut, tout litige sera soumis à la compétence exclusive des Tribunaux de Libreville, République Gabonaise.`,
  },
  {
    title: '9. Modifications des CGU',
    content: `L'éditeur se réserve le droit de modifier les présentes CGU à tout moment, sans préavis. Les modifications prennent effet dès leur publication sur le Site. L'utilisation continue du Site après publication des modifications vaut acceptation des nouvelles CGU.\n\nVersion en vigueur : juin 2026. Les utilisateurs sont invités à consulter régulièrement cette page pour prendre connaissance des éventuelles modifications.`,
  },
  {
    title: '10. Contact',
    content: `Pour toute question relative aux présentes CGU ou à l'utilisation du Site :\n\nClub 8 Pool — Icone Pool, Libreville, Gabon\nE-mail : mebodoaristide@gmail.com\nTéléphone : 077 79 10 57`,
  },
];
</script>

<template>
  <Head title="Conditions Générales d'Utilisation — Club 8 Pool">
    <meta name="description" content="Conditions générales d'utilisation du site Club 8 Pool — droit gabonais, Loi n°025/2018." head-key="description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh; display: flex; flex-direction: column;">
    <PublicNav />

    <section style="padding: 32px 24px; border-bottom: 1px solid var(--line);">
      <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">JURIDIQUE</div>
      <h1 class="disp-a" style="font-size: clamp(30px, 7vw, 60px); margin-top: 14px; line-height: 0.95;">
        Conditions Générales<br /><span style="color: var(--mute);">d'Utilisation</span>
      </h1>
      <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 16px; letter-spacing: 0.12em;">
        Version en vigueur : juin 2026 · Droit applicable : République Gabonaise
      </div>
    </section>

    <div style="flex: 1; padding: 32px 24px; max-width: 800px;">
      <div style="display: flex; flex-direction: column; gap: 36px;">
        <section v-for="s in sections" :key="s.title">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--chalk); margin-bottom: 12px; border-left: 2px solid var(--felt-2); padding-left: 12px;">
            {{ s.title }}
          </h2>
          <p
            v-for="(para, i) in s.content.split('\n\n')" :key="i"
            style="font-size: 13px; color: var(--mute); line-height: 1.8; margin-bottom: 10px;"
          >{{ para }}</p>
        </section>
      </div>
    </div>

    <PublicFooter />
  </div>
</template>
