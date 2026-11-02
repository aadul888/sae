{{-- Form konfirmasi pelaksanaan harian (status + uraian + temuan) --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Status</label>
        <select name="status" class="form-control">
            @foreach (($statusList ?? ['belum' => 'Belum Dikerjakan', 'selesai' => 'Selesai']) as $k => $v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Areal / Rencana</label>
        <input type="text" class="form-control" value="{{ $row->area ?? '' }}" disabled>
    </div>
</div>
<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Uraian Pekerjaan</label>
    <textarea name="uraian" class="form-control" rows="2" placeholder="Apa yang dikerjakan...">{{ $row->uraian ?? '' }}</textarea>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Temuan</label>
        <textarea name="temuan" class="form-control" rows="2" placeholder="Kerusakan / hal yang ditemukan...">{{ $row->temuan ?? '' }}</textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2">{{ $row->catatan ?? '' }}</textarea>
    </div>
</div>
