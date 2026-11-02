@extends('layouts.dashboard')

@section('title', 'Pencatatan Pelanggaran Siswa - Pos Keamanan')

@section('content')
<div class="dash-content-body">
    <!-- Header Standar SAE -->
    <div class="dash-header" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shield-halved" style="color: var(--primary);"></i>
                    Pencatatan Pelanggaran Siswa Pos Satpam
                </h1>
                <p style="margin: 4px 0 0 0; font-size: var(--text-sm, 0.875rem); color: var(--text-muted);">
                    Input langsung pelanggaran siswa di pos gerbang / luar saat KBM &bull; Terintegrasi realtime ke Tim Kesiswaan
                </p>
            </div>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenModalPelanggaran"
                    style="border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus-circle"></i> Catat Pelanggaran Siswa
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if (session('success'))
        <div class="alert alert-success" style="margin-bottom: 20px; border-radius: var(--radius-md, 8px); padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Stat Ringkas -->
    <div class="dash-stat-grid" style="margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($totalHariIni) }}</div>
                <div class="dash-stat-label">Pelanggaran Hari Ini</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($totalBulanIni) }}</div>
                <div class="dash-stat-label">Total Bulan Ini</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #059669;">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(count($tataTertibList)) }}</div>
                <div class="dash-stat-label">Aturan Tata Tertib Aktif</div>
            </div>
        </div>
    </div>

    <!-- Filter & Tabel Log Pelanggaran -->
    <div class="card" style="border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color); overflow: hidden; background: var(--bg-card, #fff);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
            <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-list-check" style="color: var(--primary);"></i> Log Catatan Pelanggaran Pos Gerbang
            </div>

            <form method="GET" action="{{ route('dashboard.keamanan.pelanggaran.index') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                    style="padding: 6px 12px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.8125rem);">
                <select name="kategori" class="form-control" style="padding: 6px 12px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.8125rem);">
                    <option value="">Semua Kategori</option>
                    <option value="kehadiran" {{ $kategori === 'kehadiran' ? 'selected' : '' }}>Kehadiran / Jam Belajar</option>
                    <option value="kerapian" {{ $kategori === 'kerapian' ? 'selected' : '' }}>Kerapian Seragam</option>
                    <option value="perilaku" {{ $kategori === 'perilaku' ? 'selected' : '' }}>Perilaku &amp; Ketertiban</option>
                    <option value="larangan_berat" {{ $kategori === 'larangan_berat' ? 'selected' : '' }}>Larangan Berat</option>
                </select>
                <div style="position: relative;">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama siswa / NISN..." class="form-control"
                        style="padding: 6px 12px 6px 30px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.8125rem); min-width: 200px;">
                    <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;"></i>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 6px 14px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.8125rem);">
                    Filter
                </button>
                @if ($q || $tanggal || $kategori)
                    <a href="{{ route('dashboard.keamanan.pelanggaran.index') }}" class="btn btn-outline" style="padding: 6px 12px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.8125rem);">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">Tanggal</th>
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Siswa</th>
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aturan Dilanggar</th>
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Lokasi Kejadian</th>
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Poin</th>
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pelanggaranList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);">
                                {{ date('d/m/Y', strtotime($item->tanggal_kejadian)) }}
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 700; color: var(--text-color);">
                                    {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    NISN: {{ $item->siswa?->nisn ?: '-' }} &bull; {{ $item->siswa?->rombonganBelajar?->nama ?: '-' }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <strong>{{ $item->aturan?->nama_aturan ?: 'Pelanggaran' }}</strong>
                                @if ($item->aturan?->tingkat)
                                    <span class="badge {{ $item->aturan->tingkat === 'berat' ? 'badge-danger' : ($item->aturan->tingkat === 'sedang' ? 'badge-warning' : 'badge-success') }}" style="font-size: 0.65rem; margin-left: 4px;">
                                        {{ strtoupper($item->aturan->tingkat) }}
                                    </span>
                                @endif
                                @if ($item->keterangan)
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        Ket: {{ $item->keterangan }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                <i class="fas fa-location-dot me-1"></i> {{ $item->tempat_kejadian }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($item->sanggah_status)
                                    <span style="text-decoration: line-through; font-size: 0.75rem; color: var(--text-muted);">+{{ $item->poin }}</span>
                                    <div style="font-weight: 800; color: #059669; font-size: 0.875rem;">+{{ $item->poin_netto }}</div>
                                @else
                                    <span style="font-weight: 800; color: var(--danger, #ef4444); font-size: 1rem;">+{{ $item->poin }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($item->sanggah_status)
                                    <span class="badge badge-success" style="font-size: 0.7rem;"><i class="fas fa-check"></i> DISANGGAH</span>
                                @else
                                    @php
                                        $badgeSt = match ($item->status_tindak_lanjut) {
                                            'selesai' => 'badge-success',
                                            'proses'  => 'badge-warning',
                                            default   => 'badge-danger',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeSt }}" style="font-size: 0.7rem;">{{ strtoupper($item->status_tindak_lanjut) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-shield-cat" style="font-size: 2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Belum ada catatan pelanggaran siswa dari pos satpam.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pelanggaranList->hasPages())
            <div style="padding: 12px 20px; border-top: 1px solid var(--border-color);">
                @include('partials.datatable-pagination', ['paginator' => $pelanggaranList])
            </div>
        @endif
    </div>
</div>

<!-- Modal Pencatatan Pelanggaran Siswa Satpam -->
<div id="modalPelanggaranSatpam" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 540px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.12); color: var(--danger); display: flex; align-items: center; justify-content: center; font-size: 1.125rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 800; color: var(--text-color); margin: 0;">Catat Pelanggaran Siswa</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--text-muted);">Pos Keamanan &amp; Gerbang Sekolah</p>
                </div>
            </div>
            <button type="button" class="btn-close-modal"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.125rem; padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('dashboard.keamanan.pelanggaran.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Cari Siswa -->
            <div class="form-group" style="margin-bottom: 14px; position: relative;">
                <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                    Pilih Siswa Pelanggar <span class="text-danger">*</span>
                </label>
                <input type="text" id="inputCariSiswa" class="form-control" placeholder="Ketik nama atau NISN siswa..." autocomplete="off"
                    style="width: 100%; border-radius: var(--radius-md, 8px);" required>
                <input type="hidden" name="peserta_didik_id" id="selectedPesertaDidikId" required>

                <!-- Search dropdown results -->
                <div id="searchSiswaResults" style="display: none; position: absolute; left: 0; right: 0; top: 100%; background: var(--bg-card, #fff); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; z-index: 1000; margin-top: 4px;">
                </div>

                <div id="selectedSiswaBadge" style="display: none; margin-top: 8px; padding: 8px 12px; background: rgba(59,130,246,0.08); border-radius: 6px; font-size: 0.8125rem; color: var(--primary); font-weight: 700; align-items: center; justify-content: space-between;">
                    <span id="selectedSiswaLabel"></span>
                    <button type="button" id="btnClearSelectedSiswa" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.75rem;">
                        <i class="fas fa-times"></i> Ganti
                    </button>
                </div>
            </div>

            <!-- Pilih Tata Tertib -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                    Pelanggaran Tata Tertib <span class="text-danger">*</span>
                </label>
                <select name="tata_tertib_id" id="selectTataTertib" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    <option value="">-- Pilih Aturan Pelanggaran --</option>
                    @foreach ($tataTertibList as $tatib)
                        <option value="{{ $tatib->id }}" data-poin="{{ $tatib->bobot_poin }}">
                            [{{ $tatib->kode }}] {{ $tatib->nama_aturan }} (+{{ $tatib->bobot_poin }} Poin) - {{ strtoupper($tatib->tingkat ?? 'ringan') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        Tanggal Kejadian <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal_kejadian" class="form-control" value="{{ date('Y-m-d') }}" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div class="form-group">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        Lokasi / Pos <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="tempat_kejadian" class="form-control" value="Pos Gerbang Utama" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                    Kronologi / Keterangan Temuan Satpam
                </label>
                <textarea name="keterangan" rows="3" class="form-control"
                    placeholder="Contoh: Siswa melompat pagar samping saat KBM berlangsung / tidak memakai kelengkapan seragam saat keluar gerbang..."
                    style="width: 100%; border-radius: var(--radius-md, 8px);"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                    Foto Bukti Kejadian <small class="text-muted">(Opsional, maks 5 MB)</small>
                </label>
                <input type="file" name="foto_bukti" class="form-control" accept="image/*"
                    style="width: 100%; border-radius: var(--radius-md, 8px);">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-outline btn-close-modal" style="border-radius: var(--radius-md, 8px);">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px); background: var(--danger); border-color: var(--danger);">
                    <i class="fas fa-check-circle"></i> Simpan Pelanggaran
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalPelanggaranSatpam');
    const btnOpen = document.getElementById('btnOpenModalPelanggaran');
    const btnCloses = document.querySelectorAll('.btn-close-modal');

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    btnCloses.forEach(btn => btn.addEventListener('click', closeModal));

    // Siswa Search live
    const inputCari = document.getElementById('inputCariSiswa');
    const resultsBox = document.getElementById('searchSiswaResults');
    const hiddenId = document.getElementById('selectedPesertaDidikId');
    const badgeSiswa = document.getElementById('selectedSiswaBadge');
    const labelSiswa = document.getElementById('selectedSiswaLabel');
    const btnClearSiswa = document.getElementById('btnClearSelectedSiswa');

    let debounceTimer;
    if (inputCari) {
        inputCari.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 2) {
                resultsBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('dashboard.keamanan.pelanggaran.search-siswa') }}?q=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if (data.length === 0) {
                            resultsBox.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8125rem; color: var(--text-muted);">Siswa tidak ditemukan.</div>';
                        } else {
                            data.forEach(item => {
                                const row = document.createElement('div');
                                row.style.padding = '8px 14px';
                                row.style.cursor = 'pointer';
                                row.style.borderBottom = '1px solid var(--border-color)';
                                row.style.fontSize = '0.8125rem';
                                row.innerHTML = `<strong>${item.nama}</strong> <span style="color: var(--text-muted);">(${item.nisn || '-'}) - ${item.rombel_nama || '-'}</span>`;
                                row.addEventListener('mouseover', () => row.style.background = 'rgba(59,130,246,0.06)');
                                row.addEventListener('mouseout', () => row.style.background = 'transparent');
                                row.addEventListener('click', () => {
                                    hiddenId.value = item.peserta_didik_id;
                                    labelSiswa.textContent = `${item.nama} (${item.rombel_nama || '-'})`;
                                    badgeSiswa.style.display = 'flex';
                                    inputCari.style.display = 'none';
                                    inputCari.removeAttribute('required');
                                    resultsBox.style.display = 'none';
                                });
                                resultsBox.appendChild(row);
                            });
                        }
                        resultsBox.style.display = 'block';
                    });
            }, 300);
        });
    }

    if (btnClearSiswa) {
        btnClearSiswa.addEventListener('click', function () {
            hiddenId.value = '';
            badgeSiswa.style.display = 'none';
            inputCari.style.display = 'block';
            inputCari.setAttribute('required', 'required');
            inputCari.value = '';
            inputCari.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (resultsBox && !resultsBox.contains(e.target) && e.target !== inputCari) {
            resultsBox.style.display = 'none';
        }
    });
});
</script>
@endpush
@endsection
