@extends('layouts.dashboard')

@section('title', 'Riwayat PIP & Laporan Dana — SAE')
@section('dash_title', 'Riwayat PIP Saya')

@section('content')
    @php
        $statusBadge = fn($status) => match ($status) {
            'diverifikasi' => ['Verified', 'badge-success', 'fa-circle-check'],
            'ditolak'      => ['Ditolak', 'badge-danger', 'fa-circle-xmark'],
            default        => ['Menunggu Verifikasi', 'badge-warning', 'fa-clock'],
        };
        $isOrangTua = ($role ?? '') === 'orang_tua';
    @endphp

    <div class="pip-siswa-page">

    <!-- Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
            <div
                style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <div style="min-width: 0;">
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Riwayat PIP &amp; Laporan Penggunaan Dana
                </h2>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    {{ $pd->nama ?? 'Peserta Didik' }} &bull; NISN {{ $pd->nisn ?: '-' }} &bull;
                    {{ $pd->nama_rombel ?: 'Tanpa Kelas' }}
                    @if ($isOrangTua)
                        &bull; Akses Orang Tua/Wali
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: var(--success, #10b981); padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem);">
            <div style="font-weight: 700; margin-bottom: 6px;"><i class="fas fa-circle-exclamation me-1"></i> Laporan belum dapat disimpan:</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (empty($terdaftar))
        <div class="card"
            style="margin-bottom: 18px; border-left: 4px solid var(--warning, #f59e0b); padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start;">
            <i class="fas fa-circle-info" style="color: var(--warning, #f59e0b); font-size: var(--text-xl, 1.25rem); margin-top: 2px;"></i>
            <div>
                <div style="font-weight: 800; color: var(--text-color);">Belum terdaftar sebagai penerima PIP</div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    Nama Anda tidak ada dalam daftar Riwayat PIP (Siswa Aktif). Data riwayat dan formulir laporan akan tampil di sini setelah terdaftar.
                </div>
            </div>
        </div>
    @endif

    <!-- Stat Grid -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $riwayatList->count() }}</div>
                <div class="dash-stat-label">Riwayat SK PIP</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-lg, 1.125rem);">
                    Rp {{ number_format($totalPemberian, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Dana Pemberian</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $laporanList->count() }}</div>
                <div class="dash-stat-label">Laporan Penggunaan Dana</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary-light, #6366f1);">
                <i class="fas fa-coins"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-lg, 1.125rem);">
                    Rp {{ number_format($totalDilaporkan, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Dana Sudah Dilaporkan</div>
            </div>
        </div>
    </div>

    <!-- Tabs (desktop) -->
    <div class="dash-segmented-tabs dash-desktop-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('dashboard.pip-siswa.index', ['tab' => 'riwayat']) }}"
            class="tab-btn btn {{ $tab === 'riwayat' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-clock-rotate-left me-1"></i> Riwayat PIP
        </a>
        <a href="{{ route('dashboard.pip-siswa.index', ['tab' => 'laporan']) }}"
            class="tab-btn btn {{ $tab === 'laporan' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-file-circle-check me-1"></i> Lapor Penggunaan Dana
        </a>
    </div>

    <!-- Tabs (mobile): dropdown -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="mobile-tab-select-inner">
            <select id="pipTabMobile" class="dash-mobile-tab-select" aria-label="Pilih tab PIP">
                <option value="{{ route('dashboard.pip-siswa.index', ['tab' => 'riwayat']) }}" @selected($tab === 'riwayat')>
                    Riwayat PIP
                </option>
                <option value="{{ route('dashboard.pip-siswa.index', ['tab' => 'laporan']) }}" @selected($tab === 'laporan')>
                    Lapor Penggunaan Dana
                </option>
            </select>
            <i class="fas fa-chevron-down select-chevron"></i>
        </div>
    </div>

    @if ($tab === 'riwayat')
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Jenis SK</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Nomor SK</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Tahap</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Tanggal SK</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: right;">Nominal</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Status / Keterangan</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted);">Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatList as $r)
                            @php
                                $isNominasi = ($r->jenis_sk ?? 'pemberian') === 'nominasi';
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 14px;" data-label="Jenis SK">
                                    <span class="badge {{ $isNominasi ? 'badge-warning' : 'badge-success' }}"
                                        style="font-size: var(--text-xs); padding: 3px 8px;">
                                        {{ $isNominasi ? 'SK Nominasi' : 'SK Pemberian' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-weight: 600;" data-label="Nomor SK">
                                    {{ $r->nomor_sk ?: '-' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm);" data-label="Tahap">
                                    {{ $r->tahap_keterangan ?: ($r->tahap ?: '-') }}
                                    @if ($r->tahap_id)
                                        <span class="text-muted" style="font-size: var(--text-xs);">(ID {{ $r->tahap_id }})</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm);" data-label="Tanggal SK">
                                    {{ $r->tanggal_sk ?: '-' }}
                                </td>
                                <td style="padding: 12px 14px; text-align: right; font-weight: 700; white-space: nowrap;"
                                    data-label="Nominal">
                                    {{ $r->nominal !== null ? 'Rp ' . number_format($r->nominal, 0, ',', '.') : '-' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm);" data-label="Status">
                                    @if ($isNominasi)
                                        <span
                                            class="badge {{ !empty($r->tanggal_aktivasi) ? 'badge-success' : 'badge-warning' }}">
                                            {{ !empty($r->tanggal_aktivasi) ? 'Sudah Aktivasi' : 'Belum Aktivasi' }}
                                        </span>
                                        @if ($r->tanggal_aktivasi)
                                            <div class="text-muted" style="font-size: var(--text-xs); margin-top: 3px;">
                                                Tgl aktivasi: {{ $r->tanggal_aktivasi }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge {{ $r->status_cair ? 'badge-success' : 'badge-warning' }}">
                                            {{ $r->status_cair ?: 'Belum ada status' }}
                                        </span>
                                        @if ($r->tanggal_cair)
                                            <div class="text-muted" style="font-size: var(--text-xs); margin-top: 3px;">
                                                Tgl cair: {{ $r->tanggal_cair }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm);" data-label="Laporan">
                                    @if ($isNominasi)
                                        <span class="text-muted" style="font-size: var(--text-xs);">-</span>
                                    @else
                                        @php $laporanSk = $laporanMap[$r->id] ?? null; @endphp
                                        @if ($laporanSk)
                                            @php [$statusLabel, $badgeClass, $statusIcon] = $statusBadge($laporanSk->status); @endphp
                                            <span class="badge {{ $badgeClass }}"
                                                style="font-size: var(--text-xs); padding: 3px 8px;">
                                                <i class="fas {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                                            </span>
                                            @if ($laporanSk->bukti_list->isNotEmpty())
                                                <button type="button" class="btn-icon"
                                                    data-pip-bukti="#pipBuktiTpl-{{ $laporanSk->id }}"
                                                    data-pip-bukti-title="Bukti Laporan {{ $laporanSk->tahap ?: ($r->nomor_sk ?: '') }}"
                                                    title="Lihat Bukti" aria-label="Lihat Bukti"
                                                    style="color: var(--primary); margin-left: 6px;">
                                                    <i class="fas fa-paperclip"></i>
                                                </button>
                                                <template id="pipBuktiTpl-{{ $laporanSk->id }}">
                                                    @foreach ($laporanSk->bukti_list as $b)
                                                        @php
                                                            $buktiUrl = asset('storage/' . ltrim($b->file_path, '/'));
                                                            $isGambar = in_array(
                                                                strtolower(pathinfo($b->file_path, PATHINFO_EXTENSION)),
                                                                ['jpg', 'jpeg', 'png'],
                                                                true,
                                                            );
                                                        @endphp
                                                        <a href="{{ $buktiUrl }}" target="_blank" rel="noopener"
                                                            style="display: block; margin-bottom: 12px; text-decoration: none;">
                                                            @if ($isGambar)
                                                                <img src="{{ $buktiUrl }}" alt="{{ $b->nama_asli ?: 'Bukti' }}"
                                                                    loading="lazy"
                                                                    style="max-width: 100%; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                                                            @else
                                                                <span
                                                                    style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm);">
                                                                    <i class="fas fa-file-pdf"
                                                                        style="color: var(--danger, #ef4444);"></i>
                                                                    {{ $b->nama_asli ?: 'Lihat dokumen PDF' }}
                                                                </span>
                                                            @endif
                                                            <div
                                                                style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                                                                {{ $b->nama_asli }}
                                                            </div>
                                                        </a>
                                                    @endforeach
                                                </template>
                                            @endif
                                        @else
                                            <span class="badge badge-warning"
                                                style="font-size: var(--text-xs); padding: 3px 8px;">Belum Dilaporkan</span>
                                            <a href="{{ route('dashboard.pip-siswa.index', ['tab' => 'laporan']) }}"
                                                class="btn-icon" title="Laporkan Penggunaan Dana"
                                                aria-label="Laporkan Penggunaan Dana"
                                                style="color: var(--primary); margin-left: 6px;">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                    Belum ada riwayat PIP.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Info Peruntukan Dana -->
        <div class="card" style="margin-bottom: 18px;">
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
                <i class="fas fa-circle-info text-primary me-1"></i> Peruntukan Dana PIP
            </h3>
            <div style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: 10px;">
                Dana PIP digunakan untuk membantu memenuhi biaya personal pendidikan Anda, antara lain:
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach ($kategoriPeruntukan as $kategori)
                    <span class="badge badge-outline" style="font-size: var(--text-xs); padding: 4px 10px;">
                        {{ $kategori }}
                    </span>
                @endforeach
            </div>
        </div>

        <!-- Form Lapor -->
        <div class="card" style="margin-bottom: 18px;">
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0 0 4px 0;">
                <i class="fas fa-file-circle-plus text-primary me-1"></i> Formulir Laporan Penggunaan Dana
            </h3>
            <div style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: 16px;">
                Lampirkan bukti penerimaan sampai penggunaan dana, lalu centang pernyataan di bawah ini.
            </div>

            @if ($riwayatPemberian->isEmpty())
                <div class="badge badge-warning" style="padding: 8px 12px; font-size: var(--text-sm);">
                    Belum ada SK Pemberian (pencairan dana).
                </div>
            @else
                <form method="POST" action="{{ route('dashboard.pip-siswa.laporan.store') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="pip-form-grid">
                        <div>
                            <label for="pipSkSelect" class="pip-label">SK Pemberian</label>
                            <select id="pipSkSelect" name="pip_riwayat_id" class="form-control" required style="width: 100%;">
                                <option value="">-- Pilih SK Pemberian --</option>
                                @foreach ($riwayatPemberian as $rp)
                                    <option value="{{ $rp->id }}" data-nominal="{{ (float) ($rp->nominal ?? 0) }}"
                                        {{ (string) old('pip_riwayat_id') === (string) $rp->id ? 'selected' : '' }}>
                                        {{ $rp->nomor_sk ?: 'SK #' . $rp->id }}
                                        &bull; {{ $rp->tahap_keterangan ?: ($rp->tahap ?: 'Tahap -') }}
                                        &bull; Rp {{ number_format((float) ($rp->nominal ?? 0), 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="pip-label">Dana Diterima</label>
                            <input type="text" id="pipDanaDiterima" class="form-control" value="Rp 0" readonly
                                style="width: 100%; font-weight: 700;">
                        </div>
                    </div>

                    <div class="pip-label" style="margin-bottom: 8px;">
                        Rincian Penggunaan Dana
                    </div>
                    <div class="pip-rincian-grid">
                        @foreach ($kategoriPeruntukan as $i => $kategori)
                            <div class="pip-rincian-card">
                                <label class="pip-rincian-label">
                                    {{ $kategori }}
                                </label>
                                <input type="hidden" name="rincian[{{ $i }}][kategori]" value="{{ $kategori }}">
                                <input type="text" name="rincian[{{ $i }}][nominal]" inputmode="numeric"
                                    autocomplete="off" class="form-control pip-rincian-nominal"
                                    value="{{ old("rincian.$i.nominal") }}" placeholder="0" style="width: 100%;">
                                <input type="text" name="rincian[{{ $i }}][keterangan]" maxlength="255"
                                    class="form-control" value="{{ old("rincian.$i.keterangan") }}"
                                    placeholder="Keterangan (opsional)" style="width: 100%;">
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
                        <div style="font-size: var(--text-sm); color: var(--text-muted);">
                            Total rincian: <strong id="pipTotalRincian" style="color: var(--text-color);">Rp 0</strong>
                        </div>
                        <div id="pipSisaDana" style="font-size: var(--text-sm); color: var(--text-muted);"></div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="pipBukti" style="display: block; font-size: var(--text-sm); font-weight: 700; margin-bottom: 6px;">
                            Bukti Penerimaan &amp; Penggunaan (JPG/PNG/PDF, maks. 4 MB per file, bisa lebih dari satu)
                        </label>
                        <input type="file" id="pipBukti" name="bukti[]" class="form-control"
                            accept=".jpg,.jpeg,.png,.pdf" multiple required style="width: 100%;">
                        <div id="pipBuktiInfo" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;"></div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="pipCatatan" style="display: block; font-size: var(--text-sm); font-weight: 700; margin-bottom: 6px;">Catatan (opsional)</label>
                        <textarea id="pipCatatan" name="catatan" rows="3" maxlength="1000" class="form-control"
                            style="width: 100%;">{{ old('catatan') }}</textarea>
                    </div>

                    <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px 14px; margin-bottom: 16px; display: flex; flex-direction: column; gap: 10px;">
                        <div style="font-weight: 700; font-size: var(--text-sm);">
                            <i class="fas fa-scale-balanced me-1"></i> Pernyataan
                        </div>
                        <label style="display: flex; gap: 10px; align-items: flex-start; font-size: var(--text-sm); margin: 0;">
                            <input type="checkbox" name="pernyataan_tanpa_potongan" value="1"
                                {{ old('pernyataan_tanpa_potongan') ? 'checked' : '' }}>
                            <span>Tidak ada potongan dana dari sekolah maupun pihak lain. Dana diterima utuh sesuai SK.</span>
                        </label>
                        <label style="display: flex; gap: 10px; align-items: flex-start; font-size: var(--text-sm); margin: 0;">
                            <input type="checkbox" name="pernyataan_sesuai_ketentuan" value="1"
                                {{ old('pernyataan_sesuai_ketentuan') ? 'checked' : '' }}>
                            <span>Dana telah digunakan sesuai peruntukan dan ketentuan PIP (biaya personal pendidikan).</span>
                        </label>
                        <label style="display: flex; gap: 10px; align-items: flex-start; font-size: var(--text-sm); margin: 0;">
                            <input type="checkbox" name="pernyataan_kebenaran" value="1"
                                {{ old('pernyataan_kebenaran') ? 'checked' : '' }}>
                            <span>Data, rincian, dan bukti yang saya lampirkan adalah benar dan dapat dipertanggungjawabkan.</span>
                        </label>
                    </div>

                    <div class="pip-submit-row">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Laporan
                        </button>
                    </div>
                </form>
            @endif
        </div>

    @endif

    <!-- Modal Bukti Laporan -->
    <div id="pipBuktiModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="pipBuktiModalTitle">
        <div class="card" style="max-width: 640px; width: 95%;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); margin-bottom: 14px;">
                <h4 id="pipBuktiModalTitle" style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 800;">
                    Bukti Laporan
                </h4>
                <button type="button" class="btn btn-outline" data-pip-bukti-close aria-label="Tutup"
                    style="padding: 4px 10px;">&times;</button>
            </div>
            <div id="pipBuktiModalBody" style="overflow-y: auto; max-height: 65vh;"></div>
        </div>
    </div>
    </div>
@endsection

@push('styles')
    <style>
        .pip-siswa-page .pip-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        .pip-siswa-page .pip-label {
            display: block;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .pip-siswa-page .pip-rincian-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .pip-siswa-page .pip-rincian-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md, 8px);
            background: rgba(0, 0, 0, 0.02);
        }

        .pip-siswa-page .pip-rincian-label {
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            color: var(--text-muted);
            line-height: 1.3;
        }

        .pip-siswa-page .pip-rincian-nominal {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .pip-siswa-page .pip-submit-row {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        @media (max-width: 1200px) {
            .pip-siswa-page .pip-rincian-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .pip-siswa-page .pip-rincian-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pip-siswa-page .pip-form-grid {
                grid-template-columns: 1fr;
            }

            .pip-siswa-page .dash-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .pip-siswa-page .pip-rincian-grid {
                grid-template-columns: 1fr;
            }

            .pip-siswa-page .dash-stat-grid {
                grid-template-columns: 1fr;
            }

            .pip-siswa-page .pip-submit-row .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/pip-siswa.js') }}?v={{ file_exists(public_path('js/pip-siswa.js')) ? filemtime(public_path('js/pip-siswa.js')) : time() }}"></script>
@endpush
