{{-- Form Jurnal Perbaikan Sarpras (Indikator #5) --}}
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal *</label>
        <input type="date" name="tanggal" class="form-control" required value="{{ date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Jenis Pekerjaan *</label>
        <select name="jenis_pekerjaan" class="form-control" required>
            @foreach ($jenisList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kategori Sarana</label>
        <select name="kategori_sarana" class="form-control">
            <option value="">-- Pilih --</option>
            @foreach ($kategoriList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Lokasi / Unit *</label>
        <input type="text" name="lokasi_unit" class="form-control" required list="dlLokasiPerbaikan"
            placeholder="Contoh: Ruang Guru / Toilet Lt 2">
        <datalist id="dlLokasiPerbaikan">
            @foreach ($lokasiOptions as $lok)
                <option value="{{ $lok }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Status</label>
        <select name="status" class="form-control">
            @foreach ($statusList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Aset Terkait (opsional)</label>
        <select name="aset_id" class="form-control">
            <option value="">-- Tidak terkait aset --</option>
            @foreach ($sources['aset'] ?? [] as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Ruang (opsional)</label>
        <select name="ruang_id" class="form-control">
            <option value="">-- Tidak terkait ruang --</option>
            @foreach ($sources['ruang'] ?? [] as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Uraian Kegiatan *</label>
    <textarea name="uraian_kegiatan" class="form-control" rows="2" required placeholder="Rincian pekerjaan perbaikan/pengecekan..."></textarea>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Hasil</label>
        <textarea name="hasil" class="form-control" rows="2"></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kebutuhan Sarana</label>
        <textarea name="kebutuhan_sarana" class="form-control" rows="2" placeholder="Usulan kebutuhan sarana/prasarana..."></textarea>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Kendala</label>
        <textarea name="kendala" class="form-control" rows="2"></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tindak Lanjut</label>
        <textarea name="tindak_lanjut" class="form-control" rows="2"></textarea>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Estimasi Biaya (Rp)</label>
        <input type="number" name="estimasi_biaya" class="form-control" min="0" step="1000" value="0">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Realisasi Biaya (Rp)</label>
        <input type="number" name="realisasi_biaya" class="form-control" min="0" step="1000">
    </div>
</div>
