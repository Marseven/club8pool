# QA Terrain — Jour J · Club 8 Pool

Ce document est exécutable par l'organisateur ou le responsable technique MRTECH
avant et pendant la compétition. Aucune compétence technique n'est requise pour
le suivi de cette checklist.

---

## Checklist J-1 (veille de la compétition)

### 1. Accès admin

- [ ] Ouvrir un navigateur et aller sur `/login`
- [ ] Se connecter avec le compte admin (email + mot de passe)
- [ ] Vérifier que le dashboard admin charge correctement : `/admin`
- [ ] Vérifier que la compétition du jour apparaît dans **Admin → Competitions**
- [ ] Vérifier que le statut de la compétition est `in_progress` ou `registration`
  - Si le statut est `draft` : aller sur **Admin → Competitions → [Compétition] → Edit** et changer le statut

### 2. Paramètres shot clock

- [ ] Aller sur **Admin → Competitions → [Compétition] → Edit**
- [ ] Vérifier que le champ `shot_clock_enabled` est bien coché (activé)
- [ ] Vérifier que `shot_clock` est réglé à **30** secondes
- [ ] Vérifier que `shot_clock_late_seconds` est réglé à **15** secondes
- [ ] Sauvegarder si des modifications ont été effectuées

> **Note :** Si la compétition n'utilise pas de shot clock, s'assurer que `shot_clock_enabled` est décoché.

### 3. Vérification des arbitres et PIN

- [ ] Aller sur **Admin → Arbitres** (`/admin/arbitres`)
- [ ] Vérifier que tous les arbitres du jour sont présents dans la liste
- [ ] Pour chaque arbitre, vérifier qu'un PIN est défini (la colonne PIN ne doit pas être vide)
- [ ] Si un arbitre n'a pas de PIN : cliquer sur son nom → **Reset PIN** → noter le nouveau PIN sur un slip papier
- [ ] Distribuer les slips PIN aux arbitres en main propre (ne pas envoyer par SMS ou email)

### 4. Test de connexion arbitre — interface web

- [ ] Ouvrir un onglet en navigation privée
- [ ] Aller sur `/arbitre`
- [ ] Se connecter avec un compte arbitre (nom + PIN)
- [ ] Vérifier que la queue de matchs se charge
- [ ] Se déconnecter

### 5. Test de connexion arbitre — API mobile

Ce test vérifie que l'application mobile fonctionne correctement.

- [ ] Ouvrir un terminal ou un outil comme Postman / Insomnia
- [ ] Envoyer la requête suivante :

```
POST /api/referee/login
Content-Type: application/json

{ "pin": "XXXX", "referee_id": ID_ARBITRE }
```

- [ ] Vérifier que la réponse contient un token :

```json
{ "token": "1|abc123..." }
```

- [ ] Si la réponse est une erreur 401 : vérifier le PIN dans l'admin
- [ ] Si la réponse est une erreur 429 : attendre 1 minute (limite de 5 tentatives/minute)

### 6. Affichage TV

- [ ] Sur un écran 1920×1080, ouvrir `/tv`
- [ ] Vérifier que la page s'affiche correctement en plein écran (F11)
- [ ] Si la compétition est en phase de poules : vérifier que les classements des poules sont visibles
- [ ] Si la compétition est en phase finale : vérifier que le bracket est visible
- [ ] Pour un affichage multi-écrans (par table) : utiliser `/tv/table/{id}` (ex. `/tv/table/1`)

### 7. Page live publique

- [ ] Ouvrir `/live` dans un navigateur
- [ ] Vérifier que la compétition en cours est visible
- [ ] Vérifier que les scores se mettent à jour (la page se rafraîchit toutes les 10 secondes)

### 8. Page joueurs

- [ ] Aller sur `/joueurs`
- [ ] Vérifier que la liste des joueurs de la compétition est affichée
- [ ] Cliquer sur un joueur et vérifier que sa fiche s'ouvre (`/joueurs/{id}`)

### 9. Page inscription

