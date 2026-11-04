# Starter Symfony 8

Socle de projet prêt à développer : pages essentielles, back-office, emails transactionnels, SEO, accessibilité et **données personnelles chiffrées en base**.

| Brique | Choix |
|---|---|
| Back | **Symfony 8.1**, PHP 8.4, Doctrine ORM 3, PostgreSQL 17 |
| Front | **AssetMapper** (aucun build Node), **Tailwind CSS v4**, **Alpine.js**, **Turbo**, **Twig Components**, UX Icons (Lucide) |
| Admin | **EasyAdmin 5** (utilisateurs, messages de contact, export PDF) |
| Emails | Symfony Mailer + **Brevo** (prod) / **Mailpit** (local), templates Twig avec CSS inliné |
| Sécurité | Inscription + confirmation d'email, mot de passe oublié, limitation des tentatives, anti-spam maison |
| Qualité | PHPUnit (+ Foundry, DAMA), PHPStan niveau 6, PHP-CS-Fixer |
| Infra | Docker local (php-fpm + nginx + postgres + mailpit), Docker prod pour **Coolify** |

---

## Créer un nouveau projet

```bash
composer create-project romain-genin/symfony-starter mon-projet
cd mon-projet
make install            # build Docker, composer, importmap, tailwind, migrations, fixtures
make tailwind           # dans un autre terminal : recompile le CSS à chaque modification
```

Pas de PHP 8.4 sur la machine ? Créez le projet via Docker :

```bash
docker run --rm -it -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 sh -c "composer create-project romain-genin/symfony-starter mon-projet --ignore-platform-reqs --no-scripts && php mon-projet/bin/init-project.php"
```

À la création, `bin/init-project.php` génère des secrets de dev propres au projet (`APP_SECRET`, `APP_ENCRYPTION_KEY` dans `.env.dev`) et nomme le projet Docker d'après le dossier (`COMPOSE_PROJECT_NAME`).

| URL | |
|---|---|
| http://localhost:8080 | Site |
| http://localhost:8080/admin | Admin — `admin@example.com` / `password-de-test-123` (fixtures) |
| http://localhost:8025 | Mailpit (tous les emails envoyés en local) |

`make` liste toutes les commandes disponibles (`make test`, `make qa`, `make migration`, `make admin email=…`, etc.).

> Plusieurs projets en parallèle ? Changez les ports dans `.env.local` : `HTTP_PORT=8081`, `DB_PORT=5433`, `MAILPIT_PORT=8026`.

---

## Structure

```
assets/
  app.js                  Entrée JS : Stimulus/Turbo + Alpine (+ plugins focus, collapse)
  js/cookie-consent.js    Store Alpine « consent » (bannière CNIL)
  js/a11y.js              Annonce/focus lors des navigations Turbo
  styles/app.css          Tailwind v4 (thème dans @theme)
  styles/email.css        CSS des emails (inliné)
  icons/lucide/           Icônes SVG locales → {{ ux_icon('lucide:mail') }}
config/packages/
  app_site.yaml           ⚠️ Identité du site / infos légales (variable Twig `site`)
docker/                   Dockerfile de dev, nginx, php.ini, entrypoint prod
src/
  Doctrine/Type/EncryptedType.php    Type Doctrine « encrypted »
  Security/Encryption/Encryptor.php  Chiffrement + blind index
  Spam/SpamChecker.php               Anti-spam (honeypot + jeton horodaté)
  Mailer/AppMailer.php               Tous les emails transactionnels
  Twig/Components/                   Composants avec logique (NavLink, Breadcrumb)
translations/             Textes du site (messages, validators, security), en français
templates/
  components/             Composants anonymes (Button, Alert, Card, Section, PageHeader…)
  form/tailwind_theme.html.twig      Thème de formulaire accessible
  bundles/TwigBundle/Exception/      Pages d'erreur
  legal/                  Mentions légales, confidentialité, cookies, CGU, accessibilité
Dockerfile, docker-compose.yml       Production (Coolify)
compose.yaml                         Développement local
```

---

## Chiffrement des données

Les données personnelles (email, nom, prénom, téléphone, messages) sont chiffrées **au niveau applicatif** avant d'être écrites en base : un dump SQL ou un accès direct à la base ne révèle rien.

