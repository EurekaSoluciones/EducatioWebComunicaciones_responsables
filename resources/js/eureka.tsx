import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/eureka.css';
import '../css/eureui.css';
import '../css/eureka-pranzo.css';
import '../css/eureka-family.css';

createInertiaApp({
    title: (title) => `${title} · Familias`,
    resolve: async (name) => (await resolvePageComponent<{ default: ResolvedComponent }>(
        `./pages/${name}.tsx`,
        import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx'),
    )).default,
    setup({ el, App, props }) {
        createRoot(el).render(<StrictMode><App {...props} /></StrictMode>);
    },
    progress: false,
});
