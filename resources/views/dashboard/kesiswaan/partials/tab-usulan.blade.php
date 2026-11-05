            <!-- TAB 4: USULAN PERUBAHAN DATA SISWA -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Siswa</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rombel</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Usulan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Berkas</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usulanList as $item)
                            @php
                                $usulanPdId = $item->peserta_didik_id ?: $item->siswa?->peserta_didik_id ?? '';
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-pd-nama"
                                    style="padding: 12px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Siswa">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_url))
                                            <div class="pd-foto-thumb" data-biodata-id="{{ $usulanPdId }}"
                                                role="button" tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                                style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->siswa?->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty" data-biodata-id="{{ $usulanPdId }}"
                                                role="button" tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                                style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                                <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama" data-biodata-id="{{ $usulanPdId }}"
                                                    role="button" tabindex="0"
                                                    title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                                    style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}</span>
                                            </div>
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                @if ($item->siswa?->nisn)
                                                    <span class="copyable" data-copy="{{ $item->siswa->nisn }}"
                                                        data-label="NISN" title="Klik untuk salin NISN">
                                                        {{ $item->siswa->nisn }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-pd-rombel"
                                    style="padding: 12px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Rombel">
                                    <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                                        {{ $item->siswa?->nama_rombel ?: '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 18px;" data-label="Usulan">
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="badge badge-primary"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                {{ $item->total_usulan }} Data
                                            </span>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 260px;">
                                            @foreach (array_slice($item->kolom_list ?? [], 0, 3) as $kItem)
                                                <span class="badge badge-outline"
                                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                                    {{ $kItem['label'] }}
                                                </span>
                                            @endforeach
                                            @if (count($item->kolom_list ?? []) > 3)
                                                <span class="badge badge-outline"
                                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; opacity: 0.7;">
                                                    +{{ count($item->kolom_list) - 3 }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 18px; text-align: center;" data-label="Berkas">
                                    <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center;">
                                        <span
                                            class="badge {{ ($item->kk_status ?? '') === 'valid' ? 'badge-success' : 'badge-outline' }}"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;"
                                            title="Status KK: {{ ($item->kk_status ?? '') === 'valid' ? 'Valid' : 'Belum Valid' }}">
                                            KK
                                        </span>
                                        <span
                                            class="badge {{ ($item->ijazah_status ?? '') === 'valid' ? 'badge-success' : 'badge-outline' }}"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;"
                                            title="Status Ijazah: {{ ($item->ijazah_status ?? '') === 'valid' ? 'Valid' : 'Belum Valid' }}">
                                            Ijazah
                                        </span>
                                        @if (!empty($item->berkas_bukti_path))
                                            <button type="button" class="btn-icon btn-preview-pdf-inline"
                                                data-url="{{ asset('storage/' . ltrim($item->berkas_bukti_path, '/')) }}"
                                                data-title="Bukti Usulan - {{ $item->siswa?->nama }}"
                                                title="Lihat Bukti Usulan (PDF)"
                                                style="width: 26px; height: 26px; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; justify-content: center; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #ef4444; cursor: pointer;">
                                                <i class="fas fa-file-pdf" style="font-size: 0.75rem;"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;" data-label="Status">
                                    @php
                                        $bColor = match ($item->status) {
                                            'disetujui' => 'badge-success',
                                            'sudah_ke_dapodik' => 'badge-primary',
                                            'ditolak' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                        $bLabel = match ($item->status) {
                                            'disetujui' => 'Disetujui (' . ($item->disetujui_count ?? 1) . ')',
                                            'sudah_ke_dapodik' => 'Dapodik',
                                            'ditolak' => 'Ditolak',
                                            default => 'Menunggu (' . ($item->menunggu_count ?? 1) . ')',
                                        };
                                    @endphp
                                    <span class="badge {{ $bColor }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">{{ $bLabel }}</span>
                                </td>
                                <td class="cell-pd-aksi" style="padding: 12px 18px; text-align: right;"
                                    data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <button type="button" class="btn-icon btn-kelola-usulan-modal"
                                            data-id="{{ $usulanPdId }}" data-nama="{{ $item->siswa?->nama }}"
                                            title="Formulir Usulan Perubahan"
                                            style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.12); color: var(--primary); border: 1px solid rgba(99,102,241,0.25); cursor: pointer;">
                                            <i class="fas fa-file-pen"></i>
                                        </button>
                                        @if ($item->disetujui_count > 0 && $canUpdate)
                                            <button type="button" class="btn-icon btn-mark-dapodik"
                                                data-id="{{ $usulanPdId }}" data-nama="{{ $item->siswa?->nama }}"
                                                title="Tandai Selesai di Dapodik"
                                                style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.12); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.25); cursor: pointer;">
                                                <i class="fas fa-cloud-arrow-up"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('dashboard.identitas.index', ['peserta_didik_id' => $usulanPdId]) }}"
                                            target="_blank" class="btn-icon" title="Lihat Formulir Lengkap Siswa"
                                            style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(6,182,212,0.12); color: var(--accent); border: 1px solid rgba(6,182,212,0.25);">
                                            <i class="fas fa-id-card"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-file-circle-question mb-2"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada usulan perubahan data siswa.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($usulanList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $usulanList])
                </div>
            @endif
