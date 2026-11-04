<?php

declare(strict_types=1);

/*
 * Initialise un nouveau projet créé depuis le starter :
 *   composer create-project romain-genin/symfony-starter mon-projet
 *
 * Lancé automatiquement (post-create-project-cmd), relançable à la main :
 *   php bin/init-project.php
 *
 * - génère un APP_SECRET et une APP_ENCRYPTION_KEY de dev propres au projet (.env.dev) ;
 * - nomme le projet Docker d'après le dossier (COMPOSE_PROJECT_NAME dans .env).
 * Volontairement sans dépendance (ni Symfony, ni vendor) : simple script PHP.
 */

$root = dirname(__DIR__);

$replaceLine = static function (string $file, string $key, string $value) use ($root): void {
    $path = $root.'/'.$file;
    $content = is_file($path) ? (string) file_get_contents($path) : '';
    $line = $key.'='.$value;

    if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $content)) {
        $content = (string) preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $content);
    } else {
        $content = rtrim($content)."\n\n".$line."\n";
    }

    file_put_contents($path, $content);
};

$slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', basename($root)));
$slug = trim($slug, '_') ?: 'app';

$replaceLine('.env.dev', 'APP_SECRET', bin2hex(random_bytes(16)));
$replaceLine('.env.dev', 'APP_ENCRYPTION_KEY', base64_encode(random_bytes(32)));
$replaceLine('.env', 'COMPOSE_PROJECT_NAME', $slug);

echo <<<TXT

  ✅ Projet « {$slug} » initialisé : secrets de dev générés dans .env.dev.

  Étapes suivantes :
    1. make install                         (Docker, base, assets, fixtures)
    2. config/packages/app_site.yaml        (nom, société, mentions légales)
    3. assets/styles/app.css (@theme)       (couleurs, police)
    4. assets/images/ + public/favicon.*    (logo, image Open Graph, favicons)

  Prod : générez une clé dédiée avec « make encryption-key » et sauvegardez-la.

TXT;
