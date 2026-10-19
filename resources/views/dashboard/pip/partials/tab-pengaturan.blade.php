@php
    $info = $pipStatusInfo ?? \App\Models\PipPengaturan::getStatusInfo($activeTahun ?? null);
    $settings = $pipPengaturan ?? \App\Models\PipPengaturan::getSettings($activeTahun ?? null);
    $rawDeadline = $settings->batas_waktu ? \Carbon\Carbon::parse($settings->batas_waktu)->format('Y-m-d\TH:i') : '';
@endphp

<div class="card" style="padding: 24px; margin-bottom: 24px; border-radius: 14px;">
    <div
        style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 22px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3
                style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-sliders text-primary"></i> Pengaturan Kunci &amp; Batas Waktu Usulan PIP
            </h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 4px 0 0 0;">
                Kontrol akses pengisian usulan bagi seluruh Wali Kelas pada Tahun Anggaran
                <strong>{{ $activeTahun }}</strong>.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="badge {{ $info['is_closed'] ?? false ? 'badge-danger' : 'badge-success' }}"
                style="font-size: 0.78rem; padding: 5px 12px; font-weight: 700; border-radius: 20px;">
                <i class="fas {{ $info['is_closed'] ?? false ? 'fa-lock' : 'fa-lock-open' }} me-1"></i>
                {{ $info['status_label'] ?? 'Status Aktif' }}
            </span>
        </div>
    </div>

    <!-- Live Preview Status Saat Ini -->
    <div
        style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(99,102,241,0.05); border: 1px dashed rgba(99,102,241,0.25);">
        <div
            style="font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-eye text-primary"></i> Tinjauan Status Pengisian Wali Kelas Saat Ini:
        </div>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; font-size: 0.82rem;">
            <div>
                <span style="color: var(--text-muted);">Status Penguncian:</span><br>
                @if ($info['is_locked'])
                    <strong style="color: #ef4444;"><i class="fas fa-lock me-1"></i> Terkunci Manual oleh Admin</strong>
                @elseif ($info['is_expired'])
                    <strong style="color: #ef4444;"><i class="fas fa-clock-rotate-left me-1"></i> Batas Waktu Telah
                        Berakhir</strong>
                @else
                    <strong style="color: #10b981;"><i class="fas fa-lock-open me-1"></i> Terbuka (Dapat Diisi Wali
                        Kelas)</strong>
                @endif
            </div>
            <div>
                <span style="color: var(--text-muted);">Batas Waktu Pengisian:</span><br>
                <strong style="color: var(--text-color);">{{ $info['deadline_formatted'] }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Kunci Otomatis (Auto-Lock):</span><br>
                <strong style="color: {{ $settings->kunci_otomatis ? '#10b981' : '#f59e0b' }};">
                    {{ $settings->kunci_otomatis ? 'Aktif (Otomatis Kunci Saat Waktu Habis)' : 'Non-Aktif (Manual Saja)' }}
                </strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Terakhir Diperbarui:</span><br>
                <strong style="color: var(--text-color);">
                    {{ $info['updated_by'] ?: 'Sistem' }} &bull; {{ $info['updated_at'] ?: '-' }}
                </strong>
            </div>
        </div>
    </div>

    <!-- Form Pengaturan -->
    <form id="formPengaturanPip" class="kp-modal-form" action="{{ route('dashboard.pip.update-pengaturan') }}"
        method="POST">
        @csrf
        <input type="hidden" name="tahun" value="{{ $activeTahun }}">

        <!-- 1. Identitas Usulan & Fase -->
        <div class="kp-form-group"
            style="margin-bottom: 22px; padding: 18px; border: 1px solid var(--border-color); border-radius: 12px; background: rgba(0,0,0,0.015);">
            <label class="kp-form-label"
                style="font-size: 0.92rem; font-weight: 800; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-heading text-primary"></i> 1. Identitas, Judul Usulan &amp; Fase Pelaksanaan
            </label>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="kp-form-label" for="pipJudulUsulan" style="font-size: 0.82rem; font-weight: 700;">
                        Judul Usulan PIP <span class="req">*</span>
                    </label>
                    <input type="text" id="pipJudulUsulan" name="judul_usulan" class="form-control"
                        value="{{ $settings->judul_usulan ?: $info['judul_usulan'] }}"
                        placeholder="Contoh: Usulan PIP 2026 — Fase 1 (Januari - Juni)" required
                        style="font-weight: 700; font-size: 0.9rem;">
                    <div class="kp-form-hint" style="margin-top: 4px;">
                        Judul ini tampil pada banner hitungan mundur, kartu usulan, dan tercatat permanen pada riwayat
                        siswa.
                    </div>
                </div>

                <div>
                    <label class="kp-form-label" for="pipTahunPelajaran" style="font-size: 0.82rem; font-weight: 700;">
                        Tahun Pelajaran (TP)
                    </label>
                    <input type="text" id="pipTahunPelajaran" name="tahun_pelajaran" class="form-control"
                        value="{{ $settings->tahun_pelajaran ?: $info['tahun_pelajaran'] ?? '' }}"
                        placeholder="Contoh: 2025/2026 atau 2026/2027"
                        style="font-weight: 700; font-size: 0.9rem; max-width: 220px;">
                    <div class="kp-form-hint" style="margin-top: 4px;">
                        Identitas tahun ajaran saat usulan didaftarkan.
                    </div>
                </div>
            </div>

            <div>
                <label class="kp-form-label" style="font-size: 0.82rem; font-weight: 700; margin-bottom: 8px;">
                    Pilih Fase Pengusulan:
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px;">
                    <label
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1.5px solid {{ ($settings->fase ?? 'fase_1') === 'fase_1' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 8px; cursor: pointer; background: {{ ($settings->fase ?? 'fase_1') === 'fase_1' ? 'rgba(99,102,241,0.06)' : 'transparent' }};">
                        <input type="radio" name="fase" value="fase_1"
                            {{ ($settings->fase ?? 'fase_1') === 'fase_1' ? 'checked' : '' }}
                            onchange="pipOnFaseSelect('fase_1', 'Fase 1 (Januari - Juni)')">
                        <div>
                            <strong style="font-size: 0.86rem; color: var(--text-color);">Fase 1 (Januari -
                                Juni)</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Semester Genap / Awal Tahun</div>
                        </div>
                    </label>

                    <label
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1.5px solid {{ ($settings->fase ?? 'fase_1') === 'fase_2' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 8px; cursor: pointer; background: {{ ($settings->fase ?? 'fase_1') === 'fase_2' ? 'rgba(99,102,241,0.06)' : 'transparent' }};">
                        <input type="radio" name="fase" value="fase_2"
                            {{ ($settings->fase ?? 'fase_1') === 'fase_2' ? 'checked' : '' }}
                            onchange="pipOnFaseSelect('fase_2', 'Fase 2 (Juli - Desember)')">
                        <div>
                            <strong style="font-size: 0.86rem; color: var(--text-color);">Fase 2 (Juli -
                                Desember)</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Semester Ganjil / Akhir Tahun
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 2. Master Lock (Kunci Manual Langsung) -->
        <div class="kp-form-group" style="margin-bottom: 20px;">
            <label class="kp-form-label" style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">
                2. Status Penguncian Manual (Master Lock) <span class="req">*</span>
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                <label
                    style="display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1.5px solid {{ !$settings->is_locked ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; cursor: pointer; background: {{ !$settings->is_locked ? 'rgba(99,102,241,0.06)' : 'transparent' }};">
                    <input type="radio" name="is_locked" value="0" {{ !$settings->is_locked ? 'checked' : '' }}
                        style="margin-top: 3px;">
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: #10b981;">
                            <i class="fas fa-lock-open me-1"></i> Buka Pengusulan (Terbuka)
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Wali Kelas diizinkan menambah usulan baru dan menyesuaikan urutan prioritas selama batas
                            waktu belum habis.
                        </div>
                    </div>
                </label>

                <label
                    style="display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1.5px solid {{ $settings->is_locked ? '#ef4444' : 'var(--border-color)' }}; border-radius: 10px; cursor: pointer; background: {{ $settings->is_locked ? 'rgba(239,68,68,0.06)' : 'transparent' }};">
                    <input type="radio" name="is_locked" value="1" {{ $settings->is_locked ? 'checked' : '' }}
                        style="margin-top: 3px;">
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: #ef4444;">
                            <i class="fas fa-lock me-1"></i> Kunci Pengusulan Sekarang
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Langsung kunci seluruh pengusulan untuk Wali Kelas tanpa menunggu batas waktu. Form dan
                            penyesuaian usulan dinonaktifkan.
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- 3. Batas Waktu Pengisian (Deadline Datetime) -->
        <div class="kp-form-group" style="margin-bottom: 20px;">
            <label class="kp-form-label" for="pipBatasWaktu"
                style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">
                3. Batas Akhir Waktu Pengisian Usulan (Tanggal &amp; Jam)
            </label>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="datetime-local" id="pipBatasWaktu" name="batas_waktu" class="form-control"
                    value="{{ $rawDeadline }}" style="font-weight: 700; font-size: 0.95rem; max-width: 300px;">

                <!-- Tombol Preset Cepat -->
                <div style="display: inline-flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="pipSetPresetDeadline(3)"
                        style="padding: 6px 12px; font-size: 0.76rem; font-weight: 600;">
                        +3 Hari (23:59)
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="pipSetPresetDeadline(7)"
                        style="padding: 6px 12px; font-size: 0.76rem; font-weight: 600;">
                        +7 Hari (23:59)
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="pipSetPresetDeadline(14)"
                        style="padding: 6px 12px; font-size: 0.76rem; font-weight: 600;">
                        +14 Hari (23:59)
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="pipSetPresetDeadlineEOM()"
                        style="padding: 6px 12px; font-size: 0.76rem; font-weight: 600;">
                        Akhir Bulan Ini
                    </button>
                    <button type="button" class="btn btn-outline btn-sm text-danger" onclick="pipClearDeadline()"
                        style="padding: 6px 10px; font-size: 0.76rem; border-color: rgba(239,68,68,0.3);"
                        title="Hapus Batas Waktu">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="kp-form-hint" style="margin-top: 6px;">
                Batas waktu ini akan dihitung mundur secara real-time pada halaman Wali Kelas dan Dashboard PIP Admin.
            </div>
        </div>

        <!-- 4. Kunci Otomatis saat batas waktu habis -->
        <div class="kp-form-group" style="margin-bottom: 20px;">
            <label
                style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(0,0,0,0.02);">
                <input type="checkbox" name="kunci_otomatis" value="1"
                    {{ $settings->kunci_otomatis ? 'checked' : '' }} style="width: 18px; height: 18px;">
                <div>
                    <strong style="font-size: 0.88rem; color: var(--text-color);">Aktifkan Penguncian Otomatis
                        (Auto-Lock saat Waktu Habis)</strong>
                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                        Bila dicentang, sistem otomatis mengunci akses pengisian usulan bagi Wali Kelas saat batas waktu
                        di atas telah terlewati.
                    </div>
                </div>
            </label>
        </div>

        <!-- 5. Pesan Instruksi / Pengumuman untuk Wali Kelas -->
        <div class="kp-form-group" style="margin-bottom: 24px;">
            <label class="kp-form-label" for="pipPesanPengumuman"
                style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">
                5. Pesan Instruksi / Pengumuman Khusus untuk Wali Kelas (Opsional)
            </label>
            <textarea id="pipPesanPengumuman" name="pesan_pengumuman" rows="3" class="form-control"
                placeholder="Contoh: Mohon selesaikan pengisian usulan PIP sebelum batas waktu di atas untuk proses verifikasi ke SiPintar...">{{ $settings->pesan_pengumuman }}</textarea>
            <div class="kp-form-hint">
                Pesan ini akan tampil di banner pengusulan halaman Wali Kelas tepat di bawah hitungan mundur.
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="submit" class="btn btn-primary" id="btnSimpanPengaturanPip"
                style="padding: 10px 24px; font-weight: 700;">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan PIP
            </button>
        </div>
    </form>
</div>
