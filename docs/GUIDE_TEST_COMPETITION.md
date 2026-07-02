# Guide de Test — Compétition Club 8 Pool

**Version :** Summer Edition  
**Format :** 8 poules × 6 joueurs, phase finale R32 → Finale  
**Discipline :** 8-ball | Shot clock : 30 secondes  
**Stack :** Laravel 11 + Inertia.js + Vue 3  

---

## Table des matières

1. [Préparation technique](#1-préparation-technique)
2. [Authentification](#2-authentification)
3. [Compétition Summer Edition](#3-compétition-summer-edition)
4. [Interface arbitre — match complet](#4-interface-arbitre--match-complet)
5. [Pages publiques](#5-pages-publiques)
6. [Sécurité](#6-sécurité)
7. [Tests terrain (jour J)](#7-tests-terrain-jour-j)
8. [Checklist GO / NO-GO](#8-checklist-go--no-go)

---

## 1. Préparation technique

> Effectuer ces étapes avant tout test fonctionnel.

### 1.1 Vérifications initiales

- [ ] Migrations à jour : `php artisan migrate --status` — toutes les migrations affichent **Ran**
- [ ] Aucune erreur dans les logs : `tail -n 50 storage/logs/laravel.log`
- [ ] Build Vite présent : `ls public/build/manifest.json` — fichier doit exister
- [ ] Health-check (si disponible) : `php artisan c8p:health-check`

### 1.2 Vider les caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

- [ ] Toutes les commandes se terminent sans erreur

### 1.3 Rebuild Vite (si modifications front-end récentes)

```bash
npm run build
```

- [ ] Build se termine avec `✓ built in X.Xs` (pas de warning critique)

### 1.4 Seed initial

```bash
php artisan db:seed --class=SummerEditionSeeder
```

- [ ] Commande complète sans erreur
- [ ] Message de confirmation visible dans la console
- [ ] La compétition est créée en base (vérifier via admin ou `tinker`)

---

## 2. Authentification

### 2.1 Connexion Admin

| Champ | Valeur |
|-------|--------|
| URL | `/login` |
| Identifiant | email admin |
| Mot de passe | mot de passe admin |

- [ ] Accéder à `/login` → formulaire s'affiche correctement
- [ ] Se connecter avec les identifiants admin → redirection vers le dashboard admin
- [ ] Le nom de l'admin est affiché dans la barre de navigation
- [ ] Accéder à `/admin/competitions` → liste des compétitions visible
- [ ] Accéder à `/joueurs` (admin) → liste des joueurs visible
- [ ] Modifier une compétition (titre, dates) → modification sauvegardée
- [ ] Changer le statut d'une compétition (ex: `draft` → `in_progress`) → statut mis à jour
- [ ] Générer une poule si applicable
- [ ] Se déconnecter → redirection vers `/login`
- [ ] Accéder à `/admin/competitions` sans être connecté → redirection vers `/login`

### 2.2 Connexion Arbitre

| Champ | Valeur |
|-------|--------|
| URL | `/arbitre/login` (redirige vers `/login` mode arbitre) |
| Identifiant | Prénom (ex: `Ali`) |
| PIN | 4 chiffres (ex: `1234`) |

- [ ] Accéder à `/arbitre/login` → redirection vers `/login` avec mode arbitre actif
- [ ] Le toggle "Mode arbitre" est coché/actif sur le formulaire
- [ ] Se connecter avec prénom + PIN → redirection vers `/arbitre/queue`
- [ ] La file de matchs assignés s'affiche (liste vide si aucun match assigné)
- [ ] Naviguer vers un match depuis la queue → page match accessible
- [ ] Se déconnecter → retour vers `/login`
- [ ] Tentative avec PIN erroné → message d'erreur, pas de redirection

### 2.3 Connexion Joueur

| Champ | Valeur |
|-------|--------|
| URL | `/joueur/login` |
| Identifiant | `login_name` (ex: `amauris`) |
| Mot de passe initial | `1234567` |

- [ ] Accéder à `/joueur/login` → formulaire joueur s'affiche
- [ ] Se connecter avec `login_name` + mot de passe initial `1234567`
- [ ] Redirection vers `/joueur/password/change` (changement obligatoire au premier login)
- [ ] Entrer un nouveau mot de passe respectant les règles de complexité
- [ ] Confirmer le nouveau mot de passe
- [ ] Validation → redirection vers `/joueur/dashboard`
- [ ] Le dashboard affiche : nom du joueur, statut dans la compétition, matchs à venir
- [ ] Accéder à `/joueur/competitions/{id}` → parcours compétition visible (poule, matchs, scores)
- [ ] Uploader une photo de profil (format JPG/PNG) → photo affichée
- [ ] Se déconnecter → retour vers `/joueur/login`
- [ ] Tenter d'accéder à `/joueur/dashboard` sans être connecté → redirection vers `/joueur/login`

---

## 3. Compétition Summer Edition

### 3.1 Mode initial — SummerEditionSeeder

```bash
php artisan db:seed --class=SummerEditionSeeder
```

- [ ] Compétition créée en statut `in_progress`
- [ ] 8 poules créées : A, B, C, D, E, F, G, H
- [ ] 48 joueurs inscrits (6 par poule)
- [ ] 2 tables de jeu dans le système
- [ ] 120 matchs de poule générés (15 matchs par poule)
- [ ] Aucun match de phase KO existant
- [ ] Race-to correctement fixé à **4** pour les matchs de poule
- [ ] Tous les matchs en statut `scheduled`

**Vérification SQL rapide :**
```bash
php artisan tinker --execute="
  echo 'Poules: ' . \App\Models\Group::count() . PHP_EOL;
  echo 'Joueurs: ' . \App\Models\Player::count() . PHP_EOL;
  echo 'Matchs poule: ' . \App\Models\Match::whereNotNull('group_id')->count() . PHP_EOL;
"
```

### 3.2 Mode résultats démo — SummerEditionDemoResultsSeeder

```bash
php artisan db:seed --class=SummerEditionDemoResultsSeeder
```

- [ ] Poules A à G : tous les matchs en statut `done`
- [ ] Poule H : tous les matchs en statut `scheduled`
- [ ] Scores cohérents pour les poules A-G : l'un des scores doit être **4** (race-to 4)
- [ ] Classements de poule A-G calculés et affichés sur `/competitions/{slug}`
- [ ] Poule H : classement vide ou à zéro

### 3.3 Génération du bracket KO (via admin)

> Pré-requis : toutes les poules ou les poules A-G terminées, poule H en cours ou terminée.

- [ ] Se connecter en admin
- [ ] Accéder à `/admin/competitions/{id}/phase-finale`
- [ ] La page affiche les **32 qualifiés** (4 premiers de chaque poule)
- [ ] Si des ex-aequo existent → un message d'alerte ou une interface de résolution s'affiche
- [ ] Résoudre les éventuels ex-aequo
- [ ] Cliquer "Générer le bracket"
- [ ] **16 matchs R32** créés en base
- [ ] Seeding conforme au format : 1A vs 4B, 2A vs 3B, etc. (ou autre règle définie)
- [ ] Race-to fixé à **7** pour les matchs R32
- [ ] Le bracket est visible sur `/competitions/{slug}` (côté public)
- [ ] Chaque match R32 est assignable à un arbitre

**Vérifications post-génération :**
```bash
php artisan tinker --execute="
  echo 'Matchs R32: ' . \App\Models\Match::where('round', 'R32')->count() . PHP_EOL;
"
```

### 3.4 Seeder E2E complet

```bash
php artisan db:seed --class=SummerE2ESeeder
```

- [ ] Compétition avec phase KO avancée créée
- [ ] Matchs présents pour R32, R16, QF, SF, 3P et Finale (ou une partie selon l'avancement)
- [ ] Race-to corrects par round :
  - R32 / R16 → 7
  - QF / SF → 9
  - 3P → 5
  - Finale → 11
- [ ] 152 matchs créés au total
- [ ] Le bracket est visible et navigable en admin

---

## 4. Interface arbitre — Match complet

> Pré-requis : un match assigné à l'arbitre connecté.

### 4.1 Démarrage du match

- [ ] Se connecter en tant qu'arbitre (prénom + PIN)
- [ ] Accéder à `/arbitre/queue` → le match assigné apparaît dans la liste
- [ ] Cliquer sur le match → accès à `/arbitre/matchs/{id}`
- [ ] La page affiche : noms des deux joueurs, race-to, table assignée
- [ ] Cliquer "Démarrer" (Start) → le match passe en statut `in_progress`

### 4.2 Shot clock

- [ ] Après démarrage, le shot clock affiche **30 secondes**
- [ ] Le décompte s'exécute en temps réel (sans rechargement de page)
- [ ] Le shot clock peut être réinitialisé (bouton reset / changement de main)
- [ ] Le shot clock s'arrête en cas de pause
- [ ] Le shot clock expire à 0 → signal visuel (couleur rouge, alerte, son si applicable)

### 4.3 Gestion du jeu

- [ ] Donner la main à Joueur A → indicateur "main" côté A visible
- [ ] Donner la main à Joueur B → indicateur "main" côté B visible
- [ ] Activer une extension de temps (si fonctionnalité activée) → shot clock s'allonge
- [ ] Accorder une manche (frame) à Joueur A → score A + 1
- [ ] Accorder une manche à Joueur B → score B + 1
- [ ] Annuler la dernière manche (undo) → score revient en arrière
- [ ] Enregistrer un warning pour Joueur A → warning A + 1
- [ ] Enregistrer un warning pour Joueur B → warning B + 1

### 4.4 Pause et reprise

- [ ] Mettre le match en pause → le shot clock s'arrête, bouton "Reprendre" visible
- [ ] Reprendre le match → le shot clock repart, statut revient à `in_progress`

### 4.5 Clôture du match

- [ ] Jouer jusqu'au race-to → bouton "Clore" devient disponible (ou apparaît automatiquement)
- [ ] Cliquer "Clore" → fenêtre de confirmation ou interface de signature
- [ ] Valider la clôture → match passe en statut `done`
- [ ] Le score final est enregistré en base
- [ ] Revenir sur `/competitions/{slug}` côté public → le score est visible
- [ ] Le classement de la poule est mis à jour automatiquement (points, différence de manches)
- [ ] Se déconnecter de l'arbitre, se reconnecter → le score est persisté (pas de perte de données)

### 4.6 Cohérence des données post-match

- [ ] Le gagnant est correct (joueur avec race-to manches)
- [ ] La table de jeu est libérée / mise à jour
- [ ] Le match n'apparaît plus dans la queue de l'arbitre (ou est marqué terminé)

---

## 5. Pages publiques

> Ces pages sont accessibles sans authentification.

| URL | Test attendu |
|-----|--------------|
| `/` | Landing page — affiche la compétition en cours si présente |
| `/competitions` | Liste toutes les compétitions, pas de 404 si aucune compétition active |
| `/competitions/{slug}` | Détail compétition : poules, standings, bracket KO si généré |
| `/joueurs` | Liste de joueurs — pagination si > N joueurs |
| `/joueurs/{player}` | Profil joueur — pas de téléphone ni d'email affiché |
| `/classement` | Classement ELO global |
| `/live` | Matchs en cours (404 ou message "aucun match en cours" acceptable) |
| `/tournois` | Archives des tournois passés |

- [ ] `/` — page d'accueil se charge sans erreur 500
- [ ] `/competitions` — liste affichée, même sans compétition active
- [ ] `/competitions/{slug}` — détail complet : poules, joueurs, scores, standings
- [ ] `/competitions/{slug}` — bracket KO visible après génération
- [ ] `/joueurs` — liste de joueurs, photos si uploadées
- [ ] `/joueurs/{player}` — profil joueur visible, **aucun numéro de téléphone**, **aucun email**
- [ ] `/classement` — tableau ELO affiché avec classement et scores
- [ ] `/live` — pas de crash (500) si aucun match live
- [ ] `/tournois` — page accessible, liste vide ou compétitions archivées

---

## 6. Sécurité

### 6.1 Contrôle d'accès

- [ ] Accéder à `/admin/competitions` sans être connecté → redirection vers `/login`
- [ ] Accéder à `/arbitre/queue` sans être connecté → redirection vers `/login`
- [ ] Accéder à `/joueur/dashboard` sans être connecté → redirection vers `/joueur/login`
- [ ] Connecté en tant que joueur, accéder à `/admin/competitions` → accès refusé (403 ou redirection)
- [ ] Connecté en tant que joueur, accéder à `/arbitre/queue` → accès refusé
- [ ] Connecté en tant qu'arbitre, accéder à `/admin/competitions` → accès refusé
- [ ] Arbitre tenter de scorer un match qui ne lui est pas assigné → accès refusé (403)

### 6.2 Données personnelles (PII)

- [ ] Page `/joueurs/{player}` — numéro de téléphone absent du HTML source
- [ ] Page `/joueurs/{player}` — adresse email absente du HTML source
- [ ] API JSON (si applicable) — les champs `phone`, `email` sont exclus ou masqués dans les réponses publiques

### 6.3 Upload de fichiers

- [ ] Upload photo de profil avec format invalide (`.txt`, `.pdf`, `.exe`) → erreur de validation claire
- [ ] Upload photo de profil trop lourde (> limite configurée) → erreur de validation
- [ ] Upload photo valide (`.jpg`, `.png`) → succès, image affichée

### 6.4 Rate-limiting

- [ ] Effectuer 10 tentatives de login arbitre rapides avec un mauvais PIN → blocage temporaire ou message d'erreur de rate-limit
- [ ] Vérifier que le rate-limit est logué (si observabilité configurée)

---

## 7. Tests terrain (jour J)

### 7.1 Écran TV

- [ ] Accéder à `/tv` sur un grand écran (TV ou moniteur externe)
- [ ] L'affichage est lisible à distance (grandes polices, bon contraste)
- [ ] Les matchs en cours s'affichent avec : joueurs, score, table
- [ ] L'écran se rafraîchit automatiquement (polling ou WebSocket)

### 7.2 Réseau dégradé

- [ ] Simuler une connexion lente (3G dans DevTools → Network → Slow 3G)
- [ ] `/competitions/{slug}` — chargement en moins de 10 secondes
- [ ] `/arbitre/matchs/{id}` — fonctionnel (pas de crash si réseau lent)
- [ ] Le shot clock continue localement si perte réseau courte

### 7.3 Deux arbitres simultanés

- [ ] Ouvrir deux navigateurs (ou onglets privés) connectés avec deux arbitres différents
- [ ] Arbitre 1 score le match 1, Arbitre 2 score le match 2
- [ ] Les scores des deux matchs s'enregistrent indépendamment sans conflit
- [ ] Les classements sont cohérents après les deux clôtures

### 7.4 Assignation à distance (admin → arbitre)

- [ ] Admin assigne un match à l'arbitre (via `/admin/competitions/{id}`)
- [ ] L'arbitre (déjà connecté sur `/arbitre/queue`) voit le nouveau match apparaître
  - Sans rechargement → si polling ou WebSocket actif
  - Après rechargement → si pas de temps réel
- [ ] L'arbitre accède au match et peut le scorer

### 7.5 Correction d'erreur de score

- [ ] Admin accède à un match clôturé
- [ ] Modifier le score via l'interface admin (si disponible)
- [ ] Le classement de la poule est recalculé
- [ ] Vérifier la cohérence des standings après correction

### 7.6 Backup avant les matchs importants

- [ ] Exporter la base avant le lancement des matchs de poule :
  ```bash
  mysqldump -u [user] -p [database] > backup_avant_poules_$(date +%Y%m%d_%H%M).sql
  ```
- [ ] Exporter la base avant la génération du bracket KO :
  ```bash
  mysqldump -u [user] -p [database] > backup_avant_ko_$(date +%Y%m%d_%H%M).sql
  ```
- [ ] Vérifier que le fichier de backup est non vide

---

## 8. Checklist GO / NO-GO

> Évaluation finale avant le début officiel de la compétition.

### Critères GO (compétition peut démarrer)

- [ ] Login admin fonctionnel (email + mot de passe)
- [ ] Login arbitre fonctionnel (prénom + PIN)
- [ ] Login joueur fonctionnel (login_name + mot de passe)
- [ ] Scoring match fonctionnel (arbitre peut enregistrer une manche)
- [ ] Shot clock démarre et décompte correctement
- [ ] Clôture de match enregistre le score en base
- [ ] Classement de poule se met à jour après clôture
- [ ] Pages publiques accessibles sans erreur 500
- [ ] Écran TV (`/tv`) accessible et affiche les matchs en cours

### Critères NO-GO (bloquants — arrêter et corriger avant de commencer)

| Critère | Impact |
|---------|--------|
| Login arbitre cassé | Aucun arbitre ne peut scorer |
| Scoring impossible | Les résultats ne peuvent pas être enregistrés |
| Shot clock ne démarre pas | Discipline du temps impossible à faire respecter |
| Génération bracket KO échoue | La phase finale ne peut pas démarrer |
| Pages publiques en erreur 500 | Spectateurs et joueurs sans accès aux résultats |
| Clôture de match ne persiste pas le score | Perte de données garantie |

> Si un seul critère NO-GO est rouge, **ne pas démarrer la compétition** tant qu'il n'est pas résolu.

---

*Document généré pour le projet Club 8 Pool — Summer Edition.*
