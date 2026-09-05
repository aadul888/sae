@extends('layouts.dashboard')

@section('title', 'Manajemen Pengguna — SAE')
@section('dash_title', 'Manajemen Pengguna')

@section('content')
    <!-- Banner & Aksi Tambah -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-users-gear text-primary me-2"></i> Manajemen Pengguna
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola akun Administrator, Guru / Tenaga Kependidikan, serta Siswa dalam satu modul.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" class="btn btn-primary" onclick="openCreateModal()"
                style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-user-plus me-1"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Search Bar & Tab Navigasi -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
        <div
            style="display: flex; gap: 8px; background: var(--card-bg); padding: 5px; border-radius: 12px; border: 1px solid var(--border-color);">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'admin']) }}"
                class="btn {{ $activeTab === 'admin' ? 'btn-primary' : 'btn-outline' }}"
                style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                <i class="fas fa-user-shield me-1"></i> Administrator ({{ $counts['admin'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'guru']) }}"
                class="btn {{ $activeTab === 'guru' ? 'btn-primary' : 'btn-outline' }}"
                style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                <i class="fas fa-chalkboard-user me-1"></i> Guru &amp; Tendik ({{ $counts['guru'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'siswa']) }}"
                class="btn {{ $activeTab === 'siswa' ? 'btn-primary' : 'btn-outline' }}"
                style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                <i class="fas fa-user-graduate me-1"></i> Siswa ({{ $counts['siswa'] }})
            </a>
        </div>

        <form action="{{ route('dashboard.pengguna.index') }}" method="GET" style="display: flex; gap: 8px; margin: 0;">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="input-icon-wrap" style="position: relative;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / username..."
                    class="form-control"
                    style="padding: 8px 14px 8px 36px; font-size: 0.83rem; width: 220px; border-radius: 8px;">
                <i class="fas fa-search"
                    style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
            </div>
            <button type="submit" class="btn btn-outline" style="padding: 8px 12px; font-size: 0.83rem;">Cari</button>
            @if ($q)
                <a href="{{ route('dashboard.pengguna.index', ['tab' => $activeTab]) }}" class="btn btn-outline"
                    style="padding: 8px 12px; font-size: 0.83rem;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr
                        style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Nama Lengkap</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Username</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peran / Jabatan</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kontak</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $list = $activeTab === 'admin' ? $admins : ($activeTab === 'guru' ? $gurus : $siswas);
                    @endphp

                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.86rem;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem;">
                                        <i
                                            class="fas {{ $activeTab === 'admin' ? 'fa-user-shield' : ($activeTab === 'guru' ? 'fa-chalkboard-user' : 'fa-user-graduate') }}"></i>
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
                            <td
                                style="padding: 14px 18px; font-size: 0.84rem; font-family: monospace; color: var(--text-color);">
                                {{ $item->username }}
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;">
                                <span
                                    class="badge {{ $activeTab === 'admin' ? 'badge-primary' : ($activeTab === 'guru' ? 'badge-accent' : 'badge-outline') }}"
                                    style="font-size: 0.73rem; padding: 4px 8px; border-radius: 6px;">
                                    {{ $item->peran_id_str ?: ($activeTab === 'siswa' ? 'Peserta Didik' : 'Pengguna') }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);">
                                {{ $item->no_hp ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; text-align: right; white-space: nowrap;">
                                <button type="button" class="btn btn-outline"
                                    style="padding: 5px 10px; font-size: 0.78rem;"
                                    onclick="openEditModal({{ json_encode($item) }})">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('dashboard.pengguna.destroy', $item->pengguna_id) }}" method="POST"
                                    style="display: inline-block; margin: 0;"
                                    onsubmit="return confirm('Hapus pengguna {{ $item->nama }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline"
                                        style="padding: 5px 10px; font-size: 0.78rem; color: #ef4444; border-color: rgba(239,68,68,0.3);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
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

        <!-- Pagination -->
        @if ($list->hasPages())
            <div
                style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                {{ $list->appends(['tab' => $activeTab, 'q' => $q])->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Tambah / Edit Pengguna) -->
    <div id="userModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 480px; width: 90%; margin: 0; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 id="modalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Tambah Pengguna</h3>
                <button type="button" onclick="closeUserModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem;"><i
                        class="fas fa-times"></i></button>
            </div>

            <form id="userForm" method="POST" action="{{ route('dashboard.pengguna.store') }}">
                @csrf
                <div id="methodField"></div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Nama Lengkap</label>
                    <input type="text" name="nama" id="inputNama" class="form-control" required
                        style="font-size: 0.85rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Username / Akun</label>
                    <input type="text" name="username" id="inputUsername" class="form-control" required
                        style="font-size: 0.85rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Peran / Kategori</label>
                    <select name="peran_id_str" id="inputPeran" class="form-control" required
                        style="font-size: 0.85rem; padding: 8px 12px; border-radius: 8px;">
                        <option value="Administrator">Administrator</option>
                        <option value="Guru / Tenaga Kependidikan">Guru / Tenaga Kependidikan</option>
                        <option value="Peserta Didik">Peserta Didik</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="inputHp" class="form-control"
                        style="font-size: 0.85rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600;"
                        id="labelPassword">Password</label>
                    <input type="password" name="password" id="inputPassword" class="form-control"
                        style="font-size: 0.85rem; padding: 8px 12px; border-radius: 8px;"
                        placeholder="Minimal 6 karakter">
                    <small id="passwordHelp" style="display: none; font-size: 0.72rem; color: var(--text-muted);">Biarkan
                        kosong jika tidak ingin mengubah password.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()"
                        style="padding: 8px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 16px; font-size: 0.82rem;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const title = document.getElementById('modalTitle');
        const methodField = document.getElementById('methodField');
        const passwordHelp = document.getElementById('passwordHelp');
        const inputPassword = document.getElementById('inputPassword');

        function openCreateModal() {
            title.textContent = 'Tambah Pengguna Baru';
            form.action = '{{ route('dashboard.pengguna.store') }}';
            methodField.innerHTML = '';
            document.getElementById('inputNama').value = '';
            document.getElementById('inputUsername').value = '';
            document.getElementById('inputHp').value = '';
            inputPassword.required = true;
            passwordHelp.style.display = 'none';
            modal.style.display = 'flex';
        }

        function openEditModal(user) {
            title.textContent = 'Edit Data Pengguna';
            form.action = '{{ url('dashboard/pengguna') }}/' + user.pengguna_id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('inputNama').value = user.nama || '';
            document.getElementById('inputUsername').value = user.username || '';
            document.getElementById('inputPeran').value = user.peran_id_str || 'Peserta Didik';
            document.getElementById('inputHp').value = user.no_hp || '';
            inputPassword.required = false;
            passwordHelp.style.display = 'block';
            modal.style.display = 'flex';
        }

        function closeUserModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                closeUserModal();
            }
        };
    </script>
@endsection
