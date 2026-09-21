@extends('layouts.dashboard')

@section('title', 'Pemeliharaan Preventif Sarpras - Teknisi')

@section('content')
<div class="dash-content-inner">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Jadwal Pemeliharaan Preventif (Maintenance)
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Program perawatan berkala gedung, kelistrikan, perairan, sanitasi/drainase, pendingin AC, dan infrastruktur IT.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="btnTambahJadwalPM" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;" title="Tambah Jadwal PM">
                <i class="fas fa-plus"></i>
                <span>Tambah Jadwal PM</span>
            </button>
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['terjadwal'] ?? 0) }}</div>
                <div class="dash-stat-label">Terjadwal Datang</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['proses'] ?? 0) }}</div>
                <div class="dash-stat-label">Sedang Berjalan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['selesai'] ?? 0) }}</div>
                <div class="dash-stat-label">Tuntas / Selesai</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['tertunda'] ?? 0) }}</div>
                <div class="dash-stat-label">Tertunda / Lewat Jadwal</div>
            </div>
        </div>
    </div>

    <!-- Filter & Live Search Card -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div style="position: relative; min-width: 240px; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" id="searchPM" class="form-control" placeholder="Cari kode PM, nama kegiatan, lokasi aset, atau PJ..." value="{{ $q ?? '' }}" style="padding-left: 36px;">
                </div>
                <div style="min-width: 170px;">
                    <select id="filterKategoriPM" class="form-control">
                        <option value="">-- Semua Kategori --</option>
                        <option value="bangunan" {{ ($kategori ?? '') === 'bangunan' ? 'selected' : '' }}>Bangunan & Fisik</option>
                        <option value="kelistrikan" {{ ($kategori ?? '') === 'kelistrikan' ? 'selected' : '' }}>Kelistrikan & Panel</option>
                        <option value="perairan" {{ ($kategori ?? '') === 'perairan' ? 'selected' : '' }}>Perairan & Plumbing</option>
                        <option value="kebersihan" {{ ($kategori ?? '') === 'kebersihan' ? 'selected' : '' }}>Kebersihan Lingkungan</option>
                        <option value="ac_pendingin" {{ ($kategori ?? '') === 'ac_pendingin' ? 'selected' : '' }}>AC & Pendingin</option>
                        <option value="it_jaringan" {{ ($kategori ?? '') === 'it_jaringan' ? 'selected' : '' }}>IT & Jaringan</option>
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select id="filterFrekuensi" class="form-control">
                        <option value="">-- Frekuensi --</option>
                        <option value="mingguan" {{ ($frekuensi ?? '') === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ ($frekuensi ?? '') === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                        <option value="triwulan" {{ ($frekuensi ?? '') === 'triwulan' ? 'selected' : '' }}>Triwulan</option>
                        <option value="semesteran" {{ ($frekuensi ?? '') === 'semesteran' ? 'selected' : '' }}>Semesteran</option>
                        <option value="tahunan" {{ ($frekuensi ?? '') === 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    </select>
                </div>
                <div style="min-width: 140px;">
                    <select id="filterStatusPM" class="form-control">
                        <option value="">-- Status --</option>
                        <option value="terjadwal" {{ ($status ?? '') === 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                        <option value="proses" {{ ($status ?? '') === 'proses' ? 'selected' : '' }}>Dalam Proses</option>
                        <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="tertunda" {{ ($status ?? '') === 'tertunda' ? 'selected' : '' }}>Tertunda</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Datatable Container Standard SAE -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode & Kegiatan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bidang & Lokasi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Frekuensi & Jadwal</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">PJ & Biaya</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pemeliharaan as $idx => $row)
                    @php
                        $kategoriLabel = [
                            'bangunan'     => 'Bangunan Fisik',
                            'kelistrikan'  => 'Kelistrikan',
                            'perairan'     => 'Perairan & Pipa',
                            'kebersihan'   => 'Kebersihan Lingkungan',
                            'ac_pendingin' => 'AC & Pendingin',
                            'it_jaringan'  => 'IT & Jaringan',
                        ][$row->kategori] ?? ucfirst($row->kategori);

                        $statusColor = [
                            'terjadwal' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => 'Terjadwal'],
                            'proses'    => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Dalam Proses'],
                            'selesai'   => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Selesai'],
                            'tertunda'  => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'label' => 'Tertunda'],
                        ][$row->status] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => ucfirst($row->status)];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                            {{ ($pemeliharaan->currentPage() - 1) * $pemeliharaan->perPage() + $loop->iteration }}
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 700; color: var(--text-heading); font-size: 0.92rem;">
                                {{ $row->nama_kegiatan }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                {{ $row->kode_pemeliharaan }}
                            </div>
                            @if ($row->catatan_hasil)
                                <div style="font-size: 0.78rem; color: #10b981; margin-top: 4px;">
                                    <i class="fas fa-check-circle"></i> {{ Str::limit($row->catatan_hasil, 60) }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: #f1f5f9; color: var(--text-heading); font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
                                {{ $kategoriLabel }}
                            </span>
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.85rem; margin-top: 4px;">
                                <i class="fas fa-map-marker-alt" style="color: #ef4444; font-size: 0.8rem;"></i> {{ $row->lokasi_aset }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.85rem;">
                            <span class="badge" style="background-color: #ede9fe; color: #6d28d9; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px;">
                                {{ strtoupper($row->frekuensi) }}
                            </span>
                            <div style="color: var(--text-heading); font-weight: 600; margin-top: 4px;">
                                Jadwal: {{ \Carbon\Carbon::parse($row->tgl_jadwal)->format('d M Y') }}
                            </div>
                            @if ($row->tgl_realisasi)
                                <div style="color: #10b981; font-size: 0.78rem;">
                                    Realisasi: {{ \Carbon\Carbon::parse($row->tgl_realisasi)->format('d M Y') }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.85rem;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $row->penanggung_jawab ?: '-' }}</div>
                            @if ($row->biaya > 0)
                                <div style="font-size: 0.8rem; color: #d97706; font-weight: 600; margin-top: 2px;">
                                    Rp {{ number_format($row->biaya, 0, ',', '.') }}
                                </div>
                            @else
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Biaya: Rp 0</div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: {{ $statusColor['bg'] }}; color: {{ $statusColor['color'] }}; font-size: 0.78rem; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                {{ $statusColor['label'] }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                <button type="button" class="btn-icon btn-update-pm" data-item="{{ json_encode($row) }}" title="Update Status & Realisasi" style="background-color: #3b82f6; color: #fff; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-check-square" style="font-size: 0.8rem;"></i>
                                </button>
                                <form action="{{ route('dashboard.teknisi.pemeliharaan.destroy', $row->id) }}" method="POST" style="display: inline;" data-confirm="delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete-pm" data-nama="{{ $row->nama_kegiatan }}" title="Hapus Jadwal" style="background-color: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-clipboard-list" style="font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4;"></i>
                            <p style="margin: 0; font-size: 0.95rem;">Belum ada jadwal pemeliharaan preventif.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi Baku SAE -->
    @if ($pemeliharaan->hasPages())
        <div class="custom-pagination">
            @if ($pemeliharaan->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $pemeliharaan->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $pemeliharaan->currentPage();
                $last = $pemeliharaan->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $pemeliharaan->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $pemeliharaan->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $pemeliharaan->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($pemeliharaan->hasMorePages())
                <a href="{{ $pemeliharaan->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

<!-- Modal Tambah Jadwal PM Baru -->
<div id="modalCreatePM" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                Tambah Jadwal Pemeliharaan Preventif
            </h3>
            <button type="button" onclick="closeModalCreatePM()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form action="{{ route('dashboard.teknisi.pemeliharaan.store') }}" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Nama Kegiatan / Program PM *</label>
                    <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Kuras Toren Air & Pengecekan Pompa Distribusi" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Bidang Sarpras *</label>
                        <select name="kategori" class="form-control" required>
                            <option value="bangunan">Bangunan & Fisik</option>
                            <option value="kelistrikan">Kelistrikan & Panel</option>
                            <option value="perairan">Perairan & Plumbing</option>
                            <option value="kebersihan">Kebersihan Lingkungan</option>
                            <option value="ac_pendingin">AC & Pendingin Ruangan</option>
                            <option value="it_jaringan">IT, Server & Jaringan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Frekuensi Servis *</label>
                        <select name="frekuensi" class="form-control" required>
                            <option value="mingguan">Mingguan</option>
                            <option value="bulanan" selected>Bulanan</option>
                            <option value="triwulan">Triwulan (3 Bulan)</option>
                            <option value="semesteran">Semesteran (6 Bulan)</option>
                            <option value="tahunan">Tahunan</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin: 0;">Lokasi / Aset Sasaran *</label>
                            <span style="font-size: 0.74rem; color: var(--text-muted);"><i class="fas fa-building me-1"></i>Sarpras</span>
                        </div>
                        <input list="listLokasiAsetPm" name="lokasi_aset" class="form-control" placeholder="Pilih Ruang/Aset Sarpras atau ketik..." required>
                        <datalist id="listLokasiAsetPm">
                            @foreach ($daftarRuang as $rng)
                            <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">Ruang: {{ $rng->nama_ruang }} [Lantai {{ $rng->lantai }}]</option>
                            @endforeach
                            @foreach ($daftarAset as $ast)
                            <option value="{{ $ast->nama_barang }} ({{ $ast->kode_aset }})">Aset: {{ $ast->nama_barang }} [{{ $ast->kategori }}]</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Rencana Jadwal *</label>
                        <input type="date" name="tgl_jadwal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin: 0;">Penanggung Jawab</label>
                            <span style="font-size: 0.74rem; color: var(--text-muted);"><i class="fas fa-user-check me-1"></i>GTK</span>
                        </div>
                        <input list="listGtkPm" name="penanggung_jawab" class="form-control" placeholder="Pilih Guru / Tendik atau ketik...">
                        <datalist id="listGtkPm">
                            @foreach ($daftarGtk as $gtk)
                            <option value="{{ $gtk->nama }}">{{ $gtk->nama }} {{ $gtk->nip ? '(NIP: '.$gtk->nip.')' : '' }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Estimasi Anggaran (Rp)</label>
                        <input type="number" name="biaya" class="form-control" value="0" min="0" step="5000">
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalCreatePM()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Update Realisasi PM -->
<div id="modalUpdatePM" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 540px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-tasks" style="color: #3b82f6; margin-right: 8px;"></i>
                Update Realisasi Pemeliharaan
            </h3>
            <button type="button" onclick="closeModalUpdatePM()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="formUpdatePM" action="" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div style="background: var(--table-header-bg, #f8fafc); padding: 12px; border-radius: 8px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Kegiatan:</div>
                    <div id="updateNamaPM" style="font-weight: 700; color: var(--text-heading); font-size: 1rem;">-</div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Status *</label>
                        <select name="status" id="updateStatusPMVal" class="form-control" required>
                            <option value="terjadwal">Terjadwal</option>
                            <option value="proses">Sedang Dikerjakan</option>
                            <option value="selesai">Selesai</option>
                            <option value="tertunda">Tertunda</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Realisasi</label>
                        <input type="date" name="tgl_realisasi" id="updateTglRealisasiVal" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Catatan Hasil & Rekomendasi</label>
                    <textarea name="catatan_hasil" id="updateCatatanHasilVal" class="form-control" rows="3" placeholder="Jelaskan kondisi aset setelah diservis atau catatan komponen yang perlu diganti..."></textarea>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Realisasi Biaya Aktual (Rp)</label>
                    <input type="number" name="biaya" id="updateBiayaPMVal" class="form-control" value="0" min="0" step="1000">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalUpdatePM()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: #3b82f6; border-color: #3b82f6;"><i class="fas fa-save"></i> Simpan Realisasi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/teknisi-pemeliharaan.js') }}"></script>
@endpush
