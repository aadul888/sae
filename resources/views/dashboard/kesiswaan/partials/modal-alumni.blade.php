{{-- Modal Input/Edit Data Alumni Manual (format 43 kolom Dapodik Peserta Didik Keluar) --}}
<div id="modalAlumni" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="max-width: 900px; width: 95%; max-height: 92vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <h3 id="modalAlumniTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Tambah Data Alumni
                    </h3>
                    <div id="modalAlumniSubtitle"
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Format 43 kolom Dapodik Peserta Didik Keluar.
                    </div>
                </div>
            </div>
            <button type="button" class="close-modal" data-target="#modalAlumni" aria-label="Tutup" title="Tutup"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formAlumni" method="POST" action="{{ route('dashboard.kesiswaan.peserta-didik.alumni.store') }}"
            style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
            @csrf
            <input type="hidden" id="alumniMethod" name="_method" value="POST">
            <input type="hidden" id="alumniPesertaDidikId" name="peserta_didik_id">

            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 6px;">

                {{-- A. Identitas Pokok --}}
                <div class="alumni-section-title"><i class="fas fa-id-card me-1"></i> Identitas Pokok</div>
                <div class="alumni-grid">
                    <div class="alumni-field alumni-span-2">
                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="alNama" class="form-control" required maxlength="150">
                    </div>
                    <div class="alumni-field">
                        <label>NIPD / No. Induk</label>
                        <input type="text" name="nipd" id="alNipd" class="form-control" maxlength="50">
                    </div>
                    <div class="alumni-field">
                        <label>NISN</label>
                        <input type="text" name="nisn" id="alNisn" class="form-control" maxlength="20">
                    </div>
                    <div class="alumni-field">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="alJenisKelamin" class="form-select">
                            <option value="">— Pilih —</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="alumni-field">
                        <label>NIK</label>
                        <input type="text" name="nik" id="alNik" class="form-control" maxlength="30">
                    </div>
                    <div class="alumni-field">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="alTempatLahir" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="alTanggalLahir" class="form-control">
                    </div>
                    <div class="alumni-field">
                        <label>Agama</label>
                        <input type="text" name="agama_id_str" id="alAgama" class="form-control" maxlength="50">
                    </div>
                    <div class="alumni-field">
                        <label>Kebutuhan Khusus</label>
                        <input type="text" name="kebutuhan_khusus" id="alKebutuhanKhusus" class="form-control"
                            maxlength="100" placeholder="Tidak ada">
                    </div>
                    <div class="alumni-field">
                        <label>Rombel Terakhir</label>
                        <input type="text" name="nama_rombel_terakhir" id="alRombel" class="form-control"
                            maxlength="100" placeholder="Contoh: XII TKJ 1">
                    </div>
                    <div class="alumni-field">
                        <label>Tahun Lulus</label>
                        <input type="text" name="tahun_lulus" id="alTahunLulus" class="form-control" maxlength="20"
                            placeholder="Contoh: 2025/2026">
                    </div>
                    <div class="alumni-field">
                        <label>Keluar Karena</label>
                        <input type="text" name="alasan_keluar" id="alAlasanKeluar" class="form-control"
                            maxlength="100" placeholder="Lulus / Mutasi / Keluar">
                    </div>
                    <div class="alumni-field">
                        <label>Tanggal Keluar</label>
                        <input type="date" name="tanggal_keluar" id="alTanggalKeluar" class="form-control">
                    </div>
                </div>

                {{-- B. Alamat & Kontak --}}
                <div class="alumni-section-title"><i class="fas fa-location-dot me-1"></i> Alamat & Kontak</div>
                <div class="alumni-grid">
                    <div class="alumni-field alumni-span-2">
                        <label>Alamat Jalan</label>
                        <input type="text" name="alamat_jalan" id="alAlamat" class="form-control">
                    </div>
                    <div class="alumni-field">
                        <label>RT</label>
                        <input type="text" name="rt" id="alRt" class="form-control" maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>RW</label>
                        <input type="text" name="rw" id="alRw" class="form-control" maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>Dusun</label>
                        <input type="text" name="dusun" id="alDusun" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Kelurahan / Desa</label>
                        <input type="text" name="kelurahan" id="alKelurahan" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Kecamatan</label>
                        <input type="text" name="kecamatan" id="alKecamatan" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Kode Pos</label>
                        <input type="text" name="kode_pos" id="alKodePos" class="form-control" maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>Jenis Tinggal</label>
                        <input type="text" name="jenis_tinggal" id="alJenisTinggal" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Alat Transportasi</label>
                        <input type="text" name="alat_transportasi" id="alAlatTransportasi" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Telepon Rumah</label>
                        <input type="text" name="nomor_telepon_rumah" id="alTeleponRumah" class="form-control"
                            maxlength="50">
                    </div>
                    <div class="alumni-field">
                        <label>HP / WhatsApp</label>
                        <input type="text" name="nomor_telepon_seluler" id="alNoHp" class="form-control"
                            maxlength="30">
                    </div>
                    <div class="alumni-field">
                        <label>E-Mail</label>
                        <input type="email" name="email" id="alEmail" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>SKHUN</label>
                        <input type="text" name="skhun" id="alSkhun" class="form-control" maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Penerima KPS</label>
                        <input type="text" name="penerima_kps" id="alPenerimaKps" class="form-control"
                            maxlength="20" placeholder="Ya / Tidak">
                    </div>
                    <div class="alumni-field">
                        <label>No. KPS</label>
                        <input type="text" name="no_kps" id="alNoKps" class="form-control" maxlength="100">
                    </div>
                </div>

                {{-- C. Data Ayah --}}
                <div class="alumni-section-title"><i class="fas fa-user me-1"></i> Data Ayah</div>
                <div class="alumni-grid">
                    <div class="alumni-field">
                        <label>Nama</label>
                        <input type="text" name="nama_ayah" id="alNamaAyah" class="form-control" maxlength="150">
                    </div>
                    <div class="alumni-field">
                        <label>Tahun Lahir</label>
                        <input type="text" name="tahun_lahir_ayah" id="alTahunLahirAyah" class="form-control"
                            maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>Jenjang Pendidikan</label>
                        <input type="text" name="jenjang_pendidikan_ayah" id="alPendidikanAyah" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan_ayah" id="alPekerjaanAyah" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Penghasilan</label>
                        <input type="text" name="penghasilan_ayah" id="alPenghasilanAyah" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Kebutuhan Khusus</label>
                        <input type="text" name="kebutuhan_khusus_ayah" id="alKhususAyah" class="form-control"
                            maxlength="100">
                    </div>
                </div>

                {{-- D. Data Ibu --}}
                <div class="alumni-section-title"><i class="fas fa-user me-1"></i> Data Ibu</div>
                <div class="alumni-grid">
                    <div class="alumni-field">
                        <label>Nama</label>
                        <input type="text" name="nama_ibu" id="alNamaIbu" class="form-control" maxlength="150">
                    </div>
                    <div class="alumni-field">
                        <label>Tahun Lahir</label>
                        <input type="text" name="tahun_lahir_ibu" id="alTahunLahirIbu" class="form-control"
                            maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>Jenjang Pendidikan</label>
                        <input type="text" name="jenjang_pendidikan_ibu" id="alPendidikanIbu" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan_ibu" id="alPekerjaanIbu" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Penghasilan</label>
                        <input type="text" name="penghasilan_ibu" id="alPenghasilanIbu" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Kebutuhan Khusus</label>
                        <input type="text" name="kebutuhan_khusus_ibu" id="alKhususIbu" class="form-control"
                            maxlength="100">
                    </div>
                </div>

                {{-- E. Data Wali --}}
                <div class="alumni-section-title"><i class="fas fa-people-roof me-1"></i> Data Wali</div>
                <div class="alumni-grid">
                    <div class="alumni-field">
                        <label>Nama</label>
                        <input type="text" name="nama_wali" id="alNamaWali" class="form-control" maxlength="150">
                    </div>
                    <div class="alumni-field">
                        <label>Tahun Lahir</label>
                        <input type="text" name="tahun_lahir_wali" id="alTahunLahirWali" class="form-control"
                            maxlength="10">
                    </div>
                    <div class="alumni-field">
                        <label>Jenjang Pendidikan</label>
                        <input type="text" name="jenjang_pendidikan_wali" id="alPendidikanWali" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan_wali" id="alPekerjaanWali" class="form-control"
                            maxlength="100">
                    </div>
                    <div class="alumni-field">
                        <label>Penghasilan</label>
                        <input type="text" name="penghasilan_wali" id="alPenghasilanWali" class="form-control"
                            maxlength="100">
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-shrink: 0;">
                <button type="button" class="btn btn-outline close-modal" data-target="#modalAlumni"
                    style="border-radius: var(--radius-md, 8px); padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">
                    Batal
                </button>
                <button type="submit" id="btnSimpanAlumni" class="btn btn-primary"
                    style="border-radius: var(--radius-md, 8px); padding: 8px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Alumni
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
    <style>
        .alumni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
        }

        .alumni-section-title {
            font-weight: 700;
            color: var(--primary, #3b82f6);
            font-size: var(--text-sm, 0.8125rem);
            margin: 14px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .alumni-section-title:first-child {
            margin-top: 4px;
        }

        .alumni-field label {
            display: block;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .alumni-field .form-control,
        .alumni-field .form-select {
            font-size: var(--text-sm, 0.8125rem);
            min-height: 36px;
        }

        .alumni-span-2 {
            grid-column: span 2;
        }

        @media (max-width: 640px) {
            .alumni-span-2 {
                grid-column: 1 / -1;
            }
        }
    </style>
@endpush
