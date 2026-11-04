/*
 * Accessibilité des navigations Turbo (sans rechargement, un lecteur d'écran n'est pas
 * informé du changement de page) :
 *  - annonce le titre de la nouvelle page dans une région « live » ;
 *  - place le focus au début du contenu principal (sauf ancre dans l'URL).
 */
export function registerA11y() {
    let firstLoad = true;

    document.addEventListener('turbo:load', () => {
        if (firstLoad) {
            firstLoad = false;
            return;
        }

        const announcer = document.getElementById('route-announcer');
        if (announcer) {
            announcer.textContent = '';
            window.setTimeout(() => { announcer.textContent = document.title; }, 100);
        }

        if (!window.location.hash) {
            document.getElementById('main-content')?.focus({ preventScroll: true });
        }
    });
}