- [ ] Aller sur `/inscription`
- [ ] Vérifier que la compétition apparaît dans la liste
- [ ] Ne pas soumettre de faux dossier (vérification visuelle uniquement)

### 10. Health check système

- [ ] Sur le serveur, exécuter :

```bash
php artisan c8p:health-check
```

- [ ] Vérifier que toutes les lignes affichent `[OK]` ou `[PASS]`
- [ ] Si une ligne affiche `[FAIL]` ou `[ERROR]` : contacter le responsable technique avant de continuer

### 11. Sauvegarde base de données (obligatoire avant démarrage)

- [ ] Exécuter la sauvegarde de la base de données :

```bash
mysqldump -u NOM_UTILISATEUR -p NOM_BASE > backup_avant_competition_$(date +%Y%m%d_%H%M).sql
```

- [ ] Vérifier que le fichier `.sql` a bien été créé et n'est pas vide
- [ ] Copier ce fichier sur un support externe ou un emplacement sécurisé (clé USB, Drive)
- [ ] Noter l'emplacement du fichier de sauvegarde dans ce document

> **Emplacement de la sauvegarde J-1 :** ___________________________

### 12. Réseau et connectivité

- [ ] Vérifier que le serveur est accessible depuis le réseau local (salle)
- [ ] Vérifier que les tablettes arbitres se connectent au Wi-Fi de la salle
- [ ] Tester l'accès à `/arbitre` depuis une tablette sur le réseau local
- [ ] Vérifier que l'URL utilisée en local (ex. `http://192.168.1.X` ou domaine local) fonctionne

### 13. Vérification du tirage (draw)

- [ ] Aller sur **Admin → Tirage** (`/admin/tirage`)
- [ ] Vérifier que le tirage est confirmé (statut verrouillé)
- [ ] Vérifier que les poules sont correctement générées avec les bons joueurs

### 14. Attribution des arbitres aux tables

- [ ] Aller sur **Admin → Competitions → [Compétition] → Referees → Assign**
- [ ] Vérifier que chaque table a un arbitre assigné
- [ ] Si une table n'a pas d'arbitre : assigner depuis l'interface drag-and-drop

### 15. Vérification des matchs du lendemain

- [ ] Aller sur **Admin → Poules** (`/admin/poules`)
- [ ] Vérifier que les matchs du jour sont visibles avec les bons joueurs
- [ ] Vérifier qu'aucun match ne présente d'erreur (joueur manquant, etc.)

### 16. Permissions et sécurité

- [ ] Vérifier que `APP_DEBUG=false` est actif (la page d'accueil ne doit pas afficher de détails d'erreur)
- [ ] Vérifier que l'accès à `/admin` sans être connecté redirige vers `/login`
- [ ] Vérifier que l'accès à `/arbitre` sans être connecté redirige vers `/login`
- [ ] Vérifier que les tokens Sanctum des événements précédents ont été révoqués (Admin → Arbitres)

---

## Scénario match test (J-1 obligatoire)

Ce scénario doit être exécuté intégralement la veille de la compétition pour valider
le circuit complet de saisie d'un match.

**Pré-requis :** Un match de test doit exister dans l'admin, ou utiliser un match réel planifié.

1. **Connexion arbitre** : Aller sur `/arbitre` et se connecter avec un compte arbitre de test (PIN connu)
   - Résultat attendu : la queue de matchs s'affiche

2. **Visualisation de la queue** : Vérifier que le match de test apparaît dans la liste
   - Résultat attendu : le match est visible avec le statut `scheduled`

3. **Prise en charge du match** : Cliquer sur le match → **Claim**
   - Résultat attendu : le match passe en statut `claimed` par cet arbitre

4. **Démarrage du match** : Sur la page du match, cliquer sur **Démarrer** (ou **Start**)
   - Résultat attendu : le match passe en statut `live`

5. **Vérification page live** : Dans un autre onglet, ouvrir `/live`
   - Résultat attendu : le match apparaît comme en cours

