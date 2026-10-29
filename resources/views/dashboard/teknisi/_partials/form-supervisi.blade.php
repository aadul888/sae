{{-- Form Lembar Observasi Supervisi Teknisi (5 indikator, skor 1-4) --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Tanggal Supervisi *</label>
        <input type="date" name="tanggal_supervisi" class="form-control" required value="{{ date('Y-m-d') }}">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Periode / Label</label>
        <input type="text" name="periode_label" class="form-control" placeholder="Contoh: Semester Ganjil 2026/2027">
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Teknisi yang Disupervisi *</label>
        <input type="search" name="teknisi_nama" class="form-control" required autocomplete="off"
            placeholder="Cari teknisi..." data-people-search="{{ route('dashboard.people.search') }}"
            data-people-kind="gtk" data-people-target="#spvTeknisiPtkId">
        <input type="hidden" name="teknisi_ptk_id" id="spvTeknisiPtkId">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Tahun Ajaran</label>
        <input type="text" name="tahun_ajaran" class="form-control" placeholder="2026/2027">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Semester</label>
        <input type="text" name="semester" class="form-control" placeholder="Ganjil / Genap">
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Nama Supervisor *</label>
        <input type="search" name="supervisor_nama" class="form-control" required autocomplete="off"
            placeholder="Cari GTK atau ketik nama..." data-people-search="{{ route('dashboard.people.search') }}"
            data-people-kind="gtk" data-people-target="#spvSupervisorPtkId">
        <input type="hidden" name="supervisor_ptk_id" id="spvSupervisorPtkId">
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Jabatan Supervisor</label>
        <input type="text" name="supervisor_jabatan" class="form-control" placeholder="Kepala Sekolah / Koordinator TAS">
    </div>
</div>

<hr style="margin:16px 0; border:none; border-top:1px solid var(--border-color);">

<label class="form-label" style="font-weight:700; font-size:var(--text-md); display:block; margin-bottom:8px;">
    <i class="fas fa-table-list me-1"></i> Penilaian Indikator Tupoksi (Skor 1–4)
</label>
<p style="font-size:var(--text-xs); color:var(--text-muted); margin:0 0 12px 0;">
    Skala: 1 = Kurang, 2 = Cukup, 3 = Baik, 4 = Sangat Baik.
</p>

<div style="overflow-x:auto; border:1px solid var(--border-color); border-radius:var(--radius-md, 8px);">
    <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
        <thead>
            <tr style="background:var(--table-header-bg, #f8fafc);">
                <th style="padding:8px 10px; width:36px; text-align:center; border-bottom:1px solid var(--border-color);">No</th>
                <th style="padding:8px 10px; text-align:left; border-bottom:1px solid var(--border-color);">Indikator Tupoksi &amp; Bukti Fisik</th>
                <th style="padding:8px 10px; width:90px; text-align:center; border-bottom:1px solid var(--border-color);">Skor</th>
                <th style="padding:8px 10px; width:200px; text-align:left; border-bottom:1px solid var(--border-color);">Catatan / Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($indikatorList as $no => $ind)
                <tr style="border-bottom:1px solid var(--border-color); vertical-align:top;">
                    <td style="padding:8px 10px; text-align:center; font-weight:700;">{{ $no }}</td>
                    <td style="padding:8px 10px;">
                        <div style="font-weight:700; color:var(--text-heading);">{{ $ind['indikator'] }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); white-space:pre-line; margin-top:2px;">{{ $ind['bukti'] }}</div>
                    </td>
                    <td style="padding:8px 10px; text-align:center;">
                        <select name="skor[{{ $no }}]" class="form-control" style="padding:4px 8px;">
                            <option value="">-</option>
                            @for ($s = 1; $s <= 4; $s++)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endfor
                        </select>
                    </td>
                    <td style="padding:8px 10px;">
                        <textarea name="catatan[{{ $no }}]" class="form-control" rows="2" style="font-size:var(--text-xs);"></textarea>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
    <div>
        <label class="form-label" style="font-weight:600;">Catatan Umum</label>
        <textarea name="catatan_umum" class="form-control" rows="2"></textarea>
    </div>
    <div>
        <label class="form-label" style="font-weight:600;">Rekomendasi</label>
        <textarea name="rekomendasi" class="form-control" rows="2"></textarea>
    </div>
</div>

<div style="margin-top:12px;">
    <label class="form-label" style="font-weight:600;">Status</label>
    <select name="status" class="form-control" style="max-width:200px;">
        @foreach ($statusList as $k => $v)
            <option value="{{ $k }}">{{ $v }}</option>
        @endforeach
    </select>
</div>
