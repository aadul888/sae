{{-- Modal Pengembalian Peminjaman Sarpras (Teknisi). Param: $kondisiList --}}
<div id="modalKembali" class="modal-backdrop"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99999 !important; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="card" style="width:100%; max-width:520px; padding:28px; border-radius:var(--radius-lg,12px);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-size:var(--text-xl,1.25rem); font-weight:800; color:var(--text-color);">Proses Pengembalian</h3>
            <button type="button" data-modal-close="modalKembali"
                style="background:none; border:none; font-size:var(--text-xl); color:var(--text-muted); cursor:pointer;" title="Tutup">&times;</button>
        </div>

        <form id="formKembaliPeminjaman" method="POST" action="">
            @csrf
            <p id="kembaliDesc" style="font-size:var(--text-base); color:var(--text-color); margin-bottom:18px;"></p>

            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block; font-size:var(--text-base); font-weight:600; margin-bottom:8px; color:var(--text-color);">
                    Kondisi Saat Kembali <span style="color:var(--danger,#ef4444);">*</span>
                </label>
                <select name="kondisi_kembali" class="form-control" required>
                    @foreach ($kondisiList as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
                <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:6px;">
                    <i class="fas fa-info-circle"></i> Bila rusak, kondisi aset akan diperbarui otomatis.
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; font-size:var(--text-base); font-weight:600; margin-bottom:8px; color:var(--text-color);">Catatan Pengembalian</label>
                <textarea name="catatan_pengembalian" class="form-control" rows="2" placeholder="Catatan kondisi atau keterlambatan..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn-secondary" data-modal-close="modalKembali">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Konfirmasi Pengembalian</button>
            </div>
        </form>
    </div>
</div>
