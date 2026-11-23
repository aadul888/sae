        {{-- Modal Ajukan Peminjaman --}}
        <div id="modalPeminjaman" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Ajukan Peminjaman Alat</h3>
                    <button type="button" data-close="modalPeminjaman" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPeminjaman" method="POST" action="{{ route('dashboard.laboran.peminjaman-alat.store') }}">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); align-items: start; margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tipe Peminjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="peminjam_tipe" id="pjTipe" class="form-control" required>
                                @foreach ($tipeList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Peminjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>

                            {{-- Live search: ketik nama / NISN / NIP / NIPD, atau ketik manual untuk peminjam umum --}}
                            <input type="search" name="peminjam_nama" id="pjNama" class="form-control" required
                                placeholder="Cari siswa/GTK atau ketik nama..." autocomplete="off"
                                data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                                data-people-target="#pjId" data-people-type-target="#pjTipe"
                                data-people-unit-target="#pjKelas" data-people-clear-unit-on-input="true">
                            <input type="hidden" name="peminjam_id" id="pjId">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); align-items: start; margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kelas / Unit Kerja</label>
                            <input type="text" name="peminjam_kelas" id="pjKelas" class="form-control" placeholder="Contoh: XI TKJ 1 / Guru Kejuruan">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi Pengambilan</label>
                            <div class="people-autocomplete">
                                <input type="text" name="ruang_lab_nama" id="pjLokasi" class="form-control"
                                    autocomplete="off" placeholder="Ketuk atau ketik lokasi pengambilan...">
                            </div>
                            <script type="application/json" id="daftarLokasiPeminjaman">@json($lokasiOptions)</script>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Keperluan <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="keperluan" id="pjKeperluan" class="form-control" required placeholder="Contoh: Praktik konfigurasi jaringan">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); align-items: start; margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Pinjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_pinjam" id="pjTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Rencana Kembali <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_kembali_rencana" id="pjKembali" class="form-control" required>
                        </div>
                    </div>

                    {{-- Pilih Alat Terpadu (Laboratorium & Sarpras) --}}
                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Alat yang Dipinjam <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-4);">
                            <i class="fas fa-magnifying-glass me-1"></i> Cari alat/ruang, klik hasilnya untuk menambahkan, lalu isi jumlah yang dipinjam.
                        </p>

                        {{-- Kolom pencarian dengan daftar hasil (dropdown) --}}
                        <div class="people-autocomplete" style="margin-bottom: var(--space-5);">
                            <input type="search" id="pjAlatCari" class="form-control" autocomplete="off"
                                placeholder="Cari alat / aset / ruang — nama, kode, atau merk...">
                            <div id="pjAlatHasil" class="people-autocomplete-results" role="listbox"></div>
                        </div>

                        {{-- Daftar alat yang sudah ditambahkan + isian kuantitas --}}
                        <div id="pjAlatTerpilih" class="people-autocomplete-selected-list" style="max-height: 320px;"></div>
                        <p id="pjAlatKosong" style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-3) 0 0;">
                            Belum ada alat dipilih.
                        </p>
                        <script type="application/json" id="daftarSumberPeminjaman">@json($sumberPeminjaman ?? [])</script>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="pjCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalPeminjaman">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Ajukan Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>

