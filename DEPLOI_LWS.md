# Déploiement Oikos sur LWS Performance (oikos.sygauges.com)

## Prérequis
- Sous-domaine : **oikos.sygauges.com** (document root = racine du sous-domaine)
- Accès **SSH** et **FTP**
- Base MySQL fournie par LWS

---

## Étape 1 : Créer / vérifier le sous-domaine dans l’espace LWS

1. Connectez-vous à **https://www.lws.fr** → Espace client.
2. Allez dans **Hébergement** → votre offre **Performance** → **Domaines / Sous-domaines**.
3. Créez le sous-domaine **oikos.sygauges.com** s’il n’existe pas.
4. Notez le **chemin physique** du sous-domaine (souvent du type `~/oikos.sygauges.com` ou `~/www/oikos.sygauges.com`). C’est dans ce dossier que vous enverrez les fichiers.

---

## Étape 2 : Préparer l’application en local

### 2.1 Build production

Dans le dossier du projet (Oikos) :

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 2.2 Fichier .env de production

Créez un fichier `.env.production` (à ne **pas** committer) avec le contenu suivant (à adapter) :

```env
APP_NAME=Oikos
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=false
APP_URL=https://oikos.sygauges.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=91.216.107.219
DB_PORT=3306
DB_DATABASE=sygau2566907_6aslh7
DB_USERNAME=sygau2566907_6aslh7
DB_PASSWORD=MY25Glory04@88

# Session et cache (fichier sur hébergement partagé)
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Générer une clé d’application :

```bash
php artisan key:generate --show
```

Copiez la valeur affichée et mettez-la dans `APP_KEY=base64:...` dans `.env.production`.

---

## Étape 3 : Fichiers à envoyer sur le serveur

À la **racine du sous-domaine** (le chemin noté à l’étape 1), vous devez avoir toute l’arborescence Laravel **plus** le `.htaccess` à la racine.

### 3.1 Contenu du dossier racine sur LWS (après upload)

- `.htaccess` (à la racine — celui qui redirige vers `public/`)
- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/` (contient `index.php`, `build/`, etc.)
- `resources/`
- `routes/`
- `storage/`
- `vendor/` (si vous uploadez ; sinon à installer en SSH)
- `composer.json`, `composer.lock`, `artisan`

**À ne pas envoyer :**  
`.env`, `.git/`, `node_modules/`, `storage/logs/*`, `storage/framework/cache/data/*`, `storage/framework/sessions/*`, `storage/framework/views/*`

### 3.2 Envoi des fichiers

**Option A – FTP**

1. Ouvrez FileZilla (ou autre client FTP) avec les identifiants LWS.
2. Allez dans le répertoire du sous-domaine (ex. `oikos.sygauges.com` ou `www/oikos.sygauges.com`).
3. Uploadez tous les dossiers et fichiers listés ci-dessus.
4. Vérifiez que le **fichier `.htaccess` à la racine** du sous-domaine est bien présent (il peut être masqué : afficher les fichiers cachés).

**Option B – SSH (détail ci-dessous)**

---

## Étape 3 avec SSH (détail)

### 3.B.1 Récupérer les infos SSH LWS

1. Espace client LWS → **Hébergement** → votre offre **Performance**.
2. Section **SSH** ou **Accès SSH** : notez :
   - **Hôte** (ex. `ssh.cluster042.hosting.ovh.net` ou `votre-compte.ovh.net`)
   - **Utilisateur** (souvent votre identifiant client ou un utilisateur du type `sygau2566907`)
   - **Chemin du sous-domaine** (ex. `www/oikos.sygauges.com` ou `oikos.sygauges.com`, relatif à votre répertoire personnel).

### 3.B.2 Méthode 1 : rsync (recommandé si disponible)

Sur **Git Bash** (Windows) ou **WSL** :

1. Ouvrez un terminal dans le dossier du projet Oikos.
2. Éditez le script `deploy-ssh-rsync.sh` et remplacez :
   - `VOTRE_UTILISATEUR_LWS` → votre utilisateur SSH
   - `ssh.votre-hebergeur.lws.fr` → l’hôte SSH LWS
   - `www/oikos.sygauges.com` → le chemin du sous-domaine
3. Exécutez :
   ```bash
   bash deploy-ssh-rsync.sh
   ```
4. Entrez votre mot de passe SSH quand il est demandé.

