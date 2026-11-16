import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Mỗi theme tự có entry css/js riêng (resources/themes/{theme}/{css,js}/app.*)
// — quét thư mục thay vì liệt kê cứng để thêm theme mới không phải sửa file này.
const themeInput = readdirSync('resources/themes', { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .flatMap((entry) => [
        `resources/themes/${entry.name}/css/app.css`,
        `resources/themes/${entry.name}/js/app.js`,
    ]);

export default defineConfig({
    plugins: [
        laravel({
            input: themeInput,
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
