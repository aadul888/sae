{{-- Modal Dialog Pilih Rombel untuk Cetak Data Peserta Didik (Fixed Centered Overlay) --}}
<div id="cetakDataRombelModal"
    style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999 !important; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;"
    onclick="handleCetakDataRombelBackdropClick(event)">

    <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 480px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative; z-index: 1000000;"
        onclick="event.stopPropagation()">

        <!-- Header -->
        <div
            style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fas fa-print"></i>
                </div>
                <h5 style="font-size: 0.95rem; font-weight: 800; margin: 0; color: #ffffff;">
                    Cetak Data Peserta Didik per Kelas
                </h5>
            </div>
            <button type="button" onclick="closeCetakDataRombelModal()"
                style="background: rgba(255,255,255,0.15); border: none; color: #ffffff; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer;"
                title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 20px; background: #ffffff;">
            <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 14px; line-height: 1.5;">
                Pilih Rombongan Belajar (Kelas) yang ingin dicetak datanya. Sistem akan menyusun tabel data peserta
                didik lengkap (NISN, NIPD, TTL, Agama, Orang Tua, Alamat &amp; Kontak) dalam format dokumen resmi.
            </p>

            <div style="margin-bottom: 14px;">
                <label for="selectCetakDataRombel"
                    style="display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Pilih Rombongan Belajar (Kelas): <span style="color: #ef4444;">*</span>
                </label>
                <select id="selectCetakDataRombel" class="form-control"
                    style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; font-weight: 600; color: #0f172a; background: #fff;">
                    <option value="">-- Pilih Kelas / Rombel --</option>
                    @if (isset($filterRombel))
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}"
                                {{ (isset($rombel) && $rombel === $r) || (isset($waliRombel) && $waliRombel === $r) ? 'selected' : '' }}>
                                {{ $r }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label for="selectOrientasiCetakData"
                    style="display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Tata Letak Dokumen (Orientasi Kertas A4):
                </label>
                <select id="selectOrientasiCetakData" class="form-control"
                    style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; font-weight: 600; color: #0f172a; background: #fff;">
                    <option value="landscape" selected>Landscape (Melebar - Sangat Direkomendasikan)</option>
                    <option value="portrait">Portrait (Memanjang)</option>
                </select>
            </div>

            <div
                style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 10px 14px; font-size: 0.76rem; color: #065f46; line-height: 1.45; display: flex; gap: 8px; align-items: flex-start;">
                <i class="fas fa-info-circle"
                    style="font-size: 0.9rem; margin-top: 1px; flex-shrink: 0; color: #059669;"></i>
                <span>Format dokumen dilengkapi Kop Surat Resmi Sekolah, ringkasan jumlah siswa L/P, data biodata
                    lengkap, dan lembar pengesahan resmi Kepala Sekolah serta Wali Kelas.</span>
            </div>
        </div>

        <!-- Footer -->
        <div
            style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeCetakDataRombelModal()"
                style="font-size: 0.82rem; padding: 7px 16px;">
                Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="proceedCetakDataRombel()"
                style="font-size: 0.82rem; padding: 7px 18px; background: #059669; border-color: #059669; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="fas fa-print"></i> Buka Dokumen Cetak
            </button>
        </div>
    </div>
</div>

<script>
    function openCetakDataRombelModal(defaultRombel = '') {
        const modal = document.getElementById('cetakDataRombelModal');
        if (!modal) return;
        const select = document.getElementById('selectCetakDataRombel');
        if (select) {
            const currentFilter = document.getElementById('filterRombel')?.value || defaultRombel;
            if (currentFilter) {
                select.value = currentFilter;
            }
        }
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCetakDataRombelModal() {
        const modal = document.getElementById('cetakDataRombelModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function handleCetakDataRombelBackdropClick(e) {
        if (e.target.id === 'cetakDataRombelModal') {
            closeCetakDataRombelModal();
        }
    }

    function proceedCetakDataRombel() {
        const select = document.getElementById('selectCetakDataRombel');
        if (!select || !select.value) {
            alert('Silakan pilih rombongan belajar (kelas) terlebih dahulu.');
            return;
        }
        const rombelName = select.value;
        const orientasi = document.getElementById('selectOrientasiCetakData')?.value || 'landscape';
        const url = '{{ url('/dashboard/manajemen-data/peserta-didik-aktif/cetak-rombel') }}/' + encodeURIComponent(
            rombelName) + '?orientasi=' + encodeURIComponent(orientasi);
        window.open(url, '_blank');
        closeCetakDataRombelModal();
    }
</script>
