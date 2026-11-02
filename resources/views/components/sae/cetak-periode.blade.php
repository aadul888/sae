{{--
    Tombol Cetak + dropdown PEMILIH PERIODE (Bulan / Triwulan / Semester / Tahun).

    Dipakai pada halaman INDEKS modul Laboran & Teknisi: alih-alih langsung
    membuka halaman cetak, klik tombol mencetak memunculkan pilihan periode
    (seperti dropdown cetak Jadwal KBM). Tombol "Terapkan" membuka halaman
    cetak di tab baru dengan parameter periode terpilih.

    Props:
      • route  (wajib) URL tujuan cetak
      • tipe   daftar tipe periode yang didukung (default: bulan|triwulan|semester|tahun)
      • params array parameter TETAP yang selalu dikirim (mis. id, modul)
      • only   bila true, hanya tampilkan tombol ikon (sesuai bar baku header)

    Semua nilai periode dikirim lewat query string sehingga tidak ada state
    tambahan di server.
--}}
@props([
    'route',
    'tipe' => ['minggu', 'bulan', 'triwulan', 'semester', 'tahun'],
    'params' => [],
    'judul' => 'Cetak Dokumen',
])

@php
    use App\Support\PeriodeLaporan;

    if (is_string($tipe)) {
        $tipe = array_filter(array_map('trim', explode('|', $tipe)));
    }
    $tipe = array_values(array_intersect(array_keys(PeriodeLaporan::labelTipe()), $tipe));
    if (empty($tipe)) {
        $tipe = ['bulan'];
    }

    $uid       = 'cetakPeriode' . substr(md5(uniqid('', true)), 0, 8);
    $bulanOpsi = PeriodeLaporan::labelTipe();
    $tahun     = (int) date('Y');
    $tahunOpsi = range($tahun - 3, $tahun + 2);
    [$ta1, $ta2] = PeriodeLaporan::tahunAjaranParts('', $tahun);
    $tahunAjaran = "{$ta1}/{$ta2}";
@endphp