6. **Vérification TV** : Dans un autre onglet, ouvrir `/tv`
   - Résultat attendu : le match en cours est visible sur l'affichage TV

7. **Saisie d'un frame** : Sur la page arbitre du match, enregistrer un frame pour le Joueur A
   - Résultat attendu : le score passe à 1-0, le frame est enregistré

8. **Vérification live du score** : Rafraîchir `/live`
   - Résultat attendu : le score 1-0 est visible sur la page publique

9. **Annulation du dernier frame** : Sur la page arbitre, cliquer sur **Undo frame**
   - Résultat attendu : le score repasse à 0-0

10. **Saisie d'un événement** : Enregistrer un événement (ex. `foul` pour le Joueur B)
    - Résultat attendu : l'événement apparaît dans l'historique du match

11. **Saisie des frames jusqu'à la fin** : Entrer les frames pour simuler une victoire complète
    - Résultat attendu : le score atteint le seuil de victoire (`race_to`)

12. **Clôture du match** : Cliquer sur **Terminer le match** (End)
    - Résultat attendu : le match passe en statut `finished`

13. **Signature arbitre** : Signer le match sur la page de fin (`/arbitre/match/{id}/fin`)
    - Résultat attendu : la signature est enregistrée, le match est verrouillé

14. **Vérification classement** : Aller sur `/live` ou `/admin/poules`
    - Résultat attendu : les points et frames sont correctement mis à jour dans les standings

15. **Vérification admin** : Aller sur **Admin → Poules** et ouvrir le match testé
    - Résultat attendu : le résultat frame par frame est visible, le statut est `finished`

16. **Réinitialisation** : Si ce match ne doit pas compter : aller sur **Admin → Match → Override Result**
    - Entrer les vrais scores ou annuler — ajouter une note `"Match de test J-1"`

17. **Confirmation** : Vérifier que le health check passe toujours :

```bash
php artisan c8p:health-check
```

---

## Checklist J (jour de la compétition)

### Matin (avant l'arrivée des joueurs)

- [ ] Exécuter le health check final : `php artisan c8p:health-check`
- [ ] Vérifier que la compétition est bien en statut `in_progress`
- [ ] Confirmer que tous les arbitres ont reçu leur PIN
- [ ] Confirmer que les tablettes arbitres ont accès au réseau
- [ ] Ouvrir `/tv` sur chaque écran de salle (F11 pour plein écran)
- [ ] Vérifier que `/live` est accessible depuis un smartphone externe

### Pendant la compétition

- [ ] Surveiller que les scores s'affichent en temps réel sur `/tv`
- [ ] En cas de problème arbitre : consulter **Procédure Incident** ci-dessous
- [ ] Ne pas redémarrer le serveur sans consulter le responsable technique

---

## Procédure incident

### Score contesté

**Qui peut intervenir :** Arbitre principal, Directeur de tournoi

1. L'arbitre sur la table signale le litige verbalement à l'arbitre principal
2. Le match est temporairement suspendu (ne pas saisir de frame)
3. L'arbitre principal tranche sur la base du règlement
4. Si le dernier frame doit être annulé : l'arbitre utilise **Undo frame** dans `/arbitre/match/{id}/live`
5. Si un match est déjà clôturé et qu'un override est nécessaire :
   - Aller sur **Admin → Competitions → [Compétition] → Matches → [Match]**
   - Cliquer sur **Override Result**
   - Saisir le score correct frame par frame
   - Obligatoire : remplir la note de raison (ex. `"Frame 3 contesté — décision arbitre principal"`)
   - Confirmer — les standings se recalculent automatiquement

### Arbitre se trompe de frame (saisie incorrecte)

**Avant que le match soit clôturé :**
- L'arbitre utilise **Undo frame** sur la page match dans `/arbitre`
- La dernière saisie est annulée, le score est restauré
- Ré-entrer le bon résultat

**Après clôture du match :**
- Seul un admin, directeur de tournoi ou arbitre principal peut intervenir
- Procédure : **Admin → Match → Override Result** (voir ci-dessus)

### Arbitre absent ou indisponible en cours de match

