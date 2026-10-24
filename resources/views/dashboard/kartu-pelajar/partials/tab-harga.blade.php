        <!-- TAB 2: Pengaturan Harga Standar, Rekening Bank & QRIS -->
        <div class="card" style="padding: 24px; max-width: 860px; margin-bottom: 24px;">
            <div
                style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Pengaturan Harga Standar &amp; Metode Pembayaran
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Atur nominal biaya default pembuatan kartu pelajar fisik/RFID, rekening bank sekolah, serta unggahan
                        kode QRIS resmi.
                    </div>
                </div>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="subtab" value="harga">

                <div style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Biaya Standar Pembuatan Kartu Pelajar (Rp) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="biaya_standar"
                        value="{{ old('biaya_standar', $settings->biaya_standar) }}" min="0" step="500"
                        required class="form-control"
                        style="width: 100%; max-width: 320px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.9rem; font-weight: 700;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                        Nominal ini menjadi tagihan default otomatis saat mendata pembayaran peserta didik baru.
                    </div>
                </div>

                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4
                        style="font-size: 0.92rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-university text-primary"></i> Rekening Bank Tujuan Transfer
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nama Bank
                            </label>
                            <input type="text" name="bank_nama" value="{{ old('bank_nama', $settings->bank_nama) }}"
                                placeholder="Contoh: Bank BJB / BRI / BCA" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nomor Rekening
                            </label>
                            <input type="text" name="bank_rekening"
                                value="{{ old('bank_rekening', $settings->bank_rekening) }}"
                                placeholder="Contoh: 0123-4567-8901" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem; font-family: monospace;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Atas Nama Rekening
                            </label>
                            <input type="text" name="bank_atas_nama"
                                value="{{ old('bank_atas_nama', $settings->bank_atas_nama) }}"
                                placeholder="Contoh: SMK NEGERI 1 PAGELARAN" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                    </div>
                </div>

                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4
                        style="font-size: 0.92rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-qrcode text-primary"></i> Unggah Gambar QRIS Resmi
                    </h4>

                    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                        @if ($settings->qris_image_path)
                            <div
                                style="text-align: center; background: #ffffff; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); width: 140px;">
                                <img src="{{ $settings->qris_image_url }}" alt="QRIS Sekolah"
                                    style="width: 120px; height: 120px; object-fit: contain; display: block; margin: 0 auto 6px auto;">
                                <div style="font-size: 0.7rem; color: #475569; font-weight: 600;">QRIS Aktif</div>
                            </div>
                        @endif

                        <div style="flex: 1; min-width: 260px;">
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                {{ $settings->qris_image_path ? 'Ganti File Gambar QRIS' : 'Pilih File Gambar QRIS' }}
                                (PNG, JPG, WebP — Maks. 5MB)
                            </label>
                            <input type="file" name="qris_image" accept="image/png,image/jpeg,image/webp"
                                class="form-control"
                                style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.82rem;">

                            @if ($settings->qris_image_path)
                                <div style="margin-top: 10px;">
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #ef4444; cursor: pointer;">
                                        <input type="checkbox" name="hapus_qris" value="1">
                                        <span><i class="fas fa-trash-alt me-1"></i> Hapus gambar QRIS yang terunggah saat
                                            ini</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Instruksi &amp; Deskripsi Pembayaran
                    </label>
                    <textarea name="instruksi_pembayaran" rows="3" class="form-control"
                        placeholder="Tuliskan petunjuk pembayaran untuk orang tua murid / siswa..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; line-height: 1.5;">{{ old('instruksi_pembayaran', $settings->instruksi_pembayaran) }}</textarea>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                        Teks instruksi ini otomatis disematkan ke dalam pesan notifikasi WhatsApp saat tagihan dikirimkan.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: 0.86rem; font-weight: 700; border-radius: 8px;">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan Harga &amp; QRIS
                    </button>
                </div>
            </form>
        </div>
