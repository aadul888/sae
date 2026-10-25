    <!-- Modal Terpadu: Pengaturan Waktu KBM & Auto-Generate Jadwal KBM -->
    @php
        $currentSkema = $pengaturan->skema_hari ?? '5_hari';
        $dailySlotsMap = \App\Models\JadwalPengaturan::getDailySlotCounts();
        $tingkatDailySlots =
            $tingkatDailySlots ?? \App\Models\JadwalPengaturan::getTingkatDailySlotCounts($currentSkema);
        $jpTingkatSettings = \App\Models\JadwalPengaturan::getJpTingkat();
        $breaks = $pengaturan->istirahat ?? [
            ['aktif' => true, 'jam_ke' => 8, 'durasi_menit' => 30, 'nama' => 'Istirahat'],
        ];
        $b1 = $breaks[0] ?? ['aktif' => true, 'jam_ke' => 8, 'durasi_menit' => 30, 'nama' => 'Istirahat'];
        $isB1Active = !isset($b1['aktif']) || !empty($b1['aktif']);
        $upacaraConfig = $pengaturan->upacara ?? [
            'aktif' => true,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'durasi_menit' => 45,
            'nama' => 'Upacara Bendera',
        ];
        $pembiasaanConfig = $pengaturan->pembiasaan ?? [
            'aktif' => true,
            'hari' => 'Jumat',
            'jam_ke' => 1,
            'durasi_menit' => 40,
            'nama' => 'Pembiasaan',
        ];
    @endphp

    <div id="modalAturDanGenerate" class="sae-modal"
        data-route-pengaturan="{{ route('dashboard.jadwal-kbm.pengaturan') }}"
        data-route-auto-generate="{{ route('dashboard.jadwal-kbm.auto-generate') }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 20px 14px;">
        <div class="card modal-atur-card"
            style="width: 95vw; max-width: 1320px; max-height: 90vh; overflow-y: auto; margin: auto; padding: 24px 28px; border-radius: var(--radius-xl, 16px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
            <!-- Modal Header -->
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <span
                        style="background: linear-gradient(135deg, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-wand-magic-sparkles"></i> Atur Waktu &amp; Generate Jadwal KBM
                    </span>
                </h3>
                <button type="button" id="btnTutupModalAturDanGenerate"
                    style="background: transparent; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer; padding: 4px 8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Banner Sinkronisasi -->
            <div
                style="padding: 10px 14px; background: rgba(99,102,241,0.08); border-left: 4px solid var(--primary); border-radius: var(--radius-sm, 6px); margin-bottom: 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.45;">
                <strong>Formulir Terpadu AI Scheduler:</strong> Pengaturan jam belajar dan eksekusi AI scheduler otomatis
                tersinkronisasi. Anda dapat <strong>Menyimpan Pengaturan Saja</strong> atau langsung <strong>Generate Jadwal
                    Otomatis</strong> dengan algoritma multi-pass, swap anti-bentrok, dan pemadatan jam tanpa celah kosong.
            </div>

            <form id="formAturDanGenerate">
                @csrf

                <div class="modal-atur-grid"
                    style="display: grid; grid-template-columns: 1.18fr 1fr; gap: 24px; align-items: start;">
                    <!-- KOLOM KIRI: WAKTU & ALOKASI HARIAN -->
                    <div>
                        <!-- SEKSI 1: PILIHAN SKEMA HARI SEKOLAH -->
                        <div style="margin-bottom: 16px;">
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 8px; color: var(--text-color);">
                                <i class="fas fa-calendar-check text-primary me-1"></i> Skema Hari Sekolah <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <div class="skema-options-grid"
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <!-- Opsi 5 Hari -->
                                <label class="skema-option-card {{ $currentSkema === '5_hari' ? 'active' : '' }}"
                                    style="display: block; position: relative; border: 2px solid {{ $currentSkema === '5_hari' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: var(--radius-lg, 12px); padding: 14px; background: {{ $currentSkema === '5_hari' ? 'rgba(99, 102, 241, 0.06)' : 'var(--bg-card)' }}; cursor: pointer; transition: all 0.2s ease;">
                                    <input type="radio" name="skema_hari" value="5_hari"
                                        {{ $currentSkema === '5_hari' ? 'checked' : '' }}
                                        style="position: absolute; top: 14px; right: 14px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <i class="fas fa-calendar-week text-primary" style="font-size: var(--text-xl, 1.25rem);"></i>
                                        <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">5 Hari
                                            Sekolah</strong>
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        Senin – Jumat (SMK Negeri / Full Day)
                                    </div>
                                </label>

                                <!-- Opsi 6 Hari -->
                                <label class="skema-option-card {{ $currentSkema === '6_hari' ? 'active' : '' }}"
                                    style="display: block; position: relative; border: 2px solid {{ $currentSkema === '6_hari' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: var(--radius-lg, 12px); padding: 14px; background: {{ $currentSkema === '6_hari' ? 'rgba(99, 102, 241, 0.06)' : 'var(--bg-card)' }}; cursor: pointer; transition: all 0.2s ease;">
                                    <input type="radio" name="skema_hari" value="6_hari"
                                        {{ $currentSkema === '6_hari' ? 'checked' : '' }}
                                        style="position: absolute; top: 14px; right: 14px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <i class="fas fa-calendar-days text-warning" style="font-size: var(--text-xl, 1.25rem);"></i>
                                        <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">6 Hari
                                            Sekolah</strong>
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        Senin – Sabtu (Umum SMK Swasta)
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- SEKSI 2: WAKTU MULAI & DURASI PER JP -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                            <div class="form-group">
                                <label for="setJamMulai"
                                    style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                                    Jam Mulai KBM (JP 1) <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="time" id="setJamMulai" name="jam_mulai_kbm"
                                    value="{{ substr($pengaturan->jam_mulai_kbm ?? '07:15:00', 0, 5) }}"
                                    class="form-control"
                                    style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="setDurasiJp"
                                    style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                                    Durasi per JP (Menit) <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="number" id="setDurasiJp" name="durasi_per_jp" min="20"
                                    max="90" value="{{ $pengaturan->durasi_per_jp ?? 45 }}" class="form-control"
                                    style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                                    required>
                                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Standar SMK/SMA: 40–45
                                    menit</small>
                            </div>
                        </div>

                        <!-- SEKSI 3: PREVIEW & OVERRIDE SLOT HARIAN -->
                        <div
                            style="margin-bottom: 16px; padding: 12px; background: var(--bg-hover); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                            <div
                                style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                                <span><i class="fas fa-clock text-primary me-1"></i> Alokasi Slot Jam &amp; Waktu Pulang
                                    Harian</span>
                                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Otomatis live update</small>
                            </div>
                            <div class="slot-days-grid"
                                style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px;">
                                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                                    @php
                                        $curJp =
                                            $dailySlotsMap[$dh] ?? ($dh === 'Jumat' ? 6 : ($dh === 'Sabtu' ? 0 : 13));
                                        $curSelesai = \App\Models\JadwalPengaturan::calculateJamSelesai($curJp);
                                    @endphp
                                    <div class="slot-day-card" data-hari="{{ $dh }}"
                                        style="background: var(--bg-card); padding: 8px 4px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); text-align: center;">
                                        <strong
                                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: block; margin-bottom: 4px;">{{ $dh }}</strong>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; gap: 2px; margin-bottom: 4px;">
                                            <input type="number" name="slot_harian[{{ $dh }}][total_jp]"
                                                value="{{ $curJp }}" min="0" max="16"
                                                placeholder="0" class="form-control input-slot-harian"
                                                data-hari="{{ $dh }}"
                                                style="width: 44px; padding: 4px 2px; border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); font-size: var(--text-sm, 0.8125rem); text-align: center; font-weight: 700;">
                                            <span style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">JP</span>
                                        </div>
                                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: 600;">
                                            Selesai: <span class="badge-jam-selesai" data-hari="{{ $dh }}"
                                                style="color: {{ $curJp > 0 ? 'var(--primary)' : '#ef4444' }}; font-weight: 700;">
                                                {{ $curJp > 0 ? $curSelesai : '(Libur)' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- SEKSI 4: WAKTU ISTIRAHAT & RUTIN PAGI (DIATUR WAKTUNYA SEPERTI ISTIRAHAT) -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <!-- Istirahat KBM -->
                            <div
                                style="padding: 12px; background: rgba(245, 158, 11, 0.04); border-radius: var(--radius-md, 8px); border: 1px solid rgba(245, 158, 11, 0.25);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); cursor: pointer; margin: 0;">
                                        <input type="checkbox" id="setIstirahatAktif" name="istirahat[0][aktif]"
                                            value="1" {{ $isB1Active ? 'checked' : '' }}>
                                        <span><i class="fas fa-mug-hot text-warning me-1"></i> Istirahat KBM</span>
                                    </label>
                                    <span class="badge"
                                        style="background: rgba(245,158,11,0.12); color: #f59e0b; font-size: var(--text-2xs, 0.6875rem); font-weight: 700;">Kunci
                                        Slot</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                    <div>
                                        <span
                                            style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                            Jam Ke-:</span>
                                        <input type="number" id="setIstirahatJamKe" name="istirahat[0][jam_ke]"
                                            value="{{ $b1['jam_ke'] ?? 8 }}" min="1" max="16"
                                            style="width: 100%; padding: 5px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;"
                                            required>
                                    </div>
                                    <div>
                                        <span
                                            style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                            (Mnt):</span>
                                        <input type="number" id="setIstirahatDurasi" name="istirahat[0][durasi_menit]"
                                            value="{{ $b1['durasi_menit'] ?? 30 }}" min="5" max="90"
                                            style="width: 100%; padding: 5px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;"
                                            required>
                                    </div>
                                </div>
                                <input type="hidden" name="istirahat[0][nama]" value="Istirahat">
                            </div>

                            <!-- Rutin Pagi: Upacara & Pembiasaan dengan Atur Waktu -->
                            <div
                                style="padding: 12px; background: var(--bg-hover); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                                <div
                                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                                    <span><i class="fas fa-flag text-danger me-1"></i> Rutin Pagi</span>
                                    <span class="badge"
                                        style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: var(--text-2xs, 0.6875rem); font-weight: 700;">Atur
                                        Waktu</span>
                                </div>

                                <!-- Upacara Senin -->
                                <div
                                    style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--border-color);">
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); cursor: pointer; margin-bottom: 4px;">
                                        <input type="checkbox" id="setUpacaraAktif" name="upacara[aktif]" value="1"
                                            {{ !empty($upacaraConfig['aktif']) ? 'checked' : '' }}>
                                        <span>Upacara Bendera (Senin)</span>
                                    </label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                        <div>
                                            <span
                                                style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                                Jam Ke-:</span>
                                            <input type="number" id="setUpacaraJamKe" name="upacara[jam_ke]"
                                                value="{{ $upacaraConfig['jam_ke'] ?? 1 }}" min="1"
                                                max="16"
                                                style="width: 100%; padding: 4px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;">
                                        </div>
                                        <div>
                                            <span
                                                style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                                (Mnt):</span>
                                            <input type="number" id="setUpacaraDurasi" name="upacara[durasi_menit]"
                                                value="{{ $upacaraConfig['durasi_menit'] ?? 45 }}" min="10"
                                                max="90"
                                                style="width: 100%; padding: 4px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;">
                                        </div>
                                    </div>
                                    <input type="hidden" name="upacara[hari]" value="Senin">
                                    <input type="hidden" name="upacara[nama]" value="Upacara Bendera">
                                </div>

                                <!-- Pembiasaan Jumat -->
                                <div>
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); cursor: pointer; margin-bottom: 4px;">
                                        <input type="checkbox" id="setPembiasaanAktif" name="pembiasaan[aktif]"
                                            value="1" {{ !empty($pembiasaanConfig['aktif']) ? 'checked' : '' }}>
                                        <span>Pembiasaan Pagi (Jumat)</span>
                                    </label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                        <div>
                                            <span
                                                style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                                Jam Ke-:</span>
                                            <input type="number" id="setPembiasaanJamKe" name="pembiasaan[jam_ke]"
                                                value="{{ $pembiasaanConfig['jam_ke'] ?? 1 }}" min="1"
                                                max="16"
                                                style="width: 100%; padding: 4px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;">
                                        </div>
                                        <div>
                                            <span
                                                style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                                (Mnt):</span>
                                            <input type="number" id="setPembiasaanDurasi"
                                                name="pembiasaan[durasi_menit]"
                                                value="{{ $pembiasaanConfig['durasi_menit'] ?? 40 }}" min="10"
                                                max="90"
                                                style="width: 100%; padding: 4px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-xs, 4px); background: var(--bg-card); color: var(--text-color); text-align: center;">
                                        </div>
                                    </div>
                                    <input type="hidden" name="pembiasaan[hari]" value="Jumat">
                                    <input type="hidden" name="pembiasaan[nama]" value="Pembiasaan">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: TARGET TINGKAT, OPSI AI & VALIDASI KETAT -->
                    <div>
                        <!-- SEKSI 5: DISTRIBUSI SLOT HARIAN PER TINGKAT (KELAS X, XI, XII) -->
                        <div
                            style="margin-bottom: 16px; padding: 14px; background: rgba(99, 102, 241, 0.04); border-radius: var(--radius-lg, 12px); border: 1px solid rgba(99, 102, 241, 0.25);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    <i class="fas fa-layer-group text-primary me-1"></i> Alokasi Slot Jam Harian per
                                    Tingkat
                                </span>
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: var(--text-2xs, 0.6875rem); font-weight: 700;">
                                    Presisi Bebas Celah Kosong
                                </span>
                            </div>

                            <div style="overflow-x: auto;">
                                <table class="table-tingkat-slots"
                                    style="width: 100%; border-collapse: collapse; font-size: var(--text-sm, 0.8125rem); text-align: center;">
                                    <thead>
                                        <tr
                                            style="background: var(--bg-hover); border-bottom: 2px solid var(--border-color);">
                                            <th
                                                style="padding: 8px 6px; text-align: left; font-weight: 700; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                                HARI</th>
                                            <th class="th-col-tingkat" data-col-tingkat="10"
                                                style="padding: 8px 6px; font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); transition: opacity 0.2s;">
                                                <span class="badge badge-tingkat-header" data-tingkat="10"
                                                    style="background: #3b82f6; color: #fff; padding: 2px 7px; border-radius: var(--radius-xs, 4px);">Kelas
                                                    X</span>
                                            </th>
                                            <th class="th-col-tingkat" data-col-tingkat="11"
                                                style="padding: 8px 6px; font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); transition: opacity 0.2s;">
                                                <span class="badge badge-tingkat-header" data-tingkat="11"
                                                    style="background: #8b5cf6; color: #fff; padding: 2px 7px; border-radius: var(--radius-xs, 4px);">Kelas
                                                    XI</span>
                                            </th>
                                            <th class="th-col-tingkat" data-col-tingkat="12"
                                                style="padding: 8px 6px; font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); transition: opacity 0.2s;">
                                                <span class="badge badge-tingkat-header" data-tingkat="12"
                                                    style="background: #ec4899; color: #fff; padding: 2px 7px; border-radius: var(--radius-xs, 4px);">Kelas
                                                    XII</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                                            @php
                                                $valX =
                                                    $tingkatDailySlots['10'][$dh] ??
                                                    ($dh === 'Sabtu'
                                                        ? 0
                                                        : ($dh === 'Jumat'
                                                            ? 7
                                                            : ($dh === 'Senin'
                                                                ? 13
                                                                : 12)));
                                                $valXI =
                                                    $tingkatDailySlots['11'][$dh] ??
                                                    ($dh === 'Sabtu'
                                                        ? 0
                                                        : ($dh === 'Jumat'
                                                            ? 7
                                                            : ($dh === 'Senin'
                                                                ? 13
                                                                : ($dh === 'Selasa'
                                                                    ? 12
                                                                    : 11))));
                                                $valXII =
                                                    $tingkatDailySlots['12'][$dh] ??
                                                    ($dh === 'Sabtu'
                                                        ? 0
                                                        : ($dh === 'Jumat'
                                                            ? 7
                                                            : ($dh === 'Senin'
                                                                ? 12
                                                                : 11)));
                                            @endphp
                                            <tr class="row-hari-slot" data-hari="{{ $dh }}"
                                                style="border-bottom: 1px solid var(--border-color); {{ $dh === 'Sabtu' && $currentSkema === '5_hari' ? 'display: none;' : '' }}">
                                                <td
                                                    style="padding: 6px; text-align: left; font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                                    {{ $dh }}
                                                </td>
                                                <td class="td-col-tingkat" data-col-tingkat="10"
                                                    style="padding: 6px; transition: opacity 0.2s;">
                                                    <div
                                                        style="display: flex; align-items: center; justify-content: center; gap: 3px;">
                                                        <input type="number"
                                                            name="slot_tingkat_harian[10][{{ $dh }}]"
                                                            value="{{ $valX }}" min="0" max="16"
                                                            class="form-control input-tingkat-slot" data-tingkat="10"
                                                            data-hari="{{ $dh }}"
                                                            style="width: 46px; padding: 3px; text-align: center; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                                                        <span
                                                            style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">JP</span>
                                                    </div>
                                                    <span class="pulang-tingkat-info" data-tingkat="10"
                                                        data-hari="{{ $dh }}"
                                                        style="display: block; font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 2px;"></span>
                                                </td>
                                                <td class="td-col-tingkat" data-col-tingkat="11"
                                                    style="padding: 6px; transition: opacity 0.2s;">
                                                    <div
                                                        style="display: flex; align-items: center; justify-content: center; gap: 3px;">
                                                        <input type="number"
                                                            name="slot_tingkat_harian[11][{{ $dh }}]"
                                                            value="{{ $valXI }}" min="0" max="16"
                                                            class="form-control input-tingkat-slot" data-tingkat="11"
                                                            data-hari="{{ $dh }}"
                                                            style="width: 46px; padding: 3px; text-align: center; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                                                        <span
                                                            style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">JP</span>
                                                    </div>
                                                    <span class="pulang-tingkat-info" data-tingkat="11"
                                                        data-hari="{{ $dh }}"
                                                        style="display: block; font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 2px;"></span>
                                                </td>
                                                <td class="td-col-tingkat" data-col-tingkat="12"
                                                    style="padding: 6px; transition: opacity 0.2s;">
                                                    <div
                                                        style="display: flex; align-items: center; justify-content: center; gap: 3px;">
                                                        <input type="number"
                                                            name="slot_tingkat_harian[12][{{ $dh }}]"
                                                            value="{{ $valXII }}" min="0" max="16"
                                                            class="form-control input-tingkat-slot" data-tingkat="12"
                                                            data-hari="{{ $dh }}"
                                                            style="width: 46px; padding: 3px; text-align: center; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                                                        <span
                                                            style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">JP</span>
                                                    </div>
                                                    <span class="pulang-tingkat-info" data-tingkat="12"
                                                        data-hari="{{ $dh }}"
                                                        style="display: block; font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 2px;"></span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: rgba(99, 102, 241, 0.08); font-weight: 800;">
                                            <td
                                                style="padding: 7px 6px; text-align: left; font-size: var(--text-xs, 0.75rem); color: var(--text-color);">
                                                Total KBM
                                            </td>
                                            <td class="td-col-tingkat" data-col-tingkat="10"
                                                style="padding: 7px 6px; transition: opacity 0.2s;">
                                                <span id="totalKbmX"
                                                    style="color: #3b82f6; font-size: var(--text-base, 0.875rem); font-weight: 800;">50
                                                    JP</span>
                                                <input type="hidden" name="jp_tingkat[10]" id="inputJpTingkat10"
                                                    value="{{ $jpTingkatSettings['10'] ?? 50 }}">
                                            </td>
                                            <td class="td-col-tingkat" data-col-tingkat="11"
                                                style="padding: 7px 6px; transition: opacity 0.2s;">
                                                <span id="totalKbmXI"
                                                    style="color: #8b5cf6; font-size: var(--text-base, 0.875rem); font-weight: 800;">48
                                                    JP</span>
                                                <input type="hidden" name="jp_tingkat[11]" id="inputJpTingkat11"
                                                    value="{{ $jpTingkatSettings['11'] ?? 48 }}">
                                            </td>
                                            <td class="td-col-tingkat" data-col-tingkat="12"
                                                style="padding: 7px 6px; transition: opacity 0.2s;">
                                                <span id="totalKbmXII"
                                                    style="color: #ec4899; font-size: var(--text-base, 0.875rem); font-weight: 800;">46
                                                    JP</span>
                                                <input type="hidden" name="jp_tingkat[12]" id="inputJpTingkat12"
                                                    value="{{ $jpTingkatSettings['12'] ?? 46 }}">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <small
                                style="display: block; font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 8px; line-height: 1.35;">
                                * Total JP KBM murni dihitung otomatis setelah dipotong jam Upacara (Senin), Pembiasaan
                                (Jumat), dan Istirahat harian.
                            </small>
                        </div>

                        <!-- SEKSI 6: OPSI GENERATE OTOMATIS & VALIDASI KETAT -->
                        <div
                            style="padding: 14px; background: rgba(16, 185, 129, 0.04); border-radius: var(--radius-md, 8px); border: 1px solid rgba(16, 185, 129, 0.25);">
                            <div
                                style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-sliders text-success"></i> Opsi Eksekusi Auto-Scheduler
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                                <div>
                                    <label
                                        style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Mode
                                        Pembuatan:</label>
                                    <select name="clear_existing" class="form-control"
                                        style="width: 100%; padding: 7px 10px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);">
                                        <option value="1" selected>Fresh Start (Bersihkan &amp; Buat Ulang)</option>
                                        <option value="0">Hanya Isi Jadwal Kosong (Fill Gaps)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">
                                        Cakupan Tingkat (PKL / Pengecualian):
                                    </label>
                                    <select name="tingkat" id="selectCakupanTingkat" class="form-control"
                                        style="width: 100%; padding: 7px 10px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);">
                                        <option value="" selected>Semua Tingkat (Kelas X, XI, XII)</option>
                                        <option value="no_12">Kecualikan Kelas XII (Sedang PKL)</option>
                                        <option value="no_11">Kecualikan Kelas XI (Sedang PKL)</option>
                                        <option value="no_10">Kecualikan Kelas X</option>
                                        <option value="10">Hanya Kelas X</option>
                                        <option value="11">Hanya Kelas XI</option>
                                        <option value="12">Hanya Kelas XII</option>
                                        <option value="custom">Pilih Tingkat Kustom</option>
                                    </select>
                                    <div id="tingkatCheckboxWrap"
                                        style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                                        <label class="tingkat-pill-label"
                                            style="display: inline-flex; align-items: center; gap: 4px; font-size: var(--text-xs, 0.75rem); background: var(--bg-hover); padding: 2px 7px; border-radius: var(--radius-sm, 6px); cursor: pointer; border: 1px solid var(--border-color);">
                                            <input type="checkbox" name="tingkat_aktif[]" value="10" checked
                                                class="chk-tingkat-scope" data-tingkat="10">
                                            <span style="font-weight: 700; color: #3b82f6;">Kelas X</span>
                                        </label>
                                        <label class="tingkat-pill-label"
                                            style="display: inline-flex; align-items: center; gap: 4px; font-size: var(--text-xs, 0.75rem); background: var(--bg-hover); padding: 2px 7px; border-radius: var(--radius-sm, 6px); cursor: pointer; border: 1px solid var(--border-color);">
                                            <input type="checkbox" name="tingkat_aktif[]" value="11" checked
                                                class="chk-tingkat-scope" data-tingkat="11">
                                            <span style="font-weight: 700; color: #8b5cf6;">Kelas XI</span>
                                        </label>
                                        <label class="tingkat-pill-label"
                                            style="display: inline-flex; align-items: center; gap: 4px; font-size: var(--text-xs, 0.75rem); background: var(--bg-hover); padding: 2px 7px; border-radius: var(--radius-sm, 6px); cursor: pointer; border: 1px solid var(--border-color);">
                                            <input type="checkbox" name="tingkat_aktif[]" value="12" checked
                                                class="chk-tingkat-scope" data-tingkat="12">
                                            <span style="font-weight: 700; color: #ec4899;">Kelas XII</span>
                                            <span class="badge-pkl-status"
                                                style="display: none; background: rgba(239,68,68,0.15); color: #ef4444; font-size: var(--text-2xs, 0.6875rem); padding: 1px 4px; border-radius: var(--radius-xs, 4px); font-weight: 700;">PKL</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <label
                                    style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Maksimal
                                    JP per Sesi Pertemuan:</label>
                                @php
                                    $selectedMaxJp = (int) ($pengaturan->max_jp_per_sesi ?? 3);
                                @endphp
                                <select name="max_jp_per_sesi" class="form-control"
                                    style="width: 100%; padding: 7px 10px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);">
                                    <option value="2" {{ $selectedMaxJp === 2 ? 'selected' : '' }}>Maks 2 JP (Mapel
                                        teori dipecah fleksibel)</option>
                                    <option value="3" {{ $selectedMaxJp === 3 ? 'selected' : '' }}>Maks 3 JP
                                        (Standar seimbang - Direkomendasikan)</option>
                                    <option value="4" {{ $selectedMaxJp === 4 ? 'selected' : '' }}>Maks 4 JP (Sesi
                                        blok 4 JP)</option>
                                    <option value="5" {{ $selectedMaxJp === 5 ? 'selected' : '' }}>Maks 5 JP (Sesi
                                        blok 5 JP)</option>
                                    <option value="6" {{ $selectedMaxJp === 6 ? 'selected' : '' }}>Maks 6 JP (Sesi
                                        blok 6 JP / Praktik Kejuruan)</option>
                                    <option value="7" {{ $selectedMaxJp === 7 ? 'selected' : '' }}>Maks 7 JP (Sesi
                                        blok 7 JP / Praktik Kejuruan)</option>
                                    <option value="8" {{ $selectedMaxJp === 8 ? 'selected' : '' }}>Maks 8 JP (Sesi
                                        blok 8 JP / Praktik Kejuruan)</option>
                                    <option value="9" {{ $selectedMaxJp === 9 ? 'selected' : '' }}>Maks 9 JP (Sesi
                                        blok 9 JP / Full Day Kejuruan)</option>
                                </select>
                            </div>

                            <!-- VALIDASI KETAT CHECKBOX -->
                            <div
                                style="padding: 10px 12px; background: rgba(99, 102, 241, 0.08); border-radius: var(--radius-sm, 6px); border: 1px solid rgba(99, 102, 241, 0.25);">
                                <label
                                    style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="strict_validation" id="chkStrictValidation"
                                        value="1" checked
                                        style="margin-top: 2px; transform: scale(1.15); accent-color: var(--primary);">
                                    <div>
                                        <strong style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: block;">
                                            <i class="fas fa-shield-halved text-primary me-1"></i> Validasi Ketat
                                            (Direkomendasikan)
                                        </strong>
                                        <span
                                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: block; line-height: 1.35;">
                                            Batalkan seluruh proses dan tampilkan rincian jika ada rombel yang belum terisi
                                            penuh atau mapel yang belum terpetakan. Menjamin database tidak menyimpan jadwal
                                            setengah jadi.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL FOOTER DENGAN DUA TOMBOL AKSI -->
                <div class="modal-atur-footer"
                    style="display: flex; justify-content: space-between; align-items: center; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 18px; flex-wrap: wrap;">
                    <div
                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-check-double text-success"></i> Zero-Gap &amp; Kempe-Swap Scheduler aktif
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <button type="button" id="btnBatalAturDanGenerate" class="btn btn-outline"
                            style="padding: 9px 16px; font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="button" id="btnSimpanPengaturanOnly" class="btn btn-outline"
                            style="padding: 9px 18px; font-size: var(--text-base, 0.875rem); border-color: var(--primary); color: var(--primary); font-weight: 700;">
                            <i class="fas fa-save me-1"></i> Simpan Pengaturan Saja
                        </button>
                        <button type="submit" id="btnSimpanDanGenerate" class="btn"
                            style="padding: 9px 22px; font-size: var(--text-base, 0.875rem); background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); color: #fff; font-weight: 700; border: none; box-shadow: 0 4px 14px rgba(99,102,241,0.35);">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Simpan &amp; Mulai Generate Otomatis
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

