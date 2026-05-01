# Module RH

Ce dépôt rassemble un backend Laravel et un frontend Vue destinés à la gestion d'un module RH. Les deux applications vivent dans le même dépôt afin de faciliter le développement local.

Pour comprendre le périmètre fonctionnel et ce que l'application permet de faire, consultez les [spécifications fonctionnelles et techniques](TECHNICAL-SPECIFICATIONS.md).

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

## Démarrage avec Docker (recommandé)
Prérequis : Docker Desktop (ou Docker Engine) + le plugin `docker compose`.

### Nom du “stack” (project name)
Le nom est défini par `name: module-rh1` dans `docker-compose.yml`. Pour le modifier, changez cette valeur, puis relancez.
Astuce : si vous aviez déjà lancé l'ancien nom, il faut arrêter avec l'ancien project name, ex : `docker compose -p ancien_nom down`.

### Lancer backend + frontend + PostgreSQL
À la racine du dépôt :
```bash
docker compose up --build
```

Pour lancer en arrière-plan lors d'une relance classique du projet :
```bash
docker compose up -d
```

Ensuite :
- API : `http://localhost:8001/api`
- Frontend : `http://localhost:5173`

Note :
- Le frontend Vite est exposé sur `5173` et non `5473`.
- Si `http://localhost:5173` retourne `ERR_CONNECTION_REFUSED`, vérifiez d'abord les ports publiés :
  ```bash
  docker compose ps
  ```
- La ligne du service `frontend` doit contenir `0.0.0.0:5173->5173/tcp`. Si ce port n'apparaît pas, recréez le conteneur frontend :
  ```bash
  docker compose up -d --force-recreate frontend
  ```
- Pour vérifier que Vite tourne bien dans le conteneur :
  ```bash
  docker compose logs --tail=50 frontend
  ```

### Initialiser la base (premier lancement)
Dans un autre terminal :
```bash
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed
docker compose exec backend php artisan storage:link
```

Remarque :
- `php artisan db:seed` est à lancer sur une base vide pour créer les données de démonstration et les comptes de connexion par défaut.
- Si vous conservez les volumes Docker entre deux relances, il n'est généralement pas nécessaire de reseed.

### Snapshots / tables de synthèse
Le dashboard lit des snapshots dans `dashboard_stats`. Si une période n'est pas encore générée, l'API répond rapidement avec un statut `generating` et le service `queue` calcule le snapshot en arrière-plan.

Commandes utiles en Docker :
```bash
docker compose exec backend php artisan dashboard:refresh --filtre=all --date=2026-05-01
docker compose exec backend php artisan dashboard:refresh --filtre=periode --date_debut=2026-03-01 --date_fin=2026-03-31
docker compose exec backend php artisan read-models:refresh --annee=2026
docker compose exec backend php artisan queue:work --queue=dashboard,default --tries=2
docker compose exec backend php artisan schedule:work
```

Avec `docker compose up`, les services `queue` et `scheduler` sont lancés séparément. Le scheduler exécute `read-models:refresh` toutes les 15 minutes pour maintenir les périodes courantes, et le worker traite les générations async déclenchées par l'API.

### Comptes de connexion de démonstration
Après le seeding, l'écran de connexion accepte les comptes suivants.

Le champ `Identifiant` correspond à l'email du compte :

| Rôle | Identifiant | Mot de passe |
| --- | --- | --- |
| Admin | `admin@rh.test` | `password` |
| RH | `rh@rh.test` | `password` |
| Manager | `manager@rh.test` | `password` |

Note :
- Des comptes employés supplémentaires sont aussi générés par le seeder, avec des emails aléatoires.
- Leur mot de passe par défaut est `password` sauf si la variable d'environnement `DEFAULT_USER_PASSWORD` a été modifiée.

### Arrêter / repartir de zéro
```bash
docker compose down
docker compose down -v   # supprime aussi les volumes (Postgres, vendor, storage, node_modules)
```

## Démarrage manuel
### 1. Initialiser l'API Laravel (`backend/`)
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```
Dans `.env`, vérifiez au minimum :
- `APP_URL` (URL du backend) et `FRONTEND_URL` (ex: `http://localhost:5173`) pour que CORS autorise votre interface.

Finalisez l'initialisation :
```bash
php artisan migrate          # crée les tables Laravel (sessions, tokens, migrations)
php artisan storage:link     # expose public/storage
npm install                  # dépendances Vite/Tailwind utilisées par Laravel
```

### Lancer l'API en développement
- Serveur HTTP uniquement :
  ```bash
  php artisan serve --host=127.0.0.1 --port=8001
  npm run dev    # dans un deuxième terminal pour Vite
  ```
- Tout-en-un (serveur, queue, logs, Vite) : `composer run dev` utilise `npx concurrently` pour démarrer tous les processus.

### 2. Initialiser le frontend Vue (`frontend/`)
```bash
cd frontend
npm install
```
L'URL de l'API consommée par Axios se trouve dans `src/services/api.js` (`baseURL: 'http://localhost:8001/api'`). Modifiez cette valeur ou externalisez-la via une variable d'environnement Vite si votre API tourne sur un autre hôte/port.

Lancer l'interface :
```bash
npm run dev -- --host     # http://localhost:5173 par défaut
```
Build de production :
```bash
npm run build
```

## Accès depuis un autre PC du même réseau (développement)
1. **Choisir l'IP locale du PC hôte**  
   - Linux/macOS : `ip a` ; Windows : `ipconfig`. Exemple : `192.168.1.20`.
2. **Backend Laravel**  
   - Dans `backend/.env`, mettez `APP_URL=http://192.168.1.20:8001` et `FRONTEND_URL=http://192.168.1.20:5173`.  
   - Si plusieurs frontends doivent accéder, vous pouvez définir `FRONTEND_URLS` (liste séparée par des virgules) pour CORS, par ex. `FRONTEND_URLS=http://192.168.1.20:5173,http://localhost:5173`.  
   - Démarrez en écoutant sur toutes les interfaces :  
     ```bash
     cd backend
     php artisan serve --host=0.0.0.0 --port=8001
     npm run dev             # Vite backend sur 0.0.0.0:5174 si vous utilisez les assets Blade
     ```
3. **Frontend Vue**  
   - Dans `frontend/.env.local`, mettez `VITE_API_URL=http://192.168.1.20:8001/api`.  
   - Démarrez en écoutant sur toutes les interfaces :  
     ```bash
     cd frontend
     npm run dev             # Vite frontend sur 0.0.0.0:5173
     ```
4. **Depuis le PC client**  
   - API : `http://192.168.1.20:8001/api/...`  
   - SPA : `http://192.168.1.20:5173`
5. **Pare-feu**  
   - Ouvrez les ports TCP 8001 (API) et 5173 (Vite) sur le PC hôte pour autoriser les machines du réseau local.

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
