    <div class="presensi-tab-pane" id="tab-izin" style="display: {{ $activeTab === 'izin' ? 'block' : 'none' }};">
        <div class="card" style="padding: 18px 20px; border-radius: var(--radius-xl, 16px); margin-bottom: 20px;">
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-envelope-open-text text-primary me-2"></i> Pengajuan Izin / Sakit Peserta Didik
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0;">
                Daftar permohonan surat izin atau sakit yang diajukan oleh peserta didik untuk diverifikasi oleh Wali Kelas
                atau Administrator.
            </p>
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-izin" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tanggal Pengajuan</th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Nama Peserta Didik</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Jenis</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Rentang Waktu</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Alasan / Keterangan</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Lampiran</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Status</th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 130px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($izinPending as $iz)
                        @php
                            $siswaIzin = \DB::table('peserta_didik')
                                ->where('peserta_didik_id', $iz->peserta_didik_id)
                                ->first();
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-izin-tgl"
                                style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                data-label="Tanggal Pengajuan">
                                {{ $iz->created_at->translatedFormat('d M Y, H:i') }} WIB
                            </td>
                            <td class="cell-izin-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                <div class="izin-siswa-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    <div class="izin-avatar"
                                        style="width: 38px; height: 38px; border-radius: 50%; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: var(--text-base, 0.875rem); border: 1px solid var(--border-color); flex-shrink: 0;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            {{ $siswaIzin ? $siswaIzin->nama : 'Peserta Didik' }}</div>
                                        <div
                                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                            NISN: {{ $iz->nisn }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-izin-jenis" style="padding: 14px 16px;" data-label="Jenis Izin">
                                <span class="badge {{ $iz->jenis === 'sakit' ? 'badge-purple' : 'badge-primary' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $iz->jenis_label }}
                                </span>
                            </td>
                            <td class="cell-izin-rentang" style="padding: 14px 16px;" data-label="Rentang Waktu">
                                <div style="font-weight: 600; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($iz->tanggal_mulai)->format('d/m/Y') }} s/d
                                    {{ \Carbon\Carbon::parse($iz->tanggal_selesai)->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="cell-izin-alasan" style="padding: 14px 16px;" data-label="Alasan / Keterangan">
                                <div style="max-width: 250px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.35;"
                                    title="{{ $iz->alasan }}">
                                    {{ $iz->alasan }}
                                </div>
                            </td>
                            <td class="cell-izin-lampiran" style="padding: 14px 16px;" data-label="Lampiran Dokumen">
                                @if ($iz->lampiran_url)
                                    <a href="{{ $iz->lampiran_url }}" target="_blank" class="btn btn-outline"
                                        style="padding: 4px 8px; font-size: var(--text-xs, 0.75rem);">
                                        <i class="fas fa-paperclip me-1"></i> Buka Surat
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">Tanpa Lampiran</span>
                                @endif
                            </td>
                            <td class="cell-izin-status" style="padding: 14px 16px;" data-label="Status">
                                {!! $iz->status_badge !!}</td>
                            <td class="cell-izin-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="izin-actions" style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn btn-primary btn-verif-izin"
                                        data-id="{{ $iz->id }}"
                                        data-nama="{{ $siswaIzin ? $siswaIzin->nama : 'Peserta Didik' }}"
                                        data-jenis="{{ $iz->jenis_label }}"
                                        data-rentang="{{ \Carbon\Carbon::parse($iz->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($iz->tanggal_selesai)->format('d/m/Y') }}"
                                        data-alasan="{{ $iz->alasan }}"
                                        style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 600;">
                                        Verifikasi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-envelope-circle-check"
                                    style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                Tidak ada pengajuan izin atau sakit yang menunggu verifikasi saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 5: Pengaturan Jam & Jadwal -->
