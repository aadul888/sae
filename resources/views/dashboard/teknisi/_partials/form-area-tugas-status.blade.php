{{-- Form ubah status jadwal area tugas (aksi cepat "Konfirmasi") --}}
<div>
    <label class="form-label" style="font-weight:600;">Status Jadwal *</label>
    <select name="status" class="form-control" required>
        @foreach ($statusList as $k => $v)
            <option value="{{ $k }}">{{ $v }}</option>
        @endforeach
    </select>
</div>
<p style="margin:10px 0 0; font-size:var(--text-sm); color:var(--text-muted);">
    <i class="fas fa-circle-info me-1"></i>Ubah status jadwal tanpa mengedit field lain.
</p>
