    <div class="presensi-tab-pane" id="tab-rfid" style="display: {{ $activeTab === 'rfid' ? 'block' : 'none' }};">
        <!-- Sub-Kategori Tab RFID: Peserta Didik vs Guru & Tendik -->
        <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi.index', ['tab' => 'rfid', 'rfid_kategori' => 'siswa', 'tanggal' => $tanggal]) }}"
                class="btn {{ ($rfidKategori ?? 'siswa') === 'siswa' ? 'btn-primary' : 'btn-outline' }}"
                style="padding: 8px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-graduate"></i> <span>Peserta Didik</span>
            </a>
            <a href="{{ route('dashboard.presensi.index', ['tab' => 'rfid', 'rfid_kategori' => 'gtk', 'tanggal' => $tanggal]) }}"
                class="btn {{ ($rfidKategori ?? 'siswa') === 'gtk' ? 'btn-primary' : 'btn-outline' }}"
                style="padding: 8px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-chalkboard-user"></i> <span>Guru &amp; Tendik (Akses Kiosk)</span>
            </a>
        </div>

        <!-- Toolbar & Filter Bar RFID -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <form id="formFilterRfid" action="{{ route('dashboard.presensi.index') }}" method="GET">
                <input type="hidden" name="tab" value="rfid">
                <input type="hidden" name="rfid_kategori" value="{{ $rfidKategori ?? 'siswa' }}">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                <input type="hidden" name="perPageRfid" id="inputHiddenPerPageRfid" value="{{ $perPageRfid ?? 15 }}">

                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageRfidSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageRfidSelect" class="per-page-select">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}"
                                        {{ ($perPageRfid ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <select name="rfid_status" id="rfidStatusSelect" class="toolbar-filter-select"
                            style="min-width: 170px;">
                            <option value="">Semua Status Kartu</option>
                            <option value="terdaftar" {{ request('rfid_status') === 'terdaftar' ? 'selected' : '' }}>
                                Sudah Memiliki Kartu</option>
                            <option value="belum" {{ request('rfid_status') === 'belum' ? 'selected' : '' }}>Belum
                                Memiliki Kartu</option>
                        </select>

                        @if (($rfidKategori ?? 'siswa') === 'siswa')
                            <select name="rfid_rombel" id="rfidRombelSelect" class="toolbar-filter-select"
                                style="min-width: 170px;">
                                <option value="">Semua Kelas</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}"
                                        {{ request('rfid_rombel', $rfidRombel ?? '') == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <button type="submit" class="btn btn-primary"
                            style="padding: 7px 14px; font-size: 0.82rem; font-weight: 600;">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>

                        <span id="rfidFilterResetWrap">
                            @if (request('rfid_search') || request('rfid_status') || request('rfid_rombel'))
                                <a href="{{ route('dashboard.presensi.index', ['tab' => 'rfid', 'rfid_kategori' => $rfidKategori ?? 'siswa', 'tanggal' => $tanggal]) }}"
                                    class="btn btn-outline btn-reset-rfid" style="padding: 7px 12px; font-size: 0.82rem;"
                                    title="Reset filter">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </a>
                            @endif
                        </span>
                    </div>

                    <div class="live-search-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="rfidSearchInput" name="rfid_search"
                            value="{{ request('rfid_search') }}"
                            placeholder="{{ ($rfidKategori ?? 'siswa') === 'gtk' ? 'Cari nama, NIP, UID kartu...' : 'Cari nama, NISN, UID kartu...' }}"
                            autocomplete="off">
                        <button type="button" id="clearRfidSearch"
                            class="clear-search {{ request('rfid_search') ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div id="rfidTableContainer">
            @if (($rfidKategori ?? 'siswa') === 'gtk')
                <!-- Datatable GTK (Guru & Tendik) RFID -->
                <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
                    <table class="table table-rfid" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr
                                style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                    No</th>
                                <th
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nama Guru / Tendik</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    NIP / NUPTK</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Peran / Kategori</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status Kartu</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    UID Kartu Fisik</th>
                                <th
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 140px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gtkRfidList as $idx => $g)
                                <tr
                                    style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                    <td class="cell-rfid-no"
                                        style="padding: 14px 16px; text-align: center; color: var(--text-muted);"
                                        data-label="No">
                                        {{ $gtkRfidList->firstItem() + $idx }}
                                    </td>
                                    <td class="cell-rfid-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                        <div class="rfid-siswa-wrapper"
                                            style="display: flex; align-items: center; gap: 12px;">
                                            <div class="rfid-avatar"
                                                style="width: 38px; height: 38px; border-radius: 50%; background: rgba(99,102,241,0.15); color: #818cf8; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; border: 1px solid var(--border-color); flex-shrink: 0;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <div
                                                    style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">
                                                    {{ $g->nama }}</div>
                                                <div
                                                    style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                    @<span>{{ $g->username }}</span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cell-rfid-nisn"
                                        style="padding: 14px 16px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                        data-label="NIP / NUPTK">
                                        {{ $g->nip ?: ($g->nuptk ?: '—') }}
                                    </td>
                                    <td class="cell-rfid-rombel" style="padding: 14px 16px;" data-label="Peran">
                                        <span class="badge badge-outline"
                                            style="font-size: 0.76rem; padding: 3px 8px;">{{ $g->peran_id_str ?: ($g->jenis_ptk_id_str ?: 'GTK') }}</span>
                                    </td>
                                    <td class="cell-rfid-status" style="padding: 14px 16px;" data-label="Status Kartu">
                                        @if ($g->rfid_uid)
                                            <span class="badge badge-success"
                                                style="font-size: 0.74rem; padding: 3px 8px;"><i
                                                    class="fas fa-check me-1"></i> Terpasang</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.74rem; padding: 3px 8px;">Belum
                                                Ada Kartu</span>
                                        @endif
                                    </td>
                                    <td class="cell-rfid-uid" style="padding: 14px 16px;" data-label="UID Kartu Fisik">
                                        @if ($g->rfid_uid)
                                            <code
                                                style="font-size: 0.84rem; font-weight: 700; color: var(--primary);">{{ $g->rfid_uid }}</code>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                        @endif
                                    </td>
                                    <td class="cell-rfid-aksi" style="padding: 14px 18px; text-align: right;"
                                        data-label="Aksi">
                                        <div class="rfid-actions"
                                            style="display: flex; justify-content: flex-end; gap: 6px;">
                                            <button type="button" class="btn btn-outline btn-assign-rfid"
                                                data-id="{{ $g->pengguna_id }}" data-type="gtk"
                                                data-nama="{{ $g->nama }}"
                                                data-nisn="{{ $g->nip ?: ($g->nuptk ?: $g->username) }}"
                                                data-rfid="{{ $g->rfid_uid }}"
                                                style="padding: 6px 12px; font-size: 0.78rem; font-weight: 600;">
                                                <i class="fas fa-link me-1"></i>
                                                {{ $g->rfid_uid ? 'Ubah Kartu' : 'Daftarkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="fas fa-id-card-clip"
                                            style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                        Tidak ada data Guru atau Tendik yang cocok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Datatable Siswa RFID -->
                <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
                    <table class="table table-rfid" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr
                                style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                    No</th>
                                <th
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nama Peserta Didik</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    NISN / NIPD</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Rombel</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status Kartu</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    UID Kartu Fisik</th>
                                <th
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 140px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($siswaRfidList as $idx => $s)
                                <tr
                                    style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                    <td class="cell-rfid-no"
                                        style="padding: 14px 16px; text-align: center; color: var(--text-muted);"
                                        data-label="No">
                                        {{ $siswaRfidList->firstItem() + $idx }}
                                    </td>
                                    <td class="cell-rfid-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                        <div class="rfid-siswa-wrapper"
                                            style="display: flex; align-items: center; gap: 12px;">
                                            <div class="rfid-avatar"
                                                style="width: 38px; height: 38px; border-radius: 50%; background: rgba(59,130,246,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; border: 1px solid var(--border-color); flex-shrink: 0;">
                                                {{ strtoupper(substr($s->nama, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div
                                                    style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">
                                                    {{ $s->nama }}</div>
                                                <div
                                                    style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                    ID: {{ substr($s->peserta_didik_id, 0, 8) }}...</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cell-rfid-nisn"
                                        style="padding: 14px 16px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                        data-label="NISN / NIPD">
                                        {{ $s->nisn ?: ($s->nipd ?: '-') }}
                                    </td>
                                    <td class="cell-rfid-rombel" style="padding: 14px 16px;" data-label="Rombel Kelas">
                                        <span class="badge badge-outline"
                                            style="font-size: 0.76rem; padding: 3px 8px;">{{ $s->nama_rombel ?: '-' }}</span>
                                    </td>
                                    <td class="cell-rfid-status" style="padding: 14px 16px;" data-label="Status Kartu">
                                        @if ($s->rfid_uid)
                                            <span class="badge badge-success"
                                                style="font-size: 0.74rem; padding: 3px 8px;"><i
                                                    class="fas fa-check me-1"></i> Terpasang</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.74rem; padding: 3px 8px;">Belum
                                                Ada Kartu</span>
                                        @endif
                                    </td>
                                    <td class="cell-rfid-uid" style="padding: 14px 16px;" data-label="UID Kartu Fisik">
                                        @if ($s->rfid_uid)
                                            <code
                                                style="font-size: 0.84rem; font-weight: 700; color: var(--primary);">{{ $s->rfid_uid }}</code>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                        @endif
                                    </td>
                                    <td class="cell-rfid-aksi" style="padding: 14px 18px; text-align: right;"
                                        data-label="Aksi">
                                        <div class="rfid-actions"
                                            style="display: flex; justify-content: flex-end; gap: 6px;">
                                            <button type="button" class="btn btn-outline btn-assign-rfid"
                                                data-id="{{ $s->peserta_didik_id }}" data-type="siswa"
                                                data-nama="{{ $s->nama }}" data-nisn="{{ $s->nisn ?: $s->nipd }}"
                                                data-rfid="{{ $s->rfid_uid }}"
                                                style="padding: 6px 12px; font-size: 0.78rem; font-weight: 600;">
                                                <i class="fas fa-link me-1"></i>
                                                {{ $s->rfid_uid ? 'Ubah Kartu' : 'Daftarkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="fas fa-id-card-clip"
                                            style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                        Tidak ada data peserta didik yang cocok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @php
                $currentRfidPaginated = ($rfidKategori ?? 'siswa') === 'gtk' ? $gtkRfidList : $siswaRfidList;
            @endphp
            {{-- Custom Pagination for RFID List with Entry Summary --}}
            <div
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; padding: 0 4px;">
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Menampilkan {{ $currentRfidPaginated->firstItem() ?? 0 }} sampai
                    {{ $currentRfidPaginated->lastItem() ?? 0 }} dari {{ $currentRfidPaginated->total() }} entri
                </div>
                @if ($currentRfidPaginated->hasPages())
                    <div class="custom-pagination" style="margin: 0; padding: 0;">
                        @if ($currentRfidPaginated->onFirstPage())
                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $currentRfidPaginated->previousPageUrl() }}" class="page-btn"
                                title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                        @endif
                        @php
                            $cur = $currentRfidPaginated->currentPage();
                            $last = $currentRfidPaginated->lastPage();
                            $from = max(1, $cur - 2);
                            $to = min($last, $cur + 2);
                        @endphp
                        @if ($from > 1)
                            <a href="{{ $currentRfidPaginated->url(1) }}" class="page-btn">1</a>
                            @if ($from > 2)
                                <span class="page-info">&hellip;</span>
                            @endif
                        @endif
                        @for ($i = $from; $i <= $to; $i++)
                            <a href="{{ $currentRfidPaginated->url($i) }}"
                                class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                        @endfor
                        @if ($to < $last)
                            @if ($to < $last - 1)
                                <span class="page-info">&hellip;</span>
                            @endif
                            <a href="{{ $currentRfidPaginated->url($last) }}" class="page-btn">{{ $last }}</a>
                        @endif
                        @if ($currentRfidPaginated->hasMorePages())
                            <a href="{{ $currentRfidPaginated->nextPageUrl() }}" class="page-btn"
                                title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                @endif
            </div>
        </div> <!-- /#rfidTableContainer -->
    </div> <!-- /#tab-rfid -->

    <!-- TAB 4: Pengajuan E-Izin -->
