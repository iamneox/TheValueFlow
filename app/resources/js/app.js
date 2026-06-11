import './bootstrap';
import '../css/app.css';

import axios from 'axios';
import { axiosAdapter } from '@inertiajs/core';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

function getCsrfFromMeta() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

let currentCsrfToken = getCsrfFromMeta();

function syncCsrfFromPage(page) {
    const token = page?.props?.csrf_token;
    if (!token) {
        return;
    }
    currentCsrfToken = token;
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }
}

router.on('navigate', (event) => syncCsrfFromPage(event.detail?.page));
router.on('success', (event) => syncCsrfFromPage(event.detail?.page));

function getActiveCsrfToken() {
    return currentCsrfToken || getCsrfFromMeta();
}

function getXsrfFromCookie() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

router.on('before', (event) => {
    const method = event.detail.visit.method?.toLowerCase();
    if (!['post', 'put', 'patch', 'delete'].includes(method)) {
        return;
    }

    const visit = event.detail.visit;
    const token = getActiveCsrfToken();

    visit.headers = visit.headers ?? {};
    if (!visit.headers['X-XSRF-TOKEN']) {
        const xsrf = getXsrfFromCookie();
        if (xsrf) {
            visit.headers['X-XSRF-TOKEN'] = xsrf;
        }
    }

    // Cloudflare puede eliminar headers personalizados; _token en el body siempre funciona.
    if (token && visit.data && typeof visit.data === 'object') {
        if (visit.data instanceof FormData) {
            if (!visit.data.has('_token')) {
                visit.data.append('_token', token);
            }
        } else {
            visit.data = { ...visit.data, _token: token };
        }
    }
});

createInertiaApp({
    http: axiosAdapter(axios),
    title: (title) => (title ? `${title} — TheValueFlow` : 'TheValueFlow'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});
