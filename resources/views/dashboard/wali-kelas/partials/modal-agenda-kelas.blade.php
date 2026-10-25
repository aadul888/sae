    <!-- MODAL 1: FORM TAMBAH / EDIT AGENDA KELAS (BANTUAN WALI KELAS / KOORDINATOR / PIKET) -->
    <div id="modalFormAgendaKelas" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 660px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div class="ma-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3 id="modalAgendaKelasTitle"
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-open-reader text-primary"></i> Input Agenda Kegiatan Kelas
                </h3>
                <button type="button" class="btn-close-modal close-agenda-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll"
                style="overflow-y: auto; -webkit-overflow-scrolling: touch; padding-right: 4px;">
                <div
                    style="background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); border-radius: var(--radius-md, 8px); padding: 8px 12px; margin-bottom: 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary" style="font-size: var(--text-md, 1rem); flex-shrink: 0;"></i>
                    <span>Fitur pendampingan Wali Kelas &amp; Koordinator untuk membantu mencatat jurnal KBM. Data yang
                        diinput akan otomatis tersinkronkan dengan jurnal guru mata pelajaran.</span>
                </div>

                <form id="formAgendaKelas">
                    @csrf
                    <input type="hidden" id="agendaKelasId" name="id">
                    <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                    <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                    <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                    <input type="hidden" id="inputPtkId" name="ptk_id">
                    <input type="hidden" id="inputHari" name="hari">

                    <!-- Slot Jadwal Terpilih Banner (Jika diklik dari baris jadwal) -->
                    <div id="slotJadwalBanner"
                        style="display: none; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-md, 8px); padding: 10px 12px; margin-bottom: 14px;">
                        <div
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700;">
                            Mengisi Slot Terjadwal:</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin-top: 2px;">
                            <span id="bannerSlotMapel">-</span> &bull; <span id="bannerSlotGuru"
                                class="text-primary">-</span>
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                            <span id="bannerSlotWaktu">-</span> &bull; Kelas:
                            <strong>{{ $activeRombel->nama ?? '-' }}</strong>
                        </div>
                    </div>

                    <!-- Mode Pilihan: Dari Jadwal Pelajaran Kelas -->
                    @if ($jadwalList->isNotEmpty())
                        <div id="wrapSelectJadwalKelas" style="margin-bottom: 14px;">
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal Pelajaran Kelas:
                            </label>
                            <select id="selectJadwalKelas"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                                <option value="">-- Pilih Slot Jadwal KBM (Otomatis Isi Guru &amp; Mapel) --
                                </option>
                                @foreach ($jadwalList as $j)
                                    <option value="{{ $j['id'] }}" data-ptk-id="{{ $j['ptk_id'] }}"
                                        data-guru="{{ $j['nama_guru'] }}" data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                        data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                        data-mapel-id="{{ $j['mata_pelajaran_id'] }}" data-hari="{{ $j['hari'] }}"
                                        data-jam-mulai="{{ $j['jam_ke_mulai'] }}"
                                        data-jam-selesai="{{ $j['jam_ke_selesai'] }}">
                                        [{{ $j['hari'] }}] {{ $j['nama_mata_pelajaran'] }} &bull;
                                        {{ $j['nama_guru'] }} (Jam
                                        {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-book text-primary"></i> Mata Pelajaran <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required
                                placeholder="Nama Mata Pelajaran..."
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-chalkboard-user text-primary"></i> Guru Pengampu <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select id="selectGuruPengampu" required
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                @foreach ($guruMapelList as $g)
                                    <option value="{{ $g->ptk_id }}">{{ $g->nama_guru }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-calendar-day text-primary"></i> Tanggal <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" id="inputTanggal" name="tanggal" value="{{ date('Y-m-d') }}" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-hashtag text-primary"></i> Pert. Ke <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" id="inputPertemuanKe" name="pertemuan_ke" min="1"
                                max="100" value="1" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; box-sizing: border-box; text-align: center;">
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                Jam Mulai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0"
                                max="20" value="1" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); box-sizing: border-box; text-align: center;">
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                Jam Selesai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0"
                                max="20" value="2" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); box-sizing: border-box; text-align: center;">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-heading text-primary"></i> Materi Pokok / Bahasan <span
                                style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" id="inputMateriPokok" name="materi_pokok" required
                            placeholder="Contoh: Pengenalan Topologi Jaringan &amp; Konfigurasi Router..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-list-check text-primary"></i> Uraian Kegiatan KBM <span
                                style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <textarea id="inputUraianKegiatan" name="uraian_kegiatan" rows="3" required
                            placeholder="Ringkasan aktivitas pembelajaran, diskusi, demonstrasi materi, atau praktik di kelas/lab..."
                            style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-tasks text-primary"></i> Penugasan / Tagihan Siswa
                            </label>
                            <input type="text" id="inputPenugasan" name="penugasan"
                                placeholder="Tugas mandiri / PR / kelompok..."
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-signal text-primary"></i> Status KBM <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select id="inputStatusKbm" name="status_kbm" required
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                                <option value="Terlaksana" selected>Terlaksana Penuh</option>
                                <option value="Sebagian">Sebagian</option>
                                <option value="Tertunda">Tertunda</option>
                                <option value="Digantikan">Digantikan</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-comment-dots text-primary"></i> Hambatan / Catatan Tambahan (Opsional)
                        </label>
                        <input type="text" id="inputHambatanCatatan" name="hambatan_catatan"
                            placeholder="Catatan kelas, kendala siswa, atau keterangan guru pengganti..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-outline close-agenda-modal"
                            style="padding: 7px 16px; font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitAgendaKelas" class="btn btn-primary"
                            style="padding: 7px 18px; font-size: var(--text-base, 0.875rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-save"></i> <span>Simpan Agenda</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: DETAIL LENGKAP AGENDA KELAS -->
    <div id="modalDetailAgendaKelas" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px;">
        <div class="card modal-card-responsive"
            style="max-width: 620px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                        <i class="fas fa-book-open-reader"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Detail Agenda KBM Kelas
                        </h3>
                        <div id="detailSubTitle"
                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Laporan pelaksanaan kegiatan belajar mengajar
                        </div>
                    </div>
                </div>
                <button type="button" class="close-detail-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <div id="detailLoading" style="text-align: center; padding: 30px; color: var(--text-muted);">
                    <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                    <div>Memuat detail agenda...</div>
                </div>

                <div id="detailContent" style="display: none; display: flex; flex-direction: column; gap: 12px;">
                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: var(--bg-hover); padding: 12px; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color);">
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Mata
                                Pelajaran:</span>
                            <strong id="dtMapel" style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">-</strong>
                        </div>
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Guru
                                Pengampu:</span>
                            <strong id="dtGuru" style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">-</strong>
                        </div>
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Waktu
                                Pertemuan:</span>
                            <span id="dtWaktu"
                                style="font-size: var(--text-base, 0.875rem); color: var(--text-color); font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Status
                                KBM:</span>
                            <div id="dtStatus" style="margin-top: 2px;">-</div>
                        </div>
                    </div>

                    <div>
                        <span
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Materi
                            Pokok:</span>
                        <div id="dtMateri"
                            style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); background: var(--card-bg); padding: 10px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                            -
                        </div>
                    </div>

                    <div>
                        <span
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Uraian
                            Kegiatan KBM:</span>
                        <div id="dtUraian"
                            style="font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.5; background: var(--card-bg); padding: 10px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); white-space: pre-line;">
                            -
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Penugasan:</span>
                            <div id="dtPenugasan"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); background: var(--card-bg); padding: 8px 10px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                                -
                            </div>
                        </div>
                        <div>
                            <span
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Dicatat
                                Oleh:</span>
                            <div id="dtCreatedBy"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); background: var(--card-bg); padding: 8px 10px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                                -
                            </div>
                        </div>
                    </div>

                    <div id="boxDtCatatan" style="display: none;">
                        <span
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Hambatan
                            / Catatan:</span>
                        <div id="dtCatatan"
                            style="font-size: var(--text-sm, 0.8125rem); color: var(--warning, #d97706); background: rgba(245,158,11,0.08); padding: 8px 10px; border-radius: var(--radius-md, 8px); border: 1px dashed rgba(245,158,11,0.3);">
                            -
                        </div>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-detail-modal"
                    style="padding: 7px 18px; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px);">
                    Tutup
                </button>
            </div>
        </div>
    </div>
