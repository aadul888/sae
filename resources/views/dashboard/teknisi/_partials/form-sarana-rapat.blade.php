{{-- Form Ceklist Persiapan Kegiatan Rapat (Indikator #4) --}}
@php
    $edit = $row ?? null;
    $siapTerpilih = [];
    if ($edit && !empty($edit->daftar_sarana)) {
        foreach (json_decode($edit->daftar_sarana, true) ?: [] as $it) {
            $siapTerpilih[$it['jenis'] . ':' . ($it['ref_id'] ?? '')] = true;
        }
    }
@endphp
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Rapat *</label>
        <input type="date" name="tanggal_rapat" class="form-control" required value="{{ $edit->tanggal_rapat ?? date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Nama Rapat *</label>
        <input type="text" name="nama_rapat" class="form-control" required placeholder="Contoh: Rapat Evaluasi Semester" value="{{ $edit->nama_rapat ?? '' }}">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Ruang / Gedung</label>
        <select name="ruang_id" class="form-control">
            <option value="">— Pilih Ruang —</option>
            @foreach (($ruangOptions ?? []) as $r)
                <option value="{{ $r['id'] }}" {{ (string) ($edit->ruang_id ?? '') === (string) $r['id'] ? 'selected' : '' }}>{{ $r['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Jumlah Peserta</label>
        <input type="number" name="jumlah_peserta" class="form-control" min="0" placeholder="0" value="{{ $edit->jumlah_peserta ?? '' }}">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tahap Persiapan</label>
        <select name="tahap" class="form-control">
            @foreach ($tahapList as $k => $v)
                <option value="{{ $k }}" {{ ($edit->tahap ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kelengkapan Sarana</label>
        <select name="kelengkapan_sarana" class="form-control">
            @foreach ($kelengkapanList as $k => $v)
                <option value="{{ $k }}" {{ ($edit->kelengkapan_sarana ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
</div>

<div style="margin-top:14px;">
    <label class="form-label" style="font-weight:600;">Ceklist Kesiapan</label>
    <p style="font-size:var(--text-xs); color:var(--text-muted); margin:2px 0 8px;">
        Centang sarana/ruang yang sudah siap digunakan. Sumber: Ruang &amp; Gedung serta Inventaris &amp; Aset.
    </p>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div style="border:1px solid var(--border-color); border-radius:var(--radius-sm,6px); padding:10px; max-height:190px; overflow:auto;">
            <div style="font-weight:700; font-size:var(--text-xs); color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Ruang &amp; Gedung</div>
            @forelse (($ruangOptions ?? []) as $r)
                <label style="display:flex; align-items:center; gap:6px; font-size:var(--text-sm); padding:2px 0;">
                    <input type="checkbox" name="siap[]" value="ruang:{{ $r['id'] }}"
                        {{ !empty($siapTerpilih['ruang:' . $r['id']]) ? 'checked' : '' }}>
                    <span>{{ $r['label'] }}</span>
                </label>
            @empty
                <span style="font-size:var(--text-xs); color:var(--text-muted);">Belum ada ruang master.</span>
            @endforelse
        </div>
        <div style="border:1px solid var(--border-color); border-radius:var(--radius-sm,6px); padding:10px; max-height:190px; overflow:auto;">
            <div style="font-weight:700; font-size:var(--text-xs); color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Inventaris &amp; Fasilitas</div>
            @forelse (($asetOptions ?? []) as $a)
                <label style="display:flex; align-items:center; gap:6px; font-size:var(--text-sm); padding:2px 0;">
                    <input type="checkbox" name="siap[]" value="aset:{{ $a['id'] }}"
                        {{ !empty($siapTerpilih['aset:' . $a['id']]) ? 'checked' : '' }}>
                    <span>{{ $a['label'] }}</span>
                </label>
            @empty
                <span style="font-size:var(--text-xs); color:var(--text-muted);">Belum ada aset inventaris.</span>
            @endforelse
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Catatan Kebutuhan</label>
        <textarea name="catatan_kebutuhan" class="form-control" rows="2" placeholder="Kebutuhan sarana yang belum terpenuhi...">{{ $edit->catatan_kebutuhan ?? '' }}</textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2">{{ $edit->catatan ?? '' }}</textarea>
    </div>
</div>
<input type="hidden" name="status" value="{{ $edit->status ?? 'selesai' }}">
