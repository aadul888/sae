{{-- Form Laporan Sarana & Persiapan Kegiatan Rapat (Indikator #4) --}}
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
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tahap Persiapan</label>
        <select name="tahap" class="form-control">
            @foreach ($tahapList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kondisi Ruang</label>
        <select name="kondisi_ruang" class="form-control">
            @foreach ($kondisiList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Daftar Sarana Disiapkan <small style="color:var(--text-muted);">(satu per baris — format: Nama Sarana | Jumlah | Kondisi)</small></label>
    <textarea name="daftar_sarana_text" class="form-control" rows="3"
        placeholder="Proyektor | 1 | Baik&#10;Sound System | 1 | Baik&#10;Kursi | 40 | Cukup"></textarea>
    <small style="color:var(--text-muted);">Sarana diambil dari inventaris & aset yang tersedia; tulis nama sesuai inventaris.</small>
    <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:6px;">
        @foreach (($asetOptions ?? []) as $as)
            <button type="button" class="btn btn-outline btn-sm" style="font-size:var(--text-xs);"
                onclick="var t=document.querySelector('#modalSaranaRapat [name=daftar_sarana_text]'); t.value += (t.value && !t.value.endsWith('\n') ? '\n' : '') + '{{ addslashes($as['label']) }} | 1 | Baik';">+ {{ \Illuminate\Support\Str::limit($as['label'], 30) }}</button>
        @endforeach
    </div>
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
