{{-- Form Area Tugas (Indikator #1) — dipakai modal create/edit --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Area Tugas *</label>
        <input type="text" name="area" class="form-control" required list="dlAreaTugas"
            placeholder="Contoh: Ruang Kepala Sekolah / WC / Teras">
        <datalist id="dlAreaTugas">
            @foreach ($lokasiOptions as $lok)
                <option value="{{ $lok }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Zona / Gedung</label>
        <input type="text" name="zona" class="form-control" placeholder="Contoh: Gedung A / Lantai 1">
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Jenis Tugas</label>
        <select name="jenis_tugas" class="form-control">
            @foreach ($jenisList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Periode</label>
        <select name="periode_jadwal" class="form-control">
            @foreach ($periodeList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Shift</label>
        <select name="shift" class="form-control">
            @foreach ($shiftList as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-top: 12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Nama Petugas *</label>
        <input type="search" name="petugas_nama" class="form-control" required autocomplete="off"
            placeholder="Cari GTK atau ketik nama..."
            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk"
            data-people-target="#atPetugasPtkId">
        <input type="hidden" name="petugas_ptk_id" id="atPetugasPtkId">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai" class="form-control">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Selesai</label>
        <input type="date" name="tanggal_selesai" class="form-control">
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Uraian Tugas</label>
        <textarea name="uraian" class="form-control" rows="2"
            placeholder="Rincian pekerjaan kebersihan / pengolahan sampah..."></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;"><i class="fas fa-user-shield me-1"></i>Laporan Siswa Tidak Disiplin (dari Tim Keamanan)</label>
        <textarea name="laporan_siswa" class="form-control" rows="2"
            placeholder="Catatan laporan siswa tidak disiplin yang diterima..."></textarea>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Sumber Laporan</label>
        <input type="text" name="sumber_laporan" class="form-control" placeholder="Contoh: Tim Keamanan">
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
