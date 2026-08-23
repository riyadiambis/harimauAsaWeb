import { expect, test } from '@playwright/test';
import { AKUN_UJI, HALAMAN, PALET, periksaHalaman } from './tokens.js';

async function masuk(page) {
    await page.goto('/masuk', { waitUntil: 'networkidle' });
    await page.fill('#username', AKUN_UJI.username);
    await page.fill('#password', AKUN_UJI.sandi);
    await Promise.all([page.waitForURL('**/'), page.click('button[type="submit"]')]);
}

for (const { nama, jalur, butuhLogin } of HALAMAN) {
    test.describe(nama, () => {
        test(`sesuai design-tokens`, async ({ page }, info) => {
            const lebar = page.viewportSize().width;

            if (butuhLogin) {
                await masuk(page);
            }

            await page.goto(jalur, { waitUntil: 'networkidle' });
            await page.waitForFunction(() => document.fonts.status === 'loaded');

            await page.screenshot({
                path: `storage/playwright/${nama}-${lebar}.png`,
                fullPage: true,
            });

            const hasil = await page.evaluate(periksaHalaman, PALET);

            expect(hasil.fontBody, 'font body').toContain('Plus Jakarta Sans');
            expect(hasil.fontTermuat, 'Plus Jakarta Sans termuat').toBe(true);
            expect(hasil.warnaLiar, 'warna di luar palet').toEqual([]);
            expect(hasil.ikon, 'ikon yang tidak diminta').toEqual([]);
            expect(hasil.tumpang, 'elemen tumpang tindih').toEqual([]);
            expect(hasil.kartuSalah, 'pola kartu').toEqual([]);
            expect(
                hasil.meluber,
                `meluber mendatar (${hasil.lebarDokumen}px > ${hasil.lebarViewport}px)`
            ).toBe(false);
            expect(hasil.gapKolom, 'jarak antar section').toBe('32px');
            expect(hasil.padMain, 'padding halaman').toBe(lebar >= 640 ? '24px' : '16px');

            info.annotations.push({ type: 'catatan', description: JSON.stringify(hasil, null, 2) });
        });
    });
}

test('pesan gagal masuk tampil sesuai token', async ({ page }, info) => {
    const lebar = page.viewportSize().width;

    await page.goto('/masuk', { waitUntil: 'networkidle' });
    await page.fill('#username', 'pendingcoba1');
    await page.fill('#password', 'pendingpending1');
    await page.click('button[type="submit"]');

    await expect(
        page.getByText('Akun kamu masih menunggu persetujuan pengurus.')
    ).toBeVisible();

    await page.waitForFunction(() => document.fonts.status === 'loaded');
    await page.screenshot({ path: `storage/playwright/masuk-galat-${lebar}.png`, fullPage: true });

    const hasil = await page.evaluate(periksaHalaman, PALET);

    expect(hasil.warnaLiar, 'warna di luar palet').toEqual([]);
    expect(hasil.tumpang, 'elemen tumpang tindih').toEqual([]);
    expect(hasil.meluber, 'meluber mendatar').toBe(false);

    info.annotations.push({ type: 'catatan', description: JSON.stringify(hasil, null, 2) });
});

test('cek ketersediaan username jalan sambil mengetik', async ({ page }) => {
    const lebar = page.viewportSize().width;

    await page.goto('/daftar', { waitUntil: 'networkidle' });

    const kolom = page.locator('#username');

    await kolom.fill('adminmin');
    await expect(page.getByText('Username ini sudah dipakai. Coba yang lain.')).toBeVisible();
    await page.screenshot({ path: `storage/playwright/daftar-terpakai-${lebar}.png`, fullPage: true });

    await kolom.fill('username_baru9');
    await expect(page.getByText('Username tersedia.')).toBeVisible();
    await page.screenshot({ path: `storage/playwright/daftar-tersedia-${lebar}.png`, fullPage: true });

    await kolom.fill('ab');
    await expect(
        page.getByText('Gunakan 4–20 karakter: huruf kecil, angka, dan garis bawah.')
    ).toBeVisible();

    // Warna indikator harus tetap dari palet resmi.
    const hasil = await page.evaluate(periksaHalaman, PALET);
    expect(hasil.warnaLiar, 'warna di luar palet').toEqual([]);
    expect(hasil.tumpang, 'elemen tumpang tindih').toEqual([]);
});
