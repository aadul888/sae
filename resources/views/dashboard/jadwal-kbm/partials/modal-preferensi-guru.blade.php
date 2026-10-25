    <!-- Modal Preferensi & Ketersediaan Guru (Off-Days) -->
    <div id="modalPreferensiGuru" class="sae-modal" data-route-get="{{ route('dashboard.jadwal-kbm.guru-preferensi') }}"
        data-route-save="{{ route('dashboard.jadwal-kbm.simpan-guru-preferensi') }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: var(--radius-lg, 12px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-user-clock text-primary me-2"></i> Preferensi Ketersediaan Guru
                </h3>
                <button type="button" id="btnTutupModalPref"
                    style="background: transparent; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPreferensiGuru">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prefPtkId"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Pilih Guru (PTK) <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="prefPtkId" name="ptk_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                        required>
                        <option value="">-- Pilih Guru --</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} ({{ $g->nip ?: 'Non-NIP' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="prefLoadingIndicator"
                    style="display: none; padding: 12px; text-align: center; color: var(--primary); font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-spinner fa-spin me-1"></i> Memuat preferensi guru...
                </div>

                <div id="prefFormBody">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Hari Libur / Tidak Bersedia Mengajar (Off-Days)
                        </label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                                <label
                                    style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); cursor: pointer; background: var(--bg-hover); padding: 6px 12px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color);">
                                    <input type="checkbox" name="hari_off[]" value="{{ $dh }}"
                                        class="pref-hari-off">
                                    {{ $dh }}
                                </label>
                            @endforeach
                        </div>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                            Centang hari di mana guru berhalangan mengajar (misal mengajar di sekolah/kampus lain).
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="prefMaxJp"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Batas Maksimal JP per Hari (Opsional)
                        </label>
                        <input type="number" id="prefMaxJp" name="max_jp_per_hari" min="1" max="16"
                            placeholder="Kosongkan jika tidak ada batas" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);">
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Membatasi jam mengajar per hari
                            agar
                            tidak kelelahan (contoh: maks 6 JP/hari).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="prefKeterangan"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Catatan / Alasan Khusus
                        </label>
                        <textarea id="prefKeterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Mengajar di Universitas X hari Senin & Rabu" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"></textarea>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalPref" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: var(--text-base, 0.875rem);">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanPref" class="btn btn-primary"
                        style="padding: 9px 20px; font-size: var(--text-base, 0.875rem);">
                        <i class="fas fa-save me-1"></i> Simpan Preferensi
                    </button>
                </div>
            </form>
        </div>
    </div>
