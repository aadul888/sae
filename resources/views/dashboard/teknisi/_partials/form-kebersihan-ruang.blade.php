{{-- Form Kebersihan Ruang (Indikator #2) --}}
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Kegiatan *</label>
        <input type="date" name="tanggal_kegiatan" class="form-control" required value="{{ date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kategori Ruang</label>
        <select name="kategori_ruang" class="form-control">
            @foreach ($kategoriList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kondisi Akhir</label>
        <select name="kondisi" class="form-control">
            @foreach ($kondisiList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Nama Ruang / Area *</label>
    <input type="text" name="ruang_nama" class="form-control" required list="dlRuangKebersihan"
        placeholder="Contoh: Ruang Kepala Sekolah / Jamban Siswa / Gor Sekolah">
    <datalist id="dlRuangKebersihan">
        @foreach ($lokasiOptions as $lok)
            <option value="{{ $lok }}"></option>
        @endforeach
    </datalist>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Uraian Kegiatan</label>
    <textarea name="uraian_kegiatan" class="form-control" rows="2" placeholder="Rincian pekerjaan kebersihan..."></textarea>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Peralatan Digunakan</label>
        <input type="text" name="peralatan_digunakan" class="form-control" placeholder="Sapu, pel, dsb.">
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
        <label class="form-label" style="font-weight:600;">Temuan</label>
        <textarea name="temuan" class="form-control" rows="2" placeholder="Temuan kondisi..."></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tindak Lanjut</label>
        <textarea name="tindak_lanjut" class="form-control" rows="2" placeholder="Rencana tindak lanjut..."></textarea>
    </div>
</div>
