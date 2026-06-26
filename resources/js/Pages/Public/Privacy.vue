<script setup>
import { Head } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const sections = [
  {
    title: '1. Identité du responsable de traitement',
    content: `Le responsable du traitement des données personnelles collectées sur ce Site est l'association Club 8 Pool, dont le siège social est établi à Libreville, République Gabonaise (Icone Pool, Libreville).\n\nContact : mebodoaristide@gmail.com — Téléphone : 077 79 10 57 (Dimitri)\n\nLe traitement des données personnelles est régi par la Loi n°001/2011 du 25 septembre 2011 relative à la protection des données à caractère personnel en République Gabonaise, sous le contrôle de la Commission Nationale pour la Protection des Données à Caractère Personnel (CNPDCP).`,
  },
  {
    title: '2. Données collectées',
    content: `Dans le cadre de l'inscription à une compétition, les données suivantes peuvent être collectées :\n\n— Données d'identité : nom, prénom, date de naissance (pour vérification de l'éligibilité juniors)\n— Données sportives : club d'appartenance, discipline pratiquée, nationalité\n— Données de contact : numéro de téléphone (facultatif), adresse e-mail (facultatif)\n— Photo de profil (optionnelle, fournie volontairement)\n— Données de performance : résultats des matchs, scores, statistiques sportives\n\nAucune donnée sensible au sens de la Loi n°001/2011 (données de santé, origines raciales ou ethniques, opinions politiques ou religieuses) n'est collectée.`,
  },
  {
    title: '3. Finalités et base légale du traitement',
    content: `Les données sont collectées et traitées pour les finalités suivantes :\n\n— Gestion des inscriptions aux compétitions (base légale : exécution d'un contrat)\n— Organisation et suivi des matchs (base légale : exécution d'un contrat)\n— Calcul et publication du classement Elo (base légale : intérêt légitime — classement sportif public)\n— Publication des résultats et statistiques sur ce Site (base légale : intérêt légitime — information sportive)\n— Communication relative aux compétitions (base légale : consentement ou intérêt légitime)\n\nLes données ne font l'objet d'aucun traitement automatisé à des fins de profilage commercial.`,
  },
  {
    title: '4. Droits des personnes concernées',
    content: `Conformément aux articles 15 à 18 de la Loi n°001/2011, toute personne concernée dispose des droits suivants :\n\n— Droit d'accès : obtenir la confirmation que des données vous concernant sont traitées et en recevoir une copie.\n— Droit de rectification : demander la correction de données inexactes ou incomplètes.\n— Droit d'opposition : s'opposer au traitement de vos données pour des motifs légitimes.\n— Droit à l'effacement : demander la suppression de vos données, sous réserve des obligations légales de conservation.\n\nPour exercer vos droits, adressez votre demande par e-mail à mebodoaristide@gmail.com en précisant votre identité. Nous nous engageons à répondre dans un délai de 30 jours.`,
  },
  {
    title: '5. Durée de conservation',
    content: `Les données personnelles sont conservées selon les durées suivantes :\n\n— Données d'inscription et de contact : durée de la compétition + 1 an après la fin de la dernière compétition à laquelle la personne a participé.\n— Données sportives (résultats, classement) : conservées indéfiniment à titre de données publiques sportives, conformément à l'usage habituel dans les organisations sportives.\n— Données de session (connexion arbitre, organisateur) : 30 jours après la fin de la compétition.\n\nA l'issue de ces délais, les données sont supprimées ou anonymisées de manière irréversible.`,
  },
  {
    title: '6. Sécurité des données',
    content: `Le responsable de traitement met en œuvre des mesures techniques et organisationnelles appropriées pour protéger les données personnelles contre toute perte, accès non autorisé, divulgation, altération ou destruction.\n\nLes mesures incluent notamment : chiffrement des communications (HTTPS), contrôle d'accès aux données, mots de passe hachés, accès limité aux données selon le rôle (arbitre, organisateur), hébergement sécurisé.\n\nEn cas de violation de données susceptible d'engendrer un risque pour les personnes concernées, le responsable de traitement informera les personnes concernées dans les meilleurs délais et notifiera la CNPDCP conformément à la réglementation applicable.`,
  },
  {
    title: '7. Transfert de données',
    content: `Les données personnelles collectées sur ce Site ne sont pas transmises à des tiers à des fins commerciales.\n\nElles peuvent être partagées avec des prestataires techniques strictement nécessaires au fonctionnement du Site (hébergeur, fournisseur d'infrastructure), dans le cadre d'un contrat garantissant un niveau de protection équivalent à celui prévu par la Loi n°001/2011.\n\nAucun transfert de données vers des pays tiers ne présentant pas un niveau de protection adéquat n'est effectué sans les garanties appropriées.`,
  },
  {
    title: '8. Contact et réclamation',
    content: `Pour toute question relative à la protection de vos données personnelles ou pour exercer vos droits :\n\nClub 8 Pool — Icone Pool, Libreville, Gabon\nE-mail : mebodoaristide@gmail.com\nTéléphone : 077 79 10 57\n\nVous disposez également du droit de déposer une réclamation auprès de la Commission Nationale pour la Protection des Données à Caractère Personnel (CNPDCP), autorité de contrôle compétente en République Gabonaise.\n\nCNPDCP — Libreville, République Gabonaise\nSite officiel : www.cnpdcp.ga`,
  },
];
</script>

