{{-- Modal Penguncian Pilihan Mode Jadwal (Mingguan / Semester) --}}
<div id="modalLockPilihModeJadwal" class="modal-backdrop"
    style="display: {{ !empty($perluPilihMode) ? 'flex' : 'none' }}; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(6px);">
    <div class="card"
        style="width: 100%; max-width: 620px; padding: 28px; border-radius: var(--radius-xl, 16px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; border-radius: var(--radius-xl, 16px); background: rgba(99,102,241,0.15); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 12px;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-color);">
                Pilih Pemberlakuan Jadwal Praktik
            </h3>
            <p style="margin: 6px 0 0 0; font-size: 0.875rem; color: var(--text-muted);">
                Tentukan mode acuan penjadwalan yang diberlakukan di laboratorium untuk tahun ajaran aktif.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            {{-- Kartu Pilihan 1: Mingguan --}}
            <a href="{{ route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'mingguan']) }}"
                class="card"
                style="padding: 20px 16px; text-decoration: none; border: 2px solid {{ ($modeDiberlakukan ?? 'mingguan') === 'mingguan' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 12px; text-align: center; transition: all 0.2s ease; background: var(--bg-card); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; margin-bottom: 10px;">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div style="font-weight: 800; font-size: 1rem; color: var(--text-color); margin-bottom: 6px;">
                        Jadwal Mingguan
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                        Jadwal ditentukan per minggu dengan pilihan minggu ke- dan tanggal berlaku. Tiap minggu dapat berbeda.
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <span class="btn btn-primary" style="width: 100%; font-size: 0.8125rem; padding: 7px 10px;">
                        Pilih Mingguan
                    </span>
                </div>
            </a>

            {{-- Kartu Pilihan 2: Semester --}}
            <a href="{{ route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'semester']) }}"
                class="card"
                style="padding: 20px 16px; text-decoration: none; border: 2px solid {{ ($modeDiberlakukan ?? '') === 'semester' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 12px; text-align: center; transition: all 0.2s ease; background: var(--bg-card); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; margin-bottom: 10px;">
                        <i class="fas fa-calendar-range"></i>
                    </div>
                    <div style="font-weight: 800; font-size: 1rem; color: var(--text-color); margin-bottom: 6px;">
                        Jadwal Semester
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                        Jadwal berlaku 1 semester penuh (Ganjil/Genap) untuk hari yang ditentukan dalam tahun ajaran.
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <span class="btn btn-primary" style="width: 100%; font-size: 0.8125rem; padding: 7px 10px; background: var(--success, #10b981); border-color: var(--success, #10b981);">
                        Pilih Semester
                    </span>
                </div>
            </a>
        </div>

        @if (!empty($pengaturanJadwal->mode_pemberlakuan) && $pengaturanJadwal->status === 'aktif')
            <div style="text-align: center;">
                <button type="button" class="btn btn-secondary btn-sm" id="btnCloseLockModal" style="font-size: 0.8125rem;">
                    Batal / Tutup
                </button>
            </div>
        @endif
    </div>
</div>
