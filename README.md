# Club 8 Pool

Application de gestion de compétitions de billard pour la Fédération Gabonaise de Billard. Monolithe **Laravel + Inertia + Vue 3** couvrant la plateforme publique, l'espace organisateur et le scoreboard de salle.

L'application mobile arbitre (Flutter) est dans un dépôt séparé : [marseven/club8pool-mobile](https://github.com/marseven/club8pool-mobile).

## Stack

- **Backend** : PHP 8.2, Laravel 11, Eloquent, Sanctum (API pour mobile)
- **Frontend** : Vue 3 SFC, Inertia 2, Vite 6
- **DB** : SQLite par défaut (peut basculer vers MySQL/Postgres via `.env`)
- **Auth** : multi-guard, middleware `admin` / `referee`

## Direction graphique

Direction A · ARENA — noir profond, vert craie de billard, typo display condensée (Antonio), iconographie géométrique. Voir `resources/css/app.css`.

## Surfaces

### Pages publiques

| Route | Page |
| --- | --- |
| `/` | Landing — coupe en cours, programme du jour, dotation |
| `/competitions` | Bracket + classement + sidebar live |
| `/joueurs/{id}` | Fiche joueur — palmarès, forme, historique |
| `/inscription` | Inscription joueur (wizard 4 étapes) |
| `/tv` | Scoreboard XL (1920×1080) pour projection en salle |

### Espace organisateur (admin)

| Route | Page |
| --- | --- |
| `/admin` | Tableau de bord — tables temps réel + contrôleur match |
| `/admin/competitions` | Liste des éditions |
| `/admin/competitions/nouvelle` | Wizard 5 étapes |
| `/admin/tirage` | Tirage au sort animé (joueur courant 1.8s) |
| `/admin/joueurs` | CRUD joueurs |
| `/admin/arbitres` | CRUD arbitres |

### Espace arbitre (web fallback de l'app mobile)

| Route | Page |
| --- | --- |
| `/arbitre` | File des matchs assignés |
| `/arbitre/match/{id}/pre` | Pré-match (break) |
| `/arbitre/match/{id}/live` | Shot clock + scoring |
| `/arbitre/match/{id}/fin` | Signatures + validation |

### API mobile (Sanctum)

| Méthode | Route |
| --- | --- |
| `POST` | `/api/referee/login` (`fgb_card` + `pin`) |
| `GET` | `/api/referee/queue` |
| `POST` | `/api/referee/matches/{id}/frame` |
| `POST` | `/api/referee/matches/{id}/sign` |
| `POST` | `/api/referee/matches/{id}/end` |

## Mise en route

```bash
composer install
npm install

php artisan key:generate
php artisan migrate:fresh --seed

npm run build       # production
npm run dev         # hot reload

php artisan serve   # http://127.0.0.1:8000
```

## Comptes de démo

| Rôle | Identifiants |
| --- | --- |
| Admin | `admin@club8pool.ga` / `password` |
| Arbitre (web) | `olivier@club8pool.ga` / `password` |
| Arbitre (mobile API) | `FGB-ARB-2026-0024` / PIN `12345` |

## Données seed

- 16 joueurs gabonais répartis sur 6 clubs (Libreville, Akanda, Owendo, Port-Gentil, Franceville, Lambaréné)
- Coupe du Gabon 8-Ball Édition 04 — phase de quarts en cours
- 5 tables (3 actives, 1 libre, 1 maintenance)
- Bracket R16 complet, QF en cours

## Structure

```
app/
  Http/Controllers/
    Public/     # landing, competition, player, register, TV
    Admin/      # dashboard, competitions, draw, matches, players, referees
    Referee/    # web fallback de l'app mobile
    Api/        # endpoints Sanctum pour Flutter
  Models/       # Player, Club, Competition, GameMatch, Frame, Signature, …
resources/
  css/app.css           # design tokens C8P
  js/
    Components/         # Ball8, GabonFlag, Logo, Bracket, MatchCell, …
    Pages/
      Public/
      Admin/
      Referee/
      Auth/
database/
  migrations/   # 8 tables métier
  seeders/      # données Coupe du Gabon 2026
```

## Crédits

Design : Claude Design exploration. Implémentation : [@marseven](https://github.com/marseven).
