@extends('layouts.dashboard')

@section('title', 'Anggota Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <div class="dash-banner" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg,12px);background:rgba(139,92,246,0.12);color:var(--purple,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:var(--text-2xl,1.5rem);flex-shrink:0;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h2 style="font-size:var(--text-xl,1.25rem);font-weight:800;color:var(--text-color);margin:0;">Registrasi Anggota &amp; Kartu Digital</h2>
                    <div style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-top:2px;">
                        Kelola anggota perpustakaan (siswa, GTK, umum) beserta kartu anggota digital ber-QR.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('dashboard.perpustakaan.anggota.kartu.cetak') }}" target="_blank" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;border-radius:var(--radius-md,8px);">
                    <i class="fas fa-print"></i> Cetak Kartu Massal
                </a>
                <button type="button" class="btn btn-primary" onclick="openModalAnggota()"
                    style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;border-radius:var(--radius-md,8px);">
                    <i class="fas fa-plus"></i> Daftarkan Anggota
                </button>
            </div>
        </div>

        @include('dashboard.perpus.partials.tab-nav')

        @if (session('error'))
            <div class="alert alert-danger" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom:16px;"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
        @endif

        <div class="dash-stat-grid" style="margin-bottom:20px;">
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(139,92,246,0.12);color:var(--purple,#8b5cf6);"><i class="fas fa-users"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div><div class="dash-stat-label">Total Anggota</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(59,130,246,0.12);color:var(--primary,#3b82f6);"><i class="fas fa-user-graduate"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['siswa'], 0, ',', '.') }}</div><div class="dash-stat-label">Peserta Didik</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(16,185,129,0.12);color:var(--success,#10b981);"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['gtk'], 0, ',', '.') }}</div><div class="dash-stat-label">GTK</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(245,158,11,0.12);color:var(--warning,#f59e0b);"><i class="fas fa-user"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['umum'], 0, ',', '.') }}</div><div class="dash-stat-label">Umum</div></div></div>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <form method="GET" action="{{ route('dashboard.perpustakaan.anggota.index') }}"
                style="padding:14px 16px;border-bottom:1px solid var(--border-color);display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama / NISN / nomor anggota..."
                    style="height:38px;max-width:280px;">
                <select name="tipe" class="form-control" style="height:38px;max-width:170px;">
                    <option value="">Semua Tipe</option>
                    <option value="siswa" {{ $tipe === 'siswa' ? 'selected' : '' }}>Peserta Didik</option>
                    <option value="gtk" {{ $tipe === 'gtk' ? 'selected' : '' }}>GTK</option>
                    <option value="umum" {{ $tipe === 'umum' ? 'selected' : '' }}>Umum</option>
                </select>
                <button type="submit" class="btn btn-primary" style="height:38px;padding:0 16px;"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <div style="overflow-x:auto;">
                <table class="table-minimal-compact" style="width:100%;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <th style="padding:10px;">No. Anggota</th>
                            <th style="padding:10px;">Nama</th>
                            <th style="padding:10px;">Tipe</th>
                            <th style="padding:10px;">NISN/NIP</th>
                            <th style="padding:10px;">Rombel/Unit</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;text-align:center;width:170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $a)
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:10px;" class="pp-mono"><span style="font-family:monospace;font-weight:700;">{{ $a->nomor_anggota }}</span></td>
                                <td style="padding:10px;font-weight:600;">{{ $a->nama }}</td>
                                <td style="padding:10px;">{{ ucfirst($a->tipe) }}</td>
                                <td style="padding:10px;font-family:monospace;">{{ $a->nisn_nip ?: '-' }}</td>
                                <td style="padding:10px;">{{ $a->rombel_atau_unit ?: '-' }}</td>
                                <td style="padding:10px;">
                                    <span class="badge {{ $a->is_active ? 'badge-success' : 'badge-danger' }}">{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <a href="{{ route('dashboard.perpustakaan.anggota.kartu', $a->id) }}" target="_blank" class="btn btn-sm btn-outline" title="Kartu Digital" style="padding:4px 8px;">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.toggle', $a->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline" title="{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" style="padding:4px 8px;">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.destroy', $a->id) }}" style="display:inline;" data-confirm="delete" data-confirm-message="Hapus anggota ini?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" title="Hapus" style="padding:4px 8px;color:var(--danger,#ef4444);">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-muted);">Belum ada anggota terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding:14px 16px;">{{ $list->links() }}</div>
        </div>
    </div>

    {{-- Modal Daftar Anggota --}}
    <div id="modalAnggota" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div class="card" style="max-width:520px;width:100%;padding:22px;max-height:90vh;overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h3 style="margin:0;font-size:var(--text-lg,1.1rem);font-weight:800;"><i class="fas fa-user-plus"></i> Daftarkan Anggota Baru</h3>
                <button type="button" class="btn btn-sm btn-outline" onclick="closeModalAnggota()" style="padding:4px 10px;" aria-label="Tutup formulir anggota"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.store') }}" id="formAnggota">
                @csrf
                <div style="display:grid;gap:12px;">
                    <div>
                        <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Tipe Anggota</label>
                        <select name="tipe" id="ppTipeAnggota" class="form-control" required onchange="ppGantiTipe()">
                            <option value="siswa" {{ old('tipe') === 'siswa' ? 'selected' : '' }}>Peserta Didik</option>
                            <option value="gtk" {{ old('tipe') === 'gtk' ? 'selected' : '' }}>GTK (Guru / Tenaga Kependidikan)</option>
                            <option value="umum" {{ old('tipe') === 'umum' ? 'selected' : '' }}>Umum (bukan warga sekolah)</option>
                        </select>
                    </div>

                    {{-- ===== PESERTA DIDIK & GTK: cari lewat NISN/NIP atau nama ===== --}}
                    <div id="ppBlokOtomatis">
                        <label style="font-size:var(--text-sm,0.8rem);font-weight:700;" for="ppInputNisnNip" id="ppLabelNisnNip">
                            NISN atau Nama <span style="color:var(--danger,#ef4444);">*</span>
                        </label>
                        <input type="text" name="nisn_nip" id="ppInputNisnNip" class="form-control"
                            value="{{ old('nisn_nip') }}" placeholder="Ketik NISN, atau nama untuk mencari"
                            autocomplete="off" oninput="ppCariMaster()">
                        <input type="hidden" name="master_tipe" id="ppMasterTipeInput" value="{{ old('master_tipe') }}">
                        <input type="hidden" name="master_id" id="ppMasterIdInput" value="{{ old('master_id') }}">
                        <div style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:5px;">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            Ketik NISN/NIP untuk pencarian langsung, atau ketik nama (min. 3 huruf) lalu pilih
                            dari daftar. Data nama, rombel/unit, dan kontak ditarik otomatis dari database sekolah.
                        </div>

                        {{-- Daftar kandidat hasil pencarian nama --}}
                        <div id="ppDaftarKandidat"
                            style="display:none;margin-top:10px;max-height:230px;overflow-y:auto;
                                   border:1px solid var(--border-color);border-radius:var(--radius-md,8px);"></div>

                        {{-- Kandidat yang dipilih --}}
                        <div id="ppHasilMaster"
                            style="display:none;margin-top:10px;padding:12px;border-radius:var(--radius-md,8px);
                                   border:1px solid var(--border-color);background:rgba(59,130,246,0.05);">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                                <div>
                                    <div style="font-weight:800;font-size:var(--text-sm,0.875rem);" id="ppMasterNama"></div>
                                    <div style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:2px;"
                                        id="ppMasterDetail"></div>
                                </div>
                                <span class="badge badge-primary" id="ppMasterTipe"></span>
                            </div>
                        </div>
                        <div id="ppPesanMaster" style="display:none;font-size:var(--text-xs,0.75rem);margin-top:8px;"></div>
                    </div>

                    {{-- ===== UMUM: data diisi manual ===== --}}
                    <div id="ppBlokManual" style="display:none;">
                        <div style="display:grid;gap:12px;">
                            <div>
                                <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Nama Lengkap
                                    <span style="color:var(--danger,#ef4444);">*</span></label>
                                <input type="text" name="nama" id="ppNamaUmum" class="form-control" value="{{ old('nama') }}">
                            </div>
                            <div>
                                <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">NISN/NIP
                                    <small style="color:var(--text-muted);">(opsional)</small></label>
                                <input type="text" name="nisn_nip" id="ppNisnUmum" class="form-control"
                                    value="{{ old('nisn_nip') }}">
                            </div>
                            <div>
                                <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Rombel / Unit
                                    <small style="color:var(--text-muted);">(opsional)</small></label>
                                <input type="text" name="rombel_atau_unit" class="form-control" value="{{ old('rombel_atau_unit') }}">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                <div>
                                    <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-control">
                                        <option value="">-</option>
                                        <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Telepon</label>
                                    <input type="text" name="telepon" class="form-control" value="{{ old('telepon') }}">
                                </div>
                            </div>
                            <div>
                                <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Email
                                    <small style="color:var(--text-muted);">(opsional)</small></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div>
                                <label style="font-size:var(--text-sm,0.8rem);font-weight:700;">Password Awal
                                    <small style="color:var(--text-muted);">(opsional)</small></label>
                                <input type="text" name="password" class="form-control"
                                    placeholder="Kosongkan = NISN/NIP (atau nomor anggota)" autocomplete="off">
                                <div style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:5px;">
                                    <i class="fas fa-circle-info"></i>
                                    Anggota umum tidak punya akun SAE, jadi password-nya dikelola di sini.
                                    Bila dikosongkan, password awal = NISN/NIP &mdash; atau nomor anggota bila
                                    NISN/NIP kosong. Anggota dapat mengubahnya sendiri lewat halaman profil.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModalAnggota()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="ppTombolDaftar"><i class="fas fa-save"></i> Daftarkan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Konfirmasi hapus standar SAE (window.SAE.confirm)
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form || form.getAttribute('data-confirm') !== 'delete') return;
                if (form.dataset.confirmed === 'true') return;
                e.preventDefault();
                var msg = form.getAttribute('data-confirm-message') || 'Yakin ingin menghapus data ini?';
                window.SAE.confirm(msg, 'Konfirmasi Hapus', 'danger', 'Ya, Hapus').then(function (ok) {
                    if (ok) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            });

            var PP_CEK_URL = @json(route('dashboard.perpustakaan.anggota.cek-master'));
            var ppTimer = null;
            var ppKandidat = [];

            function openModalAnggota() {
                document.getElementById('modalAnggota').style.display = 'flex';
                ppGantiTipe();
            }
            function closeModalAnggota() { document.getElementById('modalAnggota').style.display = 'none'; }

            function ppEsc(teks) {
                return String(teks == null ? '' : teks).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            // Tampilkan blok otomatis (PD/GTK) atau manual (umum).
            function ppGantiTipe() {
                var tipe = document.getElementById('ppTipeAnggota').value;
                var otomatis = tipe !== 'umum';

                document.getElementById('ppBlokOtomatis').style.display = otomatis ? 'block' : 'none';
                document.getElementById('ppBlokManual').style.display = otomatis ? 'none' : 'block';

                // Hanya satu input nisn_nip yang aktif agar tidak terkirim ganda.
                document.getElementById('ppInputNisnNip').disabled = !otomatis;
                document.getElementById('ppNisnUmum').disabled = otomatis;

                document.getElementById('ppNamaUmum').required = !otomatis;
                document.getElementById('ppInputNisnNip').required = otomatis;

                document.getElementById('ppLabelNisnNip').innerHTML = (tipe === 'gtk' ? 'NIP atau Nama' : 'NISN atau Nama')
                    + ' <span style="color:var(--danger,#ef4444);">*</span>';

                document.getElementById('ppInputNisnNip').placeholder = tipe === 'gtk'
                    ? 'Ketik NIP, atau nama GTK untuk mencari'
                    : 'Ketik NISN, atau nama peserta didik untuk mencari';

                ppResetMaster();
            }

            // Bersihkan pilihan kandidat setiap kali input berubah / tipe berganti.
            function ppResetMaster() {
                ppKandidat = [];
                document.getElementById('ppMasterTipeInput').value = '';
                document.getElementById('ppMasterIdInput').value = '';
                document.getElementById('ppHasilMaster').style.display = 'none';
                document.getElementById('ppPesanMaster').style.display = 'none';

                var daftar = document.getElementById('ppDaftarKandidat');
                daftar.style.display = 'none';
                daftar.innerHTML = '';

                document.getElementById('ppTombolDaftar').disabled = false;
                document.getElementById('ppInputNisnNip').required = true;
            }

            function ppPesan(warna, html) {
                var pesan = document.getElementById('ppPesanMaster');
                pesan.style.display = 'block';
                pesan.style.color = warna;
                pesan.innerHTML = html;
            }

            // Cari data master: angka = NISN/NIP, selain itu = nama.
            function ppCariMaster() {
                clearTimeout(ppTimer);

                var nilai = document.getElementById('ppInputNisnNip').value.trim();
                var tipe = document.getElementById('ppTipeAnggota').value;

                ppResetMaster();

                var angka = /^[0-9\s.\-]+$/.test(nilai);
                if (nilai.length < (angka ? 4 : 3)) return;

                ppTimer = setTimeout(function () {
                    fetch(PP_CEK_URL + '?q=' + encodeURIComponent(nilai) + '&tipe=' + encodeURIComponent(tipe), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (r) {
                        return r.json();
                    }).then(function (d) {
                        if (!d.ok) {
                            ppPesan('var(--danger,#ef4444)', '<i class="fas fa-circle-exclamation"></i> ' + ppEsc(d.pesan));
                            document.getElementById('ppTombolDaftar').disabled = true;
                            return;
                        }
                        ppTampilkanKandidat(d.hasil || []);
                    }).catch(function () {
                        ppPesan('var(--danger,#ef4444)', '<i class="fas fa-circle-exclamation"></i> Gagal memeriksa data. Coba lagi.');
                    });
                }, 400);
            }

            function ppTampilkanKandidat(hasil) {
                ppKandidat = hasil;

                // Hasil tunggal tidak perlu memilih: langsung tampilkan kartunya.
                if (hasil.length === 1) {
                    ppPilihKandidat(0);
                    return;
                }

                var daftar = document.getElementById('ppDaftarKandidat');
                daftar.innerHTML = hasil.map(function (d, i) {
                    var info = [d.nisn_nip || 'tanpa NISN/NIP', d.rombel_atau_unit,
                        d.sudah_terdaftar ? 'sudah terdaftar' : null].filter(Boolean).join(' \u2022 ');

                    return '<button type="button" onclick="ppPilihKandidat(' + i + ')" '
                        + 'style="display:flex;width:100%;gap:10px;align-items:center;text-align:left;padding:10px 12px;'
                        + 'background:transparent;border:none;border-bottom:1px solid var(--border-color);cursor:pointer;font:inherit;">'
                        + '<span style="flex:1;min-width:0;">'
                        + '<span style="display:block;font-weight:700;font-size:var(--text-sm,0.875rem);">' + ppEsc(d.nama) + '</span>'
                        + '<span style="display:block;font-size:var(--text-xs,0.75rem);color:var(--text-muted);">' + ppEsc(info) + '</span>'
                        + '</span>'
                        + '<i class="fas ' + (d.sudah_terdaftar ? 'fa-circle-check' : 'fa-chevron-right') + '" style="color:'
                        + (d.sudah_terdaftar ? 'var(--success,#10b981)' : 'var(--text-muted)') + ';"></i>'
                        + '</button>';
                }).join('');

                daftar.style.display = 'block';
                ppPesan('var(--text-muted)', '<i class="fas fa-list"></i> ' + hasil.length
                    + ' kandidat ditemukan \u2014 pilih salah satu.');
            }

            function ppPilihKandidat(i) {
                var d = ppKandidat[i];
                if (!d) return;

                document.getElementById('ppMasterTipeInput').value = d.tipe;
                document.getElementById('ppMasterIdInput').value = d.bisa_pilih ? d.master_id : '';
                if (d.nisn_nip) {
                    document.getElementById('ppInputNisnNip').value = d.nisn_nip;
                }

                var detail = [];
                if (d.rombel_atau_unit) detail.push(d.rombel_atau_unit);
                if (d.jenis_kelamin) detail.push(d.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan');
                if (d.nisn_nip) detail.push('NISN/NIP: ' + d.nisn_nip);

                document.getElementById('ppMasterNama').textContent = d.nama || '-';
                document.getElementById('ppMasterDetail').textContent = detail.join(' \u00b7 ') || 'Data master ditemukan';
                document.getElementById('ppMasterTipe').textContent = d.tipe_label;
                document.getElementById('ppHasilMaster').style.display = 'block';
                document.getElementById('ppDaftarKandidat').style.display = 'none';

                if (d.sudah_terdaftar) {
                    ppPesan('var(--warning,#f59e0b)', '<i class="fas fa-triangle-exclamation"></i> Sudah terdaftar (No. '
                        + ppEsc(d.nomor_anggota) + ').');
                    document.getElementById('ppTombolDaftar').disabled = true;
                } else if (!d.bisa_pilih) {
                    ppPesan('var(--danger,#ef4444)', '<i class="fas fa-circle-exclamation"></i> Data ini tidak punya ID master, tidak bisa didaftarkan otomatis.');
                    document.getElementById('ppTombolDaftar').disabled = true;
                } else {
                    ppPesan('var(--success,#10b981)', '<i class="fas fa-circle-check"></i> Siap didaftarkan.');
                    document.getElementById('ppTombolDaftar').disabled = false;
                }

                // Sudah ada pilihan master: nomor tidak wajib lagi.
                document.getElementById('ppInputNisnNip').required = !d.bisa_pilih;
            }

            @if ($errors->any()) document.addEventListener('DOMContentLoaded', openModalAnggota); @endif
        </script>
    @endpush
@endsection
