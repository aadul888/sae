{{--
    Modal "Tandai Selesai" Jurnal Pelaksanaan Tugas.
    Memuat unggah bukti foto sebelum menandai tugas selesai.
    Form action diisi via JS dari tombol `[data-selesai-pelaksanaan]`.
--}}
<div id="modalSelesaiPelaksanaan" class="modal-backdrop"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999 !important; align-items:center; justify-content:center; padding:20px;">
    <div class="modal-card"
        style="background:var(--card-bg,#fff); border-radius:var(--radius-lg,12px); max-width:520px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:var(--text-lg,1.125rem); font-weight:700; color:var(--text-heading);">
                <i class="fas fa-circle-check" style="color:var(--success,#10b981); margin-right:8px;"></i>
                Tandai Tugas Selesai
            </h3>
            <button type="button" data-modal-close="modalSelesaiPelaksanaan"
                style="background:none; border:none; font-size:var(--text-xl,1.25rem); cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form id="formSelesaiPelaksanaan" method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" value="selesai">

            <div style="background:var(--bg-card-hover); padding:12px; border-radius:var(--radius-md,8px); margin-bottom:16px;">
                <div style="font-size:var(--text-xs); color:var(--text-muted);">Area / Rencana</div>
                <div id="selesaiAreaPelaksanaan" style="font-weight:700; color:var(--text-heading);">-</div>
            </div>

            <div>
                <label class="form-label" style="font-weight:600;">Bukti Foto</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
                <small style="display:block; margin-top:4px; font-size:var(--text-xs); color:var(--text-muted);">
                    JPG/PNG/WEBP, maks {{ intdiv(\App\Support\TeknisiFoto::MAX_KB, 1024) }} MB. Opsional.
                </small>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; border-top:1px solid var(--border-color); padding-top:16px;">
                <button type="button" data-modal-close="modalSelesaiPelaksanaan" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"
                    style="background-color:var(--success,#10b981); border-color:var(--success,#10b981);">
                    <i class="fas fa-check"></i> Tandai Selesai
                </button>
            </div>
        </form>
    </div>
</div>
