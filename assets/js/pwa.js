/**
 * PWA — Service Worker + Push Notifications
 *
 * Pour activer les notifications depuis un bouton :
 *   import { requestPushPermission } from './pwa.js';
 *   btn.addEventListener('click', requestPushPermission);
 */

const VAPID_KEY = document.querySelector('meta[name="vapid-public-key"]')?.content ?? null;

function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

async function registerSW() {
    if (!('serviceWorker' in navigator)) return null;
    try {
        const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        return reg;
    } catch (err) {
        console.error('[PWA] SW registration failed:', err);
        return null;
    }
}

async function syncSubscription(registration) {
    if (!VAPID_KEY || !registration.pushManager) return;

    try {
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
        });

        await fetch('/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(subscription.toJSON()),
        });
    } catch (err) {
        console.error('[PWA] Push subscription failed:', err);
    }
}

export async function requestPushPermission() {
    if (!('Notification' in window)) {
        alert('Ce navigateur ne supporte pas les notifications.');
        return false;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return false;

    const registration = await navigator.serviceWorker.ready;
    await syncSubscription(registration);
    return true;
}

// ── Init ─────────────────────────────────────────────────────
window.addEventListener('load', async () => {
    const registration = await registerSW();
    if (!registration) return;

    // Si l'utilisateur avait déjà accordé la permission, re-sync la subscription
    if (Notification.permission === 'granted') {
        const existing = await registration.pushManager.getSubscription();
        if (!existing) await syncSubscription(registration);
    }

    // Expose globally pour usage depuis Twig / autres scripts
    window.requestPushPermission = requestPushPermission;
});
