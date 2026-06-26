# Déploiement Hostinger — Club 8 Pool

Ce document décrit la procédure complète pour déployer Club 8 Pool en production
sur Hostinger shared hosting. Il couvre le déploiement initial, les mises à jour,
le rollback et les particularités de l'environnement Hostinger.

---

## Architecture production

| Composant | Valeur |
|-----------|--------|
| Hébergeur | Hostinger shared hosting |
| PHP | 8.2 minimum (8.3 recommandé) |
| Base de données | **MySQL** (voir note critique ci-dessous) |
| Serveur web | Apache avec `.htaccess` |
| Node.js | Non disponible — assets compilés localement avant déploiement |
| Domaine | À configurer dans le panel Hostinger |

---

## IMPORTANT : SQLite vs MySQL

**SQLite est réservé aux tests locaux et CI. La production doit utiliser MySQL.**

SQLite ne supporte pas les écritures concurrentes. Sur un tournoi avec 3 arbitres
saisissant des frames simultanément, SQLite risque de produire des erreurs de verrouillage
(`database is locked`) ou, dans le pire des cas, une corruption silencieuse des données.

MySQL utilise un verrouillage au niveau de la ligne (row-level locking), ce qui garantit
que plusieurs arbitres peuvent enregistrer des frames en même temps sans conflit.

**Règle absolue :** `DB_CONNECTION=mysql` en production, toujours.

---

## Pré-déploiement (à exécuter en local)

Ces étapes doivent être complétées **avant** de transférer quoi que ce soit sur Hostinger.

### 1. Vérifier l'état du dépôt

```bash
git status
# Aucun fichier non commité ne doit apparaître
```

### 2. Lancer les tests

```bash
php artisan test
# Tous les tests doivent passer (vert)
```

### 3. Compiler les assets frontend

```bash
npm ci
npm run build
# Les fichiers compilés sont dans public/build/
```

### 4. Vérifier que public/build/ est dans le dépôt git

Hostinger shared hosting ne dispose pas de Node.js. Les assets doivent être compilés
localement et commités dans le dépôt git.

```bash
git status public/build/
# Les fichiers doivent apparaître comme "nothing to commit" ou être stagés
git add public/build/
git commit -m "chore(assets): rebuild frontend for production"
```

> **Important :** Le fichier `.gitignore` ne doit pas exclure `public/build/`.
> Vérifier que la ligne `public/build` n'est pas présente dans `.gitignore`.

---

## Configuration .env production

Créer un fichier `.env` sur le serveur Hostinger avec les variables suivantes.
Ne jamais commiter ce fichier dans git.

```dotenv
APP_NAME="Club 8 Pool"
APP_ENV=production
APP_KEY=                          # Généré par php artisan key:generate
APP_DEBUG=false
APP_URL=https://votredomaine.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost                 # Fourni par Hostinger (souvent localhost ou 127.0.0.1)
DB_PORT=3306
DB_DATABASE=nom_de_la_base        # Créé dans le panel Hostinger → Bases de données
DB_USERNAME=nom_utilisateur_mysql
DB_PASSWORD=mot_de_passe_mysql

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file               # Ne pas utiliser database sur shared hosting sans queue
SESSION_LIFETIME=120

MAIL_MAILER=smtp                  # Configurer si les emails sont utilisés
```

> **Note SESSION_DRIVER :** Sur Hostinger shared hosting, utiliser `file`.
> Le driver `database` nécessite une table sessions (créée par migration) et
> fonctionne correctement, mais `file` est plus simple à maintenir.

---

## Déploiement initial

Ces étapes s'exécutent une seule fois lors de la première mise en production.

### Option A — Via SSH (plans Business ou supérieur)

```bash
# 1. Se connecter en SSH
ssh u123456789@votre-serveur.hostinger.com

# 2. Naviguer dans le répertoire web
cd ~/public_html
# (ou ~/domains/votredomaine.com/public_html selon la config Hostinger)

# 3. Cloner le dépôt
git clone https://github.com/votre-repo/club8pool.git .

# 4. Installer les dépendances PHP (sans les packages de dev)
composer install --no-dev --optimize-autoloader

# 5. Créer le fichier .env
cp .env.example .env
# Éditer .env avec les vraies valeurs de production

# 6. Générer la clé d'application
php artisan key:generate

# 7. Créer les tables en base de données
php artisan migrate --force

# 8. Créer le lien symbolique storage
php artisan storage:link

# 9. Mettre en cache la configuration, les routes et les vues
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 10. Corriger les permissions
chmod -R 755 storage bootstrap/cache
```

### Option B — Via File Manager / FTP (plans Starter)

1. Sur le serveur local, préparer une archive :

```bash
# Exclure les dossiers inutiles en production
zip -r club8pool.zip . \
  --exclude="*.git*" \
  --exclude="node_modules/*" \
  --exclude="tests/*" \
  --exclude=".env"
```

2. Uploader `club8pool.zip` via le File Manager Hostinger dans `public_html/`
3. Extraire l'archive dans `public_html/`
4. Via le File Manager, créer le fichier `.env` avec les valeurs de production
5. Via le terminal Hostinger (si disponible) ou SSH :

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Mise à jour (release normale)

Procédure standard pour chaque nouvelle version déployée en production.

