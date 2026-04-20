# SA_SYMFONY — Symfony 7.2 Docker Starter

Stack prête à l'emploi : **PHP 8.3-FPM · Nginx · MariaDB 10.11 · Mailpit · Webpack Encore · Tailwind CSS v3 · PWA · Admin custom**

---

## Stack technique

| Composant | Version |
|---|---|
| PHP | 8.3-FPM |
| Symfony | ^7.2 |
| MariaDB | 10.11 |
| Node.js | 22 |
| Tailwind CSS | v3 |
| Webpack Encore | ^4 |

**Packages notables :** jQuery · Moment.js (fr) · Lucide Icons · DataTables · DOMPDF · minishlink/web-push · omines/datatables-bundle

---

## Premier démarrage

```bash
# Copier et adapter les variables d'environnement
cp .env .env.local

# Lancer l'installation complète (Docker build + deps + DB + assets)
make install
```

> **Prérequis :** Docker + Docker Compose v2

---

## Commandes Make

### Setup

| Commande | Description |
|---|---|
| `make install` | Installation complète (à lancer une seule fois) |
| `make start` | Démarrer les conteneurs |
| `make stop` | Arrêter les conteneurs |
| `make restart` | Redémarrer les conteneurs |
| `make build` | Rebuild les images Docker |

### Développement

| Commande | Description |
|---|---|
| `make bash` | Ouvrir un shell dans le conteneur PHP |
| `make cc` | Vider le cache Symfony |
| `make logs` | Suivre les logs des conteneurs |
| `make routes` | Lister toutes les routes |
| `make entities` | Lister les mappings Doctrine |

### Assets

| Commande | Description |
|---|---|
| `make assets-dev` | Build assets (dev, une fois) |
| `make assets-watch` | Watch & rebuild automatiquement |
| `make assets` | Build assets (production) |

### Base de données

| Commande | Description |
|---|---|
| `make migration` | Générer une migration depuis les entités |
| `make migrate` | Appliquer les migrations en attente |
| `make db-shell` | Shell MySQL interactif |

### Admin & PWA

| Commande | Description |
|---|---|
| `make create-admin` | Créer un compte administrateur (interactif) |
| `make vapid-keys` | Générer les clés VAPID pour les push notifications |

### Tests

| Commande | Description |
|---|---|
| `make test` | Lancer PHPUnit |

---

## URLs

| Service | URL |
|---|---|
| Application | http://localhost:8080 |
| Admin | http://localhost:8080/admin |
| Mailpit (emails dev) | http://localhost:8025 |
| MariaDB | localhost:3306 |

> Les ports sont configurables dans `.env` (`NGINX_PORT`, `MAILPIT_UI_PORT`, `DB_PORT`).

---

## Variables d'environnement

Copier `.env` en `.env.local` et adapter les valeurs. Ne jamais committer `.env.local`.

| Variable | Description | Défaut |
|---|---|---|
| `APP_SECRET` | Clé secrète Symfony | `changeme_...` |
| `DB_*` | Paramètres MariaDB | `symfony/symfony` |
| `DATABASE_URL` | DSN Doctrine (auto-construit) | — |
| `MAILER_DSN` | DSN mailer (Mailpit en dev) | `smtp://mailpit:1025` |
| `MAILER_SENDER_EMAIL` | Adresse expéditeur | `noreply@monprojet.fr` |
| `MAILER_SENDER_NAME` | Nom expéditeur | `Mon Projet` |
| `VAPID_PUBLIC_KEY` | Clé publique VAPID | générer via `make vapid-keys` |
| `VAPID_PRIVATE_KEY` | Clé privée VAPID | générer via `make vapid-keys` |
| `VAPID_SUBJECT` | Subject VAPID (mailto:) | `mailto:contact@monprojet.fr` |
| `NGINX_PORT` | Port HTTP exposé | `8080` |
| `MAILPIT_UI_PORT` | Port UI Mailpit | `8025` |

### Mailer Scaleway TEM (production)

Dans `.env.local` :
```
MAILER_DSN=smtp://<project_id>:<api_key>@smtp.tem.scw.cloud:587
```

---

## Architecture

