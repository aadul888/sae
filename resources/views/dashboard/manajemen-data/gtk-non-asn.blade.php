@extends('layouts.dashboard')

@section('title', 'Manajemen Data — GTK Non-ASN — SAE')
@section('dash_title', 'GTK Non-ASN')

@section('content')
    @php
        $isAdmin = $isAdmin ?? (session('user')['role'] ?? (session('user')->role ?? '')) === 'admin';
        $gtkTabs = [
            [
                'key' => 'guru',
                'label' => 'Guru',
                'icon' => 'fa-chalkboard-user',
                'href' => route('dashboard.guru-aktif.index'),
                'active' => false,
            ],
            [
                'key' => 'tendik',
                'label' => 'Tendik',
                'icon' => 'fa-id-badge',
                'href' => route('dashboard.tendik-aktif.index'),
                'active' => false,
            ],
            [
                'key' => 'non-asn',
                'label' => 'Non-ASN',
                'icon' => 'fa-user-clock',
                'href' => route('dashboard.gtk-non-asn.index'),
                'active' => true,
                'count' => $summary['total'] ?? null,
            ],
        ];
    @endphp
    @include('partials.manajemen-data-tabs', ['tabs' => $gtkTabs])

    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-clock text-primary me-2"></i> Manajemen Data — GTK Non-ASN
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Data Guru &amp; Tenaga Kependidikan Non-ASN yang diinput manual di SAE, <strong>di luar Dapodik</strong>.
                Data ini aman dari proses tarik Dapodik dan ikut terhitung pada daftar Guru / Tendik.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if (($canCreate ?? false) || $isAdmin)
                <button type="button" class="btn btn-outline" onclick="openImportNonAsnModal()"
                    style="display: inline-flex; align-items: center; gap: 8px; border-color: var(--success, #10b981); color: var(--success, #10b981);">
                    <i class="fas fa-file-excel"></i> Import Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="openNonAsnModal()"
                    style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Tambah Non-ASN
                </button>
            @endif
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

    @unless ($tersedia)
        <div
            style="background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.35); color: var(--warning, #f59e0b); padding: 14px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-triangle-exclamation"></i>
            <div>Tabel <strong>gtk_non_asn</strong> belum tersedia. Jalankan <code>php artisan migrate</code> terlebih
                dahulu.</div>
        </div>
    @endunless

    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total'], 0, ',', '.') }}</div>
                <div class="dash-stat-label">Total Non-ASN</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['aktif'], 0, ',', '.') }}</div>
                <div class="dash-stat-label">Status Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['laki'], 0, ',', '.') }}</div>
                <div class="dash-stat-label">Laki-laki</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                <i class="fas fa-venus"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['perempuan'], 0, ',', '.') }}</div>
                <div class="dash-stat-label">Perempuan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139,92,246,0.15); color: var(--purple, #8b5cf6);">
                <i class="fas fa-key"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['punya_akun'], 0, ',', '.') }}</div>
                <div class="dash-stat-label">Punya Akun Login</div>
            </div>
        </div>
    </div>

    {{-- Datatable --}}
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterJenis" name="jenis" class="toolbar-filter-select"
                        style="height: 38px; min-width: 180px;">
                        <option value="">Semua Jenis GTK</option>
                        @foreach ($filterJenis as $j)
                            <option value="{{ $j }}" {{ $jenis === $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>

                    <select id="filterStatus" name="status" class="toolbar-filter-select"
                        style="height: 38px; min-width: 140px;">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="tidak_aktif" {{ $status === 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif
                        </option>
                    </select>

                    @if ($q || $jenis || $status || $gender)
                        <a href="{{ route('dashboard.gtk-non-asn.index') }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NIK / NUPTK / jabatan..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama Lengkap'],
                                ['jenis_ptk', 'Jenis GTK'],
                                ['jenis_kelamin', 'L/P'],
                                ['jabatan', 'Jabatan'],
                                ['status_kepegawaian', 'Kepegawaian'],
                                ['status', 'Status'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span
                                    class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Akun Login
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                data-label="Nama">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span
                                        style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
                                        <i class="fas fa-user-clock"></i>
                                    </span>
                                    <div>
                                        <div>{{ $item->nama }}</div>
                                        <div
                                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 500;">
                                            {{ $item->kode }}
                                            @if ($item->nik)
                                                • NIK {{ $item->nik }}
                                            @endif
                                            @if ($item->nuptk)
                                                • NUPTK {{ $item->nuptk }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);"
                                data-label="Jenis GTK">
                                {{ $item->jenis_ptk ?: '—' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); text-align: center;"
                                data-label="L/P">
                                {{ $item->jenis_kelamin ?: '—' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);"
                                data-label="Jabatan">
                                {{ $item->jabatan ?: '—' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);"
                                data-label="Kepegawaian">
                                <span
                                    style="padding: 3px 10px; border-radius: var(--radius-pill); font-size: var(--text-xs, 0.75rem); font-weight: 700; background: rgba(245,158,11,0.14); color: var(--warning, #f59e0b);">
                                    {{ $item->status_kepegawaian ?: 'Non-ASN' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);" data-label="Status">
                                @if ($item->status === 'aktif')
                                    <span
                                        style="padding: 3px 10px; border-radius: var(--radius-pill); font-size: var(--text-xs, 0.75rem); font-weight: 700; background: rgba(16,185,129,0.14); color: var(--success, #10b981);">Aktif</span>
                                @else
                                    <span
                                        style="padding: 3px 10px; border-radius: var(--radius-pill); font-size: var(--text-xs, 0.75rem); font-weight: 700; background: rgba(148,163,184,0.18); color: var(--text-muted);">Tidak
                                        Aktif</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);" data-label="Akun Login">
                                @if ($item->username)
                                    <span style="color: var(--success, #10b981); font-weight: 600;">
                                        <i class="fas fa-circle-check"></i> {{ $item->username }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted);">
                                        <i class="fas fa-circle-xmark"></i> belum ada
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div style="display: inline-flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                    @unless ($item->pengguna_id)
                                        @if (($canCreate ?? false) || $isAdmin)
                                            <button type="button" class="btn btn-outline btn-buat-akun"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama }}"
                                                style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem);"
                                                title="Buat akun login">
                                                <i class="fas fa-key"></i> Buat Akun
                                            </button>
                                        @endif
                                    @endunless

                                    @if (($canUpdate ?? false) || $isAdmin)
                                        <button type="button" class="btn btn-outline btn-edit-non-asn"
                                            data-row="{{ base64_encode(json_encode($item)) }}"
                                            style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem);"
                                            title="Ubah data">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endif

                                    @if (($canDelete ?? false) || $isAdmin)
                                        <form action="{{ route('dashboard.gtk-non-asn.destroy', $item->id) }}"
                                            method="POST" style="display: inline;"
                                            onsubmit="return confirm('Hapus data Non-ASN {{ addslashes($item->nama) }}? Akun login (bila ada) tidak dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline"
                                                style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem); border-color: var(--danger, #ef4444); color: var(--danger, #ef4444);"
                                                title="Hapus data">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                style="padding: 40px 18px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                <i class="fas fa-user-clock"
                                    style="font-size: var(--text-3xl, 2rem); opacity: 0.35; display: block; margin-bottom: 10px;"></i>
                                Belum ada data GTK Non-ASN.
                                @if (($canCreate ?? false) || $isAdmin)
                                    Gunakan tombol <strong>Tambah Non-ASN</strong> atau <strong>Import Excel</strong>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.datatable-pagination', ['paginator' => $list])
    </div>

    {{-- Modal Tambah / Ubah Non-ASN --}}
    @if (($canCreate ?? false) || ($canUpdate ?? false) || $isAdmin)
        <div id="modalNonAsn" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 760px; width: 94%; max-height: 90vh; overflow-y: auto; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div>
                            <h3 id="nonAsnModalTitle"
                                style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Tambah GTK Non-ASN
                            </h3>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                Data lokal, tidak terpengaruh tarik Dapodik
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeNonAsnModal()"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;"
                        title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formNonAsn" method="POST" action="{{ route('dashboard.gtk-non-asn.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="nonAsnMethod" value="POST">

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Nama
                                Lengkap <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama" id="na_nama" class="form-control" required
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Jenis
                                GTK</label>
                            <select name="jenis_ptk" id="na_jenis_ptk" class="form-select"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                                <option value="Guru">Guru</option>
                                <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Guru BK">Guru BK</option>
                                <option value="Pustakawan">Pustakawan</option>
                                <option value="Laboran">Laboran</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Jabatan</label>
                            <input type="text" name="jabatan" id="na_jabatan" class="form-control"
                                placeholder="mis. Guru Mata Pelajaran"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Jenis
                                Kelamin</label>
                            <select name="jenis_kelamin" id="na_jenis_kelamin" class="form-select"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                                <option value="">— Pilih —</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">NIK</label>
                            <input type="text" name="nik" id="na_nik" class="form-control"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">NUPTK</label>
                            <input type="text" name="nuptk" id="na_nuptk" class="form-control"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">NIP</label>
                            <input type="text" name="nip" id="na_nip" class="form-control"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Gelar
                                Depan</label>
                            <input type="text" name="gelar_depan" id="na_gelar_depan" class="form-control"
                                placeholder="mis. Drs."
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Gelar
                                Belakang</label>
                            <input type="text" name="gelar_belakang" id="na_gelar_belakang" class="form-control"
                                placeholder="mis. S.Pd."
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Status
                                Kepegawaian</label>
                            <input type="text" name="status_kepegawaian" id="na_status_kepegawaian"
                                class="form-control" value="Non-ASN"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Pendidikan
                                Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" id="na_pendidikan_terakhir"
                                class="form-control" placeholder="mis. S1"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Bidang
                                Studi</label>
                            <input type="text" name="bidang_studi" id="na_bidang_studi" class="form-control"
                                placeholder="mis. Matematika"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Email</label>
                            <input type="email" name="email" id="na_email" class="form-control"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">No.
                                HP</label>
                            <input type="text" name="no_hp" id="na_no_hp" class="form-control"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Status</label>
                            <select name="status" id="na_status" class="form-select"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                                <option value="aktif">Aktif</option>
                                <option value="tidak_aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Alamat</label>
                            <textarea name="alamat" id="na_alamat" class="form-control" rows="2"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);"></textarea>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label"
                                style="display:block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Keterangan</label>
                            <textarea name="keterangan" id="na_keterangan" class="form-control" rows="2"
                                style="width:100%; padding:8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);"></textarea>
                        </div>
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                        <button type="button" onclick="closeNonAsnModal()" class="btn btn-outline"
                            style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 22px; font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-floppy-disk"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Import Non-ASN --}}
    @if (($canCreate ?? false) || $isAdmin)
        <div id="modalImportNonAsn" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 600px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Import GTK Non-ASN
                            </h3>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                Format khusus Non-ASN (bukan format Dapodik)
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeImportNonAsnModal()"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;"
                        title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formImportNonAsn" action="{{ route('dashboard.gtk-non-asn.import') }}" method="POST"
                    enctype="multipart/form-data" onsubmit="handleImportNonAsnSubmit(event)">
                    @csrf

                    <div
                        style="margin-bottom: 16px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); font-size: var(--text-sm, 0.8125rem); line-height: 1.45; color: var(--text-color);">
                        <div
                            style="font-weight: 700; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-info-circle"></i> Kolom yang dikenali
                        </div>
                        <div>
                            <code>nama</code>, <code>jenis_kelamin</code>, <code>nik</code>, <code>nuptk</code>,
                            <code>nip</code>, <code>jenis_ptk</code>, <code>jabatan</code>,
                            <code>status_kepegawaian</code>, <code>pendidikan_terakhir</code>, <code>bidang_studi</code>,
                            <code>tempat_lahir</code>, <code>tanggal_lahir</code>, <code>agama</code>,
                            <code>email</code>, <code>no_hp</code>, <code>alamat</code>, <code>tmt</code>,
                            <code>status</code>.
                        </div>
                    </div>

                    <div
                        style="margin-bottom: 18px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                        <div>
                            <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                                Belum punya berkas?</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Unduh template
                                kosong berisi contoh baris Non-ASN</div>
                        </div>
                        <a href="{{ route('dashboard.gtk-non-asn.download-template') }}" class="btn btn-outline"
                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; border-color: var(--success, #10b981); color: var(--success, #10b981); text-decoration: none; white-space: nowrap;">
                            <i class="fas fa-download"></i> Unduh Template
                        </a>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="inputNonAsnFile"
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="file" id="inputNonAsnFile" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                            style="width: 100%; padding: 8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);"
                            required>
                    </div>

                    <div style="display: grid; gap: 10px; margin-bottom: 16px;">
                        <label
                            style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer;">
                            <input type="checkbox" name="buat_akun" value="1" checked
                                style="width: 16px; height: 16px; accent-color: var(--primary);">
                            <span>Buatkan <strong>akun login</strong> otomatis untuk setiap data yang diimpor</span>
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer;">
                            <input type="checkbox" name="izinkan_duplikat" value="1"
                                style="width: 16px; height: 16px; accent-color: var(--primary);">
                            <span>Izinkan data duplikat (nama / NIK / NUPTK / NIP sama)</span>
                        </label>
                    </div>

                    <div id="importNonAsnProgress"
                        style="display: none; margin-bottom: 14px; padding: 12px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                        <i class="fas fa-spinner fa-spin me-2"></i> Sedang memproses berkas...
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                        <button type="button" onclick="closeImportNonAsnModal()" class="btn btn-outline"
                            style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" id="btnSubmitImportNonAsn" class="btn btn-primary"
                            style="padding: 8px 22px; font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <span id="btnSubmitImportNonAsnText">Import Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/gtk-non-asn.js') }}?v={{ filemtime(public_path('js/gtk-non-asn.js')) }}"></script>
@endpush
