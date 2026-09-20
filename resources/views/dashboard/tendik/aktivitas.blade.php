@extends('layouts.dashboard')

@section('title', 'Log & Aktivitas Kerja Harian Tendik — SAE')
@section('dash_title', 'Aktivitas Harian Tenaga Kependidikan')

@section('content')
    <!-- Top Welcome & Action Banner -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="flex: 1; min-width: 260px;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Log Aktivitas &amp; Agenda Pekerjaan
            </h2>
            <div style="display: flex; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 6px;">
                <span title="Nama Pegawai"><i class="fas fa-user text-primary me-1"></i> <strong>{{ $userName }}</strong></span>
                <span title="Bidang Tugas"><i class="fas fa-briefcase text-info me-1"></i> <strong>{{ $bidangOptions[$activeBidang] ?? 'Administrasi Umum' }}</strong></span>
                <span title="Bulan Aktif"><i class="fas fa-calendar-alt text-warning me-1"></i> <strong>{{ \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F Y') }}</strong></span>
            </div>
            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                Catat seluruh progres pekerjaan, agenda dinas, disposisi, dan hasil keluaran tugas harian secara terstruktur.
            </p>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="btnOpenModalTambah" title="Catat Aktivitas Baru"
                style="background: #10b981; border: none; padding: 8px 14px; font-size: 0.9rem; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                <i class="fas fa-plus"></i>
            </button>
            <a href="{{ route('dashboard.tendik.presensi.index') }}" class="btn btn-outline" title="Rekap Presensi"
                style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px; font-weight: 600; text-decoration: none;">
                <i class="fas fa-file-invoice text-info"></i>
            </a>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalAktivitas }} Agenda</div>
                <div class="dash-stat-label">Total Aktivitas Bulan Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $totalSelesai }} Selesai</div>
                <div class="dash-stat-label">Tuntas Dikerjakan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $totalProses }} Proses</div>
                <div class="dash-stat-label">Sedang Dikerjakan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #ef4444;">{{ $totalTertunda }} Tertunda</div>
                <div class="dash-stat-label">Ada Kendala / Pending</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card" style="padding: 16px 20px; border-radius: 12px; margin-bottom: 20px;">
        <form action="{{ route('dashboard.tendik.aktivitas.index') }}" method="GET" id="formFilterAktivitas" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; flex: 1;">
                <!-- Live Search -->
                <div style="position: relative; min-width: 220px; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari aktivitas, uraian, hasil..." class="form-control"
                        style="padding-left: 34px; font-size: 0.82rem; height: 38px; border-radius: 8px;">
                </div>

                <!-- Bulan -->
                <select name="bulan" class="form-control" style="width: auto; font-size: 0.82rem; height: 38px; border-radius: 8px;" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int)$bulan === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>

                <!-- Tahun -->
                <select name="tahun" class="form-control" style="width: auto; font-size: 0.82rem; height: 38px; border-radius: 8px;" onchange="this.form.submit()">
                    @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <!-- Status -->
                <select name="status" class="form-control" style="width: auto; font-size: 0.82rem; height: 38px; border-radius: 8px;" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="selesai" {{ $filterStatus === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="proses" {{ $filterStatus === 'proses' ? 'selected' : '' }}>Sedang Proses</option>
                    <option value="tertunda" {{ $filterStatus === 'tertunda' ? 'selected' : '' }}>Tertunda</option>
                </select>

                @if ($isKepalaTas)
                    <!-- Bidang Filter untuk Kepala TAS / Admin -->
                    <select name="bidang" class="form-control" style="width: auto; font-size: 0.82rem; height: 38px; border-radius: 8px;" onchange="this.form.submit()">
                        <option value="">-- Semua Bidang Kerja --</option>
                        @foreach ($bidangOptions as $bKey => $bLabel)
                            <option value="{{ $bKey }}" {{ $filterBidang === $bKey ? 'selected' : '' }}>{{ $bLabel }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="submit" class="btn btn-outline" style="height: 38px; padding: 0 12px; font-size: 0.84rem; border-radius: 8px;" title="Terapkan Filter">
                    <i class="fas fa-filter"></i>
                </button>
                @if ($q || $filterStatus || $filterBidang)
                    <a href="{{ route('dashboard.tendik.aktivitas.index') }}" class="btn btn-outline" style="height: 38px; padding: 0 12px; font-size: 0.84rem; border-radius: 8px;" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table Container -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px; border-radius: 14px; overflow: hidden;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.02);">
                    <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); width: 140px;">Waktu &amp; Tanggal</th>
                    @if ($isKepalaTas)
                        <th style="padding: 12px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted);">Pegawai / Bidang</th>
                    @endif
                    <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted);">Aktivitas &amp; Uraian Pekerjaan</th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted);">Hasil / Output</th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 110px;">Status</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($aktivitasList as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td data-label="Waktu & Tanggal" style="padding: 12px 16px; font-size: 0.82rem;">
                            <div style="font-weight: 700; color: var(--text-color);">
                                {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                            </div>
                            <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                <i class="far fa-clock me-1"></i>{{ substr($item->jam_mulai, 0, 5) }} {{ $item->jam_selesai ? '- ' . substr($item->jam_selesai, 0, 5) : 'WIB' }}
                            </div>
                        </td>

                        @if ($isKepalaTas)
                            <td data-label="Pegawai / Bidang" style="padding: 12px 14px; font-size: 0.82rem;">
                                <div style="font-weight: 600; color: var(--text-color);">{{ $item->nama_pegawai }}</div>
                                <span class="badge badge-info" style="font-size: 0.7rem; padding: 2px 6px;">
                                    {{ $bidangOptions[$item->bidang] ?? ucfirst($item->bidang) }}
                                </span>
                            </td>
                        @endif

                        <td data-label="Aktivitas" style="padding: 12px 16px; font-size: 0.82rem;">
                            <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;">
                                {{ $item->judul_aktivitas }}
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">
                                {{ $item->uraian_pekerjaan }}
                            </div>
                            @if ($item->lampiran_path)
                                <div style="margin-top: 6px;">
                                    <a href="{{ asset('storage/' . $item->lampiran_path) }}" target="_blank" class="btn btn-outline"
                                        style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                        <i class="fas fa-paperclip text-primary"></i> Lampiran Dokumen
                                    </a>
                                </div>
                            @endif
                        </td>

                        <td data-label="Hasil / Output" style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-color);">
                            {{ $item->output_hasil ?: '—' }}
                        </td>

                        <td data-label="Status" style="padding: 12px 14px; text-align: center;">
                            @if ($item->status === 'selesai')
                                <span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-circle-check"></i> Selesai
                                </span>
                            @elseif ($item->status === 'proses')
                                <span class="badge badge-warning" style="font-size: 0.74rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-spinner fa-spin"></i> Proses
                                </span>
                            @else
                                <span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-clock-rotate-left"></i> Tertunda
                                </span>
                            @endif
                        </td>

                        <td data-label="Aksi" style="padding: 12px 16px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                <button type="button" class="btn-icon btn-edit-aktivitas"
                                    data-id="{{ $item->id }}"
                                    data-tanggal="{{ $item->tanggal->format('Y-m-d') }}"
                                    data-jam_mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                                    data-jam_selesai="{{ $item->jam_selesai ? substr($item->jam_selesai, 0, 5) : '' }}"
                                    data-bidang="{{ $item->bidang }}"
                                    data-judul="{{ $item->judul_aktivitas }}"
                                    data-uraian="{{ $item->uraian_pekerjaan }}"
                                    data-output="{{ $item->output_hasil }}"
                                    data-status="{{ $item->status }}"
                                    title="Edit Aktivitas"
                                    style="border: 1px solid var(--border-color); background: transparent; padding: 6px; border-radius: 6px; cursor: pointer; color: #3b82f6;">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('dashboard.tendik.aktivitas.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->judul_aktivitas }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus Aktivitas"
                                        style="border: 1px solid var(--border-color); background: transparent; padding: 6px; border-radius: 6px; cursor: pointer; color: #ef4444;">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isKepalaTas ? 6 : 5 }}" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                            <i class="fas fa-clipboard-list" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                            <div style="font-weight: 600;">Belum ada catatan aktivitas harian pada periode ini.</div>
                            <div style="font-size: 0.78rem; margin-top: 4px;">Klik tombol <strong>"Catat Aktivitas Hari Ini"</strong> di atas untuk menambahkan log pekerjaan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi Baku -->
    @if ($aktivitasList->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
            @if ($aktivitasList->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $aktivitasList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $aktivitasList->currentPage();
                $last = $aktivitasList->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $aktivitasList->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $aktivitasList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $aktivitasList->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($aktivitasList->hasMorePages())
                <a href="{{ $aktivitasList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    <!-- Modal Form Tambah / Edit Aktivitas -->
    <div id="modalAktivitas" class="dash-modal" style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
        <div class="dash-modal-content" style="background: var(--card-bg, #1e293b); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 580px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4); animation: modalFadeIn 0.2s ease;">
            <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-color);" id="modalAktivitasTitle">
                    <i class="fas fa-pen-to-square text-primary me-2"></i> Catat Aktivitas Harian
                </div>
                <button type="button" id="btnCloseModalAktivitas" style="background: transparent; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formAktivitas" action="{{ route('dashboard.tendik.aktivitas.store') }}" method="POST" enctype="multipart/form-data" style="padding: 24px;">
                @csrf
                <div id="methodOverride"></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="inputTanggal" value="{{ date('Y-m-d') }}" required class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Bidang Tugas <span class="text-danger">*</span></label>
                        <select name="bidang" id="inputBidang" required class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                            @foreach ($bidangOptions as $bKey => $bLabel)
                                <option value="{{ $bKey }}" {{ $activeBidang === $bKey ? 'selected' : '' }}>{{ $bLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_mulai" id="inputJamMulai" value="{{ date('H:i') }}" required class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="inputJamSelesai" class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Judul Pekerjaan / Agenda <span class="text-danger">*</span></label>
                    <input type="text" name="judul_aktivitas" id="inputJudul" placeholder="Contoh: Pengarsipan Berkas Ijazah, Maintenance Jaringan Lab..." required class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Uraian Pekerjaan / Kegiatan <span class="text-danger">*</span></label>
                    <textarea name="uraian_pekerjaan" id="inputUraian" rows="3" placeholder="Jelaskan secara ringkas aktivitas dan langkah pekerjaan yang dilaksanakan..." required class="form-control" style="font-size: 0.85rem; border-radius: 8px; resize: vertical;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Hasil / Output Keluaran</label>
                        <input type="text" name="output_hasil" id="inputOutput" placeholder="Contoh: 35 Berkas selesai, Server up..." class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Status Pelaksanaan <span class="text-danger">*</span></label>
                        <select name="status" id="inputStatus" required class="form-control" style="font-size: 0.85rem; height: 38px; border-radius: 8px;">
                            <option value="selesai">Selesai</option>
                            <option value="proses">Sedang Proses</option>
                            <option value="tertunda">Tertunda / Ada Kendala</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: block;">Lampiran Bukti (Opsional - PDF/JPG/PNG)</label>
                    <input type="file" name="lampiran" id="inputLampiran" class="form-control" style="font-size: 0.82rem; height: 38px; border-radius: 8px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelModalAktivitas" class="btn btn-outline" style="padding: 9px 18px; font-size: 0.85rem; border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border: none; padding: 9px 20px; font-size: 0.85rem; border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-save me-1"></i> Simpan Aktivitas
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/tendik-aktivitas.js') }}"></script>
@endpush
