// Palet resmi docs/design-tokens.md. Dipakai untuk memeriksa tidak ada warna
// di luar daftar (larangan #2).
export const PALET = {
    canvas: [242, 241, 237],
    surface: [251, 250, 248],
    'surface-alt': [234, 232, 226],
    line: [226, 224, 217],
    ink: [38, 37, 31],
    'ink-muted': [110, 108, 100],
    'ink-faint': [155, 153, 143],
    brand: [122, 59, 51],
    action: [47, 46, 40],
    'action-hover': [69, 68, 60],
    'state-paid': [74, 107, 82],
    'state-wait': [138, 109, 59],
    'state-late': [140, 59, 52],
    'state-none': [180, 178, 169],
};

export const HALAMAN = [
    { nama: 'masuk', jalur: '/masuk' },
    { nama: 'daftar', jalur: '/daftar' },
    { nama: 'daftar-selesai', jalur: '/daftar/selesai' },
    { nama: 'ganti-sandi', jalur: '/ganti-sandi', butuhLogin: true },
];

// Akun uji dari docs/fitur/01-auth.md.
export const AKUN_UJI = { username: 'wargacoba1', sandi: 'wargawarga1' };

/**
 * Dijalankan di dalam browser. Mengumpulkan semua pelanggaran token dalam satu
 * lintasan supaya satu kali jalan menampilkan seluruh masalah, bukan yang pertama saja.
 */
export function periksaHalaman(palet) {
    const izin = Object.values(palet);
    const cocok = (r, g, b) => izin.some(([pr, pg, pb]) => pr === r && pg === g && pb === b);

    const uraiWarna = (nilai) => {
        const m = String(nilai).match(/rgba?\(([^)]+)\)/);
        if (!m) return null;
        const bagian = m[1].split(/[,\s/]+/).filter(Boolean).map(Number);
        const [r, g, b, a = 1] = bagian;
        return { r, g, b, a };
    };

    const terlihat = (el) => {
        const s = getComputedStyle(el);
        if (s.display === 'none' || s.visibility === 'hidden' || s.opacity === '0') return false;
        const r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
    };

    const semua = [...document.querySelectorAll('body *')].filter(terlihat);

    // --- 1. Warna di luar palet -------------------------------------------
    const warnaLiar = [];
    const properti = ['color', 'backgroundColor', 'borderTopColor', 'borderRightColor', 'borderBottomColor', 'borderLeftColor'];

    for (const el of semua) {
        const s = getComputedStyle(el);
        for (const prop of properti) {
            const w = uraiWarna(s[prop]);
            if (!w || w.a === 0) continue;
            // Border hanya relevan kalau lebarnya bukan nol.
            if (prop.startsWith('border')) {
                const sisi = prop.replace('Color', 'Width');
                if (parseFloat(s[sisi]) === 0) continue;
            }
            if (!cocok(w.r, w.g, w.b)) {
                warnaLiar.push(`${el.tagName.toLowerCase()}.${el.className || '-'} ${prop}=${s[prop]}`);
            }
        }
    }

    // --- 2. Ikon dekoratif yang tidak diminta ------------------------------
    const ikon = [...document.querySelectorAll('svg, i.fa, [class*="icon"]')].map(
        (el) => `${el.tagName.toLowerCase()}.${el.className || '-'}`
    );

    // --- 3. Tumpang tindih antar saudara -----------------------------------
    const tumpang = [];
    const statis = (el) => {
        const p = getComputedStyle(el).position;
        return p === 'static' || p === 'relative';
    };

    for (const induk of [...document.querySelectorAll('body *')]) {
        const anak = [...induk.children].filter((el) => terlihat(el) && statis(el));
        for (let i = 0; i < anak.length; i++) {
            for (let j = i + 1; j < anak.length; j++) {
                const a = anak[i].getBoundingClientRect();
                const b = anak[j].getBoundingClientRect();
                const potong =
                    Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left)) *
                    Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));
                if (potong > 1) {
                    tumpang.push(
                        `${anak[i].tagName.toLowerCase()}.${anak[i].className || '-'} ↔ ${anak[j].tagName.toLowerCase()}.${anak[j].className || '-'}`
                    );
                }
            }
        }
    }

    // --- 4. Pola kartu: radius 16px harus berpasangan dengan padding 24px ---
    const kartuSalah = [];
    for (const el of semua) {
        const s = getComputedStyle(el);
        if (parseFloat(s.borderTopLeftRadius) !== 16) continue;
        const pad = ['paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'].map((p) => parseFloat(s[p]));
        if (pad.some((v) => v !== 24)) {
            kartuSalah.push(`padding=${pad.join('/')} pada .${el.className || '-'}`);
        }
        if (s.boxShadow !== 'none' && !s.boxShadow.includes('0.06')) {
            kartuSalah.push(`bayangan=${s.boxShadow}`);
        }
    }

    // --- 5. Font sungguh termuat -------------------------------------------
    const fontBody = getComputedStyle(document.body).fontFamily;
    const fontTermuat = document.fonts.check('400 15px "Plus Jakarta Sans"');

    // --- 6. Meluber mendatar ------------------------------------------------
    const meluber = document.documentElement.scrollWidth > window.innerWidth + 1;

    // --- 7. Jarak antar section di kolom utama -----------------------------
    const kolom = document.querySelector('main > div');
    const gapKolom = kolom ? getComputedStyle(kolom).rowGap : null;

    // --- 8. Padding halaman -------------------------------------------------
    const main = document.querySelector('main');
    const padMain = main ? getComputedStyle(main).paddingLeft : null;

    return {
        warnaLiar: [...new Set(warnaLiar)],
        ikon,
        tumpang: [...new Set(tumpang)],
        kartuSalah: [...new Set(kartuSalah)],
        fontBody,
        fontTermuat,
        meluber,
        gapKolom,
        padMain,
        lebarDokumen: document.documentElement.scrollWidth,
        lebarViewport: window.innerWidth,
    };
}
