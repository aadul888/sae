    <!-- MODAL 1: Catat / Edit Pembayaran Kartu Pelajar -->
    <div id="modalEditPembayaran" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-wallet text-primary"></i> Catat / Edit Pembayaran Kartu Pelajar
                </h4>
                <button type="button" onclick="closeEditPembayaran()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Student Summary Info -->
            <div
                style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 10px 14px; margin-bottom: 14px;">
                <div id="bayarPdNama" style="font-weight: 800; font-size: var(--text-md, 1rem); color: var(--text-color);">-</div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    NISN: <span id="bayarPdNisn">-</span> &bull; Kelas: <span id="bayarPdRombel">-</span>
                </div>
            </div>

            <form id="formEditPembayaran">
                <input type="hidden" name="peserta_didik_id" id="bayarPdId">

                <!-- Status Pembayaran Radio -->
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Status Pembayaran <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <div style="display: flex; gap: 18px; align-items: center;">
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer;">
                            <input type="radio" name="status_pembayaran" value="lunas">
                            <i class="fas fa-check-circle"></i> LUNAS
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--warning, #f59e0b); cursor: pointer;">
                            <input type="radio" name="status_pembayaran" value="belum_bayar">
                            <i class="fas fa-clock"></i> BELUM BAYAR
                        </label>
                    </div>
                </div>

                <!-- Rincian Biaya & Metode (Toggle display if Lunas) -->
                <div id="bayarDetailFieldsWrap">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nominal / Biaya (Rp)
                            </label>
                            <input type="number" name="nominal" id="bayarNominal" class="form-control" value="25000"
                                min="0" step="1000"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Metode Pembayaran
                            </label>
                            <select name="metode_pembayaran" id="bayarMetode" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS / Digital</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Tanggal Pembayaran
                            </label>
                            <input type="date" name="tanggal_bayar" id="bayarTanggal" class="form-control"
                                value="{{ date('Y-m-d') }}"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Status Fisik Kartu
                            </label>
                            <select name="status_cetak" id="bayarStatusCetak" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                                <option value="belum_cetak">Belum Dicetak</option>
                                <option value="sudah_cetak">Sudah Dicetak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Nomor Handphone / WhatsApp Orang Tua atau Siswa
                    </label>
                    <input type="text" name="nomor_wa" id="bayarNomorWa" class="form-control"
                        placeholder="Contoh: 08123456789"
                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                </div>

                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Catatan Khusus (Opsional)
                    </label>
                    <textarea name="catatan" id="bayarCatatan" rows="2" class="form-control"
                        placeholder="Catatan bukti kwitansi / serah terima kartu..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                </div>

                <!-- Checkbox Auto-Kirim WhatsApp -->
                <div
                    style="background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.3); border-radius: var(--radius-md, 8px); padding: 10px 14px; margin-bottom: 16px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #15803d; cursor: pointer; margin: 0;">
                        <input type="checkbox" id="chkKirimWaAuto" checked>
                        <span><i class="fab fa-whatsapp me-1"></i> Buka WhatsApp &amp; Kirim Bukti beserta QR Code
                            setelah
                            disimpan</span>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeEditPembayaran()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" id="btnSubmitPembayaran" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-check me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 1B: Penyesuaian Data Pembayaran & Cetak Masal (Bulk Action) -->
    <div id="modalBulkPembayaran" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tasks text-primary"></i> Penyesuaian Data Pembayaran Masal
                </h4>
                <button type="button" onclick="closeModalBulkPembayaran()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Summary Count Alert -->
            <div
                style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); border-radius: var(--radius-md, 8px); padding: 10px 14px; margin-bottom: 16px; font-size: var(--text-base, 0.875rem); color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-square"></i>
                <span>Menyesuaikan data untuk <strong id="bulkModalSelectedCount">0</strong> peserta didik terpilih
                    sekaligus.</span>
            </div>

            <form id="formBulkPembayaran">
                <input type="hidden" name="action" id="bulkModalAction" value="custom_bulk">
                <input type="hidden" name="apply_all_filtered" id="bulkModalApplyAll" value="0">
                <input type="hidden" name="filter_q" id="bulkModalFilterQ" value="">
                <input type="hidden" name="filter_rombel" id="bulkModalFilterRombel" value="">
                <input type="hidden" name="filter_status_bayar" id="bulkModalFilterStatusBayar" value="">

                <!-- 1. Status Pembayaran -->
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Status Pembayaran:
                    </label>
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="lunas">
                            <i class="fas fa-check-circle"></i> Lunas
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--warning, #f59e0b); cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="belum_bayar">
                            <i class="fas fa-clock"></i> Belum Bayar
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-muted); cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="keep" checked>
                            <span>Tetap (Jangan Ubah)</span>
                        </label>
                    </div>
                </div>

                <!-- Detail Pembayaran (Aktif saat Lunas) -->
                <div id="bulkBayarDetailWrap"
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nominal / Biaya (Rp)
                            </label>
                            <input type="number" name="bulk_nominal" id="bulkNominal" class="form-control"
                                value="{{ $settings->biaya_standar ?? 25000 }}" min="0" step="1000"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Metode Pembayaran
                            </label>
                            <select name="bulk_metode_pembayaran" id="bulkMetode" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS / Digital</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                            Tanggal Pembayaran
                        </label>
                        <input type="date" name="bulk_tanggal_bayar" id="bulkTanggalBayar" class="form-control"
                            value="{{ date('Y-m-d') }}"
                            style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                    </div>
                </div>

                <!-- 2. Status Fisik Kartu -->
                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                                Status Fisik Kartu:
                            </label>
                            <select name="bulk_status_cetak" id="bulkStatusCetak" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                                <option value="keep">Tetap (Jangan Ubah)</option>
                                <option value="sudah_cetak">Sudah Dicetak (Garansi Aktif)</option>
                                <option value="belum_cetak">Belum Dicetak</option>
                            </select>
                        </div>
                        <div id="bulkTanggalCetakWrap">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                                Tanggal Selesai Dicetak:
                            </label>
                            <input type="date" name="bulk_tanggal_cetak" id="bulkTanggalCetak"
                                class="form-control" value="{{ date('Y-m-d') }}"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>
                </div>

                <!-- 3. Catatan Khusus Massal (Opsional) -->
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Catatan Khusus Massal (Opsional)
                    </label>
                    <textarea name="bulk_catatan" id="bulkCatatan" rows="2" class="form-control"
                        placeholder="Tambahkan catatan khusus untuk seluruh siswa terpilih..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeModalBulkPembayaran()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" id="btnSubmitBulkPembayaran" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-check-double"></i> Terapkan ke Semua Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Kirim Notifikasi WhatsApp & QR Code Langsung -->
    <div id="modalKirimWa" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fab fa-whatsapp text-success" style="font-size: var(--text-xl, 1.25rem);"></i> Kirim Informasi
                    Pembayaran
                    &amp; QR Code
                </h4>
                <button type="button" onclick="closeModalWa()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <div id="waModalLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat rincian pembayaran &amp; QR Code siswa...</div>
            </div>

            <div id="waModalBody" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 180px; gap: 16px; margin-bottom: 14px;">
                    <div>
                        <!-- Student Summary -->
                        <div
                            style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 8px 12px; margin-bottom: 12px;">
                            <div id="waTargetNama"
                                style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--text-color);">-</div>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                NISN: <span id="waTargetNisn">-</span> &bull; Kelas: <span
                                    id="waTargetRombel">-</span>
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Nomor WhatsApp Tujuan:
                            </label>
                            <input type="text" id="waInputPhone" class="form-control"
                                placeholder="Contoh: 08123456789 atau 628123456789"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem); font-family: monospace;">
                        </div>
                    </div>

                    <!-- Visual QR Code Card -->
                    <div
                        style="text-align: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg, 12px); padding: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div id="waQrSvgContainer"
                            style="width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                            <!-- Live SVG QR Code rendered here -->
                        </div>
                        <div style="font-size: var(--text-2xs, 0.6875rem); color: #475569; font-weight: 700; margin-bottom: 6px;">
                            QR Code Kartu Siswa
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="unduhQrCodeSvg()"
                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; border-radius: var(--radius-xs, 4px);"
                            title="Unduh File QR SVG">
                            <i class="fas fa-download me-1"></i> Unduh QR
                        </button>
                    </div>
                </div>

                <!-- Teks Pesan Otomatis -->
                <div style="margin-bottom: 14px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                            Pesan Otomatis WhatsApp (Dapat Diedit):
                        </label>
                        <button type="button" class="btn btn-outline btn-sm" onclick="salinTeksPesanWa()"
                            style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px);">
                            <i class="fas fa-copy me-1"></i> Salin Teks
                        </button>
                    </div>
                    <textarea id="waTextPesan" rows="9" class="form-control"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); font-family: monospace; line-height: 1.45;"></textarea>
                </div>

                <div
                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-info-circle text-primary"></i>
                    <span>Tautan QR Code resmi yang dilampirkan: <a href="#" id="waVerifyLinkText"
                            target="_blank" style="color: var(--primary); text-decoration: underline;"></a></span>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" onclick="closeModalWa()" class="btn btn-outline"
                        style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">Tutup</button>
                    <button type="button" onclick="kirimPesanWaSekarang()" class="btn"
                        style="background: #22c55e; color: #ffffff; padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; border: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fab fa-whatsapp" style="font-size: var(--text-md, 1rem);"></i> Buka Chat WhatsApp &amp; Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Klaim Garansi Kartu Pelajar (1x Ganti Kartu Full, 3x Stiker) -->
    <div id="modalKlaimGaransi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-shield-alt text-warning"></i> Klaim Garansi Kartu Pelajar
                </h4>
                <button type="button" onclick="closeKlaimGaransi()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Student Summary Info -->
            <div
                style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 10px 14px; margin-bottom: 14px;">
                <div id="garansiPdNama" style="font-weight: 800; font-size: var(--text-md, 1rem); color: var(--text-color);">-
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    NISN: <span id="garansiPdNisn">-</span> &bull; Kelas: <span id="garansiPdRombel">-</span>
                </div>
            </div>

            <!-- Banner Garansi Aktif (Jika status sudah cetak) -->
            <div id="garansiActiveNotice"
                style="display: none; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: var(--success, #059669); padding: 10px 14px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 14px; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle" style="font-size: var(--text-lg, 1.125rem); flex-shrink: 0;"></i>
                <div>
                    <strong>Status Kartu: Sudah Dicetak</strong><br>
                    Garansi resmi otomatis berlaku untuk kedua fasilitas (Ganti Kartu Penuh &amp; Cetak Stiker).
                </div>
            </div>

            <!-- Banner Garansi Belum Aktif (Jika status belum cetak) -->
            <div id="garansiNotActiveNotice"
                style="display: none; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); color: var(--warning, #d97706); padding: 10px 14px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 14px; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle" style="font-size: var(--text-lg, 1.125rem); flex-shrink: 0;"></i>
                <div>
                    <strong>Garansi Belum Berlaku:</strong> Kartu fisik belum dicetak (Status: Belum Dicetak). Garansi
                    resmi
                    baru otomatis berlaku setelah kartu berstatus Sudah Dicetak.
                </div>
            </div>

            <form id="formKlaimGaransi">
                <input type="hidden" name="peserta_didik_id" id="garansiPdId">

                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Pilih Fasilitas Garansi yang Diklaim <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label
                            style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); cursor: pointer; background: var(--bg-hover);">
                            <input type="radio" name="jenis_klaim" id="optGaransiKartu" value="ganti_kartu_full"
                                style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    <i class="fas fa-id-card me-1 text-primary"></i> Ganti Kartu Penuh (Fisik PVC/RFID
                                    &amp; Stiker Baru)
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    Sisa kuota: <strong id="garansiSisaKartu" style="color: var(--primary);">0
                                        kali</strong>
                                </div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); cursor: pointer; background: var(--bg-hover);">
                            <input type="radio" name="jenis_klaim" id="optGaransiStiker" value="cetak_stiker"
                                style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    <i class="fas fa-sticky-note me-1 text-warning"></i> Cetak Ulang Stiker Kartu
                                    Pelajar
                                    Saja
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    Sisa kuota: <strong id="garansiSisaStiker" style="color: var(--primary);">0
                                        kali</strong>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Alasan / Kendala Klaim <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <textarea name="alasan" id="garansiAlasan" rows="3" required class="form-control"
                        placeholder="Contoh: Kartu fisik patah / chip RFID rusak tidak terbaca / stiker pudar..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeKlaimGaransi()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" id="btnSubmitGaransi" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-check me-1"></i> Simpan Klaim Garansi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Catat Pengeluaran Alat & Bahan Cetak (Compact & Rapi) -->
    <div id="modalTambahPengeluaran" class="modal-backdrop">
        <div class="card kp-modal-card" style="max-width: 500px;">
            <div class="kp-modal-header" style="padding: 14px 20px 10px;">
                <h4 class="kp-modal-title" style="font-size: var(--text-md, 1rem); font-weight: 800;">
                    <i class="fas fa-plus-circle text-danger"></i> Catat Pengeluaran Alat &amp; Bahan
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeTambahPengeluaran()"
                    aria-label="Tutup">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.store-pengeluaran') }}" method="POST"
                enctype="multipart/form-data" class="kp-modal-form" id="formTambahPengeluaran">
                @csrf
                <div class="kp-modal-body" style="padding: 14px 20px;">
                    <!-- 1. Uraian / Judul Pembelanjaan -->
                    <div class="kp-form-group" style="margin-bottom: 10px;">
                        <label class="kp-form-label" for="pgJudul"
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                            Uraian / Judul Pembelanjaan <span class="req">*</span>
                        </label>
                        <input type="text" id="pgJudul" name="judul" required maxlength="255"
                            class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem);"
                            placeholder="Contoh: Beli 250 Kartu PVC RFID Blank + 1 Roll Ribbon">
                    </div>

                    <!-- 2. Kategori & Tanggal Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 10px;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgKategori"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Kategori <span class="req">*</span>
                            </label>
                            <select id="pgKategori" name="kategori" required class="form-control"
                                style="height: 36px; font-size: var(--text-base, 0.875rem);">
                                @foreach ($kategoriList as $kKey => $kVal)
                                    <option value="{{ $kKey }}">{{ $kVal }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgTanggal"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Tanggal <span class="req">*</span>
                            </label>
                            <input type="date" id="pgTanggal" name="tanggal" value="{{ date('Y-m-d') }}" required
                                class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <!-- 3. Kuantitas (Qty) & Harga Satuan Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 8px;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgJumlah"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Kuantitas (Qty) <span class="req">*</span>
                            </label>
                            <input type="number" id="pgJumlah" name="jumlah" min="1" max="10000000"
                                value="1" required class="form-control"
                                style="height: 36px; font-size: var(--text-base, 0.875rem);" placeholder="1">
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgNominal"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Harga Satuan (Rp) <span class="req">*</span>
                            </label>
                            <input type="text" id="pgNominal" name="nominal" required inputmode="numeric"
                                class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem); font-weight: 700;"
                                placeholder="Contoh: 2.500" autocomplete="off">
                        </div>
                    </div>

                    <!-- 4. Highlight Preview Total Pengeluaran -->
                    <div id="pgNominalPreview"
                        style="padding: 8px 12px; border-radius: var(--radius-md, 8px); background: rgba(239, 68, 68, 0.08); border: 1px dashed rgba(239, 68, 68, 0.3); font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Total Pengeluaran:</span>
                        <strong style="font-size: var(--text-base, 0.875rem); color: var(--danger, #ef4444);">Rp 0</strong>
                    </div>

                    <!-- 5. Bukti Nota & Keterangan / Toko Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 0;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgNota"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Bukti Nota / Kwitansi
                            </label>
                            <input type="file" id="pgNota" name="bukti_nota"
                                accept="image/jpeg,image/png,application/pdf,image/webp" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); padding: 5px 8px; height: 36px;">
                            <div class="kp-form-hint" style="font-size: var(--text-2xs, 0.6875rem); margin-top: 2px;">JPG, PNG, PDF. Maks
                                5MB.</div>
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="pgKeterangan"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Keterangan / Toko
                            </label>
                            <input type="text" id="pgKeterangan" name="keterangan" maxlength="1000"
                                class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem);"
                                placeholder="Nama toko / spesifikasi...">
                            <div class="kp-form-hint" style="font-size: var(--text-2xs, 0.6875rem); margin-top: 2px;">Opsional</div>
                        </div>
                    </div>
                </div>

                <div class="kp-modal-footer" style="padding: 10px 20px 14px;">
                    <button type="button" onclick="closeTambahPengeluaran()" class="btn btn-outline btn-sm"
                        style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="pgSubmit"
                        style="padding: 6px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700;">
                        <i class="fas fa-save me-1"></i> Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4B: Edit Catatan Pengeluaran (Compact & Rapi) -->
    <div id="modalEditPengeluaran" class="modal-backdrop">
        <div class="card kp-modal-card" style="max-width: 500px;">
            <div class="kp-modal-header" style="padding: 14px 20px 10px;">
                <h4 class="kp-modal-title" style="font-size: var(--text-md, 1rem); font-weight: 800;">
                    <i class="fas fa-edit text-primary"></i> Edit Catatan Pengeluaran
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeEditPengeluaran()"
                    aria-label="Tutup">&times;</button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="kp-modal-form"
                id="formEditPengeluaran">
                @csrf
                @method('PUT')
                <input type="hidden" id="editPgId" name="id">
                <div class="kp-modal-body" style="padding: 14px 20px;">
                    <!-- 1. Uraian / Judul Pembelanjaan -->
                    <div class="kp-form-group" style="margin-bottom: 10px;">
                        <label class="kp-form-label" for="editPgJudul"
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                            Uraian / Judul Pembelanjaan <span class="req">*</span>
                        </label>
                        <input type="text" id="editPgJudul" name="judul" required maxlength="255"
                            class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem);"
                            placeholder="Contoh: Beli 250 Kartu PVC RFID Blank + 1 Roll Ribbon">
                    </div>

                    <!-- 2. Kategori & Tanggal Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 10px;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgKategori"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Kategori <span class="req">*</span>
                            </label>
                            <select id="editPgKategori" name="kategori" required class="form-control"
                                style="height: 36px; font-size: var(--text-base, 0.875rem);">
                                @foreach ($kategoriList as $kKey => $kVal)
                                    <option value="{{ $kKey }}">{{ $kVal }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgTanggal"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Tanggal <span class="req">*</span>
                            </label>
                            <input type="date" id="editPgTanggal" name="tanggal" required class="form-control"
                                style="height: 36px; font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <!-- 3. Kuantitas (Qty) & Harga Satuan Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 8px;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgJumlah"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Kuantitas (Qty) <span class="req">*</span>
                            </label>
                            <input type="number" id="editPgJumlah" name="jumlah" min="1" max="10000000"
                                value="1" required class="form-control"
                                style="height: 36px; font-size: var(--text-base, 0.875rem);" placeholder="1">
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgNominal"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Harga Satuan (Rp) <span class="req">*</span>
                            </label>
                            <input type="text" id="editPgNominal" name="nominal" required inputmode="numeric"
                                class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem); font-weight: 700;"
                                placeholder="Contoh: 2.500" autocomplete="off">
                        </div>
                    </div>

                    <!-- 4. Highlight Preview Total Pengeluaran -->
                    <div id="editPgNominalPreview"
                        style="padding: 8px 12px; border-radius: var(--radius-md, 8px); background: rgba(239, 68, 68, 0.08); border: 1px dashed rgba(239, 68, 68, 0.3); font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Total Pengeluaran:</span>
                        <strong style="font-size: var(--text-base, 0.875rem); color: var(--danger, #ef4444);">Rp 0</strong>
                    </div>

                    <!-- 5. Bukti Nota & Keterangan / Toko Grid -->
                    <div class="kp-form-grid" style="gap: 10px; margin-bottom: 0;">
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgNota"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Ganti Bukti Nota / Kwitansi
                            </label>
                            <input type="file" id="editPgNota" name="bukti_nota"
                                accept="image/jpeg,image/png,application/pdf,image/webp" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); padding: 5px 8px; height: 36px;">
                            <div class="kp-form-hint" id="editPgNotaHint"
                                style="font-size: var(--text-2xs, 0.6875rem); margin-top: 2px;">
                                Kosongkan jika tidak ingin mengubah nota.
                            </div>
                        </div>
                        <div class="kp-form-group" style="margin-bottom: 0;">
                            <label class="kp-form-label" for="editPgKeterangan"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 3px;">
                                Keterangan / Toko
                            </label>
                            <input type="text" id="editPgKeterangan" name="keterangan" maxlength="1000"
                                class="form-control" style="height: 36px; font-size: var(--text-base, 0.875rem);"
                                placeholder="Nama toko / spesifikasi...">
                            <div class="kp-form-hint" style="font-size: var(--text-2xs, 0.6875rem); margin-top: 2px;">Opsional</div>
                        </div>
                    </div>
                </div>

                <div class="kp-modal-footer" style="padding: 10px 20px 14px;">
                    <button type="button" onclick="closeEditPengeluaran()" class="btn btn-outline btn-sm"
                        style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="editPgSubmit"
                        style="padding: 6px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700;">
                        <i class="fas fa-save me-1"></i> Perbarui Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4C: Tambah Request Cetak Kartu Pelajar (Individu / Per Kelas Masal) -->
    <div id="modalTambahRequest" class="modal-backdrop">
        <div class="card kp-modal-card" style="max-width: 520px;">
            <div class="kp-modal-header">
                <h4 class="kp-modal-title">
                    <i class="fas fa-paper-plane text-primary"></i> Tambah Request Cetak Kartu
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeTambahRequest()"
                    aria-label="Tutup">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.store-request') }}" method="POST" class="kp-modal-form"
                id="formTambahRequest">
                @csrf
                <input type="hidden" name="mode" id="rqMode" value="individu">
                <input type="hidden" name="peserta_didik_id" id="rqPdId">
                <div class="kp-modal-body">
                    <!-- Mode Switcher: Individu vs Per Kelas -->
                    <div
                        style="display: flex; gap: 6px; margin-bottom: 14px; background: var(--bg-hover); padding: 4px; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color);">
                        <button type="button" id="tabBtnRqIndividu" onclick="switchRequestMode('individu')"
                            class="btn btn-sm"
                            style="flex: 1; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px); border: none; padding: 7px 10px; background: var(--primary); color: #ffffff;">
                            <i class="fas fa-user me-1"></i> Per Siswa (Individu)
                        </button>
                        <button type="button" id="tabBtnRqRombel" onclick="switchRequestMode('rombel')"
                            class="btn btn-sm"
                            style="flex: 1; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px); border: none; padding: 7px 10px; background: transparent; color: var(--text-muted);">
                            <i class="fas fa-users-viewfinder me-1"></i> Sekaligus Per Kelas
                        </button>
                    </div>

                    <!-- MODE 1: Input Individu Siswa -->
                    <div id="rqModeWrapIndividu" class="kp-form-group">
                        <label class="kp-form-label" for="rqCari">Cari Peserta Didik <span
                                class="req">*</span></label>
                        <div class="kp-ac-wrap">
                            <input type="text" id="rqCari" class="form-control" autocomplete="off"
                                placeholder="Ketik nama / NISN / NIPD (min. 2 huruf)...">
                            <div id="rqAcList" class="kp-ac-list"></div>
                        </div>
                        <div class="kp-form-hint" id="rqPilihInfo">Belum ada peserta didik dipilih.</div>
                    </div>

                    <!-- MODE 2: Input Sekaligus Per Kelas / Rombel -->
                    <div id="rqModeWrapRombel" style="display: none; margin-bottom: 14px;">
                        <div class="kp-form-group" style="margin-bottom: 8px;">
                            <label class="kp-form-label" for="rqRombelSelect">Pilih Rombongan Belajar (Kelas) <span
                                    class="req">*</span></label>
                            <select id="rqRombelSelect" name="rombongan_belajar_id" class="form-control"
                                style="width: 100%;">
                                <option value="">-- Pilih Kelas / Rombel --</option>
                                @if (isset($rombelList))
                                    @foreach ($rombelList as $r)
                                        <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Checkbox Hanya yang ada foto kartu pelajar -->
                        <div
                            style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md, 8px); padding: 8px 12px; margin-bottom: 8px;">
                            <label
                                style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #166534; cursor: pointer; margin: 0;">
                                <input type="checkbox" name="hanya_ada_foto" id="rqHanyaAdaFoto" value="1"
                                    checked style="accent-color: var(--success, #059669); width: 16px; height: 16px; cursor: pointer;">
                                <span><i class="fas fa-camera text-success me-1"></i> Hanya Siswa yang Sudah Ada Foto
                                    Formal</span>
                            </label>
                            <div style="font-size: var(--text-xs, 0.75rem); color: #15803d; margin-left: 24px; margin-top: 2px;">
                                Siswa yang belum memiliki foto kartu pelajar otomatis dilewati agar antrean cetak tidak
                                kosong.
                            </div>
                        </div>

                        <!-- Info Hitung Ketersediaan Foto Siswa di Kelas -->
                        <div id="rqRombelFotoInfo"
                            style="display: none; font-size: var(--text-sm, 0.8125rem); color: #047857; padding: 6px 10px; background: rgba(5,150,105,0.08); border-radius: var(--radius-sm, 6px); border: 1px dashed rgba(5,150,105,0.3);">
                            <i class="fas fa-info-circle me-1"></i> <span id="rqRombelFotoText">-</span>
                        </div>
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rqJenis">Jenis Request <span
                                class="req">*</span></label>
                        <select id="rqJenis" name="jenis_request" required class="form-control">
                            @foreach (\App\Models\KartuPelajarRequest::JENIS as $jKey => $jVal)
                                <option value="{{ $jKey }}">{{ $jVal }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rqStatusPembayaran">Status Pembayaran</label>
                            <select id="rqStatusPembayaran" name="status_pembayaran" class="form-control">
                                <option value="">Ikuti data peserta didik</option>
                                <option value="belum_bayar">Belum Bayar</option>
                                <option value="lunas">Lunas</option>
                            </select>
                        </div>

                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rqMetodePembayaran">Metode Pembayaran</label>
                            <select id="rqMetodePembayaran" name="metode_pembayaran" class="form-control">
                                <option value="">Ikuti data peserta didik</option>
                                @foreach (\App\Models\KartuPelajarRequest::METODE_PEMBAYARAN as $mKey => $mVal)
                                    <option value="{{ $mKey }}">{{ $mVal }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="kp-form-hint" style="margin-top: -6px; margin-bottom: 12px;">
                        Jika diisi/diubah, status &amp; metode pembayaran peserta didik otomatis tersinkronisasi.
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rqAlasan">Alasan / Catatan</label>
                        <textarea id="rqAlasan" name="alasan" rows="2" maxlength="500" class="form-control"
                            placeholder="Contoh: Cetak serentak rombel, kartu hilang, chip tidak terbaca..."></textarea>
                    </div>
                </div>

                <div class="kp-modal-footer">
                    <button type="button" onclick="closeTambahRequest()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" id="rqSubmit" disabled>
                        <i class="fas fa-paper-plane me-1"></i> Kirim Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Cetak Lembar Pengambilan / Serah Terima Kartu -->
    <div id="modalCetakPengambilan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 480px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Cetak Lembar Pengambilan Kartu
                </h4>
                <button type="button" onclick="closeModalCetakPengambilan()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.cetak-pengambilan') }}" method="GET" target="_blank">
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                        Pilih Tanggal Selesai Dicetak <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="date" name="tanggal_cetak" value="{{ date('Y-m-d') }}" required
                        class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                        Mencetak daftar siswa yang status cetaknya selesai pada tanggal tersebut untuk diambil.
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                        Filter Rombel / Kelas (Opsional)
                    </label>
                    <select name="rombel" class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-base, 0.875rem);">
                        <option value="">Semua Rombel (Seluruh Siswa yang Dicetak)</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->nama }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeModalCetakPengambilan()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                    <button type="submit" class="btn btn-primary" onclick="closeModalCetakPengambilan()"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-print"></i> Buka Lembar Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: Pratinjau & Cetak Lembar Kartu Pelajar Digital (Sama dengan Peserta Didik Aktif) -->
    @include('kartu-pelajar.modal-preview')

    <!-- MODAL 7: Dialog Pilih Rombel untuk Cetak Masal -->
    @include('kartu-pelajar.modal-cetak-rombel', ['filterRombel' => $rombelList->pluck('nama')])

    <!-- MODAL 8: Reader RFID Selesai Cetak & Registrasi Presensi -->
    @include('dashboard.kartu-pelajar.partials.modal-scan-rfid')

    @push('scripts')
        <script
            src="{{ asset('js/kartu-pelajar-preview.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-preview.js')) ? filemtime(public_path('js/kartu-pelajar-preview.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/kartu-pelajar-bulk.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-bulk.js')) ? filemtime(public_path('js/kartu-pelajar-bulk.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/kartu-pelajar-manage.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-manage.js')) ? filemtime(public_path('js/kartu-pelajar-manage.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/kartu-pelajar-finance.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-finance.js')) ? filemtime(public_path('js/kartu-pelajar-finance.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/kartu-pelajar-scan-rfid.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-scan-rfid.js')) ? filemtime(public_path('js/kartu-pelajar-scan-rfid.js')) : time() }}">
        </script>
    @endpush
