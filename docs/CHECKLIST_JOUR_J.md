# Checklist Jour J — Club 8 Pool

**Format :** Summer Edition | 8 poules × 6 joueurs | R32 → Finale  
**Discipline :** 8-ball | Shot clock 30s | 2 tables

---

## J-1 — Veille du tournoi

### Technique
- [ ] Migrations à jour : `php artisan migrate`
- [ ] Build Vite à jour : `npm run build`
- [ ] Caches vidés : `php artisan config:clear && php artisan route:clear`
- [ ] Seed compétition lancé : `php artisan db:seed --class=SummerEditionSeeder`
- [ ] Logs sans erreur : `tail -n 50 storage/logs/laravel.log`

### Tests de connexion
- [ ] Login admin fonctionnel (email + mot de passe)
- [ ] Login arbitre fonctionnel (prénom + PIN)
- [ ] Login joueur fonctionnel (login_name + `1234567`)
- [ ] Changement de mot de passe joueur fonctionnel

### Affichage
- [ ] Écran TV `/tv` testé et lisible
- [ ] Pages publiques (`/competitions/{slug}`, `/classement`) accessibles

### Données
- [ ] 48 joueurs inscrits
- [ ] 8 poules (A-H) avec 6 joueurs chacune
- [ ] 120 matchs de poule générés
- [ ] Arbitres ont leurs PINs confirmés

### Sécurité
- [ ] Backup base de données effectué :
  ```bash
  mysqldump -u [user] -p [database] > backup_veille_$(date +%Y%m%d).sql
  ```

---

## J — Matin du tournoi

### Infrastructure
- [ ] Serveur web MAMP démarré et accessible
- [ ] Accès réseau vérifié (Wi-Fi ou Ethernet opérationnel)
- [ ] Mode local confirmé si pas d'internet (accès via IP locale)
- [ ] 2 tables de jeu opérationnelles dans le système

### Équipe
- [ ] Arbitres présents et connectés avec leur prénom + PIN
- [ ] Admin connecté sur le dashboard
- [ ] Joueurs informés de leur `login_name` et mot de passe initial

---

## Phase de poules

### Démarrage
- [ ] Premier match assigné à un arbitre
- [ ] Arbitre voit le match dans `/arbitre/queue`
- [ ] Arbitre démarre le match → shot clock actif
- [ ] Premier score enregistré sans erreur

### Suivi
- [ ] Shot clock fonctionne sur les 2 tables simultanément
- [ ] Classement poule visible sur `/competitions/{slug}` après chaque match
- [ ] Écran TV `/tv` affiche les matchs en cours
- [ ] Aucune erreur 500 sur les pages publiques

### Fin des poules
- [ ] Toutes les poules terminées (ou décision admin de forcer)
- [ ] Vérification des 8 classements de poule
- [ ] Ex-aequo identifiés et traités (départage si nécessaire)
- [ ] Backup base avant génération KO :
  ```bash
  mysqldump -u [user] -p [database] > backup_avant_ko_$(date +%Y%m%d_%H%M).sql
  ```

---

## Passage en phase finale (KO)

### Génération du bracket
- [ ] Admin → `/admin/competitions/{id}/phase-finale`
- [ ] 32 qualifiés affichés (4 par poule)
- [ ] Cliquer "Générer le bracket"
- [ ] 16 matchs R32 créés (race-to 7)
- [ ] Bracket visible sur `/competitions/{slug}`

### Déroulement KO

| Round | Matchs | Race-to |
|-------|--------|---------|
| R32   | 16     | 7       |
| R16   | 8      | 7       |
| QF    | 4      | 9       |
| SF    | 2      | 9       |
| 3e Place | 1   | 5       |
| Finale | 1     | 11      |

- [ ] Matchs R32 assignés et démarrés
- [ ] Race-to corrects par round (vérifier sur interface arbitre)
- [ ] Bracket se met à jour après chaque victoire
- [ ] Bracket public visible et à jour

---

## Fin de compétition

### Clôture
- [ ] Finale clôturée et score enregistré
- [ ] Champion affiché sur `/competitions/{slug}`
- [ ] Match pour la 3e place clôturé
- [ ] Tous les matchs en statut `done`

### Archivage
- [ ] Compétition passée en statut `finished` via l'admin
- [ ] Classement ELO mis à jour sur `/classement`
- [ ] Backup final :
  ```bash
  mysqldump -u [user] -p [database] > backup_final_$(date +%Y%m%d_%H%M).sql
  ```

---

## GO / NO-GO — Rappel rapide

| Test | GO | NO-GO |
|------|----|-------|
| Login arbitre | OK | Cassé → STOP |
| Scoring match | OK | Impossible → STOP |
| Shot clock | Démarre | Bloqué → STOP |
| Génération bracket KO | OK | Échoue → STOP |
| Pages publiques | Accessibles | Erreur 500 → STOP |

---

*Club 8 Pool — Summer Edition · checklist terrain*
