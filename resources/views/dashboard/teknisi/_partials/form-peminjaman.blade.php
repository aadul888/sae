{{-- Form Ajukan Peminjaman Sarpras (Teknisi) — sumber ruang + aset sarpras. --}}
@php $edit = $row ?? null; @endphp
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tipe Peminjam *</label>
        <select name="peminjam_tipe" id="tpTipe" class="form-control" required>
            @foreach ($tipeList as $k => $v)
                <option value="{{ $k }}" {{ ($edit->peminjam_tipe ?? 'tendik') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Nama Peminjam *</label>
        <input type="search" name="peminjam_nama" id="tpNama" class="form-control" required
            placeholder="Cari siswa/GTK atau ketik nama..." autocomplete="off"
            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="all"
            data-people-target="#tpId" data-people-type-target="#tpTipe" data-people-unit-target="#tpKelas"
            value="{{ $edit->peminjam_nama ?? '' }}">
        <input type="hidden" name="peminjam_id" id="tpId" value="{{ $edit->peminjam_id ?? '' }}">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Kelas / Unit</label>
        <input type="text" name="peminjam_kelas" id="tpKelas" class="form-control" placeholder="Contoh: XI TKJ 1 / Guru" value="{{ $edit->peminjam_kelas ?? '' }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Lokasi Pengambilan</label>
        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Ruang Sarpras" value="{{ $edit->lokasi ?? '' }}">
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Keperluan *</label>
    <input type="text" name="keperluan" class="form-control" required placeholder="Contoh: Kegiatan rapat / perbaikan" value="{{ $edit->keperluan ?? '' }}">
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Pinjam *</label>
        <input type="date" name="tanggal_pinjam" class="form-control" required value="{{ $edit->tanggal_pinjam ?? date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Rencana Kembali *</label>
        <input type="date" name="tanggal_kembali_rencana" class="form-control" required value="{{ $edit->tanggal_kembali_rencana ?? date('Y-m-d', strtotime('+3 days')) }}">
    </div>
</div>

<div style="margin-top:14px;">
    <label class="form-label" style="font-weight:600;">Pilih Ruang / Aset *</label>
    <div style="max-height:250px; overflow-y:auto; padding:10px; border:1px solid var(--border-color); border-radius:var(--radius-sm,6px);">
        <div style="font-size:var(--text-xs); font-weight:700; color:var(--success,#10b981); text-transform:uppercase; margin-bottom:6px;">
            <i class="fas fa-door-open"></i> Ruang & Bangunan
        </div>
        @forelse (($ruangOptions ?? []) as $ro)
            <div style="display:flex; align-items:center; gap:8px; padding:4px 0; border-bottom:1px dashed var(--border-color);">
                <input type="checkbox" name="ruang_ids[]" value="{{ $ro->id }}" class="tp-item" style="cursor:pointer;">
                <div style="flex:1; min-width:0;">
                    <div style="font-size:var(--text-sm); font-weight:600; color:var(--text-color);">{{ $ro->nama_ruang }}</div>
                    <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $ro->kode_ruang ?: '—' }}@if ($ro->gedung) &bull; {{ $ro->gedung }}@endif &bull; Kondisi: {{ $ro->kondisi ?: 'baik' }}</div>
                </div>
            </div>
        @empty
            <p style="font-size:var(--text-xs); color:var(--text-muted); margin:0 0 8px;">Belum ada ruang dalam tanggung jawab Anda.</p>
        @endforelse

        <div style="font-size:var(--text-xs); font-weight:700; color:var(--info,#0ea5e9); text-transform:uppercase; margin:10px 0 6px;">
            <i class="fas fa-boxes-stacked"></i> Inventaris & Aset
        </div>
        @forelse (($asetOptions ?? []) as $ao)
            @php
                $aoTersedia = (int) floor((float) ($ao->unit_tersedia ?? 0));
                $aoTidakBisa = in_array($ao->status_ketersediaan ?? 'tersedia', ['dipinjam', 'hilang'], true) || $aoTersedia <= 0;
            @endphp
            <div style="display:flex; align-items:center; gap:8px; padding:4px 0; border-bottom:1px dashed var(--border-color); {{ $aoTidakBisa ? 'opacity:.55;' : '' }}">
                <input type="checkbox" name="aset_ids[]" value="{{ $ao->id }}" class="tp-item" style="cursor:pointer;" {{ $aoTidakBisa ? 'disabled' : '' }}>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:var(--text-sm); font-weight:600; color:var(--text-color);">{{ $ao->nama_barang }}</div>
                    <div style="font-size:var(--text-xs); color:var(--text-muted);">
                        {{ $ao->kode_aset ?: '—' }} &bull; Tersedia: {{ $aoTersedia }} {{ $ao->satuan ?: 'unit' }}
                        @if ($ao->ruang_nama) &bull; {{ $ao->ruang_nama }} @endif
                        @if ($aoTidakBisa) &bull; <span style="color:var(--danger,#ef4444); font-weight:600;">Tidak tersedia</span> @endif
                    </div>
                </div>
                <input type="number" step="1" min="1" max="{{ max(1, $aoTersedia) }}" name="aset_jumlah[{{ $ao->id }}]" value="1"
                    class="form-control" style="width:80px; height:32px; font-size:var(--text-sm);" title="Jumlah" {{ $aoTidakBisa ? 'disabled' : '' }}>
            </div>
        @empty
            <p style="font-size:var(--text-xs); color:var(--text-muted); margin:0;">Belum ada aset dalam tanggung jawab Anda.</p>
        @endforelse
    </div>
</div>

<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Catatan</label>
    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan...">{{ $edit->catatan ?? '' }}</textarea>
</div>