```bash
# 1. Passer en mode maintenance (affiche une page "Maintenance en cours" aux utilisateurs)
php artisan down

# 2. Sauvegarder la base de données AVANT toute modification
mysqldump -u DB_USERNAME -p DB_DATABASE > ~/backup_avant_deploy_$(date +%Y%m%d_%H%M).sql
# Vérifier que le fichier n'est pas vide :
ls -lh ~/backup_avant_deploy_*.sql

# 3. Récupérer le nouveau code
git pull origin main

# 4. Mettre à jour les dépendances PHP
composer install --no-dev --optimize-autoloader

# 5. Appliquer les migrations de base de données
php artisan migrate --force

# 6. Reconstruire les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Vérifier que le système est opérationnel
php artisan c8p:health-check

# 8. Remettre l'application en ligne
php artisan up
```

> **Règle :** Ne jamais exécuter `php artisan up` si `c8p:health-check` signale
> une erreur. Résoudre le problème ou déclencher un rollback.

---

## Vérifications post-déploiement

Après chaque déploiement, vérifier les 8 points suivants dans l'ordre :

- [ ] **Page publique** : ouvrir `https://votredomaine.com` — la page d'accueil doit charger
- [ ] **Login admin** : aller sur `/login`, se connecter, vérifier que `/admin` charge
- [ ] **Login arbitre** : aller sur `/arbitre`, se connecter avec un compte arbitre, vérifier la queue
- [ ] **API referee** : tester `POST /api/referee/login` avec un PIN valide — vérifier le token
- [ ] **Mode TV** : ouvrir `/tv` sur un écran 1920×1080, vérifier l'affichage
- [ ] **Page live** : ouvrir `/live`, vérifier que la compétition en cours est visible
- [ ] **Health check** : `php artisan c8p:health-check` — tous les checks doivent passer
- [ ] **Signature arbitre** : sur un match test, vérifier que la signature fonctionne (`/arbitre/match/{id}/fin`)

---

## Rollback

En cas de problème bloquant après un déploiement.

```bash
# 1. Mettre en maintenance si ce n'est pas déjà fait
php artisan down

# 2. Identifier la sauvegarde disponible
ls -lh ~/backup_avant_deploy_*.sql

# 3. Restaurer la base de données
mysql -u DB_USERNAME -p DB_DATABASE < ~/backup_avant_deploy_YYYYMMDD_HHMM.sql

# 4. Revenir au commit précédent
git log --oneline -10
# Identifier le hash du dernier commit stable
git checkout [HASH_COMMIT_STABLE]

# 5. Restaurer les dépendances pour cette version
composer install --no-dev --optimize-autoloader

# 6. Vider les caches (éviter les incohérences entre code et cache)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 7. Reconstruire les caches pour la version restaurée
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Vérifier
php artisan c8p:health-check

# 9. Remettre en ligne
php artisan up
```

---

## Notes Hostinger spécifiques

### .htaccess et réécriture d'URL

Laravel nécessite un `.htaccess` dans le répertoire `public/` pour que les URLs
propres fonctionnent (sans `index.php`). Ce fichier est déjà présent dans le squelette
Laravel. Ne pas le supprimer.

Si les URLs ne fonctionnent pas (erreur 404 sur toutes les pages sauf la racine),
vérifier que `mod_rewrite` est activé dans le panel Hostinger → Configuration PHP.

### Permissions de stockage

```bash
# Permissions correctes pour storage et bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

Sur Hostinger shared hosting, `chown` n'est généralement pas disponible.
Le serveur Apache exécute les scripts sous votre utilisateur — `755` suffit.

### Lien symbolique storage

```bash
php artisan storage:link
```

Si cette commande échoue (erreur de permissions ou lien déjà existant) :

```bash
# Supprimer le lien existant si cassé
rm public/storage

# Recréer manuellement
ln -s ../storage/app/public public/storage
```

Si les liens symboliques ne sont pas supportés (rare sur Hostinger) : utiliser une
règle `.htaccess` dans `public/` pour rediriger les requêtes vers `storage/app/public/`.

### SSH vs File Manager

| Plan Hostinger | SSH | Terminal web |
|---------------|-----|-------------|
| Starter | Non | Non |
| Premium | Limité | Oui (via panel) |
| Business | Oui | Oui |
| Cloud | Oui | Oui |

Pour les plans sans SSH, toutes les commandes Artisan doivent être exécutées
via le **Terminal web** dans le panel hPanel de Hostinger.

### Tâches planifiées (cron)

Les tâches planifiées Laravel (`php artisan schedule:run`) ne sont **pas disponibles**
sur tous les plans Hostinger shared hosting.

- **Plans Starter/Premium** : pas de cron jobs → `php artisan schedule:run` ne peut pas
  être planifié automatiquement. Les commandes comme `c8p:recalculate-ratings` doivent
  être lancées manuellement.
- **Plans Business/Cloud** : cron jobs disponibles dans hPanel → Advanced → Cron Jobs.
  Ajouter : `* * * * * cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1`

### Variables d'environnement sensibles

Sur Hostinger, le fichier `.env` est dans la racine du projet (pas dans `public/`),
ce qui est correct. Apache ne sert pas les fichiers hors de `public/`.
Vérifier que l'URL `https://votredomaine.com/.env` retourne bien une erreur 403 ou 404.

### Mise à jour de Composer sans SSH

Si Composer n'est pas disponible en ligne de commande :

1. Uploader le dossier `vendor/` complet via FTP (lourd mais fonctionnel)
2. Ou utiliser le terminal web Hostinger si disponible sur votre plan

Il est recommandé d'utiliser un plan avec accès SSH pour un déploiement fiable.
