        {{-- Modal Tambah / Ubah Jadwal Praktik --}}
        <div id="modalJadwal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 680px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalJadwalTitle"
                        style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        Jadwalkan Praktik
                    </h3>
                    <button type="button" id="btnCloseModalJadwal"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                        title="Tutup">&times;</button>
                </div>

                <form id="formJadwal" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="jadwalMethod" value="POST">
                    <input type="hidden" name="jurusan_id" id="inputJadwalJurusan" value="{{ $context['jurusan_id'] ?? '' }}">
                    <input type="hidden" name="pembelajaran_id" id="inputPembelajaranId">
                    <input type="hidden" name="mata_pelajaran_id" id="inputMapelId">

                    {{-- Baris 1: Jenis lokasi + Lokasi --}}
                    <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Lokasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_lokasi" id="selectJenisLokasi" class="form-control" required>
                                @foreach (\App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jKey => $jLabel)
                                    <option value="{{ $jKey }}">{{ $jLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                                <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                    Lokasi Praktik <span style="color: var(--danger, #ef4444);">*</span>
                                </label>
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank"
                                    style="font-size: var(--text-xs); color: var(--primary); text-decoration: none;"
                                    title="Kelola Data Ruang, Lahan & Lapangan di Sarpras">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                                </a>
                            </div>
                            <select name="ruang_lab_nama" id="selectLokasi" class="form-control" required>
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($lokasiByJenis as $jKey => $rows)
                                    @if ($rows->isNotEmpty())
                                        <optgroup label="{{ \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jKey] ?? $jKey }}"
                                            data-jenis="{{ $jKey }}">
                                            @foreach ($rows as $r)
                                                <option value="{{ $r->nama_ruang }}" data-jenis="{{ $jKey }}">
                                                    {{ $r->nama_ruang }}{{ !empty($r->gedung) ? ' • ' . $r->gedung : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                <i class="fas fa-link me-1 text-primary"></i> Terintegrasi dengan <strong>Sarpras &amp;
                                    Aset</strong> (lab, bengkel, lahan, lapangan).
                            </div>
                        </div>
                    </div>

                    {{-- Baris 2: Kelas + KBM yang berlaku --}}
                    <div style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kelas / Rombel <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="rombel_id" id="selectRombel" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($allRombel as $rb)
                                <option value="{{ $rb->rombongan_belajar_id }}">
                                    {{ $rb->nama }}{{ !empty($rb->jurusan_id_str) ? ' — ' . $rb->jurusan_id_str : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pilihan Sesuai Jadwal KBM yang berlaku (Point 1) --}}
                    <div style="margin-bottom: var(--space-7); background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--radius-md); padding: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--primary);">
                            <i class="fas fa-calendar-check me-1"></i> Sesuaikan Jadwal KBM yang Berlaku
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(otomatis terisi)</span>
                        </label>
                        <select id="selectKbmSlot" class="form-control" disabled>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                        <div id="kbmSlotHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            Pilih slot jadwal KBM untuk mengisi mapel, jam pelajaran, dan guru pengampu secara otomatis.
                        </div>
                    </div>

                    {{-- Mapel --}}
                    <div style="margin-bottom: var(--space-7);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                            <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                Mata Pelajaran <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0; display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;"
                                title="Aktifkan bila mata pelajaran tidak tersedia pada data kelas">
                                <input type="checkbox" id="chkMapelManual" style="cursor: pointer;">
                                Isi manual
                            </label>
                        </div>
                        <select id="selectMapel" class="form-control" disabled>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                        <input type="text" id="inputMapelManual" class="form-control" placeholder="Contoh: Praktik Kejuruan"
                            style="display: none;" disabled>
                    </div>

                    {{-- Pemberlakuan Jadwal: 2 Tab (Mingguan / Semester) --}}
                    <div style="margin-bottom: var(--space-7);">
                        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-5);">
                            <div class="periode-nav-desktop">
                                <button type="button" class="periode-nav-tab active" data-pemberlakuan-tab="mingguan">
                                    <i class="fas fa-calendar-week"></i> Mingguan
                                </button>
                                <button type="button" class="periode-nav-tab" data-pemberlakuan-tab="semester">
                                    <i class="fas fa-calendar-range"></i> Semester
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="pemberlakuan" id="inputPemberlakuan" value="mingguan">

                        <div id="hintPemberlakuan"
                            style="font-size: var(--text-xs); color: var(--text-muted); padding: 8px 12px; background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--radius-md, 8px);">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            <strong>Mingguan:</strong> pilih satu/lebih hari lalu isi tanggal awal — jadwal dibuat
                            berulang setiap minggu pada hari tersebut sesuai rentang tanggal.
                        </div>
                    </div>

                    {{-- Pemetaan Hari (gaya input manual Jadwal KBM) --}}
                    <div id="wrapHariMingguan" style="margin-bottom: var(--space-7);">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: var(--space-3);">
                            <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                Hari Praktik <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <span style="font-size: var(--text-xs); color: var(--text-muted);">Boleh pilih lebih dari satu hari</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                                <label style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); cursor: pointer; font-size: var(--text-sm); background: var(--bg-hover);">
                                    <input type="checkbox" name="hari[]" value="{{ $hari }}" class="chk-hari-praktik"
                                        style="cursor: pointer; accent-color: var(--primary);">
                                    <span>{{ $hari }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                <span id="labelTanggalAwal">Tanggal Awal Berlaku</span> <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="inputTanggal" class="form-control" required>
                            <div id="pemberlakuanHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                Tanggal pertama praktik dijadwalkan.
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                <span id="labelTanggalAkhir">Tanggal Akhir Berlaku</span>
                                <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                            </label>
                            <input type="date" name="tanggal_akhir" id="inputTanggalAkhir" class="form-control">
                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                Kosongkan untuk 1 bulan penuh (Mingguan) atau 1 semester (Bulanan).
                            </div>
                        </div>
                    </div>

                    {{-- Jam mulai + selesai --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jam Mulai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="time" name="jam_mulai" id="inputJamMulai" class="form-control" value="08:00" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jam Selesai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="time" name="jam_selesai" id="inputJamSelesai" class="form-control" value="09:30" required>
                        </div>
                    </div>

                    {{-- Guru Pengampu: Bisa lebih dari 1 guru (Point 2) --}}
                    <div style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Guru Pengampu Utama <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="ptk_id" id="selectGuru" class="form-control" required>
                            <option value="">-- Pilih Guru Utama --</option>
                            @foreach ($allGtk as $g)
                                <option value="{{ $g->ptk_id }}">{{ $g->nama }}{{ !empty($g->nip) ? ' — ' . $g->nip : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom: var(--space-7);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                            <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                Guru Pendamping / Kolaborasi (Bisa &gt; 1 Guru)
                            </label>
                            <span style="font-size: var(--text-xs); color: var(--primary); font-weight: var(--font-semibold);">
                                <i class="fas fa-users me-1"></i> Tim Teaching
                            </span>
                        </div>
                        <div style="max-height: 140px; overflow-y: auto; padding: var(--space-4) var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md); background: rgba(255,255,255,0.01);">
                            @foreach ($allGtk as $g)
                                <label style="display: flex; align-items: center; gap: var(--space-3); padding: 4px 0; font-size: var(--text-sm); cursor: pointer;">
                                    <input type="checkbox" name="ptk_ids[]" value="{{ $g->ptk_id }}" class="chk-guru-pendamping" style="cursor: pointer;">
                                    <span>{{ $g->nama }}{{ !empty($g->nip) ? ' (' . $g->nip . ')' : '' }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            <i class="fas fa-shield-halved text-primary me-1"></i> Setiap guru yang dipilih akan otomatis dideteksi agar tidak bentrok dengan jadwal lain.
                        </div>
                    </div>

                    {{-- Baris 5: Topik (opsional) --}}
                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Topik / Materi Praktik
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                        </label>
                        <input type="text" name="topik_praktik" id="inputTopik" class="form-control"
                            placeholder="Contoh: Pengukuran pH tanah / Konfigurasi VLAN / Reaksi Redoks">
                    </div>

                    {{-- Baris 6: Alat & bahan --}}
                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kebutuhan Alat / Bahan Praktik
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                        </label>
                        <textarea name="alat_bahan_digunakan" id="inputAlat" class="form-control" rows="2"
                            placeholder="Sebutkan alat, komputer, cangkul, bibit, bahan kimia, atau bahan yang diperlukan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalJadwal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>

