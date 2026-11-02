{{--
    Form Pindahkan Aset — satu-satunya aksi modul Mutasi Lokasi Aset.

    Daftar aset berasal dari Inventaris & Aset (`sarpras_aset`). Menyimpan
    memperbarui `sarpras_aset.ruang_id` (aset benar-benar pindah) sekaligus
    mencatat riwayat ke `teknisi_aset_mutasi`.
--}}
@php
    $ruangOptions = $ruangOptions ?? [];
    $kondisiList  = $kondisiList ?? [];
@endphp

<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div style="grid-column:1 / -1;">
        <label class="form-label" style="font-weight:600;">Aset</label>
        <input type="text" class="form-control" name="aset_label" readonly>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Ruang Saat Ini</label>
        <input type="text" class="form-control" name="dari_ruang" readonly>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Ruang Tujuan <span style="color:var(--danger,#ef4444);">*</span></label>
        <select name="ke_ruang_id" class="form-control" required>
            <option value="">— Pilih Ruang Tujuan —</option>
            @foreach ($ruangOptions as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="{{ now()->toDateString() }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Kondisi Aset</label>
        <select name="kondisi" class="form-control">
            @foreach ($kondisiList as $k => $v)
                <option value="{{ $k }}" {{ $k === 'baik' ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div style="grid-column:1 / -1;">
        <label class="form-label" style="font-weight:600;">Alasan Pemindahan</label>
        <textarea name="alasan" class="form-control" rows="2" placeholder="Alasan memindahkan aset..."></textarea>
    </div>
    <div style="grid-column:1 / -1;">
        <label class="form-label" style="font-weight:600;">Nomor Berita Acara</label>
        <input type="text" name="berita_acara" class="form-control" placeholder="Opsional">
    </div>
</div>
