{{-- Modal Popup Interaktif Pratinjau Kartu Pelajar Digital (Fixed Centered Overlay) --}}
<div id="kartuPelajarModal"
    style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999 !important; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;"
    onclick="handleKpModalBackdropClick(event)">
    
    <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 660px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; position: relative; z-index: 1000000;"
        onclick="event.stopPropagation()">
        
        <!-- Header Modal -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(56, 189, 248, 0.15); display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.1rem;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h5 style="font-size: 0.95rem; font-weight: 800; margin: 0; color: #ffffff;" id="kpModalTitle">
                        Kartu Pelajar Digital SAE
                    </h5>
                    <div style="font-size: 0.72rem; color: #94a3b8;" id="kpModalSubtitle">
                        Format Portrait Standar ISO CR-80 (54mm &times; 85.6mm)
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeKartuPelajarModal()" style="background: rgba(255,255,255,0.08); border: none; color: #cbd5e1; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; transition: all 0.15s;" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div style="background: #f8fafc; padding: 20px; overflow-y: auto; flex: 1; min-height: 240px;">
            <!-- Loading State -->
            <div id="kpModalLoading" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #0284c7; margin-bottom: 12px;"></i>
                <div style="font-size: 0.88rem; font-weight: 600;">Memuat Data Kartu Pelajar...</div>
            </div>

            <!-- Content State -->
            <div id="kpModalContent" style="display: none;">
                <!-- Tab Pilihan Tampilan Sisi -->
                <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                    <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 8px; gap: 2px;">
                        <button type="button" id="kpBtnTabBoth" onclick="setKpModalTab('both')" class="btn-kp-tab active">
                            Depan &amp; Belakang
                        </button>
                        <button type="button" id="kpBtnTabFront" onclick="setKpModalTab('front')" class="btn-kp-tab">
                            Depan Saja
                        </button>
                        <button type="button" id="kpBtnTabBack" onclick="setKpModalTab('back')" class="btn-kp-tab">
                            Belakang Saja
                        </button>
                    </div>
                </div>

                <!-- Container Kartu -->
                <div id="kpCardContainer" style="display: flex; justify-content: center; align-items: center; margin: 10px 0;">
                    <!-- Injected via AJAX -->
                </div>

                <!-- Direct Verification Link Notice -->
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 14px; margin-top: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.76rem; color: #1e40af;">
                        <i class="fas fa-shield-check" style="font-size: 0.95rem; color: #2563eb;"></i>
                        <span>QR Code siap scan direct ke halaman verifikasi resmi online.</span>
                    </div>
                    <a href="#" id="kpBtnVerifyLink" target="_blank" style="font-size: 0.76rem; font-weight: 700; color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <span>Coba Direct Scan Link</span> <i class="fas fa-external-link-alt" style="font-size: 0.68rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div style="background: #ffffff; border-top: 1px solid #e2e8f0; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeKartuPelajarModal()" style="font-size: 0.82rem; padding: 7px 16px;">
                Tutup
            </button>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="#" id="kpBtnPrintLink" target="_blank" class="btn btn-primary" style="font-size: 0.82rem; padding: 7px 18px; display: inline-flex; align-items: center; gap: 6px; background: #0284c7; border-color: #0284c7;">
                    <i class="fas fa-print"></i> Cetak Kartu Pelajar
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.btn-kp-tab {
    border: none;
    background: transparent;
    padding: 5px 12px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #475569;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-kp-tab.active {
    background: #ffffff;
    color: #0284c7;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>

<script>
let currentKpNisn = '';

function openKartuPelajarModal(nisn) {
    currentKpNisn = nisn;
    const modal = document.getElementById('kartuPelajarModal');
    if (!modal) return;

    // Tampilkan modal sebagai fixed overlay popup
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    const loading = document.getElementById('kpModalLoading');
    const content = document.getElementById('kpModalContent');
    const container = document.getElementById('kpCardContainer');
    const title = document.getElementById('kpModalTitle');
    const printLink = document.getElementById('kpBtnPrintLink');
    const verifyLink = document.getElementById('kpBtnVerifyLink');

    loading.style.display = 'flex';
    content.style.display = 'none';

    fetch('{{ url("/kartu-pelajar/preview") }}/' + encodeURIComponent(nisn), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Gagal memuat kartu pelajar.');
            closeKartuPelajarModal();
            return;
        }

        title.innerHTML = 'Kartu Pelajar: ' + escapeKpHtml(data.card.nama) + ' (' + escapeKpHtml(data.card.nisn) + ')';
        container.innerHTML = data.html;
        printLink.href = '{{ url("/dashboard/kartu-pelajar/cetak") }}/' + encodeURIComponent(data.card.nisn);
        verifyLink.href = data.card.verify_url;

        loading.style.display = 'none';
        content.style.display = 'block';
        setKpModalTab('both');
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat memuat data kartu pelajar.');
        closeKartuPelajarModal();
    });
}

function closeKartuPelajarModal() {
    const modal = document.getElementById('kartuPelajarModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function handleKpModalBackdropClick(e) {
    if (e.target.id === 'kartuPelajarModal') {
        closeKartuPelajarModal();
    }
}

function setKpModalTab(tab) {
    const btnBoth = document.getElementById('kpBtnTabBoth');
    const btnFront = document.getElementById('kpBtnTabFront');
    const btnBack = document.getElementById('kpBtnTabBack');

    [btnBoth, btnFront, btnBack].forEach(b => { if (b) b.classList.remove('active'); });

    const frontCard = document.querySelector('#kpCardContainer .kp-card-front');
    const backCard = document.querySelector('#kpCardContainer .kp-card-back');

    if (!frontCard || !backCard) return;

    if (tab === 'front') {
        if (btnFront) btnFront.classList.add('active');
        frontCard.style.display = 'flex';
        backCard.style.display = 'none';
    } else if (tab === 'back') {
        if (btnBack) btnBack.classList.add('active');
        frontCard.style.display = 'none';
        backCard.style.display = 'flex';
    } else {
        if (btnBoth) btnBoth.classList.add('active');
        frontCard.style.display = 'flex';
        backCard.style.display = 'flex';
    }
}

function escapeKpHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeKartuPelajarModal();
        if (typeof closeCetakRombelModal === 'function') {
            closeCetakRombelModal();
        }
    }
});
</script>
