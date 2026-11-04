/*
 * Point d'entrée JS (AssetMapper / importmap — aucun build Node).
 *  - Turbo Drive : navigation sans rechargement (via @symfony/ux-turbo, chargé par Stimulus)
 *  - Alpine.js   : interactions légères déclarées dans le HTML (menu, dialogues, bannière cookies…)
 */
import './stimulus_bootstrap.js';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { registerCookieConsent } from './js/cookie-consent.js';
import { registerA11y } from './js/a11y.js';
import './styles/app.css';

Alpine.plugin(focus);
Alpine.plugin(collapse);
registerCookieConsent(Alpine);
registerA11y();

// Turbo met la page en cache avant de la quitter : on referme menus et dialogues
// pour que l'aperçu restauré (bouton « retour ») soit propre.
document.addEventListener('turbo:before-cache', () => {
    window.dispatchEvent(new CustomEvent('app:before-cache'));
});

window.Alpine = Alpine;
Alpine.start();