- Algorithme : **XChaCha20-Poly1305** (libsodium), nonce aléatoire par valeur, chiffrement authentifié (toute altération est détectée).
- Les sous-clés sont dérivées de `APP_ENCRYPTION_KEY`.
- Usage dans une entité :

  ```php
  #[ORM\Column(type: EncryptedType::NAME, nullable: true)]
  private ?string $phone = null;
  ```

- Une colonne chiffrée n'est **ni triable, ni filtrable** en SQL. Pour une recherche exacte, ajoutez un **blind index** (empreinte HMAC), comme `User::$emailHash`, utilisé pour la connexion et l'unicité des emails (contrainte `#[UniqueEmail]`).
- Les mots de passe sont hachés (bcrypt/argon), jamais chiffrés.

> ⚠️ **La clé est critique.** Générez-la avec `make encryption-key`, stockez-la dans les variables Coolify **et** dans un gestionnaire de secrets. Si vous la perdez ou la changez, les données existantes deviennent définitivement illisibles.

---

## Emails (Brevo)

- Local : `MAILER_DSN=smtp://mailpit:1025` (valeur par défaut).
- Prod : `MAILER_DSN=brevo+api://VOTRE_CLE_API@default`. Pensez à authentifier le domaine d'envoi dans Brevo (SPF, DKIM, DMARC).
- L'expéditeur est défini globalement par `MAILER_FROM`, le destinataire du formulaire de contact par `CONTACT_RECIPIENT`.
- Chaque email porte un tag Brevo (`contact-notification`, `verify-email`…) pour les statistiques.

## Formulaire de contact & anti-spam