**Sans script, à la main :**
```bash
rsync -avz --progress \
  --exclude '.env' --exclude '.env.*' --exclude '.git' --exclude 'node_modules' \
  --exclude 'storage/logs/*' --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/views/*' \
  ./ VOTRE_USER@HOSTE_SSH:CHEMIN_SOUS_DOMAINE/
```
(Remplacez `VOTRE_USER`, `HOSTE_SSH`, `CHEMIN_SOUS_DOMAINE`.)

### 3.B.3 Méthode 2 : archive ZIP + SCP (PowerShell, sans rsync)

1. Ouvrez **PowerShell** et allez dans le dossier du projet :
   ```powershell
   cd C:\wamp64\www\Oikos
   ```
2. Éditez `deploy-ssh-zip.ps1` : modifiez `$SSH_USER`, `$SSH_HOST`, `$REMOTE_PATH` avec les valeurs LWS.
3. Autorisez l’exécution des scripts si besoin :
   ```powershell
   Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
   ```
4. Lancez le script :
   ```powershell
   .\deploy-ssh-zip.ps1
   ```
5. Entrez votre mot de passe SSH lorsque demandé. Le script crée une archive (sans `.env`, `.git`, `node_modules`, etc.), l’envoie sur le serveur puis s’y connecte pour décompresser dans le bon dossier.

**Remarque :** Le serveur doit avoir `unzip` (en général le cas sur LWS). Si ce n’est pas le cas, installez-le ou décompressez via le gestionnaire de fichiers LWS.

### 3.B.4 Vérifications après l’envoi

En SSH sur le serveur :

```bash
ssh VOTRE_USER@HOSTE_SSH
cd www/oikos.sygauges.com   # ou le chemin noté
ls -la
```

Vous devez voir : `.htaccess`, `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `vendor/`, `artisan`, `composer.json`, etc. Le fichier **`.htaccess`** doit bien être à la racine (pour rediriger vers `public/`).

---

## Étape 4 : .env et permissions sur le serveur

### 4.1 Créer le .env sur le serveur

- En **FTP** : créez un fichier `.env` à la racine du sous-domaine.
- En **SSH** : `nano .env` et collez le contenu de `.env.production` (avec les vrais `APP_KEY` et paramètres).

Enregistrez le fichier. Vérifiez que les variables (surtout `APP_URL`, `DB_*`) correspondent bien à **oikos.sygauges.com** et à la base MySQL LWS.

### 4.2 Permissions

En SSH (en vous plaçant dans la racine du sous-domaine) :

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

(Si votre hébergeur indique un utilisateur PHP dédié, vous pouvez faire `chown -R user:group storage bootstrap/cache` selon leur doc.)

---

## Étape 5 : Base de données et Laravel

### 5.1 Migrations

En SSH, à la racine du projet sur le serveur :

```bash
php artisan migrate --force
```

Cela crée les tables dans la base `sygau2566907_6aslh7`.

### 5.2 Caches Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Étape 6 : Vérifier le point d’entrée (public)

Le **.htaccess à la racine** du sous-domaine doit contenir :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Ainsi, toutes les requêtes (ex. `https://oikos.sygauges.com/`, `https://oikos.sygauges.com/login`) sont envoyées vers le dossier `public/`, et Laravel utilise `public/index.php` comme point d’entrée.

Le fichier `public/.htaccess` fourni par Laravel reste inchangé.

---

## Étape 7 : Test

1. Ouvrez **https://oikos.sygauges.com**.
2. Vous devez voir la page de connexion (ou la redirection vers login).
3. En cas d’erreur 500 : consultez `storage/logs/laravel.log` sur le serveur (FTP ou SSH).

---

## Résumé des infos à retenir

| Élément | Valeur |
|--------|--------|
| Sous-domaine | oikos.sygauges.com |
| Racine du site | Racine du sous-domaine (avec .htaccess → public/) |
| MySQL host | 91.216.107.219 |
| MySQL base | sygau2566907_6aslh7 |
| MySQL user | sygau2566907_6aslh7 |
| MySQL mot de passe | (celui fourni par LWS) |
| APP_URL | https://oikos.sygauges.com |

---

## Dépannage

- **500 Internal Server Error** : vérifier `storage/logs/laravel.log`, permissions `storage/` et `bootstrap/cache/`, et présence du `.htaccess` racine.
- **Page blanche** : `APP_DEBUG=true` temporairement dans `.env` pour afficher l’erreur (à remettre à `false` après).
- **CSS/JS absents** : vérifier que `public/build/` a bien été uploadé et que `APP_URL` est correct.
- **Connexion MySQL impossible** : vérifier IP autorisées (parfois LWS impose une IP ou « localhost » pour MySQL).

Si vous voulez, on peut détailler une seule étape (par exemple uniquement FTP ou uniquement SSH).
