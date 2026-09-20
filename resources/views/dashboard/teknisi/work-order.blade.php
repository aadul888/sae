@extends('layouts.dashboard')

@section('title', 'Work Order & Tiket Perbaikan - Teknisi')

@section('content')
<div class="dash-content-inner">
    <!-- Header Page -->
    <div class="dash-header-section" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                    <i class="fas fa-tools" style="color: var(--primary-color); margin-right: 8px;"></i>
                    Work Order & Tiket Perbaikan
                </h1>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                    Layanan perbaikan sarpras multi-bidang: Bangunan, Kelistrikan, Perairan/Plumbing, Kebersihan Lingkungan, AC, dan IT.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" id="btnBuatWorkOrder" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Buat Work Order
                </button>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Antrean Tiket</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #d97706; margin-top: 4px;">{{ number_format($stats['total_antrean'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: #d97706; margin-top: 4px;"><i class="fas fa-clock"></i> Menunggu penanganan</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Sedang Dikerjakan</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #2563eb; margin-top: 4px;">{{ number_format($stats['total_proses'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: #2563eb; margin-top: 4px;"><i class="fas fa-spinner fa-spin"></i> Dalam proses teknisi</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Perbaikan Selesai</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ number_format($stats['total_selesai'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: #10b981; margin-top: 4px;"><i class="fas fa-check-circle"></i> Selesai dituntaskan</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Urgensi Darurat</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #ef4444; margin-top: 4px;">{{ number_format($stats['total_darurat'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: #ef4444; margin-top: 4px;"><i class="fas fa-exclamation-triangle"></i> Butuh respon cepat</div>
        </div>
    </div>

    <!-- Filter & Live Search Card -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div style="position: relative; min-width: 240px; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" id="searchWorkOrder" class="form-control" placeholder="Cari nomor WO, lokasi, deskripsi kerusakan, atau pelapor..." value="{{ $q ?? '' }}" style="padding-left: 36px;">
                </div>
                <div style="min-width: 170px;">
                    <select id="filterKategori" class="form-control">
                        <option value="">-- Semua Bidang --</option>
                        <option value="bangunan_fisik" {{ ($kategori ?? '') === 'bangunan_fisik' ? 'selected' : '' }}>Bangunan & Fisik</option>
                        <option value="kelistrikan" {{ ($kategori ?? '') === 'kelistrikan' ? 'selected' : '' }}>Kelistrikan & Panel</option>
                        <option value="sanitasi_plumbing" {{ ($kategori ?? '') === 'sanitasi_plumbing' ? 'selected' : '' }}>Perairan & Sanitasi</option>
                        <option value="kebersihan_lingkungan" {{ ($kategori ?? '') === 'kebersihan_lingkungan' ? 'selected' : '' }}>Kebersihan & Lingkungan</option>
                        <option value="ac_pendingin" {{ ($kategori ?? '') === 'ac_pendingin' ? 'selected' : '' }}>AC & Pendingin</option>
                        <option value="komputer" {{ ($kategori ?? '') === 'komputer' ? 'selected' : '' }}>Komputer & IT</option>
                        <option value="internet_jaringan" {{ ($kategori ?? '') === 'internet_jaringan' ? 'selected' : '' }}>Jaringan & Internet</option>
                        <option value="audio_bel" {{ ($kategori ?? '') === 'audio_bel' ? 'selected' : '' }}>Audio & Bel Sekolah</option>
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select id="filterStatus" class="form-control">
                        <option value="">-- Semua Status --</option>
                        <option value="antrean" {{ ($status ?? '') === 'antrean' ? 'selected' : '' }}>Antrean</option>
                        <option value="proses" {{ ($status ?? '') === 'proses' ? 'selected' : '' }}>Dalam Proses</option>
                        <option value="menunggu_sparepart" {{ ($status ?? '') === 'menunggu_sparepart' ? 'selected' : '' }}>Menunggu Sparepart</option>
                        <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div style="min-width: 140px;">
                    <select id="filterUrgensi" class="form-control">
                        <option value="">-- Urgensi --</option>
                        <option value="darurat" {{ ($urgensi ?? '') === 'darurat' ? 'selected' : '' }}>Darurat</option>
                        <option value="normal" {{ ($urgensi ?? '') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="rendah" {{ ($urgensi ?? '') === 'rendah' ? 'selected' : '' }}>Rendah</option>
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
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nomor WO & Tgl</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bidang & Lokasi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Deskripsi Kerusakan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Urgensi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($workOrders as $idx => $row)
                    @php
                        $kategoriLabel = [
                            'bangunan_fisik'        => 'Bangunan Fisik',
                            'kelistrikan'           => 'Kelistrikan',
                            'sanitasi_plumbing'     => 'Perairan / Plumbing',
                            'kebersihan_lingkungan' => 'Kebersihan Lingkungan',
                            'ac_pendingin'          => 'AC & Pendingin',
                            'komputer'              => 'Komputer & IT',
                            'internet_jaringan'     => 'Jaringan Internet',
                            'audio_bel'             => 'Audio & Bel',
                        ][$row->kategori_perbaikan] ?? ucfirst(str_replace('_', ' ', $row->kategori_perbaikan));

                        $urgensiColor = [
                            'darurat' => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
                            'normal'  => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
                            'rendah'  => ['bg' => '#f1f5f9', 'color' => '#475569'],
                        ][$row->tingkat_urgensi] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];

                        $statusColor = [
                            'antrean'            => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Antrean'],
                            'proses'             => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'Dikerjakan'],
                            'menunggu_sparepart' => ['bg' => '#fce7f3', 'color' => '#9d174d', 'label' => 'Menunggu Part'],
                            'selesai'            => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Selesai'],
                        ][$row->status] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => ucfirst($row->status)];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                            {{ ($workOrders->currentPage() - 1) * $workOrders->perPage() + $loop->iteration }}
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 700; color: var(--text-heading); font-family: monospace; font-size: 0.9rem;">
                                {{ $row->nomor_wo }}
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                <i class="far fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}
                            </div>
                            @if ($row->pelapor_nama)
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Oleh: <strong>{{ $row->pelapor_nama }}</strong>
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: #f1f5f9; color: var(--text-heading); font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
                                {{ $kategoriLabel }}
                            </span>
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.88rem; margin-top: 4px;">
                                <i class="fas fa-map-marker-alt" style="color: #ef4444; font-size: 0.8rem;"></i> {{ $row->lokasi_unit }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-size: 0.88rem; color: var(--text-heading); max-width: 320px; line-height: 1.4;">
                                {{ Str::limit($row->deskripsi_kerusakan, 90) }}
                            </div>
                            @if ($row->tindakan_perbaikan)
                                <div style="font-size: 0.78rem; color: #10b981; margin-top: 4px;">
                                    <i class="fas fa-check"></i> {{ Str::limit($row->tindakan_perbaikan, 60) }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: {{ $urgensiColor['bg'] }}; color: {{ $urgensiColor['color'] }}; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 600; text-transform: uppercase;">
                                {{ $row->tingkat_urgensi }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: {{ $statusColor['bg'] }}; color: {{ $statusColor['color'] }}; font-size: 0.78rem; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                {{ $statusColor['label'] }}
                            </span>
                            @if ($row->teknisi)
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
                                    Teknisi: {{ $row->teknisi->nama }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                <button type="button" class="btn-icon btn-update-wo" data-item="{{ json_encode($row) }}" title="Update Status & Tindakan" style="background-color: #3b82f6; color: #fff; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                </button>
                                <form action="{{ route('dashboard.teknisi.work-order.destroy', $row->id) }}" method="POST" style="display: inline;" data-confirm="delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete-wo" data-nomor="{{ $row->nomor_wo }}" title="Hapus WO" style="background-color: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-clipboard-check" style="font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4;"></i>
                            <p style="margin: 0; font-size: 0.95rem;">Belum ada tiket work order perbaikan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi Baku SAE -->
    @if ($workOrders->hasPages())
        <div class="custom-pagination">
            @if ($workOrders->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $workOrders->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $workOrders->currentPage();
                $last = $workOrders->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $workOrders->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $workOrders->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $workOrders->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($workOrders->hasMorePages())
                <a href="{{ $workOrders->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

<!-- Modal Buat Work Order Baru -->
<div id="modalCreateWO" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 620px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                Buat Work Order / Tiket Perbaikan
            </h3>
            <button type="button" onclick="closeModalCreateWO()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form action="{{ route('dashboard.teknisi.work-order.store') }}" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Laporan *</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tingkat Urgensi *</label>
                        <select name="tingkat_urgensi" class="form-control" required>
                            <option value="normal">Normal</option>
                            <option value="darurat">Darurat (Mendesak)</option>
                            <option value="rendah">Rendah (Dapat Ditunda)</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Bidang Perbaikan *</label>
                        <select name="kategori_perbaikan" class="form-control" required>
                            <option value="bangunan_fisik">Bangunan Fisik (Plafon, Pintu, Atap, Dinding)</option>
                            <option value="kelistrikan">Kelistrikan (Lampu, MCB, Saklar, Genset)</option>
                            <option value="sanitasi_plumbing">Perairan & Plumbing (Pipa, Toren, Pompa, WC)</option>
                            <option value="kebersihan_lingkungan">Kebersihan & Lingkungan (Selokan, Drainase, Sampah)</option>
                            <option value="ac_pendingin">AC & Pendingin Ruangan</option>
                            <option value="komputer">Komputer & Perangkat IT</option>
                            <option value="internet_jaringan">Jaringan Internet / WiFi</option>
                            <option value="audio_bel">Audio & Bel Sekolah</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Lokasi / Ruangan *</label>
                        <input type="text" name="lokasi_unit" class="form-control" placeholder="Contoh: Lab Komputer 2, Toilet Guru, R. Kelas XI" required>
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Nama Pelapor</label>
                    <input type="text" name="pelapor_nama" class="form-control" placeholder="Nama guru, siswa, atau staf yang melapor" value="{{ session('user.nama') ?? '' }}">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Deskripsi Kerusakan / Kendala *</label>
                    <textarea name="deskripsi_kerusakan" class="form-control" rows="3" placeholder="Jelaskan secara rinci tanda-tanda kerusakan atau permasalahan yang terjadi..." required></textarea>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalCreateWO()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Work Order</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Update Status & Tindakan Perbaikan -->
<div id="modalUpdateWO" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 580px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-wrench" style="color: #3b82f6; margin-right: 8px;"></i>
                Update Tindakan & Status WO
            </h3>
            <button type="button" onclick="closeModalUpdateWO()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="formUpdateWO" action="" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div style="background: var(--table-header-bg, #f8fafc); padding: 12px; border-radius: 8px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Nomor WO:</div>
                    <div id="updateNomorWO" style="font-weight: 700; color: var(--text-heading); font-size: 1rem; font-family: monospace;">-</div>
                    <div id="updateDeskripsiWO" style="font-size: 0.85rem; color: var(--text-heading); margin-top: 4px;">-</div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Status Pengerjaan *</label>
                        <select name="status" id="updateStatusVal" class="form-control" required>
                            <option value="antrean">Antrean</option>
                            <option value="proses">Sedang Dikerjakan</option>
                            <option value="menunggu_sparepart">Menunggu Sparepart</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Teknisi Penanggung Jawab</label>
                        <select name="teknisi_ptk_id" id="updateTeknisiVal" class="form-control">
                            <option value="">-- Pilih Petugas / Teknisi --</option>
                            @foreach ($teknisiList as $gtk)
                                <option value="{{ $gtk->ptk_id }}">{{ $gtk->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tindakan Perbaikan yang Dilakukan</label>
                    <textarea name="tindakan_perbaikan" id="updateTindakanVal" class="form-control" rows="3" placeholder="Jelaskan tindakan teknisi yang telah dilakukan untuk menyelesaikan masalah..."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Biaya Sparepart / Bahan (Rp)</label>
                        <input type="number" name="estimasi_biaya_part" id="updateBiayaVal" class="form-control" value="0" min="0" step="1000">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" id="updateTglSelesaiVal" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalUpdateWO()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: #3b82f6; border-color: #3b82f6;"><i class="fas fa-save"></i> Simpan Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/teknisi-work-order.js') }}"></script>
@endpush