```
.docker/
  nginx/default.conf      # Config Nginx
  php/Dockerfile          # PHP 8.3-FPM + Composer + Node 22
  php/php.ini             # Config PHP custom

assets/
  js/app.js               # Point d'entrée JS (jQuery, Moment, DataTables, Lucide)
  js/pwa.js               # Service Worker + Push Notifications
  styles/app.css          # Tailwind + composants (@layer components)
  styles/_theme.css        # Variables CSS (couleurs, typo, ombres…)

config/
  bundles.php             # Bundles Symfony enregistrés
  packages/               # Configuration des bundles
  routes.yaml             # Chargement des routes depuis src/Controller/
  services.yaml           # Injection de dépendances

public/
  index.php               # Point d'entrée Symfony
  sw.js                   # Service Worker (cache-first + push)
  manifest.webmanifest    # Manifest PWA
  icons/icon.svg          # Icône PWA

src/
  Command/
    CreateAdminUserCommand.php      # app:create-admin
    GenerateVapidKeysCommand.php    # app:generate-vapid-keys
  Controller/
    Admin/DashboardController.php   # /admin (ROLE_ADMIN)
    SecurityController.php          # /admin/login + /admin/logout
    PushSubscriptionController.php  # /push/subscribe + /push/unsubscribe
  Entity/
    User.php                        # UserInterface + ROLE_ADMIN
    PushSubscription.php            # Abonnements push VAPID
  Service/
    Mailer/AppMailer.php            # Envoi d'emails via template Twig
    Pdf/PdfService.php              # Génération PDF (DOMPDF)
    Push/PushNotificationService.php # Envoi push notifications

templates/
  base.html.twig                    # Layout public (PWA meta tags)
  security/login.html.twig          # Page de connexion admin
  admin/
    base.html.twig                  # Layout admin (sidebar + topbar)
    _nav.html.twig                  # Navigation sidebar (macro Twig)
    dashboard/index.html.twig       # Dashboard avec cartes de stats
```

---

## Accès admin

1. Générer les clés VAPID : `make vapid-keys` (coller dans `.env.local`)
2. Créer un compte admin : `make create-admin`
3. Se connecter sur http://localhost:8080/admin/login

---

## PWA & Push Notifications

### Service Worker

Le SW (`public/sw.js`) implémente :
- **Cache-first** pour les assets statiques (JS/CSS/images)
- **Network-first** pour les pages HTML
- Listener `push` → affichage de la notification
- Listener `notificationclick` → focus ou ouverture de l'onglet

### Clés VAPID

```bash
make vapid-keys
# → VAPID_PUBLIC_KEY=...
# → VAPID_PRIVATE_KEY=...
```

Coller les valeurs dans `.env.local`. Ne jamais committer `VAPID_PRIVATE_KEY`.

### Demander la permission (front)

```js
// Disponible globalement après chargement de app.js
await window.requestPushPermission();
```

### Envoyer une notification (back)

```php
$this->pushNotificationService->sendToAll('Titre', 'Corps', '/url');
```

---

## Services réutilisables

### AppMailer

```php
use App\Service\Mailer\AppMailer;

$mailer->send(
    to: 'destinataire@example.com',
    subject: 'Sujet',
    template: 'emails/montemplate.html.twig',
    context: ['variable' => 'valeur'],
);
```

### PdfService

```php
use App\Service\Pdf\PdfService;

// Depuis un template Twig
$pdf = $pdfService->generateFromTemplate('pdf/facture.html.twig', ['data' => $data]);

// Depuis du HTML brut
$pdf = $pdfService->generateFromHtml('<h1>Hello</h1>');

// $pdf est une instance Dompdf — appeler output() pour récupérer le binaire
```

---

## DataTables

Auto-initialisation via attribut `data-datatable` :

```html
<table data-datatable>
    <thead>...</thead>
    <tbody>...</tbody>
</table>
```

Configuration globale dans `config/packages/datatables.yaml`.

---

## Ajouter des entités

```bash
make bash
php bin/console make:entity MonEntite
php bin/console make:migration
exit
make migrate
```

---

## Déploiement (production)

```bash
# Sur le serveur
APP_ENV=prod composer install --no-dev --optimize-autoloader
npm run build
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

Ne pas oublier de configurer `.env.local` avec les vraies valeurs (Scaleway TEM, VAPID keys, APP_SECRET fort).
