        <!-- TAB 3: Pengaturan Template Pesan Otomatis WhatsApp -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- Left: Form Template -->
            <div class="card" style="padding: 22px;">
                <div
                    style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: rgba(34,197,94,0.12); color: #22c55e; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Template Pesan Otomatis WhatsApp
                        </h3>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Sesuaikan format pesan teks yang dikirimkan ke nomor WhatsApp siswa atau orang tua.
                        </div>
                    </div>
                </div>

                <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST">
                    @csrf
                    <input type="hidden" name="subtab" value="whatsapp">

                    <!-- Variable Tags Chips -->
                    <div style="margin-bottom: 12px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Klik Tag Variabel untuk Menyisipkan ke Pesan:
                        </label>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            @php
                                $tags = [
                                    '{NAMA_SEKOLAH}' => 'Nama Sekolah',
                                    '{NAMA_SISWA}' => 'Nama Siswa',
                                    '{NISN}' => 'NISN',
                                    '{ROMBEL}' => 'Kelas / Rombel',
                                    '{STATUS_BAYAR}' => 'Status Bayar',
                                    '{NOMINAL}' => 'Biaya / Nominal',
                                    '{METODE_BAYAR}' => 'Metode Bayar',
                                    '{TANGGAL_BAYAR}' => 'Tgl Bayar',
                                    '{STATUS_CETAK}' => 'Status Cetak',
                                    '{INFO_GARANSI}' => 'Rincian Garansi',
                                    '{DESKRIPSI_PEMBAYARAN}' => 'Rekening & Instruksi',
                                    '{VERIFY_URL}' => 'Tautan Verifikasi',
                                ];
                            @endphp
                            @foreach ($tags as $tagCode => $tagDesc)
                                <button type="button" class="btn-wa-tag-chip" data-tag="{{ $tagCode }}"
                                    style="background: var(--bg-hover); border: 1px solid var(--border-color); color: var(--primary); padding: 3px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem); font-family: monospace; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                    title="{{ $tagDesc }}">
                                    {{ $tagCode }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-bottom: 18px;">
                        <textarea name="template_wa" id="templateWaTextarea" rows="15" required class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); font-family: monospace; line-height: 1.5;">{{ old('template_wa', $settings->template_wa ?: \App\Models\KartuPelajarPengaturan::getDefaultWaTemplate()) }}</textarea>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-outline" onclick="confirmResetWaTemplate(this)"
                            style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem);">
                            <i class="fas fa-undo me-1"></i> Reset ke Default
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 20px; font-size: var(--text-base, 0.875rem); font-weight: 700;">
                            <i class="fas fa-save me-1"></i> Simpan Template
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: WhatsApp Chat Mockup Live Preview -->
            <div class="card" style="padding: 22px; display: flex; flex-direction: column;">
                <div
                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <div
                        style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fab fa-whatsapp text-success"></i> Pratinjau Tampilan WhatsApp Live
                    </div>
                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">Realtime</span>
                </div>

                <div
                    style="flex: 1; background: #ece5dd; border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid #d1d7db; position: relative; overflow-y: auto; max-height: 520px;">
                    <div
                        style="background: #ffffff; color: #111827; padding: 12px 14px; border-radius: var(--radius-lg, 12px); max-width: 95%; font-size: var(--text-sm, 0.8125rem); line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; box-shadow: 0 1px 2px rgba(0,0,0,0.12); position: relative; margin-bottom: 8px;">
                        <div id="waLivePreviewContent" style="white-space: pre-wrap; word-break: break-word;">
                            <!-- Live content rendered by JS -->
                        </div>
                        <div style="text-align: right; font-size: var(--text-2xs, 0.6875rem); color: #64748b; margin-top: 6px;">
                            {{ date('H:i') }} <i class="fas fa-check-double text-primary"></i>
                        </div>
                    </div>
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 10px;">
                    *Pratinjau ini memformat teks otomatis: tanda bintang (*) menjadi <strong>tebal</strong>, underscore (_)
                    menjadi <em>miring</em>, dan tilde (~) menjadi <del>coret</del>.
                </div>
            </div>
        </div>
