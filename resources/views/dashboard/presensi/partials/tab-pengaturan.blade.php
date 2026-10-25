    <div class="presensi-tab-pane" id="tab-pengaturan"
        style="display: {{ $activeTab === 'pengaturan' ? 'block' : 'none' }};">
        <form id="formPengaturanPresensi">
            <!-- Row 1: 2-Column Grid (Jam Kerja vs Geolokasi GPS) -->
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 20px; margin-bottom: 20px;">

                <!-- CARD 1: Konfigurasi Jam Kerja & Toleransi Waktu -->
                <div class="card" style="padding: 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                        <div
                            style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(99, 102, 241, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                                Konfigurasi Jam Kerja &amp; Toleransi
                            </h3>
                            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 2px 0 0 0;">
                                Jam operasional scan masuk, batas toleransi, dan jadwal kepulangan peserta didik.
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Jam Buka Presensi Masuk:
                            </label>
                            <input type="time" name="jam_masuk_mulai"
                                value="{{ substr($pengaturan->jam_masuk_mulai, 0, 5) }}" required class="form-control"
                                style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Waktu awal scanner memproses
                                tap.</small>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Masuk Tepat Waktu:
                            </label>
                            <input type="time" name="jam_masuk_selesai"
                                value="{{ substr($pengaturan->jam_masuk_selesai, 0, 5) }}" required class="form-control"
                                style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Lewat dari ini dihitung
                                Terlambat.</small>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Akhir Scan Pagi:
                            </label>
                            <input type="time" name="jam_masuk_toleransi"
                                value="{{ substr($pengaturan->jam_masuk_toleransi, 0, 5) }}" required
                                class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Batas akhir toleransi scan
                                pagi.</small>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Toleransi Keterlambatan:
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="toleransi_terlambat_menit"
                                    value="{{ $pengaturan->toleransi_terlambat_menit }}" min="0" max="120"
                                    required class="form-control" style="flex: 1;">
                                <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Menit</span>
                            </div>
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Dispensasi sebelum status
                                'T'.</small>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Jam Buka Presensi Pulang:
                            </label>
                            <input type="time" name="jam_pulang_mulai"
                                value="{{ substr($pengaturan->jam_pulang_mulai, 0, 5) }}" required class="form-control"
                                style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Waktu awal scanner
                                kepulangan.</small>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Akhir Presensi Pulang:
                            </label>
                            <input type="time" name="jam_pulang_selesai"
                                value="{{ substr($pengaturan->jam_pulang_selesai, 0, 5) }}" required
                                class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Batas akhir tap
                                kepulangan.</small>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                            <i class="fas fa-calendar-week text-primary me-1"></i> Hari Aktif Belajar Sekolah:
                        </label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            @php
                                $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                $activeDays = is_array($pengaturan->hari_aktif) ? $pengaturan->hari_aktif : [];
                            @endphp
                            @foreach ($days as $day)
                                @php $isDayActive = in_array($day, $activeDays); @endphp
                                <label
                                    style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid {{ $isDayActive ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: var(--radius-md, 8px); background: {{ $isDayActive ? 'rgba(99, 102, 241, 0.1)' : 'transparent' }}; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; transition: all 0.2s ease;">
                                    <input type="checkbox" name="hari_aktif[]" value="{{ $day }}"
                                        {{ $isDayActive ? 'checked' : '' }} style="accent-color: var(--primary);">
                                    {{ $day }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Geolokasi & Radius Kehadiran (GPS) -->
                <div class="card" style="padding: 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                        <div
                            style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                                Geolokasi &amp; Radius Titik Sekolah
                            </h3>
                            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 2px 0 0 0;">
                                Validasi jarak GPS kehadiran dari koordinat pusat satuan pendidikan.
                            </p>
                        </div>
                    </div>

                    <!-- Profil Referensi Sekolah Dapodik -->
                    <div
                        style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px;">
                        <div
                            style="background: var(--primary); color: #fff; width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: flex; align-items: center; justify-content: center; font-size: var(--text-md, 1rem); flex-shrink: 0;">
                            <i class="fas fa-school"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div
                                style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $sekolah->nama ?? 'Satuan Pendidikan' }} <span
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: normal;">(NPSN:
                                    {{ $sekolah->npsn ?? '-' }})</span>
                            </div>
                            <div
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $sekolah->alamat_jalan ?? 'Alamat Sekolah' }}, {{ $sekolah->desa_kelurahan ?? '' }}
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 4px; font-size: var(--text-xs, 0.75rem); flex-wrap: wrap;">
                                <span style="color: var(--primary); font-weight: 600;">
                                    <i class="fas fa-crosshairs me-1"></i> Titik Dapodik:
                                    <span id="labelDapodikCoord" data-lat="{{ $sekolah->lintang ?? '' }}"
                                        data-lon="{{ $sekolah->bujur ?? '' }}">{{ $sekolah->lintang ?? '-' }},
                                        {{ $sekolah->bujur ?? '-' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Switch Wajibkan Geolokasi -->
                    <div
                        style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin-bottom: 16px;">
                        <label
                            style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; gap: 12px;">
                            <div>
                                <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color); display: block;">
                                    Wajibkan Geolokasi Presensi (GPS)
                                </strong>
                                <span
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: block; margin-top: 2px;">
                                    Hanya terima presensi jika berada dalam batas radius meter dari titik sekolah.
                                </span>
                            </div>
                            <input type="checkbox" id="settingRequireLocation" name="require_location"
                                {{ $pengaturan->require_location ? 'checked' : '' }}
                                style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0;">
                        </label>
                    </div>

                    <!-- Pengaturan Radius Meter -->
                    <div style="margin-bottom: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">
                                Radius Kehadiran (Meter):
                            </label>
                            <span id="labelRadiusDisplay" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981);">
                                {{ $effectiveRadius }} Meter
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="radius_meter" id="inputRadiusMeter"
                                value="{{ $effectiveRadius }}" min="10" max="50000" class="form-control"
                                style="flex: 1;" placeholder="100">
                            <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-shrink: 0;">m</span>
                        </div>
                        <!-- Quick Radius Chips -->
                        <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                            @foreach ([50, 100, 200, 500, 1000] as $rPreset)
                                <button type="button" class="btn-radius-chip" data-radius="{{ $rPreset }}"
                                    style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-muted); cursor: pointer; transition: all 0.15s ease;">
                                    {{ $rPreset >= 1000 ? $rPreset / 1000 . ' km' : $rPreset . ' m' }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Koordinat Titik Pusat Sekolah (Lintang & Bujur) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Lintang (Latitude):
                            </label>
                            <input type="text" name="latitude" id="inputLatitude"
                                value="{{ $effectiveLat !== null ? $effectiveLat : '' }}" placeholder="-7.17840000"
                                class="form-control" style="width: 100%; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Bujur (Longitude):
                            </label>
                            <input type="text" name="longitude" id="inputLongitude"
                                value="{{ $effectiveLon !== null ? $effectiveLon : '' }}" placeholder="107.13570000"
                                class="form-control" style="width: 100%; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                        </div>
                    </div>

                    <!-- Tombol Cepat Koordinat -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        <button type="button" id="btnResetToDapodikLocation" class="btn btn-outline"
                            style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem);">
                            <i class="fas fa-rotate-left me-1"></i> Gunakan Titik Dapodik
                        </button>
                        <button type="button" id="btnDetectMyLocation" class="btn btn-outline"
                            style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem);">
                            <i class="fas fa-location-arrow me-1"></i> Deteksi GPS Saya
                        </button>
                        <a href="https://www.google.com/maps?q={{ $effectiveLat }},{{ $effectiveLon }}"
                            id="btnOpenGoogleMaps" target="_blank" rel="noopener noreferrer" class="btn btn-outline"
                            style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem);">
                            <i class="fas fa-arrow-up-right-from-square me-1"></i> Buka Peta
                        </a>
                    </div>
                </div>

            </div>

            <!-- Row 2: Metode Identifikasi & Perangkat Terminal (Full Width Card) -->
            <div class="card" style="padding: 20px 24px; border-radius: var(--radius-xl, 16px); margin-bottom: 20px;">
                <h4
                    style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-id-badge text-primary"></i> Metode Identifikasi &amp; Perangkat Terminal Kiosk
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
                    <label
                        style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingRequireCamera" name="require_camera"
                            {{ $pengaturan->require_camera ? 'checked' : '' }}
                            style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color); display: block;">Snapshot Kamera
                                Live</strong>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Kamera terminal
                                otomatis memotret wajah peserta didik saat tap / scan kartu.</div>
                        </div>
                    </label>

                    <label
                        style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingAllowRfid" name="allow_rfid"
                            {{ $pengaturan->allow_rfid ? 'checked' : '' }}
                            style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color); display: block;">Izinkan
                                Pemindaian RFID Reader</strong>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Menerima tap kartu
                                fisik contactless RFID (kartu pelajar/e-KTP).</div>
                        </div>
                    </label>

                    <label
                        style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingAllowQr" name="allow_qr"
                            {{ $pengaturan->allow_qr ? 'checked' : '' }}
                            style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: var(--text-base, 0.875rem); color: var(--text-color); display: block;">Izinkan
                                Pemindaian QR / Barcode</strong>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Menerima scan
                                barcode atau QR Code digital dari aplikasi mobile peserta didik.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Row 3: Terminal Kiosk Publik & Otorisasi Kode Akses (Permintaan Khusus) -->
            <div class="card"
                style="padding: 24px; border-radius: var(--radius-xl, 16px); margin-bottom: 20px; border-left: 4px solid var(--primary); background: rgba(99, 102, 241, 0.03);">
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h4
                            style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-key text-primary"></i> Akses Terminal Kiosk Publik &amp; Keamanan Scanner
                        </h4>
                        <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0; max-width: 680px;">
                            Layar pemindai absensi dapat dibuka di perangkat gerbang/lobi sekolah secara publik menggunakan
                            <strong>Kode Akses</strong> tanpa perlu login akun admin. Input manual NISN telah dinonaktifkan
                            demi keaslian kehadiran fisik.
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="{{ route('presensi.scan') }}" target="_blank" class="btn btn-primary"
                            style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-arrow-up-right-from-square"></i> Buka Terminal Scan
                        </a>
                        <button type="button" id="btnCopyKioskUrl" class="btn btn-outline"
                            data-url="{{ url('/scan') }}"
                            style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-copy"></i> Salin URL Kiosk
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                    <!-- Input Kode Akses -->
                    <div
                        style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Kode Akses Terminal Kiosk:
                            </label>
                            <span id="kodeAksesSavedBadge" class="badge badge-success"
                                style="display: none; font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                <i class="fas fa-circle-check me-1"></i> Tersimpan Otomatis
                            </span>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <div style="position: relative; flex: 1; min-width: 140px;">
                                <input type="password" id="inputKodeAksesKiosk" name="kode_akses"
                                    value="{{ $pengaturan->kode_akses ?? 'SAE123' }}" class="form-control"
                                    style="width: 100%; font-family: monospace; font-size: var(--text-lg, 1.125rem); font-weight: 800; letter-spacing: 0.1em; padding-right: 42px; text-transform: uppercase;"
                                    placeholder="SAE123" required autocomplete="off" spellcheck="false">
                                <button type="button" id="btnToggleShowKodeAkses"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-md, 1rem);"
                                    title="Lihat/Sembunyikan Kode">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <button type="button" id="btnCopyKodeAkses" class="btn btn-outline"
                                style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;"
                                title="Salin kode akses ke clipboard">
                                <i class="fas fa-copy"></i> Salin
                            </button>
                            <button type="button" id="btnGenerateRandomCode" class="btn btn-primary"
                                style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;"
                                title="Buat kode acak baru & otomatis simpan">
                                <i class="fas fa-dice"></i> Acak &amp; Simpan
                            </button>
                        </div>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 8px;">
                            Klik <strong>Acak &amp; Simpan</strong> untuk membuat kode baru secara instan dan otomatis
                            menyimpannya ke database. Berikan kode ini kepada petugas gerbang/satpam untuk membuka terminal.
                        </small>
                    </div>

                    <!-- Informasi URL & Status Proteksi -->
                    <div
                        style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 16px; display: flex; flex-direction: column; justify-content: center;">
                        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                            <i class="fas fa-link text-primary me-1"></i> Tautan Publik Kiosk Gerbang:
                        </div>
                        <div
                            style="font-family: monospace; font-size: var(--text-base, 0.875rem); color: #38bdf8; background: rgba(56, 189, 248, 0.08); padding: 6px 12px; border-radius: var(--radius-md, 8px); border: 1px solid rgba(56, 189, 248, 0.2); margin-bottom: 8px; word-break: break-all;">
                            {{ url('/scan') }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--success, #10b981); display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Input manual dikunci otomatis (Hanya RFID USB &amp; Kamera
                            Barcode/QR).
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Kompetensi Keahlian (Jurusan) yang Diizinkan Presensi (Full Width Card) -->
            <div class="card" style="padding: 24px; border-radius: var(--radius-xl, 16px); margin-bottom: 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4
                            style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0 0 2px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-graduation-cap text-primary"></i> Kompetensi Keahlian (Jurusan) yang Diizinkan
                            Presensi
                        </h4>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                            Pilih jurusan yang aktif melakukan absensi gerbang/harian (misal: non-aktifkan jurusan yang
                            sedang PKL / Praktik Kerja Lapangan).
                        </small>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" id="btnSelectAllJurusan" class="btn btn-outline"
                            style="padding: 5px 12px; font-size: var(--text-xs, 0.75rem);">
                            <i class="fas fa-check-double me-1"></i> Pilih Semua
                        </button>
                        <button type="button" id="btnDeselectAllJurusan" class="btn btn-outline"
                            style="padding: 5px 12px; font-size: var(--text-xs, 0.75rem);">
                            <i class="fas fa-times me-1"></i> Hapus Semua
                        </button>
                    </div>
                </div>

                @php
                    $activeJurusans = is_array($pengaturan->jurusan_aktif) ? $pengaturan->jurusan_aktif : [];
                    $isAllJurusan = empty($activeJurusans);
                @endphp

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                    @foreach ($jurusanList ?? [] as $j)
                        @php
                            $isChecked =
                                $isAllJurusan ||
                                in_array((string) $j->kode, array_map('strval', $activeJurusans), true);
                        @endphp
                        <label
                            style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.015); cursor: pointer; transition: all 0.2s ease;">
                            <input type="checkbox" name="jurusan_aktif[]" value="{{ $j->kode }}"
                                class="chk-jurusan" {{ $isChecked ? 'checked' : '' }}
                                style="width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--primary);">
                            <img src="{{ $j->logo_url ?: asset('img/logo-dark.png') }}" alt="{{ $j->nama }}"
                                style="width: 34px; height: 34px; border-radius: var(--radius-sm, 6px); object-fit: contain; flex-shrink: 0; background: rgba(255,255,255,0.05); padding: 2px;"
                                onerror="this.src='/img/logo-dark.png';">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="{{ $j->nama }}">
                                    {{ $j->nama }}
                                </div>
                                <div
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; gap: 8px; margin-top: 2px;">
                                    <span style="font-family: monospace;">{{ $j->kode }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $j->total_rombel }} Rombel</span>
                                    <span>&bull;</span>
                                    <span>{{ $j->total_siswa }} Peserta Didik</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Row 4: Action Bar -->
            <div class="card"
                style="padding: 16px 24px; border-radius: var(--radius-xl, 16px); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); flex-wrap: wrap; gap: 12px;">
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <i class="fas fa-shield-halved text-primary me-1"></i> Perubahan konfigurasi akan langsung diterapkan
                    ke seluruh terminal &amp; sistem presensi.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 10px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Simpan Konfigurasi Presensi
                    </button>
                </div>
            </div>
        </form>
    </div>
