/*
 * Gestion du consentement cookies (conforme aux recommandations CNIL) :
 *  - aucun traceur non essentiel avant un choix explicite ;
 *  - « Tout refuser » aussi simple que « Tout accepter » ;
 *  - choix conservé 6 mois, puis redemandé ; redemandé aussi si la version change ;
 *  - choix modifiable à tout moment (lien « Gérer les cookies » du footer).
 *
 * Charger un script uniquement après consentement :
 *   <script type="text/plain" data-consent="analytics" data-src="https://…"></script>
 *   <script type="text/plain" data-consent="analytics">/* code inline *\/</script>
 * Ou écouter l'événement : window.addEventListener('consent:changed', e => e.detail.analytics)
 */
const COOKIE_NAME = 'cookie_consent';
const MAX_AGE = 60 * 60 * 24 * 182; // ~6 mois

export const CATEGORIES = {
    analytics: { label: 'Mesure d\'audience', description: 'Statistiques anonymisées de fréquentation pour améliorer le site.' },
    marketing: { label: 'Contenus tiers & publicité', description: 'Vidéos intégrées, réseaux sociaux, publicité ciblée.' },
};

function readCookie() {
    const match = document.cookie.split('; ').find((row) => row.startsWith(`${COOKIE_NAME}=`));
    if (!match) {
        return null;
    }
    try {
        return JSON.parse(decodeURIComponent(match.split('=').slice(1).join('=')));
    } catch {
        return null;
    }
}

function writeCookie(value) {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE_NAME}=${encodeURIComponent(JSON.stringify(value))}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

function activateScripts(choices) {
    document.querySelectorAll('script[type="text/plain"][data-consent]').forEach((placeholder) => {
        if (!choices[placeholder.dataset.consent]) {
            return;
        }
        const script = document.createElement('script');
        [...placeholder.attributes].forEach(({ name, value }) => {
            if (!['type', 'data-consent', 'data-src'].includes(name)) {
                script.setAttribute(name, value);
            }
        });
        if (placeholder.dataset.src) {
            script.src = placeholder.dataset.src;
        } else {
            script.textContent = placeholder.textContent;
        }
        placeholder.replaceWith(script);
    });
}

export function registerCookieConsent(Alpine) {
    const version = Number(document.querySelector('meta[name="cookie-consent-version"]')?.content ?? 1);

    Alpine.store('consent', {
        categories: CATEGORIES,
        choices: Object.fromEntries(Object.keys(CATEGORIES).map((key) => [key, false])),
        decided: false,
        bannerOpen: false,
        preferencesOpen: false,

        init() {
            const saved = readCookie();
            if (saved && saved.v === version) {
                this.decided = true;
                Object.keys(this.choices).forEach((key) => { this.choices[key] = Boolean(saved.choices?.[key]); });
                activateScripts(this.choices);
            } else {
                this.bannerOpen = true;
            }
            // Pages chargées par Turbo : activer les éventuels nouveaux scripts autorisés
            document.addEventListener('turbo:load', () => activateScripts(this.choices));
        },

        has(category) {
            return Boolean(this.choices[category]);
        },

        acceptAll() {
            Object.keys(this.choices).forEach((key) => { this.choices[key] = true; });
            this.save();
        },

        rejectAll() {
            Object.keys(this.choices).forEach((key) => { this.choices[key] = false; });
            this.save();
        },

        openPreferences() {
            this.preferencesOpen = true;
        },

        save() {
            writeCookie({ v: version, date: new Date().toISOString(), choices: this.choices });
            this.decided = true;
            this.bannerOpen = false;
            this.preferencesOpen = false;
            activateScripts(this.choices);
            window.dispatchEvent(new CustomEvent('consent:changed', { detail: { ...this.choices } }));
        },
    });
}
