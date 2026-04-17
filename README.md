# Symfony 7.2 — Starter Docker

Stack prête à l'emploi : PHP 8.3 · Nginx · MariaDB 10.11 · Webpack Encore · Tailwind CSS · PWA · Admin custom.

---

## Stack

| Couche          | Technologie                                           |
|-----------------|-------------------------------------------------------|
| Runtime         | PHP 8.3-FPM                                           |
| Web             | Nginx 1.25                                            |
| Base de données | MariaDB 10.11 LTS                                     |
| ORM             | Doctrine ORM + Migrations                             |
| Frontend        | Webpack Encore + Tailwind CSS v3 + PostCSS            |
| Icons           | Lucide Icons                                          |
| JS              | jQuery 3, Moment.js (locale fr), DataTables.net       |
| PDF             | DOMPDF                                                |
| Email           | Symfony Mailer · dev → Mailpit · prod → Scaleway TEM  |
| Push            | Web Push API + minishlink/web-push                    |
| PWA             | Service Worker, manifest, icônes                      |
| Admin           | Panel custom (Tailwind, sidebar, login)               |
| Dev tools       | Mailpit, Symfony Profiler, Maker Bundle               |

---

## Premier lancement

```bash
git clone <repo-url> mon-projet
cd mon-projet
cp .env .env.local          # puis édite .env.local avec tes valeurs
make install                # build Docker + composer + npm + migrations + assets
```

L'app est disponible sur **http://localhost:8080**
Mailpit (catch-all emails) : **http://localhost:8025**

---

## Commandes Make

```bash
# ── Setup ─────────────────────────────────────────────────────
make install          # Premier lancement complet
make build            # Rebuild les images Docker

# ── Docker ────────────────────────────────────────────────────
make start            # Démarrer les conteneurs
make stop             # Arrêter les conteneurs
make restart          # Redémarrer les conteneurs
make logs             # Suivre les logs en temps réel

# ── Développement ─────────────────────────────────────────────
make bash             # Shell bash dans le conteneur PHP
make cc               # Vider le cache Symfony

# ── Base de données ───────────────────────────────────────────
make migration        # Générer une migration Doctrine
make migrate          # Jouer les migrations en attente
make db-shell         # Shell MySQL (MariaDB)

# ── Assets ────────────────────────────────────────────────────
make assets-watch     # Watch + rebuild automatique (dev)
make assets-dev       # Build dev one-shot
make assets           # Build production

# ── Admin & sécurité ──────────────────────────────────────────
make create-admin     # Créer un compte administrateur (interactif)
make vapid-keys       # Générer les clés VAPID (push notifications)

# ── Symfony ───────────────────────────────────────────────────
make routes           # Lister toutes les routes
make entities         # Lister les entités Doctrine

# ── Tests ─────────────────────────────────────────────────────
make test             # Lancer PHPUnit
```

---

## Variables d'environnement

Copie `.env` en `.env.local` et modifie les valeurs. Le fichier `.env.local` n'est jamais commité.

### Base de données

```dotenv
DB_HOST=mariadb
DB_PORT=3306
DB_NAME=symfony
DB_USER=symfony
DB_PASSWORD=symfony
```

### Mailer

**Dev** — Mailpit intercepte tous les emails (aucun envoi réel) :
```dotenv
MAILER_DSN=smtp://mailpit:1025
```

**Prod** — Scaleway Transactional Email (TEM) :
```dotenv
MAILER_DSN=smtp://<project_id>:<api_secret_key>@smtp.tem.scw.cloud:587
MAILER_SENDER_EMAIL=noreply@mondomaine.fr
MAILER_SENDER_NAME="Mon Projet"
```

