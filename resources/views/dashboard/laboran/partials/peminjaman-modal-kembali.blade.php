        {{-- Modal Pengembalian --}}
        <div id="modalKembali" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Proses Pengembalian</h3>
                    <button type="button" data-close="modalKembali" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKembali" method="POST" action="">
                    @csrf
                    <p id="kembaliDesc" style="font-size: var(--text-base); color: var(--text-color); margin-bottom: var(--space-7);"></p>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kondisi Alat Saat Kembali <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="kondisi_kembali" id="kembaliKondisi" class="form-control" required>
                            @foreach ($kondisiList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            <i class="fas fa-info-circle me-1"></i> Jika rusak, kondisi inventaris akan otomatis diperbarui.
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Pengembalian</label>
                        <textarea name="catatan_pengembalian" id="kembaliCatatan" class="form-control" rows="2" placeholder="Catatan kondisi atau keterlambatan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalKembali">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Konfirmasi Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>

