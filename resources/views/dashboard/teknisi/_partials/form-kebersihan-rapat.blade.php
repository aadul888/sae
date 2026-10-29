{{-- Form Kebersihan Ruang Rapat (Indikator #3) --}}
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Kegiatan *</label>
        <input type="date" name="tanggal_kegiatan" class="form-control" required value="{{ date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Area</label>
        <select name="area" class="form-control">
            @foreach ($areaList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tahap</label>
        <select name="tahap" class="form-control">
            @foreach ($tahapList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Lokasi</label>
        <input type="text" name="lokasi" class="form-control" list="dlLokasiRapat" placeholder="Contoh: Ruang Rapat Utama" value="Ruang Rapat">
        <datalist id="dlLokasiRapat">
            @foreach (($lokasiOptions ?? []) as $lok)
                <option value="{{ $lok }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Nama Rapat / Kegiatan</label>
        <input type="text" name="nama_rapat" class="form-control" placeholder="Contoh: Rapat Dinas Bulanan">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Kondisi Akhir</label>
        <select name="kondisi" class="form-control">
            @foreach ($kondisiList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
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
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Uraian Kegiatan</label>
    <textarea name="uraian_kegiatan" class="form-control" rows="2" placeholder="Rincian pekerjaan kebersihan teras/halaman/ruang rapat..."></textarea>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Temuan</label>
        <textarea name="temuan" class="form-control" rows="2"></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tindak Lanjut</label>
        <textarea name="tindak_lanjut" class="form-control" rows="2"></textarea>
    </div>
</div>
