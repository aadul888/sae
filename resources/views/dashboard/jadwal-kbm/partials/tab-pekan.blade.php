{{--
    Tab: Pemberlakuan Jadwal per Pekan (lapis 2 dari 3).

    Tabel ini TIDAK mengubah pola `jadwal_kbm`. Ia hanya mengatur pekan mana
    yang sudah boleh diberlakukan (terlihat guru/siswa) beserta rentang
    tanggalnya. Status awal setiap pekan hasil generate adalah `draft`.
--}}
<div class="datatable-card-wrapper" id="tabPekanContainer">
    {{-- 1. Toolbar --}}
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="pekanPerPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="pekanPerPageSelect" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>pekan</span>
                </div>

                <select id="pekanFilterStatus" class="toolbar-filter-select" style="height: 38px;">
                    <option value="">Semua Status</option>
                    @foreach (\App\Models\JadwalKbmPekan::STATUS_LABELS as $sKey => $sLabel)
                        <option value="{{ $sKey }}">{{ $sLabel }}</option>
                    @endforeach
                </select>

                <span class="badge"
                    style="background: var(--bg-hover); color: var(--text-muted); border: 1px solid var(--border-color); font-size: var(--text-sm); padding: 6px 10px; border-radius: var(--radius-md);">
                    <i class="fas fa-calendar-week me-1"></i>
                    {{ $periodePekan['label'] ?? '-' }}
                </span>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="pekanLiveSearch" placeholder="Cari pekan, tanggal, catatan..."
                        autocomplete="off">
                </div>

                @if ($canUpdate)
                    <button type="button" class="btn btn-primary" id="btnGeneratePekan"
                        data-url="{{ route('dashboard.jadwal-kbm.pekan.generate') }}"
                        style="padding: 8px 14px; font-size: var(--text-base); height: 38px;"
                        title="Sinkronkan pekan dengan Kalender Pendidikan">
                        <i class="fas fa-rotate me-1"></i> Sinkron Pekan
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 2. Datatable --}}
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                    <th
                        style="width: 60px; text-align: center; padding: 12px 8px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Pekan
                    </th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Rentang Tanggal
                    </th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Hari Efektif
                    </th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Sumber
                    </th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Status
                    </th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Catatan
                    </th>
                    @if ($canUpdate)
                        <th
                            style="width: 90px; text-align: center; padding: 12px 14px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Aksi
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody id="pekanTableBody">
                @forelse ($pekanList as $pekan)
                    <tr data-pekan-id="{{ $pekan->id }}" data-status="{{ $pekan->status }}"
                        data-search="{{ strtolower('pekan ' . $pekan->pekan_ke . ' ' . $pekan->rentang_label . ' ' . ($pekan->catatan ?? '') . ' ' . $pekan->status_label) }}"
                        style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                        <td style="text-align: center; padding: 12px 8px;">
                            <span class="badge badge-primary"
                                style="font-size: var(--text-sm); font-weight: 700; padding: 4px 10px; border-radius: var(--radius-md);">
                                {{ $pekan->pekan_ke }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base);">
                                {{ $pekan->rentang_label }}
                            </div>
                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px;">
                                <i class="fas fa-calendar-day me-1"></i>
                                {{ $pekan->tanggal_mulai->translatedFormat('l') }} –
                                {{ $pekan->tanggal_selesai->translatedFormat('l') }}
                            </div>
                        </td>
                        <td style="padding: 12px 14px;">
                            @if ($pekan->is_efektif)
                                <span class="badge"
                                    style="background: rgba(16,185,129,0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.3); font-size: var(--text-sm); padding: 4px 8px;">
                                    <i class="fas fa-check-circle me-1"></i> Efektif
                                </span>
                            @else
                                <span class="badge"
                                    style="background: var(--bg-hover); color: var(--text-muted); border: 1px solid var(--border-color); font-size: var(--text-sm); padding: 4px 8px;">
                                    <i class="fas fa-minus-circle me-1"></i> Tidak Efektif
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px;">
                            @if (!empty($pekan->sumber))
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: var(--text-sm); padding: 4px 8px;">
                                    {{ ucfirst($pekan->sumber) }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: var(--text-sm);"
                                    title="Mengikuti mode pemberlakuan global">
                                    Ikut global
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px;">
                            <span class="badge pekan-status-badge"
                                style="{{ $pekan->status_badge }} font-size: var(--text-sm); padding: 4px 8px;">
                                {{ $pekan->status_label }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px;">
                            <span style="font-size: var(--text-sm); color: var(--text-muted);">
                                {{ $pekan->catatan ?: '-' }}
                            </span>
                        </td>
                        @if ($canUpdate)
                            <td style="text-align: center; padding: 12px 14px;">
                                <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                    <button type="button" class="btn-icon btn-edit-pekan"
                                        title="Atur pekan ke-{{ $pekan->pekan_ke }}"
                                        aria-label="Atur pekan ke-{{ $pekan->pekan_ke }}"
                                        data-id="{{ $pekan->id }}" data-pekan-ke="{{ $pekan->pekan_ke }}"
                                        data-tanggal-mulai="{{ $pekan->tanggal_mulai->toDateString() }}"
                                        data-tanggal-selesai="{{ $pekan->tanggal_selesai->toDateString() }}"
                                        data-sumber="{{ $pekan->sumber }}" data-status="{{ $pekan->status }}"
                                        data-catatan="{{ $pekan->catatan }}"
                                        data-url="{{ route('dashboard.jadwal-kbm.pekan.update', $pekan->id) }}">
                                        <i class="fas fa-pencil text-primary"></i>
                                    </button>

                                    @if ($pekan->status === 'draft')
                                        <button type="button" class="btn-icon btn-toggle-pekan"
                                            title="Berlakukan pekan ke-{{ $pekan->pekan_ke }}"
                                            aria-label="Berlakukan pekan ke-{{ $pekan->pekan_ke }}"
                                            data-id="{{ $pekan->id }}" data-status="aktif"
                                            data-label="pekan ke-{{ $pekan->pekan_ke }}"
                                            data-url="{{ route('dashboard.jadwal-kbm.pekan.toggle', $pekan->id) }}">
                                            <i class="fas fa-play text-success"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn-icon btn-toggle-pekan"
                                            title="Tarik ke draft pekan ke-{{ $pekan->pekan_ke }}"
                                            aria-label="Tarik ke draft pekan ke-{{ $pekan->pekan_ke }}"
                                            data-id="{{ $pekan->id }}" data-status="draft"
                                            data-label="pekan ke-{{ $pekan->pekan_ke }}"
                                            data-url="{{ route('dashboard.jadwal-kbm.pekan.toggle', $pekan->id) }}">
                                            <i class="fas fa-pause text-warning"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canUpdate ? 7 : 6 }}"
                            style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                            <div style="font-size: var(--text-3xl); margin-bottom: 10px; color: var(--border-color);">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div style="font-weight: 700; font-size: var(--text-md); color: var(--text-color); margin-bottom: 4px;">
                                Pekan Belum Dibentuk
                            </div>
                            <p style="font-size: var(--text-base); margin-bottom: 0; max-width: 520px; margin-left: auto; margin-right: auto;">
                                Klik <strong>Sinkron Pekan</strong> untuk membentuk pekan otomatis dari rentang
                                Semester {{ $semesterAktif }} ({{ $periodePekan['label'] ?? '-' }}).
                                Seluruh pekan baru berstatus <strong>Draft</strong> sampai Anda berlakukan.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 3. Footer ringkasan (pagination klien: datatable pekan kecil & dimuat penuh) --}}
    <div class="datatable-footer"
        style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;">
        <span style="font-size: var(--text-sm); color: var(--text-muted);">
            Total <strong id="pekanTotalInfo">{{ $pekanList->count() }}</strong> pekan
            &bull; Aktif: <strong>{{ $ringkasanPekan['aktif'] ?? 0 }}</strong>
            &bull; Draft: <strong>{{ $ringkasanPekan['draft'] ?? 0 }}</strong>
            &bull; Libur: <strong>{{ $ringkasanPekan['libur'] ?? 0 }}</strong>
            &bull; Ujian: <strong>{{ $ringkasanPekan['ujian'] ?? 0 }}</strong>
        </span>
        <span id="pekanPageInfo" style="font-size: var(--text-sm); color: var(--text-muted);"></span>
    </div>
</div>
