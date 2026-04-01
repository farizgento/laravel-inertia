import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const originalVisit = router.visit.bind(router);

router.visit = (href, options = {}) => {
    const token = typeof window !== 'undefined' ? window.localStorage.getItem('auth_token') : null;
    const headers = {
        ...(options.headers ?? {}),
    };

    if (token && !headers.Authorization) {
        headers.Authorization = `Bearer ${token}`;
    }

    return originalVisit(href, {
        ...options,
        headers,
    });
};

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
