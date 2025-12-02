## Prerequis 
- nodejs22
- postgres 14
- python 3.13.7
- vuejs

## lancement du projet 
- aller dans backend avec la commande:
```
cd backend\hrms_project\
```
- creer un environment python 
```
python3 -m venv .venv
```
- activer l'environnement 
```
.venv\Scripts\Activate.ps1
```
- installer les dependance
```
pip install -r requirements.txt
```

### preparer le base de donnee
- se connecter en tant que superutilisateur 
```
psql -U postgres
```
creation nouvelle utilisateur 
```
CREATE USER hrms_user WITH PASSWORD 'hrms_password';
CREATE DATABASE hrms OWNER hrms_user;
```

3. Create `.env` (copy `.env.example`) and adjust variables if needed. Example `.env` for local Postgres:

```
DJANGO_SECRET_KEY=change-me-please
DJANGO_DEBUG=1
DJANGO_ALLOWED_HOSTS=127.0.0.1,localhost
POSTGRES_DB=hrms
POSTGRES_USER=hrms_user
POSTGRES_PASSWORD=hrms_password
POSTGRES_HOST=127.0.0.1
POSTGRES_PORT=5432
```

 migration des base de donnee et lancement du projet 




```
Get-Content .env | ForEach-Object {if ($_ -match '^(.*)=(.*)$') {Set-Item -Path "env:$($matches[1])" -Value $matches[2]}}
python manage.py makemigrations
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

## lancement du front end
- aller dans frontend
```
cd frontend
```
- installer les nodes module
```
npm install
```
- lancer le projet

```
npm run dev
```