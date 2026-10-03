{{-- Modal Unggah & Kelola Pasfoto Peserta Didik (Format PNG untuk Kartu Pelajar Digital) --}}
<div id="fotoUploadModal" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="card"
        style="max-width: 500px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                    <i class="fas fa-camera"></i>
                </div>
                <div>
                    <h3 id="fotoModalTitle"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Unggah Pasfoto Peserta Didik
                    </h3>
                    <div id="fotoModalSubtitle" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeUploadFotoModal()"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
            <input type="hidden" id="fotoUploadPdId" value="">

            {{-- Info Box Persistensi & Kartu Pelajar --}}
            <div
                style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 0.78rem; line-height: 1.5; color: var(--text-color);">
                <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                    <i class="fas fa-shield-halved me-1"></i> Perlindungan Dapodik &amp; Standar Gambar:
                </div>
                <ul style="margin: 0; padding-left: 18px; color: var(--text-muted);">
                    <li>Pasfoto disimpan secara <strong>persisten</strong> di tabel metadata dan <strong>tidak akan
                            terhapus</strong> ketika melakukan tarik data Dapodik.</li>
                    <li>Wajib format <strong>PNG</strong> (akan digunakan untuk kartu pelajar digital &amp; sistem
                        presensi).</li>
                    <li>Sistem melakukan <strong>kompresi otomatis lossless</strong> sehingga file ringan tanpa
                        mengurangi ketajaman.</li>
                </ul>
            </div>

            {{-- Area Preview Pasfoto (3:4) --}}
            <div style="text-align: center; margin-bottom: 16px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                    Pratinjau Pasfoto (Aspek Rasio 3:4)
                </div>
                <div id="fotoPreviewContainer"
                    style="width: 126px; height: 168px; margin: 0 auto; border-radius: 12px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 10px 10px; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 16px rgba(0,0,0,0.35);">
                    <img id="fotoPreviewImg" src="" alt="Pratinjau Foto"
                        style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    <div id="fotoPreviewPlaceholder"
                        style="color: var(--text-muted); font-size: 0.8rem; padding: 10px;">
                        <i class="fas fa-user-graduate mb-2" style="font-size: 2.2rem; opacity: 0.4;"></i>
                        <div style="font-size: 0.72rem;">Belum ada pasfoto</div>
                    </div>
                </div>
                <div id="fotoFileSpecs"
                    style="display: none; font-size: 0.74rem; color: #10b981; margin-top: 8px; font-weight: 600;">
                    -
                </div>
            </div>

            {{-- Form Unggah Drag & Drop --}}
            <form id="fotoUploadForm" enctype="multipart/form-data">
                @csrf
                <div id="fotoDropZone"
                    style="border: 2px dashed rgba(99,102,241,0.4); border-radius: 12px; padding: 20px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.01);"
                    onclick="document.getElementById('fotoFileInput').click()">
                    <i class="fas fa-file-image"
                        style="font-size: 1.8rem; color: var(--primary); margin-bottom: 8px;"></i>
                    <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                        Pilih file atau seret file PNG ke sini
                    </div>
                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                        Hanya file <strong>.PNG</strong> (Maks. 5 MB)
                    </div>
                    <input type="file" id="fotoFileInput" name="foto" accept=".png,image/png"
                        style="display: none;">
                </div>
            </form>
        </div>

        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color);">
            <button type="button" id="btnDeleteFoto" onclick="handleDeleteFoto()" class="btn btn-danger"
                style="display: none; padding: 8px 14px; font-size: 0.8rem;">
                <i class="fas fa-trash-can me-1"></i> Hapus Pasfoto
            </button>
            <div style="display: flex; gap: 8px; margin-left: auto;">
                <button type="button" onclick="closeUploadFotoModal()" class="btn btn-outline"
                    style="padding: 8px 16px; font-size: 0.8rem;">Batal</button>
                <button type="button" id="btnSubmitFoto" onclick="handleSubmitFoto()" class="btn btn-primary"
                    style="padding: 8px 18px; font-size: 0.8rem;">
                    <i class="fas fa-cloud-arrow-up me-1"></i> Simpan Pasfoto
                </button>
            </div>
        </div>
    </div>
</div>
