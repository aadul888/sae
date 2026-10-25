import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

/**
 * Konfigurasi Vite.
 *
 * Catatan: proyek ini TIDAK memakai Tailwind CSS maupun Alpine.js.
 * Seluruh tampilan memakai CSS kustom berbasis design token
 * (public/css/tokens.css, components.css, sae.css) yang dimuat langsung
 * lewat <link> pada layout, dan JavaScript dimuat sebagai berkas modular
 * di public/js/.
 *
 * Vite dipertahankan hanya untuk aset yang kelak membutuhkan bundling.
 * Bila tidak diperlukan, entry di bawah dapat dihapus seluruhnya.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
});
