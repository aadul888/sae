{{-- Modal Dialog Pilih Rombel untuk Cetak Masal Kartu Pelajar (Fixed Centered Overlay) --}}
<div id="cetakRombelModal"
    style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999 !important; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;"
    onclick="handleCetakRombelBackdropClick(event)">

    <div style="background: #ffffff; border-radius: var(--radius-xl, 16px); box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 460px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative; z-index: 1000000;"
        onclick="event.stopPropagation()">

        <!-- Header -->
        <div
            style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: var(--text-md, 1rem);">
                    <i class="fas fa-print"></i>
                </div>
                <h5 style="font-size: var(--text-md, 1rem); font-weight: 800; margin: 0; color: #ffffff;">
                    Cetak Masal Kartu Pelajar
                </h5>
            </div>
            <button type="button" onclick="closeCetakRombelModal()"
                style="background: rgba(255,255,255,0.15); border: none; color: #ffffff; width: 30px; height: 30px; border-radius: var(--radius-md, 8px); display: flex; align-items: center; justify-content: center; font-size: var(--text-md, 1rem); cursor: pointer;"
                title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 20px; background: #ffffff;">
            <p style="font-size: var(--text-sm, 0.8125rem); color: #64748b; margin-bottom: 14px; line-height: 1.5;">
                Pilih Rombongan Belajar (Kelas) yang kartu pelajarnya ingin dicetak masal. Sistem akan menata kartu
                dalam format siap cetak Portrait CR-80 pada kertas A4.
            </p>

            <div style="margin-bottom: 16px;">
                <label for="selectCetakRombel"
                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Pilih Rombongan Belajar (Kelas):
                </label>
                <select id="selectCetakRombel" class="form-control"
                    style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid #cbd5e1; font-size: var(--text-base, 0.875rem); font-weight: 600; color: #0f172a;">
                    <option value="">-- Pilih Kelas / Rombel --</option>
                    @if (isset($filterRombel))
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}"
                                {{ isset($waliRombel) && $waliRombel === $r ? 'selected' : '' }}>{{ $r }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Opsi Filter Antrean Request -->
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Cakupan Siswa:
                </label>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: #334155; cursor: pointer;">
                        <input type="radio" name="cakupan_cetak_rombel" value="semua" checked
                            style="accent-color: #0284c7; width: 16px; height: 16px;">
                        <span>Semua Siswa di Rombel Terpilih</span>
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: #b45309; font-weight: 600; cursor: pointer;">
                        <input type="radio" name="cakupan_cetak_rombel" value="menunggu"
                            style="accent-color: #f59e0b; width: 16px; height: 16px;">
                        <span><i class="fas fa-hourglass-half text-warning me-1"></i> Hanya Siswa Status "Menunggu" di
                            Request Cetak</span>
                    </label>
                </div>
            </div>

            <!-- Validasi Foto Siswa -->
            <div
                style="margin-bottom: 16px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: var(--radius-md, 8px); padding: 8px 12px;">
                <label
                    style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #1e293b; cursor: pointer; margin: 0;">
                    <input type="checkbox" id="checkHanyaAdaFoto" checked
                        style="accent-color: #059669; width: 16px; height: 16px; cursor: pointer;">
                    <span><i class="fas fa-camera text-success me-1"></i> Hanya Siswa yang Sudah Ada Foto</span>
                </label>
                <div style="font-size: var(--text-xs, 0.75rem); color: #64748b; margin-left: 24px; margin-top: 2px;">
                    Siswa yang belum mengunggah foto formal akan otomatis dilewati agar kartu tidak tercetak
                    kosong/avatar.
                </div>
            </div>

            <div
                style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md, 8px); padding: 10px 14px; font-size: var(--text-sm, 0.8125rem); color: #0369a1; line-height: 1.45; display: flex; gap: 8px; align-items: flex-start;">
                <i class="fas fa-info-circle" style="font-size: var(--text-base, 0.875rem); margin-top: 1px; flex-shrink: 0;"></i>
                <span>Anda dapat memilih cetak sisi depan saja (untuk kertas PVC / stiker ID card) atau kedua sisi pada
                    halaman pratinjau cetak.</span>
            </div>
        </div>

        <!-- Footer -->
        <div
            style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeCetakRombelModal()"
                style="font-size: var(--text-sm, 0.8125rem); padding: 7px 16px;">
                Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="proceedCetakRombel()"
                style="font-size: var(--text-sm, 0.8125rem); padding: 7px 18px; background: #0284c7; border-color: #0284c7; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-up-right-from-square"></i> Buka Halaman Cetak &amp; Unduh ZIP
            </button>
        </div>
    </div>
</div>

<script
    src="{{ asset('js/kartu-pelajar-preview.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-preview.js')) ? filemtime(public_path('js/kartu-pelajar-preview.js')) : time() }}">
</script>
