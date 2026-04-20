import '../styles/app.css';
import 'datatables.net-dt/css/dataTables.dataTables.css';

import './pwa.js';

import $ from 'jquery';
window.$ = window.jQuery = $;

import moment from 'moment';
import 'moment/locale/fr';
moment.locale('fr');
window.moment = moment;

import DataTable from 'datatables.net';

import { createIcons, icons } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });

    document.querySelectorAll('[data-datatable]').forEach((table) => {
        new DataTable(table, {
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
        });
    });
});
