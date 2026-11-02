{{-- Modal pilih periode cetak generik modul Keamanan.
     Variabel: $cetakRoute (route halaman cetak modul), $modulJudul --}}
<div id="modalCetakKeamanan" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 560px; padding: 24px; border-radius: var(--radius-lg, 12px); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                <i class="fas fa-print text-primary me-2"></i> Cetak {{ $modulJudul }}
            </h3>
            <button type="button" id="btnCloseModalCetakKeamanan"
                style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                aria-label="Tutup Modal">&times;</button>
        </div>

        <p style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin: 0 0 14px;">
            Pilih jenis periode. Filter yang sedang aktif di halaman ini ikut terbawa. Hasil terbuka di tab baru siap dicetak.
        </p>

        <form id="formCetakKeamanan" method="GET" action="{{ $cetakRoute }}" target="_blank" data-no-ajax>
            <div id="filterCetakKeamanan"></div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis Periode</label>
                <select name="periode" id="selectPeriodeCetak" class="form-control" style="height: 38px;">
                    <option value="minggu">Mingguan</option>
                    <option value="bulan" selected>Bulanan</option>
                    <option value="triwulan">Triwulan</option>
                    <option value="semester">Semester</option>
                    <option value="tahun_ajaran">Tahun Ajaran</option>
                </select>
            </div>

            <div class="periode-field" data-for="minggu" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tentukan Tanggal (Minggu)</label>
                <input type="date" name="minggu" value="{{ date('Y-m-d') }}" class="form-control" style="height: 38px;">
            </div>

            <div class="periode-field" data-for="bulan triwulan" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Bulan</label>
                <select name="bulan" class="form-control" style="height: 38px;">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="periode-field" data-for="bulan triwulan" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tahun</label>
                <select name="tahun" class="form-control" style="height: 38px;">
                    @for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="periode-field" data-for="triwulan" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Triwulan</label>
                <select name="triwulan" class="form-control" style="height: 38px;">
                    @foreach ([1 => 'I (Jan-Mar)', 2 => 'II (Apr-Jun)', 3 => 'III (Jul-Sep)', 4 => 'IV (Okt-Des)'] as $tw => $lbl)
                        <option value="{{ $tw }}" {{ (int) ceil(date('n') / 3) === $tw ? 'selected' : '' }}>Triwulan {{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="periode-field" data-for="semester" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Semester</label>
                <select name="semester" class="form-control" style="height: 38px;">
                    <option value="1" {{ date('n') >= 7 ? 'selected' : '' }}>Ganjil (Jul-Des)</option>
                    <option value="2" {{ date('n') < 7 ? 'selected' : '' }}>Genap (Jan-Jun)</option>
                </select>
            </div>

            <div class="periode-field" data-for="semester tahun_ajaran" style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tahun Ajaran</label>
                <select name="tahun_ajaran" class="form-control" style="height: 38px;">
                    @php
                        $tyr = (int) date('Y');
                        $thisYa = date('n') >= 7 ? $tyr : $tyr - 1;
                    @endphp
                    @for ($y = $thisYa; $y >= $thisYa - 4; $y--)
                        <option value="{{ $y }}/{{ $y + 1 }}">{{ $y }}/{{ $y + 1 }}</option>
                    @endfor
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalCetakKeamanan">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-print me-1"></i> Cetak Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modalCetakKeamanan');
        const select = document.getElementById('selectPeriodeCetak');
        const holder = document.getElementById('filterCetakKeamanan');
        if (!modal || !select || !holder) return;

        const SKIP = ['periode', 'bulan', 'tahun', 'triwulan', 'semester', 'tahun_ajaran', 'minggu', 'page'];

        window.openModalCetakKeamanan = function () {
            holder.innerHTML = '';
            const params = new URLSearchParams(window.location.search);
            params.forEach(function (val, key) {
                if (SKIP.indexOf(key) !== -1 || val === '') return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = val;
                holder.appendChild(input);
            });
            modal.style.display = 'flex';
        };
        window.closeModalCetakKeamanan = function () {
            modal.style.display = 'none';
        };

        function syncFields() {
            const val = select.value;
            modal.querySelectorAll('.periode-field').forEach(function (el) {
                const targets = (el.dataset.for || '').split(' ');
                el.style.display = targets.indexOf(val) !== -1 ? '' : 'none';
            });
        }
        select.addEventListener('change', syncFields);
        syncFields();

        document.getElementById('btnCloseModalCetakKeamanan').addEventListener('click', window.closeModalCetakKeamanan);
        document.getElementById('btnCancelModalCetakKeamanan').addEventListener('click', window.closeModalCetakKeamanan);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) window.closeModalCetakKeamanan();
        });
    })();
</script>
