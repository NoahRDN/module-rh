# Module RH

Ce dépôt rassemble un backend Laravel et un frontend Vue destinés à la gestion d'un module RH. Les deux applications vivent dans le même dépôt afin de faciliter le développement local.

## Architecture du dépôt
- `backend/` – API Laravel 12 (PHP 8.2, Sanctum, Vite pour les assets).
- `frontend/` – SPA Vue 3 + Vite consommant l'API exposée par le backend.
- `utilitaires/` – information sur le fonctionnement metier et fiche commande utile

## Prérequis
- PHP 8.2+ et [Composer 2](https://getcomposer.org/).
- Node.js 20.19+ et npm 10+ (pour `backend/` et `frontend/`).
- PostgreSQL 14+ avec le client `psql` (les scripts ciblent une base `rh` sur `localhost:5432`).
- Git ainsi que vos outils CLI habituels.

## Installation rapide
```bash
git clone <votre-url> module-rh
cd module-rh
```

### 1. Initialiser l'API Laravel (`backend/`)
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```
Dans `.env`, vérifiez au minimum :
- `APP_URL` (URL du backend) et `FRONTEND_URL` (ex: `http://localhost:5173`) pour que CORS autorise votre interface.
- Les identifiants `DB_*` correspondant à votre instance PostgreSQL.

Configurez ensuite PostgreSQL (les commandes ci-dessous supposent que vous vous trouvez dans `backend/database/`) :

```bash
# Linux / macOS
psql -h localhost -U postgres -p 5432 -f create_db.sql           # crée la base + le schéma
psql -h localhost -U postgres -p 5432 -d rh -f donnee.sql         # insère les données de référence
psql -h localhost -U postgres -p 5432 -d rh -f view.sql           # crée les vues analytiques
psql -h localhost -U postgres -p 5432 -d rh -f idx.sql            # ajoute les index
psql -h localhost -U postgres -p 5432 -d rh -f mise_a_jour_sequence.sql
```

> Sous PowerShell/Windows, remplacez le chemin par `.\create_db.sql`, `.\donnee.sql`, etc. après vous être placé dans `backend\database`. Le script `create_db.sql` active automatiquement l'extension `pgcrypto` requise pour hacher les mots de passe d'exemple.

Adaptez `DB_USERNAME` et `DB_PASSWORD` dans `.env` si votre instance Postgres n'utilise pas `postgres/postgres`.  
Si vous aviez déjà importé les utilisateurs avant cette mise à jour et que le login renvoie « This password does not use the Bcrypt algorithm », réexécutez `donnee.sql` pour réinsérer les comptes avec des hachages `$2y$` compatibles Laravel.

Finalisez l'initialisation :
```bash
php artisan migrate          # crée les tables Laravel (sessions, tokens, migrations)
php artisan storage:link     # expose public/storage
npm install                  # dépendances Vite/Tailwind utilisées par Laravel
```

### Lancer l'API en développement
- Serveur HTTP uniquement :
  ```bash
  php artisan serve --host=127.0.0.1 --port=8000
  npm run dev    # dans un deuxième terminal pour Vite
  ```
- Tout-en-un (serveur, queue, logs, Vite) : `composer run dev` utilise `npx concurrently` pour démarrer tous les processus.

### 2. Initialiser le frontend Vue (`frontend/`)
```bash
cd frontend
npm install
```
L'URL de l'API consommée par Axios se trouve dans `src/services/api.js` (`baseURL: 'http://localhost:8000/api'`). Modifiez cette valeur ou externalisez-la via une variable d'environnement Vite si votre API tourne sur un autre hôte/port.

Lancer l'interface :
```bash
npm run dev -- --host     # http://localhost:5173 par défaut
```
Build de production :
```bash
npm run build
```

## Scripts utiles
| Commande | Description |
| --- | --- |
| `composer run setup` (backend) | Installe les dépendances PHP/JS, génère la clé et construit les assets Vite. |
| `composer run dev` (backend) | Démarre serveur HTTP, queue, stream de logs et Vite en parallèle. |
| `php artisan test` | Exécute la suite de tests backend. |
| `npm run dev` / `npm run build` (frontend) | Dev server / build optimisé pour la SPA. |

## Tests & qualité
- Backend : `cd backend && php artisan test`. Ajoutez vos jeux de données via les seeders si nécessaire.
- Frontend : aucun test out-of-the-box, mais Vite est prêt pour intégrer Vitest/Cypress (voir `package.json`).

## Déploiement / production
1. Construire les assets Laravel : `cd backend && npm run build`.
2. Construire la SPA : `cd frontend && npm run build`.
3. Configurer l'environnement (`APP_URL`, `APP_ENV=production`, caches via `php artisan config:cache route:cache`, etc.).
4. Assurez-vous d'exécuter `php artisan migrate --force` lors des mises à jour.

## Ressources supplémentaires
- `backend/database/*.sql` : scripts PG pour créer la base `rh`, insérer les données métiers, vues, index et resynchroniser les séquences.
