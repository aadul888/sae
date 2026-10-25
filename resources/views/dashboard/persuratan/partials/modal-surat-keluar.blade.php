        <!-- Modal 1: Catat / Edit Surat Keluar Umum -->
        <div class="modal-overlay" id="modalSuratKeluar"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <h4 id="modalKeluarTitle"
                        style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Catat Surat Keluar Baru
                    </h4>
                    <button type="button" id="btnCloseModalKeluar"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formSuratKeluar" action="" method="POST" enctype="multipart/form-data"
                    style="overflow-y: auto; flex: 1;">
                    @csrf
                    <input type="hidden" name="_method" id="methodSpoofKeluar" value="POST">

                    <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Kode Indeks Klasifikasi: <span class="text-danger">*</span>
                            </label>
                            <select name="kode_indeks" id="selectKodeIndeksKeluar"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                                @foreach ($indeksList as $idx)
                                    <option value="{{ $idx->kode }}">{{ $idx->kode }} - {{ $idx->judul }}
                                        ({{ $idx->kategori }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Nomor Surat Keluar:
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="nomor_surat" id="inputNomorSuratKeluar"
                                    value="{{ $suggestedNumber }}"
                                    placeholder="Otomatis digenerate sistem jika dikosongkan..."
                                    style="flex: 1; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-family: monospace; font-size: var(--text-base, 0.875rem);">
                                <button type="button" class="btn btn-outline" id="btnAutoNumberKeluar"
                                    style="padding: 0 12px; height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                                    title="Generate ulang nomor">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                Sistem akan otomatis menghitung nomor urut selanjutnya jika Anda tidak mengubahnya.
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tujuan Penerima: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tujuan_penerima" id="inputTujuanKeluar"
                                placeholder="Contoh: Kepala Balai Besar Penjaminan Mutu Pendidikan"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Perihal Surat: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="perihal" id="inputPerihalKeluar"
                                placeholder="Contoh: Permohonan Narasumber Workshop Digitalisasi"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Tanggal Surat: <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_surat" id="inputTanggalSuratKeluar"
                                    value="{{ date('Y-m-d') }}"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                    required>
                            </div>
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Status Surat:
                                </label>
                                <select name="status" id="selectStatusKeluar"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                    <option value="selesai">Selesai / Terbit</option>
                                    <option value="draf">Draf</option>
                                    <option value="diarsipkan">Diarsipkan</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Unggah Dokumen Final / Scan Bertanda Tangan (Disimpan ke HDD):
                            </label>
                            <input type="file" name="file_arsip" accept=".pdf,.jpg,.jpeg,.png"
                                style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                            <div id="fileInfoExistingKeluar"
                                style="display: none; font-size: var(--text-xs, 0.75rem); margin-top: 4px;">
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                Maksimal 25MB (PDF, JPG, PNG). Berkas fisik disimpan langsung di partisi harddisk.
                            </div>
                        </div>

                        <!-- Integrasi Sarpras Aset (Opsional BAST / Pemeliharaan / Mutasi) -->
                        <div
                            style="background: rgba(245,158,11,0.05); border: 1px dashed rgba(245,158,11,0.3); border-radius: var(--radius-lg, 12px); padding: 12px 14px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label
                                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--warning, #d97706); margin: 0; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-boxes-stacked"></i> Kaitkan Barang Sarpras / BAST (Opsional):
                                </label>
                                <button type="button" id="btnQuickBastKeluar" class="btn btn-outline"
                                    style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); border-color: rgba(245,158,11,0.4); color: var(--warning, #d97706);">
                                    <i class="fas fa-magic me-1"></i> Format BAST Otomatis
                                </button>
                            </div>
                            <select name="sarpras_aset_id" id="selectSarprasAsetKeluar"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                                <option value="">-- Tidak dikaitkan dengan aset sarpras --</option>
                                @if (isset($daftarAset))
                                    @foreach ($daftarAset as $ast)
                                        <option value="{{ $ast->id }}" data-kode="{{ $ast->kode_aset }}"
                                            data-nama="{{ $ast->nama_barang }}" data-merk="{{ $ast->merk_tipe }}"
                                            data-kondisi="{{ $ast->kondisi }}">
                                            [{{ $ast->kode_aset }}] {{ $ast->nama_barang }}
                                            ({{ $ast->merk_tipe ?: 'Merk -' }} &bull; Kondisi:
                                            {{ ucfirst($ast->kondisi) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                Pilih aset sekolah jika surat keluar ini merupakan Berita Acara Serah Terima (BAST),
                                penghapusan aset, atau surat permohonan servis.
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Keterangan Tambahan:
                            </label>
                            <textarea name="keterangan" id="inputKeteranganKeluar" rows="2" placeholder="Catatan perihal surat..."
                                style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                        </div>
                    </div>

                    <div
                        style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                        <button type="button" id="btnCancelModalKeluar" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-save me-1"></i> Simpan Surat Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Pembuatan Surat Keterangan Siswa Aktif Otomatis -->
        <div class="modal-overlay" id="modalSuratKeterangan"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 580px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem);">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div>
                            <h4 id="modalKetTitle"
                                style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Terbitkan Surat Keterangan / Panggilan Siswa
                            </h4>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Terintegrasi dengan data induk siswa Dapodik, Kedisiplinan &amp; QR Code verifikasi.
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnCloseModalKet"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formSuratKeterangan" action="{{ route('dashboard.persuratan.keterangan.store') }}"
                    method="POST" style="overflow-y: auto; flex: 1;">
                    @csrf
                    <input type="hidden" name="peserta_didik_id" id="inputPesertaDidikId" required>

                    <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                        <!-- 1. Pilihan Jenis Surat -->
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Jenis Surat yang Diterbitkan: <span class="text-danger">*</span>
                            </label>
                            <select name="jenis_surat" id="selectJenisSuratKet"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 600;"
                                required>
                                <option value="siswa_aktif">Surat Keterangan Siswa Aktif</option>
                                <option value="kelakuan_baik">Surat Keterangan Berkelakuan Baik</option>
                                <option value="panggilan_ortu">Surat Panggilan Orang Tua / Wali (Integrasi BK &amp;
                                    Kedisiplinan)</option>
                                <option value="rekomendasi">Surat Rekomendasi Siswa</option>
                            </select>
                        </div>

                        <!-- Autocomplete Siswa Dapodik -->
                        <div style="position: relative;">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Cari Peserta Didik (Nama / NISN / NIPD): <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <input type="search" id="searchSiswaInput"
                                    placeholder="Ketik nama, NISN, atau NIPD..." autocomplete="off" required
                                    data-people-search="{{ route('dashboard.people.search') }}"
                                    data-people-kind="siswa" data-people-target="#inputPesertaDidikId"
                                    data-people-require-selection="true"
                                    style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            </div>
                        </div>

                        <!-- Kartu Info Siswa Terpilih -->
                        <div id="selectedSiswaCard"
                            style="display: none; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius-lg, 12px); padding: 12px 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div
                                        style="font-size: var(--text-xs, 0.75rem); text-transform: uppercase; font-weight: 700; color: var(--primary);">
                                        <i class="fas fa-check-circle me-1"></i> Peserta Didik Terpilih
                                    </div>
                                    <div id="selectedSiswaNama"
                                        style="font-weight: 800; font-size: var(--text-md, 1rem); color: var(--text-color); margin: 2px 0;">
                                        -</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        Identitas: <strong id="selectedSiswaNisn">-</strong> | Kelas: <strong
                                            id="selectedSiswaKelas">-</strong>
                                    </div>
                                </div>
                                <button type="button" id="btnChangeSiswa" class="btn btn-outline"
                                    style="padding: 4px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px);">
                                    Ganti
                                </button>
                            </div>
                        </div>

                        <!-- Kode Indeks Klasifikasi Surat -->
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Kode Indeks Klasifikasi Surat: <span class="text-danger">*</span>
                            </label>
                            <select name="kode_indeks" id="selectKodeIndeksKet"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                                @foreach ($indeksList as $idx)
                                    <option value="{{ $idx->kode }}"
                                        {{ $idx->kode === 'KS.02.23' ? 'selected' : '' }}>
                                        {{ $idx->kode }} - {{ $idx->judul }} ({{ $idx->kategori }})
                                    </option>
                                @endforeach
                            </select>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                Pilih klasifikasi indeks surat. Nomor surat otomatis akan menyesuaikan format indeks
                                ini.
                            </div>
                        </div>

                        <!-- Nomor Surat Keterangan -->
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Nomor Surat Keterangan:
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="nomor_surat" id="inputNomorSuratKet"
                                    value="{{ $suggestedKetNumber }}"
                                    placeholder="Otomatis mengikuti template indeks..."
                                    style="flex: 1; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-family: monospace; font-size: var(--text-base, 0.875rem);">
                                <button type="button" class="btn btn-outline" id="btnAutoNumberKet"
                                    style="padding: 0 12px; height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                                    title="Generate ulang nomor">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                Format standar mengikuti template pengaturan:
                                <code>{kode_indeks}/{nomor}/{sekolah_kode}/{romawi_bulan}/{tahun}</code>.
                            </div>
                        </div>

                        <!-- Keperluan Surat -->
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Keperluan Penerbitan Surat: <span class="text-danger">*</span>
                            </label>
                            <select id="selectKeperluanPreset"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 6px;">
                                <option value="">-- Pilih Contoh Keperluan / Ketik Sendiri --</option>
                                <option value="Kelengkapan Berkas Tunjangan Gaji / Anak Orang Tua">Kelengkapan Berkas
                                    Tunjangan Gaji / Anak Orang Tua</option>
                                <option value="Pengurusan Beasiswa PIP / Prestasi">Pengurusan Beasiswa PIP / Prestasi
                                </option>
                                <option value="Pembukaan Rekening Tabungan Bank">Pembukaan Rekening Tabungan Bank
                                </option>
                                <option value="Pendaftaran BPJS / Asuransi Kesehatan">Pendaftaran BPJS / Asuransi
                                    Kesehatan
                                </option>
                                <option value="Melanjutkan Studi / Pindah Sekolah">Melanjutkan Studi / Pindah Sekolah
                                </option>
                                <option value="Pengurusan Visa / Paspor Kedutaan">Pengurusan Visa / Paspor Kedutaan
                                </option>
                            </select>
                            <input type="text" name="keperluan" id="inputKeperluan"
                                placeholder="Tuliskan keperluan lengkap..."
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <!-- Rincian Khusus Surat Panggilan Orang Tua (Integrasi BK & Kedisiplinan) -->
                        <div id="sectionPanggilanOrtu"
                            style="display: none; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.25); border-radius: var(--radius-lg, 12px); padding: 12px 14px; flex-direction: column; gap: 10px;">
                            <div
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-calendar-check"></i> Rincian Jadwal &amp; Agenda Pertemuan dengan
                                Orang
                                Tua:
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label
                                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 3px; color: var(--text-color);">Hari/Tanggal
                                        Menghadap:</label>
                                    <input type="date" name="tanggal_agenda" id="inputTanggalAgenda"
                                        value="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 3px; color: var(--text-color);">Waktu
                                        / Pukul:</label>
                                    <input type="text" name="waktu_agenda" id="inputWaktuAgenda"
                                        value="08:30 WIB" placeholder="08:30 WIB"
                                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label
                                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 3px; color: var(--text-color);">Tempat:</label>
                                    <input type="text" name="tempat_agenda" id="inputTempatAgenda"
                                        value="Ruang Bimbingan Konseling (BK)" placeholder="Ruang BK / Ruang TAS"
                                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 3px; color: var(--text-color);">Menghadap
                                        Kepada:</label>
                                    <input type="text" name="menghadap_agenda" id="inputMenghadapAgenda"
                                        value="Guru BK & Wali Kelas" placeholder="Guru BK / Wali Kelas"
                                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                                </div>
                            </div>
                            <div>
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; margin-bottom: 3px; color: var(--text-color);">Catatan
                                    Khusus Pembinaan (Opsional):</label>
                                <input type="text" name="catatan_khusus" id="inputCatatanKhusus"
                                    placeholder="Misal: Harap didampingi orang tua kandung..."
                                    style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tanggal Surat:
                            </label>
                            <input type="date" name="tanggal_surat" value="{{ date('Y-m-d') }}"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>
                    </div>

                    <div
                        style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                        <button type="button" id="btnCancelModalKet" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-check-circle me-1"></i> Terbitkan &amp; Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Pratinjau Dokumen Berkas dari HDD Komputer -->
        <div class="modal-overlay" id="modalPreviewDokumen"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 900px; height: 85vh; box-shadow: 0 25px 50px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden;">
                <div
                    style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); flex-shrink: 0;">
                    <div
                        style="display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%;">
                        <i class="fas fa-file-lines text-success"></i>
                        <span id="previewTitle"
                            style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            Pratinjau Dokumen Arsip
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="#" id="previewDownloadBtn" class="btn btn-outline"
                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);"
                            title="Unduh Berkas ke Komputer">
                            <i class="fas fa-download me-1"></i> Unduh
                        </a>
                        <button type="button" id="btnClosePreview"
                            style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer; padding: 4px 8px;" aria-label="Tutup">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div
                    style="flex: 1; background: #525659; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <iframe id="previewIframe" src="about:blank"
                        style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                    <img id="previewImage" src="" alt="Preview Berkas"
                        style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                </div>
            </div>
        </div>

        <!-- Modal 4: Buat Surat Perintah Tugas (SPT) -->
        <div class="modal-overlay" id="modalSpt"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <h4
                        style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-contract text-primary"></i> Terbitkan Surat Perintah Tugas (SPT)
                    </h4>
                    <button type="button" id="btnCloseModalSpt"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form action="{{ route('dashboard.persuratan.keluar.spt.store') }}" method="POST" id="formSpt">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama
                            Kegiatan / Tugas Dinas <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="text" name="nama_kegiatan" class="form-control"
                            placeholder="Contoh: Mengikuti Bimtek Implementasi Kurikulum Merdeka" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lokasi
                            / Tempat Tujuan <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="text" name="lokasi_tujuan" class="form-control"
                            placeholder="Contoh: Hotel Grand Sahid / BBGP Provinsi" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Berangkat <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="date" name="tanggal_berangkat" class="form-control" required
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Kembali <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="date" name="tanggal_kembali" class="form-control" required
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Beban
                            Anggaran <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select name="beban_anggaran" class="form-control" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <option value="BOS Reguler">BOS Reguler</option>
                            <option value="BOS Kinerja">BOS Kinerja</option>
                            <option value="Komite Sekolah">Komite Sekolah</option>
                            <option value="Penyelenggara / Panitia">Biaya Penyelenggara / Gratis</option>
                            <option value="Mandiri">Mandiri</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                            GTK Yang Ditugaskan <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="search" id="searchGtkSpt" class="form-control"
                            placeholder="Cari nama, NIP, NUPTK, atau NIK..." autocomplete="off"
                            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk"
                            data-people-multiple-target="#selectedGtkSpt" data-people-multiple-name="daftar_ptk_id[]"
                            data-people-multiple-required="true">
                        <div id="selectedGtkSpt" class="people-autocomplete-selected-list"></div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Dasar
                            Penugasan (Opsional)</label>
                        <textarea name="dasar_penugasan" class="form-control" rows="2"
                            placeholder="Contoh: Surat Undangan Dinas Pendidikan No. 421/123/2026 tanggal 15 September 2026..."
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" id="btnCancelModalSpt" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"><i
                                class="fas fa-check-circle me-1"></i> Terbitkan SPT</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 5: Buat Surat Keluar dari Template (Autocrat Merge) -->
        <div class="modal-overlay" id="modalTemplateSurat"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 720px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <h4
                        style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-wand-magic-sparkles text-primary"></i> Buat Surat Keluar dari Template
                        (Autocrat)
                    </h4>
                    <button type="button" id="btnCloseModalTemplate"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form action="{{ route('dashboard.persuratan.keluar.template.store') }}" method="POST"
                    id="formTemplateSurat">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Pilihan Template Naskah <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="template_type" id="selectTemplateType" class="form-control" required
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                @foreach ($templatePresets as $tKey => $tVal)
                                    <option value="{{ $tKey }}" data-indeks="{{ $tVal['indeks'] }}"
                                        data-perihal="{{ $tVal['perihal'] }}" data-salam="{{ $tVal['salam'] }}"
                                        data-pembuka="{{ $tVal['pembuka'] }}" data-penutup="{{ $tVal['penutup'] }}">
                                        {{ $tVal['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Kode Indeks Klasifikasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="kode_indeks" id="inputTmplKodeIndeks" class="form-control" required
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                @foreach ($indeksList as $idx)
                                    <option value="{{ $idx->kode }}"
                                        {{ $idx->kode === 'HM.01.02' ? 'selected' : '' }}>
                                        {{ $idx->kode }} - {{ $idx->judul }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Nomor Surat Resmi (Auto / Manual)
                            </label>
                            <input type="text" name="nomor_surat" id="inputTmplNomorSurat" class="form-control"
                                value="{{ $suggestedTemplateNumber }}" placeholder="Kosongkan untuk auto-generate..."
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-family: monospace;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Tanggal Surat <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_surat" class="form-control"
                                value="{{ date('Y-m-d') }}" required
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Hal / Perihal Surat <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="perihal" id="inputTmplPerihal" class="form-control" required
                                value="{{ $templatePresets['undangan']['perihal'] ?? '' }}"
                                placeholder="Contoh: Undangan Rapat Evaluasi Belajar Semester Genap"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Lampiran
                            </label>
                            <input type="text" name="lampiran" class="form-control" value="-"
                                placeholder="Misal: 1 Berkas"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Kepada Yth. (Tujuan Penerima) <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="tujuan_penerima" class="form-control" required
                                placeholder="Contoh: Bapak / Ibu Orang Tua / Wali Murid Kelas X"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Lokasi / Alamat Tujuan
                            </label>
                            <input type="text" name="tujuan_lokasi" class="form-control" value="Di Tempat"
                                placeholder="Contoh: Di Tempat / Pandeglang"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Salam &amp; Paragraf Pembuka
                        </label>
                        <input type="text" name="salam_pembuka" id="inputTmplSalam" class="form-control mb-2"
                            value="Dengan hormat,"
                            style="width: 100%; height: 36px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        <textarea name="paragraf_pembuka" id="inputTmplPembuka" class="form-control" rows="2"
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">{{ $templatePresets['undangan']['pembuka'] ?? '' }}</textarea>
                    </div>

                    {{-- Form Rincian Acara / Kegiatan (Autocrat Merge Fields) --}}
                    <div
                        style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 14px; margin-bottom: 14px;">
                        <div
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                            <i class="fas fa-calendar-check text-primary me-1"></i> Rincian Agenda / Kegiatan / Berkas
                            (Opsional)
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                            <div>
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 4px;">Hari
                                    / Tanggal</label>
                                <input type="text" name="hari_tanggal_kegiatan"
                                    placeholder="Contoh: Senin, 20 Oktober 2026"
                                    style="width: 100%; height: 34px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                            </div>
                            <div>
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 4px;">Waktu
                                    / Pukul</label>
                                <input type="text" name="waktu_kegiatan"
                                    placeholder="Contoh: 08.00 WIB s.d. Selesai"
                                    style="width: 100%; height: 34px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 4px;">Tempat
                                    Pelaksanaan</label>
                                <input type="text" name="tempat_kegiatan"
                                    placeholder="Contoh: Aula Serbaguna Sekolah"
                                    style="width: 100%; height: 34px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                            </div>
                            <div>
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 4px;">Agenda
                                    / Acara</label>
                                <input type="text" name="agenda_kegiatan"
                                    placeholder="Contoh: Rapat Komite &amp; Sosialisasi Program"
                                    style="width: 100%; height: 34px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Isi Tambahan / Uraian Bebas (Opsional)
                        </label>
                        <textarea name="isi_surat" class="form-control" rows="2"
                            placeholder="Tuliskan poin-poin tambahan atau isi bebas lainnya jika diperlukan..."
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Paragraf Penutup
                        </label>
                        <textarea name="paragraf_penutup" id="inputTmplPenutup" class="form-control" rows="2"
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">{{ $templatePresets['undangan']['penutup'] ?? '' }}</textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Tembusan (Opsional)
                        </label>
                        <textarea name="tembusan" class="form-control" rows="1" placeholder="1. Ketua Komite Sekolah&#10;2. Arsip"
                            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" id="btnCancelModalTemplate" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 20px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); background: linear-gradient(135deg, #4f46e5 0%, var(--purple, #7c3aed) 100%); border: none;">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Terbitkan &amp; Cetak Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 5: Filter & Cetak Buku Agenda Surat Keluar -->
        <div class="modal-overlay" id="modalAgendaKeluar"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h4
                        style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-print text-primary"></i> Cetak Buku Agenda Surat Keluar
                    </h4>
                    <button type="button" id="btnCloseModalAgendaKeluar"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form action="{{ route('dashboard.persuratan.keluar.agenda.cetak') }}" method="GET"
                    target="_blank" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Rentang Tanggal Surat (Opsional):
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="date" name="tanggal_mulai" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                            <input type="date" name="tanggal_selesai" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tahun:
                            </label>
                            <select name="tahun" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                @for ($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Bulan (Opsional):
                            </label>
                            <select name="bulan" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                <option value="">Semua Bulan (1 Tahun)</option>
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $mNum => $mName)
                                    <option value="{{ $mNum }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kode Indeks Klasifikasi (Opsional):
                        </label>
                        <select name="kode_indeks" class="form-control"
                            style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <option value="">Semua Indeks Klasifikasi</option>
                            @foreach ($indeksList as $idx)
                                <option value="{{ $idx->kode }}">{{ $idx->kode }} - {{ $idx->judul }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                        <button type="button" id="btnCancelModalAgendaKeluar" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-print"></i> Buka Lembar Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>
