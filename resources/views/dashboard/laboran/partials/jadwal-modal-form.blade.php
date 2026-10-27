{{-- Modal Tambah / Ubah Jadwal Praktik — Form simpel: Lokasi + Kelas + Guru&Mapel + Alokasi JP --}}
<div id="modalJadwal" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 580px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
            <h3 id="modalJadwalTitle"
                style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                Jadwalkan Praktik
            </h3>
            <button type="button" id="btnCloseModalJadwal"
                style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                title="Tutup">&times;</button>
        </div>

        <form id="formJadwal" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
            @csrf
            <input type="hidden" name="_method" id="jadwalMethod" value="POST">
            <input type="hidden" name="jurusan_id" id="inputJadwalJurusan" value="{{ $context['jurusan_id'] ?? '' }}">
            <input type="hidden" name="pembelajaran_id" id="inputPembelajaranId">
            <input type="hidden" name="mata_pelajaran_id" id="inputMapelId">
            <input type="hidden" name="jenis_lokasi" id="inputJenisLokasi" value="lab">

            {{-- 0. Cakupan Jadwal: Mingguan (pilih minggu ke-/rentang) atau Semester --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-calendar-range text-primary me-1"></i> Cakupan Jadwal <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <div class="periode-nav-wrapper" style="margin-bottom: var(--space-5);">
                    <div class="periode-nav-desktop">
                        <button type="button" class="periode-nav-tab active" data-cakupan-tab="mingguan">
                            <i class="fas fa-calendar-week"></i> Mingguan
                        </button>
                        <button type="button" class="periode-nav-tab" data-cakupan-tab="semester">
                            <i class="fas fa-calendar-day"></i> Semester
                        </button>
                    </div>
                </div>
                <input type="hidden" name="pemberlakuan" id="inputPemberlakuan" value="mingguan">

                <div id="wrapOpsiMingguan">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-5); align-items: end;">
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                                Minggu Ke- <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="pemberlakuan_minggu" id="selectPemberlakuanMinggu" class="form-control">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">Minggu ke-{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                                Tanggal Awal <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="inputTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                                Sampai Minggu Ke-
                            </label>
                            <select name="pemberlakuan_minggu_akhir" id="selectPemberlakuanMingguAkhir" class="form-control">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $m === 1 ? 'selected' : '' }}>Minggu ke-{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div id="cakupanHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                        <i class="fas fa-info-circle me-1"></i> Jadwal dibuat pada rentang minggu terpilih — tiap minggu dapat berisi jadwal berbeda.
                    </div>
                </div>

                <div id="wrapOpsiSemester" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5);">
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                                Semester Berlaku <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="pemberlakuan_semester" id="selectPemberlakuanSemester" class="form-control">
                                <option value="1">Semester 1 (Ganjil: Juli – Desember)</option>
                                <option value="2">Semester 2 (Genap: Januari – Juni)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                                Tahun Ajaran <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="pemberlakuan_ta" id="selectPemberlakuanTa" class="form-control">
                                @foreach (\App\Http\Controllers\LaboranJadwalController::tahunAjaranOptions() as $ta)
                                    <option value="{{ $ta }}" {{ $ta === (\App\Http\Controllers\LaboranJadwalController::tahunAjaranAktif()) ? 'selected' : '' }}>{{ $ta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="cakupanHintSemester" style="display: none; font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                        <i class="fas fa-info-circle me-1"></i> Jadwal dibuat untuk satu semester penuh sesuai tahun ajaran.
                    </div>
                </div>
            </div>

            {{-- 1. Lokasi / Tempat Praktik --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-location-dot text-primary me-1"></i> Lokasi / Tempat Praktik <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select name="ruang_lab_nama" id="selectLokasi" class="form-control" required>
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach ($lokasiByJenis as $jKey => $rows)
                        @if ($rows->isNotEmpty())
                            <optgroup label="{{ \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jKey] ?? $jKey }}" data-jenis="{{ $jKey }}">
                                @foreach ($rows as $r)
                                    <option value="{{ $r->nama_ruang }}" data-jenis="{{ $jKey }}">
                                        {{ $r->nama_ruang }}{{ !empty($r->gedung) ? ' • ' . $r->gedung : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                <div id="lokasiTerpilihInfo" style="display: none; font-size: var(--text-xs); color: var(--primary); margin-top: var(--space-2);">
                    <i class="fas fa-circle-check me-1"></i> Dipilih: <strong id="lokasiTerpilihNama">-</strong>
                </div>
            </div>

            {{-- 2. Kelas / Rombel --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-users-rectangle text-primary me-1"></i> Kelas <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select name="rombel_id" id="selectRombel" class="form-control" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach ($allRombel as $rb)
                        <option value="{{ $rb->rombongan_belajar_id }}">
                            {{ $rb->nama }}{{ !empty($rb->jurusan_id_str) ? ' — ' . $rb->jurusan_id_str : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Guru Pengampu & Mata Pelajaran (mengikuti kelas terpilih) --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu & Mata Pelajaran <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select id="selectGuruMapel" class="form-control" required disabled>
                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
                <div id="guruMapelHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    Daftar diambil otomatis dari data pembelajaran kelas terpilih.
                </div>
            </div>

            {{-- 4. Alokasi Waktu: Jam Ke-1 s/d Jam Ke-N --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-clock text-primary me-1"></i> Alokasi Waktu <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: var(--space-4); align-items: center;">
                    <div>
                        <select name="jam_ke_mulai" id="selectJamKeMulai" class="form-control" required>
                            <option value="">-- Jam Mulai --</option>
                        </select>
                    </div>
                    <span style="color: var(--text-muted); font-weight: var(--font-bold);">s/d</span>
                    <div>
                        <select name="jam_ke_selesai" id="selectJamKeSelesai" class="form-control" required>
                            <option value="">-- Jam Selesai --</option>
                        </select>
                    </div>
                </div>
                <div id="alokasiHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    <i class="fas fa-info-circle me-1"></i> Pilih rentang jam pelajaran (JP). Waktu jam dihitung otomatis dari Pengaturan KBM.
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                <button type="button" class="btn btn-secondary" id="btnCancelModalJadwal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>
