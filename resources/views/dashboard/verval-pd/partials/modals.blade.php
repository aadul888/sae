{{-- MODAL: Verifikasi Berkas Digital Siswa (PDF) --}}
<div id="modalVerifikasiBerkasDigital" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="max-width: 98vw; width: 98vw; height: 92vh; max-height: 92vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h3 id="modalVbNama"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Validasi Berkas Persyaratan Siswa</h3>
                    <div id="modalVbInfo"
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Memuat data siswa...
                    </div>
                </div>
            </div>
            <button type="button" class="close-modal" data-target="#modalVerifikasiBerkasDigital"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;"
                aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="modalVbLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
            <div>Memuat berkas siswa...</div>
        </div>

        <div id="modalVbContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;"></div>

        <div
            style="display: flex; justify-content: flex-end; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
            <button type="button" class="btn btn-outline close-modal" data-target="#modalVerifikasiBerkasDigital"
                style="border-radius: var(--radius-md, 8px); padding: 7px 16px; font-size: var(--text-base, 0.875rem);">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- MODAL: Pengelolaan Usulan Perubahan Data (Field Asal vs Penyesuaian) --}}
<div id="modalPengelolaanUsulan" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="max-width: 860px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0; overflow: hidden; border: 1px solid var(--border-color);">
                    <img id="modalUsulanFoto" src="" alt="Foto Siswa"
                        style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="modalUsulanFotoDefault"
                        style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <i class="fas fa-file-pen"></i>
                    </div>
                </div>
                <div>
                    <h3 id="modalUsulanNama"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Pengelolaan Usulan Perubahan Data</h3>
                    <div id="modalUsulanSubtitle"
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Memuat data usulan siswa...
                    </div>
                </div>
            </div>
            <button type="button" class="close-modal close-modal-pengelolaan-usulan"
                data-target="#modalPengelolaanUsulan"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;"
                aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="modalUsulanLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
            <div>Memuat rincian usulan perubahan data...</div>
        </div>

        <div id="modalUsulanContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
            <div id="modalUsulanAlasanBox" style="display: none; margin-bottom: 10px;"></div>

            <div
                style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; background: var(--bg-hover); padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px);">
                <span
                    style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Berkas:</span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <div
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-xs, 0.75rem); background: var(--card-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                        <span style="font-weight: 600;">KK:</span>
                        <span id="docKkStatusBadge"></span>
                        <button type="button" id="docKkPreviewBtn" class="btn-icon" title="Lihat KK"
                            style="width: 20px; height: 20px; border: none; background: transparent; color: var(--primary); cursor: pointer; padding: 0;">
                            <i class="fas fa-eye" style="font-size: var(--text-2xs, 0.7rem);"></i>
                        </button>
                    </div>
                    <div
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-xs, 0.75rem); background: var(--card-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                        <span style="font-weight: 600;">Ijazah:</span>
                        <span id="docIjazahStatusBadge"></span>
                        <button type="button" id="docIjazahPreviewBtn" class="btn-icon" title="Lihat Ijazah"
                            style="width: 20px; height: 20px; border: none; background: transparent; color: var(--primary); cursor: pointer; padding: 0;">
                            <i class="fas fa-eye" style="font-size: var(--text-2xs, 0.7rem);"></i>
                        </button>
                    </div>
                    <div
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-xs, 0.75rem); background: var(--card-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                        <span style="font-weight: 600;">Akta:</span>
                        <span id="docAktaStatusBadge"></span>
                        <button type="button" id="docAktaPreviewBtn" class="btn-icon" title="Lihat Akta"
                            style="width: 20px; height: 20px; border: none; background: transparent; color: var(--primary); cursor: pointer; padding: 0;">
                            <i class="fas fa-eye" style="font-size: var(--text-2xs, 0.7rem);"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div
                style="margin-bottom: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); overflow: hidden;">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 30%;">
                                Data</th>
                            <th
                                style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 26%;">
                                Data Awal</th>
                            <th
                                style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 28%;">
                                Data Baru</th>
                            <th
                                style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 16%;">
                                <div
                                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                    <span>Verifikasi</span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <button type="button" id="btnCeklisSemuaUsulan" class="btn-icon"
                                            title="Tandai semua Setujui"
                                            style="width: 24px; height: 24px; border-radius: 50%; border: 1.5px solid rgba(16,185,129,0.4); background: transparent; color: var(--success, #10b981); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                                            <i class="fas fa-check" style="font-size: var(--text-2xs, 0.65rem);"></i>
                                        </button>
                                        <button type="button" id="btnXSemuaUsulan" class="btn-icon"
                                            title="Tandai semua Tolak"
                                            style="width: 24px; height: 24px; border-radius: 50%; border: 1.5px solid rgba(239,68,68,0.4); background: transparent; color: var(--danger, #ef4444); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                                            <i class="fas fa-xmark" style="font-size: var(--text-2xs, 0.65rem);"></i>
                                        </button>
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="modalUsulanTableBody"></tbody>
                </table>
            </div>

            <div
                style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin-bottom: 10px;">
                <div
                    style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                    Rekomendasi Alasan / Catatan Penolakan</div>
                <div id="rekomendasiAlasanChips"
                    style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;"></div>
                <textarea id="catatanVerifikasiUsulanInput" class="form-control" rows="2"
                    placeholder="Catatan verifikasi atau alasan penolakan (opsional untuk setujui, wajib jika tolak)..."
                    style="width: 100%; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);"></textarea>
            </div>
        </div>

        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); gap: 10px; flex-wrap: wrap;">
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                <i class="fas fa-shield-halved text-success me-1"></i>
                Verifikasi keputusan resmi untuk sinkronisasi Dapodik.
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <button type="button" id="btnTandaiDapodikUsulan" class="btn btn-outline"
                    style="display: none; border-color: var(--primary); color: var(--primary); border-radius: var(--radius-md, 8px); padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700;"
                    title="Tandai selesai di-input ke Dapodik">
                    <i class="fas fa-cloud-arrow-up"></i>
                </button>
                <button type="button" id="btnSimpanVerifikasiUsulan" class="btn btn-primary"
                    style="display: none; border-radius: var(--radius-md, 8px); padding: 7px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700;"
                    title="Simpan keputusan verifikasi">
                    <i class="fas fa-save"></i>
                </button>
                <button type="button" class="btn btn-outline close-modal close-modal-pengelolaan-usulan"
                    data-target="#modalPengelolaanUsulan"
                    style="border-radius: var(--radius-md, 8px); padding: 7px 16px; font-size: var(--text-base, 0.875rem);">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>


