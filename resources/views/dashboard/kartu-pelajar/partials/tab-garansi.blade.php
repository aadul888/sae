        <!-- TAB 4: Ketentuan & Kuota Garansi Kartu Pelajar -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['total_klaim_kartu'], 0, ',', '.') }} kali
                    </div>
                    <div class="dash-stat-label">Total Klaim Ganti Kartu Full (Fisik &amp; Stiker)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-sticky-note"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                        {{ number_format($stats['total_klaim_stiker'], 0, ',', '.') }} kali
                    </div>
                    <div class="dash-stat-label">Total Klaim Cetak Stiker Saja</div>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan Kuota & Ketentuan -->
        <div class="card" style="padding: 24px; max-width: 860px; margin-bottom: 24px;">
            <div
                style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                <div
                    style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Pengaturan Kuota &amp; Ketentuan Garansi Kartu Pelajar
                    </h3>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Tentukan jatah garansi gratis per siswa serta kebijakan teknis pergantian kartu fisik atau stiker.
                    </div>
                </div>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST">
                @csrf
                <input type="hidden" name="subtab" value="garansi">

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 20px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Kuota Ganti Kartu Penuh (Fisik PVC/RFID + Stiker) <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" name="kuota_ganti_kartu_full"
                                value="{{ old('kuota_ganti_kartu_full', $settings->kuota_ganti_kartu_full ?? 1) }}"
                                min="0" max="10" required class="form-control"
                                style="width: 100px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; text-align: center;">
                            <span style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">kali per peserta didik</span>
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                            Jatah penggantian satu paket kartu fisik utuh jika kartu patah/hilang.
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Kuota Cetak Ulang Stiker Kartu Saja <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" name="kuota_ganti_stiker"
                                value="{{ old('kuota_ganti_stiker', $settings->kuota_ganti_stiker ?? 3) }}"
                                min="0" max="20" required class="form-control"
                                style="width: 100px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; text-align: center;">
                            <span style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">kali per peserta didik</span>
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                            Jatah cetak ulang stiker jika pudar/terkelupas tanpa ganti kartu fisik RFID.
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Ketentuan &amp; SOP Garansi Kartu Pelajar
                    </label>
                    <textarea name="ketentuan_garansi" rows="5" class="form-control"
                        placeholder="Uraikan ketentuan garansi kartu pelajar resmi sekolah..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); line-height: 1.5;">{{ old('ketentuan_garansi', $settings->ketentuan_garansi ?: \App\Models\KartuPelajarPengaturan::getDefaultGaransiKetentuan()) }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: var(--text-base, 0.875rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan Garansi
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat Log Klaim Garansi -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4
                    style="margin: 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-history text-primary"></i> Riwayat Pengajuan Klaim Garansi Siswa
                </h4>
            </div>

            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tanggal Klaim</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik</th>
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kelas / Rombel</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Jenis Klaim</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Alasan Klaim</th>
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Petugas Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($garansiLogs && $garansiLogs->count() > 0)
                        @foreach ($garansiLogs as $gIdx => $log)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="text-align: center; font-size: var(--text-base, 0.875rem); color: var(--text-muted); padding: 12px 14px;">
                                    {{ $garansiLogs->firstItem() + $gIdx }}
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($log->tanggal_klaim)->translatedFormat('d F Y H:i') }}
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                        {{ $log->nama_siswa ?? '-' }}</div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                        NISN: {{ $log->nisn ?: '-' }}</div>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                                        {{ $log->rombel_siswa ?? '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;">
                                    @if ($log->jenis_klaim === 'ganti_kartu_full')
                                        <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">
                                            <i class="fas fa-id-card me-1"></i> Ganti Kartu Full
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">
                                            <i class="fas fa-sticky-note me-1"></i> Cetak Stiker
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                    {{ $log->alasan }}
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    <i class="fas fa-user-shield me-1 text-primary"></i> {{ $log->petugas ?: 'Admin' }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-shield-alt mb-2"
                                    style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
                                <div>Belum ada riwayat pengajuan klaim garansi kartu pelajar.</div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($garansiLogs && $garansiLogs->hasPages())
            <div style="margin-bottom: 24px;">
                {{ $garansiLogs->links() }}
            </div>
        @endif