1. Contacter le directeur de tournoi immédiatement
2. Le directeur de tournoi réassigne un autre arbitre via :
   **Admin → Competitions → [Compétition] → Referees → Assign**
3. Le nouvel arbitre se connecte sur `/arbitre` et prend en charge le match

### Tablette arbitre en panne

1. L'arbitre bascule sur la saisie papier (score sur feuille de match officielle)
2. Une tablette de remplacement est récupérée si disponible
3. Si aucune tablette disponible : le directeur de tournoi saisit les frames manuellement depuis l'admin :
   **Admin → Poules → [Match] → Saisir frame**
4. En fin de match, l'arbitre signe physiquement la feuille de match

### Coupure Internet

**Cas 1 — coupure partielle (serveur accessible en local) :**
- L'interface arbitre `/arbitre` continue de fonctionner sur le réseau local
- La page live `/live` n'est plus accessible depuis l'extérieur mais la compétition continue
- Toute la saisie de frames reste opérationnelle

**Cas 2 — coupure totale (serveur inaccessible) :**
- Basculer sur la saisie papier : utiliser les feuilles de match officielles
- Continuer la compétition normalement avec arbitrage papier
- Dès que la connexion est rétablie : saisir tous les résultats dans l'admin (**Admin → Poules → [Match] → Override**)
- Recalculer les ratings après saisie :

```bash
php artisan c8p:recalculate-ratings [slug-competition]
```

### Bug critique (page blanche, erreur 500)

1. Prendre une capture d'écran de l'erreur
2. Contacter le responsable technique MRTECH immédiatement
3. Ne pas continuer à rafraîchir ou saisir des données
4. Si le bug bloque complètement la saisie : basculer sur papier (voir Coupure Internet)
5. Si une intervention serveur est nécessaire : consulter **Procédure Rollback** ci-dessous

---

## Procédure rollback

**Autorité de déclenchement :** Le rollback ne peut être décidé que par le **Responsable Technique MRTECH** ou le **Directeur de Tournoi**, en accord mutuel. Cette décision est irréversible sur les données saisies depuis le dernier backup.

### Quand déclencher un rollback

- Bug bloquant empêchant toute saisie de score
- Corruption de données (scores incohérents, joueurs manquants)
- Migration de base de données échouée lors d'une mise à jour

### Étapes

```bash
# 1. Mettre l'application en maintenance (les utilisateurs voient une page d'attente)
php artisan down

# 2. Identifier la sauvegarde à restaurer (celle faite en J-1 ou la plus récente)
ls -lh backup_avant_competition_*.sql

# 3. Restaurer la base de données
mysql -u NOM_UTILISATEUR -p NOM_BASE < backup_avant_competition_YYYYMMDD_HHMM.sql

# 4. Revenir au commit précédent (remplacer HASH par le hash du commit stable)
git log --oneline -10
git checkout [HASH_COMMIT_STABLE]

# 5. Réinstaller les dépendances PHP
composer install --no-dev --optimize-autoloader

# 6. Appliquer les migrations si nécessaire (uniquement si la version restaurée l'exige)
php artisan migrate --force

# 7. Vider les caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache

# 8. Vérifier que le système fonctionne
php artisan c8p:health-check

# 9. Remettre l'application en ligne
php artisan up
```

### Après le rollback

- [ ] Vérifier que `/login` fonctionne
- [ ] Vérifier que `/admin` est accessible
- [ ] Vérifier que `/arbitre` est accessible
- [ ] Informer les arbitres de reprendre la saisie depuis l'état restauré
- [ ] Documenter l'incident : date, heure, cause, données perdues, responsable
- [ ] Si des frames ont été saisis après le dernier backup : les ré-entrer manuellement via l'admin

### Contacts d'urgence

| Rôle | Responsabilité |
|------|---------------|
| Responsable Technique MRTECH | Décision technique, accès serveur |
| Directeur de Tournoi | Décision sportive, autorisation rollback |
| Arbitre Principal | Gestion terrain, litiges |
