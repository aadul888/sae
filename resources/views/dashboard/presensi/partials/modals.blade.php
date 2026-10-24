    <!-- Modal Pasangkan / Binding RFID Siswa -->
    <div id="modalAssignRfid" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card"
            style="max-width: 480px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-id-card text-primary me-2"></i> Pendaftaran Kartu RFID
                </h3>
                <button type="button" id="btnCloseRfidModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formAssignRfid">
                <input type="hidden" id="rfidPdId">
                <input type="hidden" id="rfidTargetType" value="siswa">
                <div style="margin-bottom: 14px; font-size: 0.85rem;">
                    <div style="color: var(--text-muted); margin-bottom: 2px;" id="rfidLabelTarget">Peserta Didik:</div>
                    <div id="rfidPdNama" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color);"></div>
                    <div id="rfidPdNisn" style="font-family: monospace; color: var(--text-muted); font-size: 0.8rem;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        UID Kartu RFID:
                    </label>
                    <input type="text" id="rfidUidInput" required
                        placeholder="Tap kartu pada reader atau ketik UID..." class="form-control"
                        style="width: 100%; padding: 10px 14px; font-family: monospace; font-size: 1rem; letter-spacing: 1px;">
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Tempelkan kartu RFID pada card reader USB (reader akan otomatis mengetikkan nomor UID kartu).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelRfidModal" class="btn btn-outline"
                        style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Simpan Kartu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Snapshot Foto -->
    <div id="modalFotoSnapshot" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card"
            style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); text-align: center;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="fotoSnapshotTitle"
                    style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseFotoModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgFotoSnapshot" src="" alt="Snapshot"
                    style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>
@endsection
