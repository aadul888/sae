        {{-- Modal Form Kebutuhan --}}
        <div id="modalKebutuhan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalKebutuhanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        Tambah Kebutuhan
                    </h3>
                    <button type="button" id="btnCloseKebutuhan"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKebutuhan" method="POST" action="{{ route('dashboard.laboran.kebutuhan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="kebutuhanMethod" value="POST">

                    {{-- Referensi Cepat dari Inventaris Lab (Auto-Fill) --}}
                    @if (isset($inventarisList) && $inventarisList->isNotEmpty())
                        <div id="wrapperInventarisRef"
                            style="margin-bottom: var(--space-6); padding: 12px 14px; background: rgba(59,130,246,0.06); border: 1px dashed rgba(59,130,246,0.3); border-radius: var(--radius-md, 8px);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--primary); margin: 0;">
                                    <i class="fas fa-boxes-stacked me-1"></i> Pilih Referensi Inventaris Lab (Auto-Fill)
                                </label>
                                <small style="color: var(--text-muted); font-size: var(--text-xs);">Otomatis isi nama, spek, ketersediaan &amp; satuan</small>
                            </div>
                            <select id="selectInventarisRef" class="form-control" style="font-size: var(--text-sm); height: 38px;">
                                <option value="">-- Ketik Baru atau Pilih dari Inventaris --</option>
                                @foreach ($inventarisList as $inv)
                                    <option value="{{ $inv->id }}"
                                        data-nama="{{ $inv->nama_item }}"
                                        data-jenis="{{ $inv->jenis }}"
                                        data-kategori="{{ $inv->kategori ?? '' }}"
                                        data-lokasi="{{ $inv->ruang_lab_nama }}"
                                        data-satuan="{{ $inv->satuan }}"
                                        data-spek="{{ $inv->spesifikasi }}"
                                        data-total="{{ (float) $inv->stok_total }}"
                                        data-tersedia="{{ (float) ($inv->jumlah_baik > 0 ? $inv->jumlah_baik : $inv->stok_tersedia) }}"
                                        data-kondisi="{{ $inv->kondisi }}"
                                        data-rusakberat="{{ (float) ($inv->jumlah_rusak_berat ?? 0) }}"
                                        data-rusakringan="{{ (float) ($inv->jumlah_rusak_ringan ?? 0) }}"
                                        data-kadaluwarsa="{{ (float) ($inv->jumlah_kedaluwarsa ?? 0) }}"
                                        data-exp="{{ $inv->tgl_kedaluwarsa }}"
                                        data-jurusan="{{ $inv->jurusan_id ?? '' }}">
                                        {{ $inv->nama_item }} &bull; {{ $inv->kode_item }} (Stok: {{ (float)$inv->stok_tersedia }}/{{ (float)$inv->stok_total }} {{ $inv->satuan }} - {{ $inv->kondisi }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Item <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="nama_item" id="kbNama" class="form-control" required placeholder="Contoh: Mikroskop Binokuler">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis" id="kbJenis" class="form-control" required>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kategori</label>
                            <input type="text" name="kategori" id="kbKategori" class="form-control" placeholder="Instrumen / APD / Habis Pakai">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi Praktik</label>
                            <select name="ruang_lab_nama" id="kbLokasi" class="form-control">
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Spesifikasi</label>
                        <textarea name="spesifikasi" id="kbSpesifikasi" class="form-control" rows="2" placeholder="Detail teknis, ukuran, bahan, atau tipe yang dibutuhkan..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-4);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jumlah Kebutuhan (Ideal) <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="jumlah_kebutuhan" id="kbKebutuhan" class="form-control" value="1" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jumlah Tersedia (Laik Pakai)</label>
                            <input type="number" step="0.01" min="0" name="jumlah_tersedia" id="kbTersedia" class="form-control" value="0">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Satuan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="satuan" id="kbSatuan" class="form-control" value="unit" required>
                        </div>
                    </div>

                    {{-- Live Kalkulasi Kekurangan / Selisih --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: rgba(59,130,246,0.04); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                        <span style="font-size: var(--text-sm); color: var(--text-muted);">
                            <i class="fas fa-calculator text-primary me-1"></i> Kekurangan (Selisih yang dibutuhkan):
                        </span>
                        <span id="labelKekurangan" style="font-size: var(--text-md); font-weight: var(--font-bold); color: var(--danger, #ef4444);">
                            1 unit
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Prioritas <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="prioritas" id="kbPrioritas" class="form-control" required>
                                @foreach ($prioritasList as $k => $v)
                                    <option value="{{ $k }}" {{ $k === 'sedang' ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Sumber Analisis</label>
                            <select name="sumber_analisis" id="kbSumber" class="form-control">
                                @foreach ($sumberList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="kbStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Referensi Mapel</label>
                            <input type="text" name="referensi_mapel" id="kbMapel" class="form-control" placeholder="Contoh: Biologi Terapan">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" id="kbTA" class="form-control" placeholder="2026/2027">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Semester</label>
                            <select name="semester" id="kbSemester" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="1">Ganjil</option>
                                <option value="2">Genap</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Input</label>
                            <input type="date" name="tanggal_input" id="kbTanggal" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jurusan</label>
                            <select name="jurusan_id" id="kbJurusan" class="form-control" {{ $context['unrestricted'] ? '' : 'disabled' }}>
                                @if ($context['unrestricted'])
                                    <option value="">-- Ikut Penugasan --</option>
                                @endif
                                @foreach ($jurusanOptions as $j)
                                    <option value="{{ $j->jurusan_id }}" {{ $context['jurusan_id'] === $j->jurusan_id ? 'selected' : '' }}>
                                        {{ $j->nama_jurusan }}{{ $j->singkatan ? ' (' . $j->singkatan . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (!$context['unrestricted'])
                                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                    <i class="fas fa-lock me-1"></i> Terkunci pada jurusan penugasan Anda.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Analisis / Justifikasi Kebutuhan</label>
                        <textarea name="analisis_kebutuhan" id="kbAnalisis" class="form-control" rows="2" placeholder="Jelaskan dasar perhitungan kebutuhan (jumlah siswa, frekuensi praktik, dsb)..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelKebutuhan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

