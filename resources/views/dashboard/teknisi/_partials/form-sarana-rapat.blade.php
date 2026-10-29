{{-- Form Laporan Sarana Rapat (Indikator #4) --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Rapat *</label>
        <input type="date" name="tanggal_rapat" class="form-control" required value="{{ date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Nama Rapat *</label>
        <input type="text" name="nama_rapat" class="form-control" required placeholder="Contoh: Rapat Evaluasi Semester">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Ruang Rapat</label>
        <input type="text" name="ruang_nama" class="form-control" list="dlRuangRapat" placeholder="Pilih ruang">
        <datalist id="dlRuangRapat">
            @foreach ($lokasiOptions as $lok)
                <option value="{{ $lok }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Jumlah Peserta</label>
        <input type="number" name="jumlah_peserta" class="form-control" min="0" placeholder="0">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kelengkapan Sarana</label>
        <select name="kelengkapan_sarana" class="form-control">
            @foreach ($kelengkapanList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Daftar Sarana Disiapkan <small style="color:var(--text-muted);">(satu per baris — format: Nama | Jumlah | Kondisi)</small></label>
    <textarea name="daftar_sarana_text" class="form-control" rows="3"
        placeholder="Sound System | 1 | Baik&#10;Proyektor | 1 | Baik&#10;Kursi | 40 | Cukup"></textarea>
    <small style="color:var(--text-muted);">Daftar ini otomatis dikonversi ke rincian sarana tersimpan saat simpan.</small>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Catatan Kebutuhan</label>
        <textarea name="catatan_kebutuhan" class="form-control" rows="2" placeholder="Kebutuhan sarana yang belum terpenuhi..."></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<input type="hidden" name="status" value="selesai">