> Génère les credentials sur [console.scaleway.com](https://console.scaleway.com) → Transactional Email.

### Push Notifications (VAPID)

```bash
make vapid-keys   # affiche VAPID_PUBLIC_KEY et VAPID_PRIVATE_KEY
```

Colle les valeurs dans `.env.local` :
```dotenv
VAPID_PUBLIC_KEY=<clé publique générée>
VAPID_PRIVATE_KEY=<clé privée générée>
VAPID_SUBJECT=mailto:contact@mondomaine.fr
```

> Ne commite jamais `VAPID_PRIVATE_KEY`.

### Ports Docker (optionnel)

```dotenv
NGINX_PORT=8080        # app → http://localhost:8080
MAILPIT_UI_PORT=8025   # mailpit → http://localhost:8025
DB_PORT=3306           # MariaDB
```

---

## Administration

### Accès

URL : `http://localhost:8080/admin/login`

### Créer le premier admin

```bash
make create-admin
# → Email : admin@monprojet.fr
# → Nom : Admin
# → Mot de passe : (masqué, min. 8 caractères)
```

### Ajouter une page admin

1. Créer le contrôleur dans `src/Controller/Admin/`
2. Préfixer les noms de routes avec `admin_`
3. Ajouter l'entrée dans `templates/admin/_nav.html.twig`
4. Étendre `admin/base.html.twig` dans le template

```php
// src/Controller/Admin/UserController.php
#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/users/index.html.twig');
    }
}
```

```twig
{# templates/admin/users/index.html.twig #}
{% extends 'admin/base.html.twig' %}
{% block title %}Utilisateurs{% endblock %}
{% block page_title %}Utilisateurs{% endblock %}
{% block content %}
    {# ton contenu ici #}
{% endblock %}
```

Décommenter dans `templates/admin/_nav.html.twig` :
```twig
{{ _self.item(route, 'admin_users_index', 'Utilisateurs', 'users', 'admin_users_') }}
```

---

## Assets

### Structure

```
assets/
├── js/
│   ├── app.js          ← point d'entrée principal
│   └── pwa.js          ← Service Worker + Push (importé par app.js)
└── styles/
    ├── app.css         ← Tailwind + @layer components (btn, card, badge…)
    └── _theme.css      ← variables CSS du projet (couleurs, typo, spacing…)
```

### Personnaliser le thème

Édite `assets/styles/_theme.css` pour changer les variables CSS globales :
```css
:root {
    --color-primary: #3b82f6;
    --font-family-base: 'Inter', sans-serif;
}
```

### Composants Tailwind disponibles

```html
<button class="btn-primary">Enregistrer</button>
<button class="btn-secondary">Annuler</button>
<button class="btn-danger">Supprimer</button>

<div class="card">…</div>

<input class="form-input" type="text">
<label class="form-label">Nom</label>

<span class="badge badge-success">Actif</span>
<span class="badge badge-danger">Inactif</span>
<span class="badge badge-warning">En attente</span>
<span class="badge badge-info">Info</span>
```

### Icônes (Lucide)

```html
<i data-lucide="nom-icone" class="w-5 h-5 text-blue-600"></i>
```

> Catalogue : [lucide.dev/icons](https://lucide.dev/icons)

---

## PDF (DOMPDF)

```php
use App\Service\Pdf\PdfService;

public function export(PdfService $pdf): Response
{
    $content = $pdf->generateFromTemplate('pdf/invoice.html.twig', [
        'order' => $order,
    ]);

    return new Response($content, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="facture.pdf"',
    ]);
}
```

---

## Push Notifications

### Envoi côté backend

```php
use App\Service\Push\PushNotificationService;

// Tous les abonnés
$push->sendToAll([
    'title' => 'Nouvelle commande',
    'body'  => 'Vous avez reçu une commande.',
    'url'   => '/admin/commandes',
]);

// Abonné spécifique
$push->sendTo($subscription, ['title' => '…', 'body' => '…']);
```

### Demande de permission côté frontend

```html
<button onclick="requestPushPermission()">Activer les notifications</button>
```

---

## PWA

L'app est installable sur smartphone ("Ajouter à l'écran d'accueil").

### Remplacer l'icône

Remplace `public/icons/icon.svg` par ton logo.  
Pour un support maximal, ajoute aussi des PNG 192×192 et 512×512 dans `public/icons/` et mets à jour `public/manifest.webmanifest`.

### Personnaliser le manifest

```json
{
    "name": "Nom complet de l'app",
    "short_name": "App",
    "theme_color": "#3b82f6"
}
```

---

## Email (AppMailer)

```php
use App\Service\Mailer\AppMailer;

$mailer->send(
    to: 'user@example.com',
    subject: 'Bienvenue !',
    template: 'emails/welcome.html.twig',
    context: ['user' => $user],
);
```

En développement, tous les emails sont capturés par Mailpit : **http://localhost:8025**

---

## DataTables

Activation automatique via attribut HTML :

```html
<table data-datatable>
    <thead>…</thead>
    <tbody>…</tbody>
</table>
```

Initialisation manuelle avec options :
```javascript
import DataTable from 'datatables.net';
new DataTable('#ma-table', { pageLength: 50 });
```

---

## Structure du projet

```
.
├── .docker/
│   ├── nginx/default.conf           ← vhost Nginx
│   └── php/
│       ├── Dockerfile               ← PHP 8.3-FPM + Composer + Node 22
│       └── php.ini
├── assets/
│   ├── js/app.js                    ← point d'entrée JS
│   ├── js/pwa.js                    ← Service Worker + Push
│   └── styles/
│       ├── app.css                  ← Tailwind + composants
│       └── _theme.css               ← variables CSS
├── config/packages/
│   ├── datatables.yaml
│   ├── mailer.yaml
│   ├── security.yaml
│   └── twig.yaml
├── public/
│   ├── icons/icon.svg               ← icône PWA (à remplacer)
│   ├── manifest.webmanifest
│   └── sw.js                        ← Service Worker
├── src/
│   ├── Command/
│   │   ├── CreateAdminUserCommand.php
│   │   └── GenerateVapidKeysCommand.php
│   ├── Controller/
│   │   ├── Admin/DashboardController.php
│   │   ├── PushSubscriptionController.php
│   │   └── SecurityController.php
│   ├── Entity/
│   │   ├── PushSubscription.php
│   │   └── User.php
│   └── Service/
│       ├── Mailer/AppMailer.php
│       ├── Pdf/PdfService.php
│       └── Push/PushNotificationService.php
├── templates/
│   ├── admin/
│   │   ├── base.html.twig           ← layout admin (sidebar + topbar)
│   │   ├── _nav.html.twig           ← menu latéral
│   │   └── dashboard/index.html.twig
│   ├── base.html.twig               ← layout public
│   └── security/login.html.twig
├── .env                             ← variables (template, commiter)
├── composer.json
├── docker-compose.yml
├── Makefile
├── package.json
├── postcss.config.js
├── tailwind.config.js
└── webpack.config.js
```