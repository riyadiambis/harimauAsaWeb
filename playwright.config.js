import { defineConfig } from '@playwright/test';

// Verifikasi tampilan wajib menurut docs/design-tokens.md: 1440px dan 390px.
export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.APP_URL_UJI ?? 'http://127.0.0.1:8123',
    },
    projects: [
        {
            name: 'desktop-1440',
            use: { viewport: { width: 1440, height: 900 } },
        },
        {
            name: 'mobile-390',
            use: { viewport: { width: 390, height: 844 } },
        },
    ],
});
