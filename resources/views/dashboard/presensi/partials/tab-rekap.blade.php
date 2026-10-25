    <div class="presensi-tab-pane" id="tab-rekap" style="display: {{ $activeTab === 'rekap' ? 'block' : 'none' }};">
        <div class="presensi-rekap-card">
            <div
                style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                <div>
                    <h3
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-pdf text-danger"></i> Cetak Laporan &amp; Rekapitulasi Presensi Bulanan
                    </h3>
                    <p style="font-size: var(--text-base, 0.875rem); color: var(--text-muted); margin: 0;">
                        Cetak dan unduh rekapitulasi kehadiran peserta didik dalam format dokumen resmi PDF (standar cetak
                        A4 Landscape) lengkap dengan kop surat dan pengesahan.
                    </p>
                </div>
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: var(--radius-2xl, 20px); font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--danger);">
                    <i class="fas fa-print"></i> Format Dokumen Resmi PDF
                </div>
            </div>

            <form action="{{ route('dashboard.presensi.export') }}" method="GET" target="_blank">
                <div class="presensi-rekap-filter-grid">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            <i class="fas fa-school text-primary me-1"></i> Pilih Rombongan Belajar:
                        </label>
                        <select name="rombel_id" class="form-control" style="width: 100%; padding: 10px 14px;">
                            <option value="">-- Semua Kelas / Seluruh Sekolah --</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> Pilih Bulan &amp; Tahun:
                        </label>
                        <input type="month" name="bulan" value="{{ now()->format('Y-m') }}" class="form-control"
                            style="width: 100%; padding: 10px 14px;">
                    </div>

                    <div class="rekap-btn-col" style="display: flex; gap: 8px;">
                        <button type="submit" name="format" value="pdf" class="btn btn-primary"
                            style="padding: 10px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
                            <i class="fas fa-print"></i> Cetak Laporan (PDF)
                        </button>
                    </div>
                </div>
            </form>

            <!-- Feature / Panduan Highlights Grid (Memanfaatkan Ruang Layar Secara Rapi) -->
            <div class="presensi-rekap-feature-grid">
                <div class="rekap-feature-box">
                    <div style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 2px;">
                            Kop Surat &amp; Tanda Tangan</div>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.35;">Menampilkan kop resmi
                            satuan pendidikan, logo sekolah, dan kolom tanda tangan Kepala Sekolah serta Wali Kelas.</div>
                    </div>
                </div>

                <div class="rekap-feature-box">
                    <div style="font-size: var(--text-2xl, 1.5rem); color: var(--success);"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 2px;">
                            Sinkron Hari Efektif Belajar</div>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.35;">Persentase kehadiran
                            dihitung akurat berdasarkan akumulasi Hari Efektif Belajar (HEB) kalender akademik berjalan.
                        </div>
                    </div>
                </div>

                <div class="rekap-feature-box">
                    <div style="font-size: var(--text-2xl, 1.5rem); color: var(--purple);"><i class="fas fa-table-list"></i></div>
                    <div>
                        <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 2px;">
                            Rincian 6 Parameter Presensi</div>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.35;">Memuat rincian Hadir
                            (H), Terlambat (T), Izin (I), Sakit (S), Dispensasi (D), dan Alpha (A) per peserta didik.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Manajemen Kartu RFID -->
