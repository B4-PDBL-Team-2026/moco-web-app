import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

const appName: string = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title: string) => (title ? `${title} - ${appName}` : appName),
    resolve: (name: string) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#2E5AA7',
    },
});