Les messages sont **enregistrés chiffrés** (consultables dans l'admin) puis notifiés par email, avec un accusé de réception envoyé à l'expéditeur. L'anti-spam n'utilise aucun service tiers, donc aucun cookie ni transfert de données :

1. un champ « pot de miel » invisible ;
2. un jeton horodaté signé, qui rejette les envois en moins de 3 s et les jetons falsifiés ou expirés ;
3. une limitation de débit à 3 envois / 10 min par IP.

Un robot détecté reçoit une fausse confirmation, pour ne pas lui donner d'indice.

Conservation RGPD : `app.contact_retention_months` (12 mois par défaut). Planifiez `php bin/console app:contact:purge` (tâche planifiée Coolify, une fois par jour).

## Traductions

Le site est **monolingue, en français** (`framework.default_locale`), mais **aucun texte n'est écrit en dur** :
templates, formulaires, messages flash, validation et emails passent par des clés de traduction.

| Fichier | Contenu |
|---|---|
| `translations/messages.fr.yaml` | Textes du site (navigation, pages, formulaires, flashs, emails…) |
| `translations/validators.fr.yaml` | Messages de validation |
| `translations/security.fr.yaml` | Messages de connexion |

- **Modifier un texte** : changez sa valeur dans `messages.fr.yaml`, le template ne bouge pas.
- **Changer la langue du site** (site monolingue dans une autre langue) : traduisez ces fichiers en `*.<code>.yaml`
  et passez `default_locale` à `<code>` dans `config/packages/translation.yaml`.
- **Ajouter une langue** : copiez les fichiers `*.fr.yaml` en `*.<code>.yaml` et traduisez les valeurs.
  Le test `TranslationFilesTest` vérifie qu'aucune clé ni aucun paramètre (`%name%`, `{{ limit }}`) ne manque.
  Pour un site multilingue (URL par langue, sélecteur de langue…), ajoutez le bundle de votre choix et suivez
  sa documentation.
- **Pages légales** : textes longs et propres au droit de chaque pays, ils se traduisent par fichier :
  copiez `templates/legal/notice.html.twig` en `templates/legal/notice.en.html.twig` (sinon, la version française s'affiche).

---

## SEO

- `<title>`, meta description, canonical, Open Graph et Twitter Card, par page via des blocs Twig (`title`, `meta_description`, `og_image`, `robots`…).
- JSON-LD : `Organization` et `WebSite` dans le layout, `BreadcrumbList` via `<twig:Breadcrumb>`, `ContactPage`.
- `/sitemap.xml` généré à partir des routes qui déclarent `options: ['sitemap' => [...]]`.
- `/robots.txt` dynamique. **L'indexation est bloquée tant que `APP_INDEXABLE=1` n'est pas défini** (robots.txt, meta robots et en-tête `X-Robots-Tag`), ce qui protège les préprods.

## Accessibilité

Lien d'évitement, un seul `h1` par page, landmarks, `aria-current` dans la navigation, focus visible renforcé, formulaires avec erreurs reliées aux champs (`aria-describedby`, `aria-invalid`), champs obligatoires signalés, annonce des changements de page Turbo, menu mobile et modale cookies avec focus piégé et touche Échap, respect de `prefers-reduced-motion`. Contrastes AA vérifiés avec axe-core.

La page `/accessibilite` contient un modèle de déclaration RGAA, à mettre à jour après audit (statut actuel : « non conforme »).

## Cookies

La bannière respecte les recommandations de la CNIL : « Tout refuser » est aussi accessible que « Tout accepter », le choix est conservé 6 mois et reste modifiable depuis le lien « Gérer les cookies » du footer. Pour charger un script uniquement après consentement :

```html
<script type="text/plain" data-consent="analytics" data-src="https://exemple.com/analytics.js"></script>
```

Incrémentez `app.cookie_consent_version` pour redemander le consentement à tout le monde.

## Composants Twig

```twig
<twig:Button href="{{ path('app_contact') }}" icon="lucide:arrow-right">Contact</twig:Button>
<twig:Button variant="secondary" size="sm" type="button">Annuler</twig:Button>
<twig:Alert type="success" dismissible>Enregistré.</twig:Alert>
<twig:Card title="Titre" icon="lucide:zap">Contenu</twig:Card>
<twig:Section id="atouts" title="Nos atouts" intro="…">…</twig:Section>
<twig:PageHeader title="Contact" :breadcrumb="[{label: 'Contact'}]" />
<twig:NavLink route="app_contact">Contact</twig:NavLink>
```

Ajouter une icône : `php bin/console ux:icons:import lucide:nom-icone`.

---

## Production (Coolify)

Coolify construit `docker-compose.yml`, qui utilise le `Dockerfile` racine : la cible `php` (php-fpm) et la cible `nginx` (fichiers statiques embarqués). À chaque démarrage, l'entrypoint applique les migrations et préchauffe le cache.

Variables d'environnement à définir dans Coolify :

| Variable | Exemple |
|---|---|
| `APP_SECRET` | chaîne aléatoire de 32+ caractères |
| `APP_ENCRYPTION_KEY` | `make encryption-key` ⚠️ à sauvegarder |
| `DATABASE_URL` | `postgresql://user:pass@host:5432/app?serverVersion=17&charset=utf8` |
| `DEFAULT_URI` | `https://www.mon-site.fr` |
| `MAILER_DSN` | `brevo+api://CLE@default` |
| `MAILER_FROM` | `"Mon Site <no-reply@mon-site.fr>"` |
| `CONTACT_RECIPIENT` | `contact@mon-site.fr` |
| `APP_INDEXABLE` | `1` **uniquement** sur le domaine de production |
| `TRUSTED_PROXIES` | `private_ranges` (défaut) |

Après le premier déploiement, créez l'administrateur :

```bash
php bin/console app:user:create admin@mon-site.fr --admin
```

## Avant la mise en ligne

- [ ] Compléter `config/packages/app_site.yaml` (raison sociale, SIRET, hébergeur, adresse…)
- [ ] Relire les pages légales, mettre à jour les dates
- [ ] Remplacer le logo, `assets/images/og-default.png` (1200×630) et les favicons
- [ ] Ajuster les couleurs dans `assets/styles/app.css` (`@theme`)
- [ ] Générer et sauvegarder `APP_ENCRYPTION_KEY`
- [ ] Authentifier le domaine d'envoi dans Brevo
- [ ] `APP_INDEXABLE=1` en production
- [ ] Planifier `app:contact:purge`

---

## Publier une nouvelle version du starter

Le paquet est publié sur [Packagist](https://packagist.org/packages/romain-genin/symfony-starter), qui suit les tags Git du dépôt GitHub :

```bash
git tag -a v1.1.0 -m "v1.1.0" && git push origin v1.1.0
```

`composer create-project` installe automatiquement la dernière version taguée. Les projets déjà créés ne sont pas affectés : chacun vit sa vie.