<div style="position: relative; display: inline-block;" class="cetak-periode-wrap">
    <button type="button" class="btn btn-outline dash-banner-access__trigger"
        id="{{ $uid }}Btn" title="{{ $judul }}" aria-label="{{ $judul }}"
        aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-print"></i>
    </button>

    <div id="{{ $uid }}Menu" class="cetak-periode-menu"
        style="display: none; position: fixed; top: 0; left: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); box-shadow: 0 10px 25px rgba(0,0,0,0.25); width: min(320px, calc(100vw - 24px)); max-width: calc(100vw - 24px); max-height: min(70vh, 480px); overflow-y: auto; box-sizing: border-box; z-index: 99999; padding: 12px;">
        {{-- data-no-ajax: cegah intersepsi dashboard-live-search.js yang mengubah
             submit GET menjadi refresh tabel (harus membuka tab cetak). --}}
        <form method="GET" action="{{ $route }}" target="_blank" data-no-ajax
            class="cetak-periode-form" data-uid="{{ $uid }}"
            style="display: flex; flex-direction: column; gap: 8px; margin: 0;">
            @foreach ($params as $pk => $pv)
                <input type="hidden" name="{{ $pk }}" value="{{ $pv }}">
            @endforeach

            <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Periode</label>
            <select name="periode" class="select-cetak" data-role="tipe"
                onchange="saeCetakToggleTipe(this)">
                @foreach ($tipe as $t)
                    <option value="{{ $t }}" {{ $t === ($tipe[0] ?? 'bulan') ? 'selected' : '' }}>
                        {{ $bulanOpsi[$t] ?? $t }}
                    </option>
                @endforeach
            </select>

            <span class="cetak-periode-field" data-p="minggu">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tgl acuan</label>
                <input type="date" name="tanggal" class="select-cetak" value="{{ date('Y-m-d') }}">
            </span>

            <span class="cetak-periode-field" data-p="bulan triwulan tahun">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Bulan</label>
                <select name="bulan" class="select-cetak">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </span>

            <span class="cetak-periode-field" data-p="triwulan">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Triwulan</label>
                <select name="triwulan" class="select-cetak">
                    @foreach ([1 => 'I (Jan-Mar)', 2 => 'II (Apr-Jun)', 3 => 'III (Jul-Sep)', 4 => 'IV (Okt-Des)'] as $tw => $lbl)
                        <option value="{{ $tw }}" {{ (int) ceil(date('n') / 3) === $tw ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>

            <span class="cetak-periode-field" data-p="semester">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Semester</label>
                <select name="semester" class="select-cetak">
                    <option value="1" {{ date('n') >= 7 ? 'selected' : '' }}>Ganjil (Jul-Des)</option>
                    <option value="2" {{ date('n') < 7 ? 'selected' : '' }}>Genap (Jan-Jun)</option>
                </select>
            </span>

            <span class="cetak-periode-field" data-p="semester">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="select-cetak" placeholder="2026/2027"
                    value="{{ $tahunAjaran }}" pattern="\d{4}\s*/\s*\d{4}">
            </span>

            <span class="cetak-periode-field" data-p="bulan triwulan semester tahun">
                <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tahun</label>
                <select name="tahun" class="select-cetak">
                    @foreach ($tahunOpsi as $y)
                        <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </span>

            <button type="submit" class="btn btn-primary" style="margin-top: 4px;">
                <i class="fas fa-print"></i> Cetak
            </button>
        </form>
    </div>
</div>

@once
    <style>
        /* Dropdown periode: tata letak vertikal, bidang penuh lebar, aman di layar sempit. */
        .cetak-periode-menu .cetak-periode-form { width: 100%; }
        .cetak-periode-menu .cetak-periode-form select,
        .cetak-periode-menu .cetak-periode-form input {
            width: 100%;
            box-sizing: border-box;
            height: var(--control-height-sm, 34px);
            padding: 0 var(--space-3);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md, 8px);
            background: var(--bg-input, var(--bg-card));
            color: var(--text-color);
            font-size: var(--text-sm);
        }
        .cetak-periode-menu .cetak-periode-form label {
            font-size: var(--text-xs);
            color: var(--text-muted);
            margin: 0;
        }
        .cetak-periode-field { display: none; flex-direction: column; gap: 4px; }
        .cetak-periode-field.is-open { display: flex; }
    </style>
    <script>
        // Buka/tutup dropdown periode + toggle bidang sesuai tipe periode.
        function saeCetakToggleTipe(sel) {
            var form = sel.closest('.cetak-periode-form');
            if (!form) return;
            form.querySelectorAll('.cetak-periode-field').forEach(function (el) {
                var list = (el.getAttribute('data-p') || '').split(/\s+/);
                el.classList.toggle('is-open', list.indexOf(sel.value) !== -1);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.cetak-periode-wrap').forEach(function (wrap) {
                var btn = wrap.querySelector('.dash-banner-access__trigger');
                var menu = wrap.querySelector('div[id$="Menu"]');
                if (!btn || !menu) return;

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var open = menu.style.display === 'block';
                    document.querySelectorAll('.cetak-periode-wrap div[id$="Menu"]')
                        .forEach(function (m) { m.style.display = 'none'; });
                    menu.style.display = open ? 'none' : 'block';
                    btn.setAttribute('aria-expanded', open ? 'false' : 'true');

                    if (!open) {
                        // Posisi `fixed`: jangkar di bawah tombol, lalu di-clamp
                        // agar selalu berada di dalam viewport (aman di layar sempit).
                        var pad = 8;
                        var b = btn.getBoundingClientRect();
                        var w = menu.offsetWidth || 320;
                        var h = menu.offsetHeight || 0;
                        var left = Math.min(b.left, window.innerWidth - w - pad);
                        left = Math.max(pad, left);
                        var top = b.bottom + 6;
                        if (top + h > window.innerHeight - pad) {
                            top = Math.max(pad, window.innerHeight - h - pad);
                        }
                        menu.style.left = left + 'px';
                        menu.style.top = top + 'px';
                    }
                });

                // Bidang awal mengikuti tipe periode terpilih.
                var tipeSel = menu.querySelector('[data-role="tipe"]');
                if (tipeSel) saeCetakToggleTipe(tipeSel);
            });

            document.addEventListener('click', function (e) {
                document.querySelectorAll('.cetak-periode-wrap').forEach(function (wrap) {
                    if (wrap.contains(e.target)) return;
                    var menu = wrap.querySelector('div[id$="Menu"]');
                    var btn = wrap.querySelector('.dash-banner-access__trigger');
                    if (menu) menu.style.display = 'none';
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            });
        });
    </script>
@endonce
