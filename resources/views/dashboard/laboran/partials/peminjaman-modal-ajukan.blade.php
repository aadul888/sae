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
                        <div style="max-height: 260px; overflow-y: auto; padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            {{-- Seksi Alat Lab (hanya inventaris jurusan laboran) --}}
                            <div style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary); text-transform: uppercase; margin-bottom: var(--space-3);">
                                <i class="fas fa-flask me-1"></i> Alat Praktik Laboratorium / Bengkel
                            </div>
                            @forelse ($itemOptions as $io)
                                <div style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-3) 0; border-bottom: 1px dashed var(--border-color);">
                                    <input type="checkbox" name="item_ids[]" value="{{ $io->id }}" class="pj-item" style="cursor: pointer;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--text-color);">{{ $io->nama_item }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $io->kode_item }} &bull; Stok: {{ (float) $io->stok_tersedia }} {{ $io->satuan }}
                                            @if ($io->ruang_lab_nama) &bull; {{ $io->ruang_lab_nama }} @endif
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0.01" max="{{ (float) $io->stok_tersedia }}"
                                        name="item_jumlah[{{ $io->id }}]" value="1" class="form-control"
                                        style="width: 90px; height: 34px; font-size: var(--text-sm);" title="Jumlah pinjam">
                                </div>
                            @empty
                                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-4) 0;">
                                    Belum ada alat dalam inventaris laboratorium.
                                </p>
                            @endforelse
                        </div>
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

