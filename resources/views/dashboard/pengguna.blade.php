@extends('layouts.dashboard')

@section('title', 'Manajemen Pengguna — SAE')
@section('dash_title', 'Manajemen Pengguna')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-users-gear text-primary me-2"></i> Manajemen Pengguna
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola akun Administrator, Guru / Tenaga Kependidikan, serta Peserta Didik dalam satu modul.
            </p>
        </div>
    </div>

    <!-- Desktop Segmented Tabs -->
    <div class="dash-tabs-nav dash-desktop-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'admin']) }}"
            class="btn {{ $activeTab === 'admin' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-shield me-1"></i> Administrator ({{ $counts['admin'] }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'guru']) }}"
            class="btn {{ $activeTab === 'guru' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-chalkboard-user me-1"></i> Guru ({{ $counts['guru'] }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'tendik']) }}"
            class="btn {{ $activeTab === 'tendik' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-id-badge me-1"></i> Tendik ({{ $counts['tendik'] }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'peserta_didik']) }}"
            class="btn {{ $activeTab === 'peserta_didik' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-graduate me-1"></i> Peserta Didik ({{ $counts['peserta_didik'] }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'tugas_tambahan']) }}"
            class="btn {{ $activeTab === 'tugas_tambahan' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-briefcase me-1"></i> Tugas Tambahan ({{ $counts['tugas_tambahan'] ?? 0 }})
        </a>
    </div>

    <!-- Mobile Custom Dropdown Selector -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ $activeTab === 'admin' ? 'fa-user-shield' : ($activeTab === 'guru' ? 'fa-chalkboard-user' : ($activeTab === 'tendik' ? 'fa-id-badge' : ($activeTab === 'tugas_tambahan' ? 'fa-briefcase' : 'fa-user-graduate'))) }} text-primary me-2"></i>
                    <span>{{ $activeTab === 'admin' ? 'Administrator' : ($activeTab === 'guru' ? 'Guru' : ($activeTab === 'tendik' ? 'Tendik' : ($activeTab === 'tugas_tambahan' ? 'Tugas Tambahan' : 'Peserta Didik'))) }}</span>
                    <span class="badge badge-primary badge-sm ms-2">{{ $counts[$activeTab] ?? 0 }}</span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'admin']) }}"
                    class="custom-dropdown-item {{ $activeTab === 'admin' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-shield text-primary me-2"></i>
                        <span>Administrator</span>
                    </div>
                    <span
                        class="badge {{ $activeTab === 'admin' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['admin'] }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'guru']) }}"
                    class="custom-dropdown-item {{ $activeTab === 'guru' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-chalkboard-user text-primary me-2"></i>
                        <span>Guru</span>
                    </div>
                    <span
                        class="badge {{ $activeTab === 'guru' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['guru'] }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'tendik']) }}"
                    class="custom-dropdown-item {{ $activeTab === 'tendik' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-id-badge text-primary me-2"></i>
                        <span>Tendik</span>
                    </div>
                    <span
                        class="badge {{ $activeTab === 'tendik' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['tendik'] }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'peserta_didik']) }}"
                    class="custom-dropdown-item {{ $activeTab === 'peserta_didik' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-graduate text-primary me-2"></i>
                        <span>Peserta Didik</span>
                    </div>
                    <span
                        class="badge {{ $activeTab === 'peserta_didik' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['peserta_didik'] }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'tugas_tambahan']) }}"
                    class="custom-dropdown-item {{ $activeTab === 'tugas_tambahan' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-briefcase text-primary me-2"></i>
                        <span>Tugas Tambahan</span>
                    </div>
                    <span
                        class="badge {{ $activeTab === 'tugas_tambahan' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['tugas_tambahan'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="toolbar-row" style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div class="toolbar-entries">
                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="perPageSelect" class="per-page-select">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                        </option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>

            @if ($activeTab === 'tugas_tambahan' && $canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenAssignModal" style="padding: 7px 14px; font-size: 0.84rem;">
                    <i class="fas fa-plus me-1"></i> Tambah Penugasan
                </button>
                <button type="button" class="btn btn-outline" id="btnSyncWaliKelas" style="padding: 7px 14px; font-size: 0.84rem;">
                    <i class="fas fa-sync-alt me-1"></i> Tarik Wali Kelas (Dapodik)
                </button>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="{{ $activeTab === 'tugas_tambahan' ? 'Cari nama PTK / tugas / kelas...' : 'Cari nama / username / no HP...' }}" value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    @if ($activeTab === 'tugas_tambahan')
        {{-- TABEL PENUGASAN TUGAS TAMBAHAN --}}
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">PTK / Personel</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tugas Tambahan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bidang & Kelompok</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">SK & Periode</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel / Info</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $list = $tugasTambahanList; @endphp
                    @forelse ($tugasTambahanList as $idx => $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-size: 0.84rem; color: var(--text-muted);" data-label="No">
                                {{ ($tugasTambahanList->currentPage() - 1) * $tugasTambahanList->perPage() + $idx + 1 }}
                            </td>
                            <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.86rem;" data-label="PTK">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem;">
                                        <i class="fas {{ $item->tugas_icon ?: 'fa-user' }}"></i>
                                    </div>
                                    <div>
                                        <div>{{ $item->ptk_nama }}</div>
                                        <div style="font-size: 0.73rem; color: var(--text-muted); font-family: monospace;">NIP: {{ $item->ptk_nip }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.84rem;" data-label="Tugas">
                                <span class="badge badge-primary" style="font-size: 0.76rem; padding: 4px 8px; border-radius: 6px;">
                                    {{ $item->tugas_nama }}
                                </span>
                                @if (!empty($item->ekuivalensi_jam))
                                    <span class="badge badge-outline ms-1" style="font-size: 0.7rem;">{{ $item->ekuivalensi_jam }} JP</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Bidang">
                                <div><strong style="color: var(--text-color);">{{ $item->tugas_bidang ?: '-' }}</strong></div>
                                <div style="font-size: 0.72rem; text-transform: uppercase;">{{ $item->tugas_kelompok }}</div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="SK & Periode">
                                <div><i class="fas fa-file-signature me-1"></i> {{ $item->nomor_sk ?: 'SK Sekolah' }}</div>
                                <div style="font-size: 0.72rem;">{{ $item->tmt_tugas ? date('d/m/Y', strtotime($item->tmt_tugas)) : 'TMT Aktif' }}</div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Rombel / Info">
                                @if ($item->rombel_nama)
                                    <span class="badge badge-accent" style="font-size: 0.74rem;">
                                        <i class="fas fa-chalkboard-user me-1"></i> {{ $item->rombel_nama }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.76rem;">{{ $item->keterangan ?: '-' }}</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                @if ($canDelete)
                                    <button type="button" class="btn-icon danger btn-delete-duty" data-id="{{ $item->id }}" data-name="{{ $item->ptk_nama }} ({{ $item->tugas_nama }})" title="Hapus Penugasan">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada penugasan tugas tambahan. Silakan klik "Tambah Penugasan" atau "Tarik Wali Kelas (Dapodik)".</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- TABEL PENGGUNA STANDAR (ADMIN, GURU, TENDIK, SISWA) --}}
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php $cols = [['nama','Nama'],['username','Username'],['peran_id_str','Peran'],['no_hp','Kontak']]; @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $list = match ($activeTab) {
                            'admin' => $admins,
                            'guru' => $gurus,
                            'tendik' => $tendiks,
                            default => $pesertaDidiks,
                        };
                    @endphp
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.86rem;"
                                data-label="Nama">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem;">
                                        <i
                                            class="fas {{ $activeTab === 'admin' ? 'fa-user-shield' : ($activeTab === 'guru' ? 'fa-chalkboard-user' : ($activeTab === 'tendik' ? 'fa-id-badge' : 'fa-user-graduate')) }}"></i>
                                    </div>
                                    <div>
                                        <div>{{ $item->nama ?: 'Tanpa Nama' }}</div>
                                        @if ($item->alamat)
                                            <div style="font-size: 0.73rem; color: var(--text-muted); font-weight: 400;">
                                                {{ Str::limit($item->alamat, 35) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.84rem; font-family: monospace; color: var(--text-color);"
                                data-label="Username">
                                {{ $item->username }}
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Peran">
                                <span
                                    class="badge {{ $activeTab === 'admin' ? 'badge-primary' : ($activeTab === 'guru' ? 'badge-accent' : ($activeTab === 'tendik' ? 'badge-info' : 'badge-outline')) }}"
                                    style="font-size: 0.73rem; padding: 4px 8px; border-radius: 6px;">
                                    {{ $item->peran_id_str ?: ($activeTab === 'peserta_didik' ? 'Peserta Didik' : ($activeTab === 'tendik' ? 'Tenaga Kependidikan' : 'Pengguna')) }}
                                </span>
                                @if (!empty($item->rfid_uid))
                                    <div style="margin-top: 5px;">
                                        <span class="badge badge-success" style="font-size: 0.7rem; font-family: monospace; padding: 2px 6px;" title="Kartu RFID Fisik Aktif">
                                            <i class="fas fa-id-card me-1"></i> {{ $item->rfid_uid }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Kontak">
                                {{ $item->no_hp ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit"
                                        onclick="openEditModal({{ json_encode($item) }})">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    @endif
                                    @if ($canUpdate && $activeTab === 'peserta_didik' && $item->peserta_didik_id)
                                        <form action="{{ route('dashboard.pengguna.resetPassword', $item->pengguna_id) }}"
                                            method="POST" style="display: inline; margin: 0;" data-action-type="reset"
                                            data-name="{{ $item->nama }}">
                                            @csrf
                                            <button type="submit" class="btn-icon reset" title="Reset Password ke NISN">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($canDelete)
                                    <form action="{{ route('dashboard.pengguna.destroy', $item->pengguna_id) }}"
                                        method="POST" style="display: inline; margin: 0;" data-action-type="delete"
                                        data-name="{{ $item->nama }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Hapus">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Tidak ada data pengguna ditemukan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Edit Modal Pengguna --}}
    <div id="userModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 520px; width: 90%; margin: 0; border-radius: 14px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 id="modalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Edit Pengguna</h3>
                <button type="button" onclick="closeUserModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem;"><i
                        class="fas fa-times"></i></button>
            </div>

            <form id="userForm" method="POST" action="">
                @csrf
                <div id="methodField"></div>

                <div class="modal-form-grid">
                    <div class="form-group-compact">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" id="inputNama" required>
                    </div>
                    <div class="form-group-compact">
                        <label>Username</label>
                        <input type="text" name="username" id="inputUsername" required>
                    </div>
                    <div class="form-group-compact">
                        <label>Peran / Kategori</label>
                        <select name="peran_id_str" id="inputPeran" required>
                            <option value="Administrator">Administrator</option>
                            <option value="Guru">Guru</option>
                            <option value="Tenaga Kependidikan">Tenaga Kependidikan (Tendik)</option>
                            <option value="Peserta Didik">Peserta Didik</option>
                        </select>
                    </div>
                    <div class="form-group-compact">
                        <label>Nomor HP</label>
                        <input type="text" name="no_hp" id="inputHp">
                    </div>
                    <div class="form-group-compact full-width">
                        <label>Alamat</label>
                        <input type="text" name="alamat" id="inputAlamat">
                    </div>
                    <div class="form-group-compact full-width">
                        <label><i class="fas fa-id-card text-primary me-1"></i> UID Kartu RFID (Akses Kiosk Terminal)</label>
                        <input type="text" name="rfid_uid" id="inputRfidUid" placeholder="Tempel kartu RFID atau ketik UID..." autocomplete="off">
                        <div class="input-hint">Digunakan untuk membuka akses layar pemindai terminal kiosk presensi secara instan dengan tap kartu.</div>
                    </div>
                    <div class="form-group-compact full-width">
                        <label>Password Baru</label>
                        <input type="password" name="password" id="inputPassword" placeholder="Minimal 6 karakter">
                        <div class="input-hint" id="passwordHelp" style="display: none;">Biarkan kosong jika tidak ingin
                            mengubah password.</div>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()"
                        style="padding: 8px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 16px; font-size: 0.82rem;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Tambah Penugasan Tugas Tambahan --}}
    <div id="dutyModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
        data-store-url="{{ route('dashboard.pengguna.tugas-tambahan.store') }}"
        data-destroy-base-url="{{ url('/dashboard/pengguna/tugas-tambahan') }}"
        data-sync-wali-url="{{ route('dashboard.pengguna.tugas-tambahan.sync-wali') }}">
        <div class="card" style="max-width: 540px; width: 92%; margin: 0; border-radius: 14px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-user-plus text-primary me-2"></i> Tambah Penugasan Tugas Tambahan
                </h3>
                <button type="button" id="btnCloseDutyModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem;"><i
                        class="fas fa-times"></i></button>
            </div>

            <form id="formAddDuty" method="POST" action="{{ route('dashboard.pengguna.tugas-tambahan.store') }}">
                @csrf
                <div class="modal-form-grid">
                    <div class="form-group-compact full-width">
                        <label>Pilih Personel / PTK <span class="text-danger">*</span></label>
                        <select name="ptk_id" id="dutyPtkSelect" required>
                            <option value="">-- Pilih PTK / GTK --</option>
                            @foreach ($ptkList as $p)
                                <option value="{{ $p->ptk_id }}">{{ $p->nama }} ({{ $p->nip ? 'NIP: ' . $p->nip : ($p->jenis_ptk_id_str ?: 'GTK') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact full-width">
                        <label>Tugas Tambahan <span class="text-danger">*</span></label>
                        <select name="tugas_tambahan_id" id="dutyTugasSelect" required>
                            <option value="">-- Pilih Tugas Tambahan --</option>
                            @foreach ($refTugasList as $rt)
                                <option value="{{ $rt->id }}" data-kode="{{ $rt->kode }}">{{ $rt->nama }} ({{ $rt->bidang ?: $rt->kelompok }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact full-width" id="dutyRombelWrap" style="display: none;">
                        <label>Rombongan Belajar (Wali Kelas) <span class="text-danger">*</span></label>
                        <select name="rombel_id" id="dutyRombelSelect">
                            <option value="">-- Pilih Rombel --</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Nomor SK</label>
                        <input type="text" name="nomor_sk" placeholder="Misal: 421.3/SK-01/2026">
                    </div>

                    <div class="form-group-compact">
                        <label>TMT Tugas (Tanggal Mulai)</label>
                        <input type="date" name="tmt_tugas" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group-compact">
                        <label>TST Tugas (Tanggal Selesai)</label>
                        <input type="date" name="tst_tugas">
                    </div>

                    <div class="form-group-compact full-width">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" placeholder="Catatan tambahan opsional...">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-outline" id="btnCancelDutyModal" style="padding: 8px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitDutyModal" style="padding: 8px 16px; font-size: 0.82rem;">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/pengguna.js') }}"></script>
    @endpush
@endsection
