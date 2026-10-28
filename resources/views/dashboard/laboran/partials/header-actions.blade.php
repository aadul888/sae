{{--
    Header Actions — Action bar baku modul Laboran.

    • Semua aksi dirender sebagai tombol IKON 38×38; teks dipindah ke
      `title` + `aria-label` (tooltip & pembaca layar tetap jelas).
    • Tombol selalu SEJAJAR dalam satu baris (flex + wrap rapi), tidak
      lagi menumpuk jadi kolom penuh di layar kecil.
    • Bila jumlah tombol LEBIH DARI 4 → otomatis menjadi grid 4 kolom,
      sehingga 5–8 tombol tersusun 4 x 2.
    • Grid juga bisa dipaksa manual: `.dash-banner-access--grid4`.
--}}
@once
    <style>
        /* ---- 1. Wadah: sejajar, boleh membungkus, tidak menumpuk ---- */
        .dash-banner-actions.dash-banner-access,
        .dash-banner-access {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: var(--space-4) !important;
            width: auto !important;
        }

        /* ---- 2. Grid 4 kolom (5–8 tombol ⇒ 4 x 2) ---- */
        .dash-banner-access.dash-banner-access--grid4 {
            display: grid !important;
            grid-template-columns: repeat(4, var(--control-height-md, 38px)) !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: var(--space-4) !important;
            width: auto !important;
        }

        /* ---- 3. Bentuk tombol: murni ikon, kotak 38x38 seragam ---- */
        .dash-banner-access > .btn,
        .dash-banner-access > div > .dash-banner-access__trigger {
            width: var(--control-height-md, 38px) !important;
            height: var(--control-height-md, 38px) !important;
            min-width: var(--control-height-md, 38px) !important;
            min-height: var(--control-height-md, 38px) !important;
            padding: 0 !important;
            margin: 0 !important;
            gap: 0 !important;
            flex: 0 0 auto !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: var(--radius-md, 8px) !important;
            line-height: 1 !important;
            text-align: center;
            text-decoration: none !important;
        }

        /* Label teks disembunyikan — header hanya ikon */
        .dash-banner-access .btn > span {
            display: none !important;
        }

        .dash-banner-access .btn > i {
            margin: 0 !important;
            font-size: var(--text-md, 1rem) !important;
            line-height: 1 !important;
        }

        /* ---- 4. Elemen pendamping (badge scope, view-switcher) tetap sejajar ---- */
        .dash-banner-actions.dash-banner-access > .badge,
        .dash-banner-access > .badge {
            flex: 0 0 auto !important;
            align-self: center !important;
            margin: 0 !important;
            white-space: nowrap;
        }

        .dash-banner-access > div {
            flex: 0 0 auto !important;
            align-self: center;
        }

        /* ---- 5. Mobile: tetap sejajar & rapi, bukan kolom penuh ---- */
        @media (max-width: 768px) {
            .dash-banner-actions.dash-banner-access,
            .dash-banner-access {
                width: 100% !important;
                flex-direction: row !important;
                justify-content: flex-start !important;
                gap: var(--space-3) !important;
            }

            .dash-banner-access.dash-banner-access--grid4 {
                grid-template-columns: repeat(4, var(--control-height-md, 38px)) !important;
                justify-content: flex-start !important;
                gap: var(--space-3) !important;
            }

            /* Netralkan aturan lama yang memaksa tombol selebar container */
            .dash-banner-actions.dash-banner-access > * {
                flex: 0 0 auto !important;
                width: auto !important;
            }

            .dash-banner-actions.dash-banner-access > .btn {
                width: var(--control-height-md, 38px) !important;
                padding: 0 !important;
                font-size: var(--text-md, 1rem) !important;
            }
        }
    </style>

    <script>
        // > 4 tombol aksi header ⇒ susun jadi grid 4 kolom (4 x 2 untuk 5–8 tombol)
        (function () {
            function applyGrid() {
                document.querySelectorAll('.dash-banner-access').forEach(function (box) {
                    var buttons = box.querySelectorAll(':scope > .btn');
                    box.classList.toggle('dash-banner-access--grid4', buttons.length > 4);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', applyGrid);
            } else {
                applyGrid();
            }
        })();
    </script>
@endonce