<template>
  <Head title="Politique de confidentialité — Club 8 Pool">
    <meta name="description" content="Politique de confidentialité Club 8 Pool — Loi n°001/2011, CNPDCP, protection des données personnelles au Gabon." head-key="description" />
  </Head>
  <div style="background: var(--ink); min-height: 100vh; display: flex; flex-direction: column;">
    <PublicNav />

    <section style="padding: 32px 0; border-bottom: 1px solid var(--line);">
      <div class="container">
        <div class="mono" style="font-size: 11px; letter-spacing: 0.22em; color: var(--mute);">DONNÉES PERSONNELLES</div>
        <h1 class="disp-a" style="font-size: clamp(30px, 7vw, 60px); margin-top: 14px; line-height: 0.95;">
          Politique de<br /><span style="color: var(--mute);">confidentialité</span>
        </h1>
        <div class="mono" style="font-size: 11px; color: var(--mute); margin-top: 16px; letter-spacing: 0.12em;">
          Version : juin 2026 · Loi n°001/2011 · CNPDCP · République Gabonaise
        </div>
      </div>
    </section>

    <!-- CNPDCP notice -->
    <section style="background: rgba(46,125,94,0.07); border-bottom: 1px solid var(--felt);">
      <div class="container" style="padding-top: 14px; padding-bottom: 14px; max-width: 800px;">
        <div style="font-size: 12px; color: var(--felt-2); line-height: 1.6;">
          <strong>CNPDCP</strong> — Commission Nationale pour la Protection des Données à Caractère Personnel.
          Autorité de contrôle compétente en République Gabonaise conformément à la Loi n°001/2011.
        </div>
      </div>
    </section>

    <div class="container" style="flex: 1; max-width: 800px;">
      <div style="display: flex; flex-direction: column; gap: 36px;">
        <section v-for="s in sections" :key="s.title">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--chalk); margin-bottom: 12px; border-left: 2px solid var(--felt-2); padding-left: 12px;">
            {{ s.title }}
          </h2>
          <p
            v-for="(para, i) in s.content.split('\n\n')" :key="i"
            style="font-size: 13px; color: var(--mute); line-height: 1.8; margin-bottom: 10px; white-space: pre-line;"
          >{{ para }}</p>
        </section>
      </div>
    </div>

    <PublicFooter />
  </div>
</template>
