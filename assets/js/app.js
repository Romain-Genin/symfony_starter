// CSS — theme variables + Tailwind + components
import '../styles/app.css';

// PWA — Service Worker + Push Notifications
import './pwa.js';

// DataTables default stylesheet
import 'datatables.net-dt/css/dataTables.dataTables.css';

// jQuery (also auto-provided globally by Encore via autoProvidejQuery())
import $ from 'jquery';
window.$ = window.jQuery = $;

// Moment.js
import moment from 'moment';
import 'moment/locale/fr';
moment.locale('fr');
window.moment = moment;

// DataTables
import DataTable from 'datatables.net';

// ============================================================
// Initialisation DataTables
// Ajoute l'attribut data-datatable sur tes <table> pour l'activer auto
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datatable]').forEach((table) => {
        new DataTable(table, {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json',
            },
        });
    });
});

// ============================================================
// Lucide Icons
// Usage Twig : <i data-lucide="icon-name"></i>
// Icônes disponibles : https://lucide.dev/icons
// ============================================================
import { createIcons, icons } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
});

// ============================================================
// Ajoute tes imports et initialisations JS ici
// ============================================================
