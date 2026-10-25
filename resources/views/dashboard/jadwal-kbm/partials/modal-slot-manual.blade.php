    <!-- Modal Form Input / Edit Slot Jadwal Manual (z-index 99999 !important) -->
    <div id="modalSlotManual" class="sae-modal"
        data-route-store="{{ route('dashboard.jadwal-kbm.manual.store-slot') }}"
        data-route-update="{{ url('/dashboard/master-data/jadwal-kbm/manual/update-slot') }}"
        data-route-delete="{{ url('/dashboard/master-data/jadwal-kbm/manual/delete-slot') }}"
        data-route-clear="{{ route('dashboard.jadwal-kbm.manual.clear-rombel') }}"
        data-route-copy="{{ route('dashboard.jadwal-kbm.manual.copy-from-otomatis') }}"
        data-route-conflict="{{ route('dashboard.jadwal-kbm.check-conflict') }}"
        data-slots="{{ json_encode($timeSlotsByDay) }}" data-routines="{{ json_encode($routinesMap) }}"
        data-rombel-id="{{ $activeRombel ? $activeRombel->rombongan_belajar_id : '' }}"
        data-rombel-name="{{ $activeRombel ? $activeRombel->nama : '' }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: var(--radius-lg, 12px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalSlotTitle"
                    style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-calendar-plus text-primary me-2"></i> Jadwalkan Pelajaran
                </h3>
                <button type="button" id="btnTutupModalSlot"
                    style="background: transparent; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSlotManual">
                @csrf
                <input type="hidden" id="slotId" name="slot_id" value="">
                <input type="hidden" id="slotRombelId" name="rombongan_belajar_id"
                    value="{{ $activeRombel ? $activeRombel->rombongan_belajar_id : '' }}">

                <!-- Pilih Mata Pelajaran & Guru -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="slotPembelajaranId"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Mata Pelajaran &amp; Guru Pengampu <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="slotPembelajaranId" name="pembelajaran_id" required class="form-control"
                        style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @if ($activeRombel)
                            @foreach ($pembelajaranData as $p)
                                @php
                                    $isMine = !empty($currentPtkId) && $p->ptk_id === $currentPtkId;
                                @endphp
                                <option value="{{ $p->pembelajaran_id }}" data-ptk-id="{{ $p->ptk_id }}"
                                    data-target-jp="{{ $p->target_jp }}" data-remaining-jp="{{ $p->remaining_jp }}">
                                    {{ $isMine ? '★ [Mapel Saya] ' : '' }}{{ $p->nama_mata_pelajaran }} —
                                    {{ $p->nama_guru ?: 'Tanpa Guru' }} (Sisa:
                                    {{ $p->remaining_jp }} JP)
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Pilihan Hari & Ruangan -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="slotHari"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Hari <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotHari" name="hari" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            @foreach ($hariAktif as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slotRuangan"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Ruangan / Lab
                        </label>
                        <input type="text" id="slotRuangan" name="ruangan" placeholder="Contoh: R. Kelas / Lab 1"
                            value="{{ $activeRombel ? $activeRombel->nama : '' }}" class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                    </div>
                </div>

                <!-- Pilihan Jam Ke (Mulai & Selesai) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="slotJamKeMulai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Jam Ke-Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotJamKeMulai" name="jam_ke_mulai" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            @for ($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slotJamKeSelesai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Jam Ke-Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotJamKeSelesai" name="jam_ke_selesai" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            @for ($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Durasi Info -->
                <div id="slotDurasiInfo"
                    style="padding: 8px 12px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); font-size: var(--text-sm, 0.8125rem); color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Alokasi Jam: <strong id="slotDurasiJp">1 JP</strong></span>
                    <span id="slotDurasiWaktu"></span>
                </div>

                <!-- Alert Live Anti-Bentrok -->
                <div id="conflictAlertBoxManual"
                    style="display: none; padding: 10px 14px; border-radius: var(--radius-md, 8px); margin-bottom: 16px; font-size: var(--text-sm, 0.8125rem);">
                </div>

                <!-- Opsi Timpa / Ganti Jadwal Kelas Jika Sudah Terisi -->
                <div id="boxOverwriteSlot"
                    style="margin-bottom: 16px; padding: 10px 14px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: var(--radius-md, 8px); display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="chkOverwriteSlot" name="overwrite" value="1"
                        style="width: 17px; height: 17px; cursor: pointer; accent-color: #f59e0b;">
                    <label for="chkOverwriteSlot"
                        style="margin: 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; line-height: 1.4;">
                        <strong>Ganti / Timpa jadwal kelas ini</strong> jika jam yang dipilih telah terisi oleh pelajaran
                        lain.
                    </label>
                </div>

                <!-- Keterangan Opsional -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="slotKeterangan"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Keterangan Tambahan (Opsional)
                    </label>
                    <input type="text" id="slotKeterangan" name="keterangan"
                        placeholder="Contoh: Praktik Bengkel / Teori" class="form-control"
                        style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalSlot" class="btn btn-outline"
                        style="padding: 8px 16px;">Batal</button>
                    <button type="submit" id="btnSimpanSlot" class="btn btn-primary" style="padding: 8px 20px;">
                        <i class="fas fa-save me-1"></i> Simpan Slot
                    </button>
                </div>
            </form>
        </div>
    </div>
