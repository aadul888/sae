{{-- Modal Dialog Pilih Rombel untuk Cetak Masal Kartu Pelajar (Fixed Centered Overlay) --}}
<div id="cetakRombelModal"
    style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999 !important; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;"
    onclick="handleCetakRombelBackdropClick(event)">
    
    <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 460px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative; z-index: 1000000;"
        onclick="event.stopPropagation()">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fas fa-print"></i>
                </div>
                <h5 style="font-size: 0.95rem; font-weight: 800; margin: 0; color: #ffffff;">
                    Cetak Masal Kartu Pelajar
                </h5>
            </div>
            <button type="button" onclick="closeCetakRombelModal()" style="background: rgba(255,255,255,0.15); border: none; color: #ffffff; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer;" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 20px; background: #ffffff;">
            <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 14px; line-height: 1.5;">
                Pilih Rombongan Belajar (Kelas) yang kartu pelajarnya ingin dicetak masal. Sistem akan menata kartu dalam format siap cetak Portrait CR-80 pada kertas A4.
            </p>

            <div style="margin-bottom: 16px;">
                <label for="selectCetakRombel" style="display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Pilih Rombongan Belajar (Kelas):
                </label>
                <select id="selectCetakRombel" class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; font-weight: 600; color: #0f172a;">
                    <option value="">-- Pilih Kelas / Rombel --</option>
                    @if(isset($filterRombel))
                        @foreach($filterRombel as $r)
                            <option value="{{ $r }}" {{ (isset($waliRombel) && $waliRombel === $r) ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 14px; font-size: 0.76rem; color: #0369a1; line-height: 1.45; display: flex; gap: 8px; align-items: flex-start;">
                <i class="fas fa-info-circle" style="font-size: 0.9rem; margin-top: 1px; flex-shrink: 0;"></i>
                <span>Anda dapat memilih cetak sisi depan saja (untuk kertas PVC / stiker ID card) atau kedua sisi pada halaman pratinjau cetak.</span>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeCetakRombelModal()" style="font-size: 0.82rem; padding: 7px 16px;">
                Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="proceedCetakRombel()" style="font-size: 0.82rem; padding: 7px 18px; background: #0284c7; border-color: #0284c7; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-up-right-from-square"></i> Buka Halaman Cetak
            </button>
        </div>
    </div>
</div>

<script>
function openCetakRombelModal(defaultRombel = '') {
    const modal = document.getElementById('cetakRombelModal');
    if (!modal) return;
    const select = document.getElementById('selectCetakRombel');
    if (select && defaultRombel) {
        select.value = defaultRombel;
    }
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCetakRombelModal() {
    const modal = document.getElementById('cetakRombelModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function handleCetakRombelBackdropClick(e) {
    if (e.target.id === 'cetakRombelModal') {
        closeCetakRombelModal();
    }
}

function proceedCetakRombel() {
    const select = document.getElementById('selectCetakRombel');
    if (!select || !select.value) {
        alert('Silakan pilih rombongan belajar terlebih dahulu.');
        return;
    }
    const rombelName = select.value;
    window.open('{{ url("/dashboard/kartu-pelajar/cetak-rombel") }}/' + encodeURIComponent(rombelName), '_blank');
    closeCetakRombelModal();
}
</script>
