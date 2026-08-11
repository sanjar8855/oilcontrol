import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // lang/{locale}/app.php dan kelgan tarjimalarni o'qish uchun global $t()
        app.config.globalProperties.$t = function (key, replacements = {}) {
            const translations = this.$page.props.translations ?? {};
            const value = key.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : undefined), translations);

            if (value === undefined) {
                return key;
            }

            return Object.entries(replacements).reduce(
                (text, [search, replacement]) => text.replace(`:${search}`, replacement),
                value
            );
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
