{{-- Modal Unggah & Kelola Pasfoto GTK (Guru / Tendik / Non-ASN) — Format PNG --}}
@php
    $gtkFotoUploadUrl = $uploadUrl ?? '';
    $gtkFotoDeleteUrl = $deleteUrl ?? '';
@endphp
<div id="gtkFotoUploadModal" class="modal-backdrop"
    data-upload-url="{{ $gtkFotoUploadUrl }}"
    data-delete-url="{{ $gtkFotoDeleteUrl }}"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="card"
        style="max-width: 500px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                    <i class="fas fa-camera"></i>
                </div>
                <div>
                    <h3 id="gtkFotoModalTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Unggah Pasfoto GTK
                    </h3>
                    <div id="gtkFotoModalSubtitle" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeGtkFotoModal()"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
            <input type="hidden" id="gtkFotoUploadPtkId" value="">

            <div
                style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius-lg, 12px); padding: 12px; margin-bottom: 16px; font-size: var(--text-sm, 0.8125rem); line-height: 1.5; color: var(--text-color);">
                <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                    <i class="fas fa-shield-halved me-1"></i> Perlindungan Dapodik &amp; Standar Gambar:
                </div>
                <ul style="margin: 0; padding-left: 18px; color: var(--text-muted);">
                    <li>Pasfoto disimpan di akun pengguna dan <strong>tidak akan terhapus</strong> saat tarik data Dapodik.</li>
                    <li>Wajib format <strong>PNG</strong> (maks. 5 MB) — dikompresi otomatis tanpa mengurangi ketajaman.</li>
                    <li>GTK harus sudah punya akun login agar pasfoto dapat disimpan.</li>
                </ul>
            </div>

            <div style="text-align: center; margin-bottom: 16px;">
                <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                    Pratinjau Pasfoto (Aspek Rasio 3:4)
                </div>
                <div id="gtkFotoPreviewContainer"
                    style="width: 126px; height: 168px; margin: 0 auto; border-radius: var(--radius-lg, 12px); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 10px 10px; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 16px rgba(0,0,0,0.35); cursor: pointer;"
                    onclick="if (event.target !== document.getElementById('gtkFotoFileInput')) document.getElementById('gtkFotoFileInput').click()"
                    title="Klik atau seret file PNG ke sini untuk memilih pasfoto">
                    <img id="gtkFotoPreviewImg" src="" alt="Pratinjau Foto"
                        style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    <div id="gtkFotoPreviewPlaceholder"
                        style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); padding: 10px;">
                        <i class="fas fa-id-badge mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                        <div style="font-size: var(--text-xs, 0.75rem);">Belum ada pasfoto</div>
                    </div>
                </div>
                <div id="gtkFotoFileSpecs"
                    style="display: none; font-size: var(--text-xs, 0.75rem); color: var(--success, #10b981); margin-top: 8px; font-weight: 600;">
                    -
                </div>
            </div>

            <form id="gtkFotoUploadForm" enctype="multipart/form-data">
                @csrf
                <div id="gtkFotoDropZone"
                    style="border: 2px dashed rgba(99,102,241,0.4); border-radius: var(--radius-lg, 12px); padding: 20px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.01);"
                    onclick="if (event.target !== document.getElementById('gtkFotoFileInput')) document.getElementById('gtkFotoFileInput').click()">
                    <i class="fas fa-file-image"
                        style="font-size: var(--text-3xl, 1.875rem); color: var(--primary); margin-bottom: 8px;"></i>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                        Pilih file atau seret file PNG ke sini
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                        Hanya file <strong>.PNG</strong> (Maks. 5 MB)
                    </div>
                    <input type="file" id="gtkFotoFileInput" name="foto" accept=".png,image/png"
                        style="display: none;" onclick="event.stopPropagation()">
                </div>
            </form>
        </div>

        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); gap: 10px; flex-wrap: wrap;">
            <button type="button" id="btnGtkDeleteFoto" onclick="handleGtkDeleteFoto()" class="btn btn-danger"
                style="display: none; padding: 8px 14px; font-size: var(--text-sm, 0.8125rem);">
                <i class="fas fa-trash-can me-1"></i> Hapus Pasfoto
            </button>
            <div style="display: flex; gap: 8px; margin-left: auto;">
                <button type="button" onclick="closeGtkFotoModal()" class="btn btn-outline"
                    style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                <button type="button" id="btnGtkSubmitFoto" onclick="handleGtkSubmitFoto()" class="btn btn-primary"
                    style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">
                    <i class="fas fa-cloud-arrow-up me-1"></i> Simpan Pasfoto
                </button>
            </div>
        </div>
    </div>
</div>
