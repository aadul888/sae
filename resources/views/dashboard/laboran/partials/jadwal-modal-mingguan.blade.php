{{-- Modal Tambah / Ubah Jadwal Praktik MINGGUAN --}}
<div id="modalJadwalMingguan" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 580px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
            <div>
                <h3 id="modalJadwalMingguanTitle"
                    style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                    Jadwalkan Praktik (Mingguan)
                </h3>
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px;">
                    Penjadwalan per minggu berulang sesuai pilihan minggu ke- dan tanggal awal.
                </div>
            </div>
            <button type="button" class="btn-close-modal-jadwal" data-target="modalJadwalMingguan"
                style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                title="Tutup">&times;</button>
        </div>

        <form id="formJadwalMingguan" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
            @csrf
            <input type="hidden" name="_method" id="jadwalMethodMingguan" value="POST">
            <input type="hidden" name="jurusan_id" value="{{ $context['jurusan_id'] ?? '' }}">
            <input type="hidden" name="pemberlakuan" value="mingguan">
            <input type="hidden" name="jenis_lokasi" id="inputJenisLokasiMingguan" value="lab">

            {{-- 1. Lokasi / Tempat Praktik --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-location-dot text-primary me-1"></i> Lokasi / Tempat Praktik <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select name="ruang_lab_nama" id="selectLokasiMingguan" class="form-control" required>
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
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    <i class="fas fa-circle-info text-primary me-1"></i> Tempat praktik boleh dipakai guru lain pada jam yang sama. Yang tidak boleh bentrok hanya guru pengampu.
                </div>
            </div>

            {{-- 2. Kelas / Rombel --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-users-rectangle text-primary me-1"></i> Kelas <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select name="rombel_id" id="selectRombelMingguan" class="form-control" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach ($allRombel as $rb)
                        <option value="{{ $rb->rombongan_belajar_id }}">
                            {{ $rb->nama }}{{ !empty($rb->jurusan_id_str) ? ' — ' . $rb->jurusan_id_str : '' }}
                        </option>
                    @endforeach
                </select>
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    <i class="fas fa-circle-info text-primary me-1"></i> Saat diisi dari matriks, kelas terkunci otomatis sesuai sel yang diklik.
                </div>
            </div>

            {{-- 3. Guru Pengampu & Mata Pelajaran --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu & Mata Pelajaran <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select name="pembelajaran_id" id="selectGuruMapelMingguan" class="form-control" required disabled>
                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
                <div id="guruMapelHintMingguan" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    Daftar diambil otomatis dari pembelajaran kelas yang dipilih.
                </div>
            </div>

            {{-- 4. Alokasi Waktu: Jam Ke-1 s/d Jam Ke-N --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-clock text-primary me-1"></i> Jam Pelajaran (JP) <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                {{-- Satu pengisian boleh mencakup beberapa JP sekaligus,
                     mis. Jam ke-1 s/d Jam ke-4. --}}
                <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: var(--space-4); align-items: center;">
                    <div>
                        <select name="jam_ke_mulai" id="selectJamKeMulaiMingguan" class="form-control" required>
                            <option value="">-- Jam Mulai --</option>
                        </select>
                    </div>
                    <span style="color: var(--text-muted); font-weight: var(--font-bold);">s/d</span>
                    <div>
                        <select name="jam_ke_selesai" id="selectJamKeSelesaiMingguan" class="form-control" required>
                            <option value="">-- Jam Selesai --</option>
                        </select>
                    </div>
                </div>
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    <i class="fas fa-circle-info text-primary me-1"></i> Pilih rentang jam praktik, mis. Jam ke-1 s/d Jam ke-4 untuk 4 JP sekaligus.
                </div>
            </div>

            {{-- 5. Hari Praktik (otomatis dari slot matriks / tanggal) --}}
            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    <i class="fas fa-calendar-day text-primary me-1"></i> Hari Praktik
                </label>
                <input type="text" name="hari[]" id="inputHariMingguan" class="form-control" value="Senin" readonly
                    style="background: var(--bg-hover); cursor: not-allowed;" tabindex="-1">
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    <i class="fas fa-circle-info text-primary me-1"></i> Otomatis mengikuti slot hari yang dipilih pada matriks.
                </div>
            </div>

            {{-- 6. Penentuan Minggu Ke- & Tanggal --}}
            <div style="margin-bottom: var(--space-8); padding: var(--space-5); background: rgba(99,102,241,0.05); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius-md);">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-5); align-items: end;">
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                            Minggu Ke- <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="pemberlakuan_minggu" id="selectPemberlakuanMingguM" class="form-control">
                            @for ($m = 1; $m <= 24; $m++)
                                <option value="{{ $m }}">Minggu ke-{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                            Tanggal Awal <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="date" name="tanggal" id="inputTanggalMingguan" class="form-control" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">
                            Sampai Minggu Ke-
                        </label>
                        <select name="pemberlakuan_minggu_akhir" id="selectPemberlakuanMingguAkhirM" class="form-control">
                            @for ($m = 1; $m <= 24; $m++)
                                <option value="{{ $m }}">Minggu ke-{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                <button type="button" class="btn btn-secondary btn-cancel-modal-jadwal" data-target="modalJadwalMingguan">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Jadwal Mingguan</button>
            </div>
        </form>
    </div>
</div>
