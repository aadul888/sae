@extends('layouts.dashboard')

@section('title', 'Patroli & Insiden - Keamanan')

@section('content')
<div class="content-wrapper">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                <i class="fas fa-shield-virus" style="color: var(--primary); margin-right: 8px;"></i>
                Log Patroli & Laporan Insiden
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                Pencatatan rute patroli keamanan lingkungan sekolah dan pelaporan insiden/kejadian khusus.
            </p>
        </div>
        @if ($canCreate)
            <div style="display: flex; gap: 10px;">
                @if ($tab === 'patroli')
                    <button type="button" class="btn btn-primary" onclick="openModalPatroli()" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Log Patroli</span>
                    </button>
                @else
                    <button type="button" class="btn btn-danger" onclick="openModalInsiden()" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Laporkan Insiden</span>
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Patroli</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">{{ $stats['total_patroli'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Patroli Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ $stats['patroli_hari_ini'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #ef4444;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Insiden Dicatat</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #ef4444; margin-top: 4px;">{{ $stats['total_insiden'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Insiden Aktif</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #f59e0b; margin-top: 4px;">{{ $stats['insiden_aktif'] }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px;">
        <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'patroli']) }}"
           style="padding: 10px 18px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border-bottom: 2px solid {{ $tab === 'patroli' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'patroli' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
            <i class="fas fa-walking"></i> Log Patroli Keliling
        </a>
        <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'insiden']) }}"
           style="padding: 10px 18px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border-bottom: 2px solid {{ $tab === 'insiden' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'insiden' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
            <i class="fas fa-exclamation-circle"></i> Laporan Insiden & Kejadian
        </a>
    </div>

    @if ($tab === 'patroli')
        {{-- TAB 1: PATROLI --}}
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu Patroli</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rute / Zona</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kondisi Lingkungan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Catatan Temuan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Petugas</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patroliList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px;">
                                <div style="font-weight: 600; color: var(--text-heading);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Pukul {{ substr($item->jam_patroli, 0, 5) }} WIB</div>
                            </td>
                            <td style="padding: 12px 18px;">
                                <span style="font-weight: 600; color: var(--text-heading);">{{ $item->rute_zona }}</span>
                            </td>
                            <td style="padding: 12px 18px;">
                                @if ($item->kondisi_lingkungan === 'aman_terkendali')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Aman & Terkendali</span>
                                @elseif ($item->kondisi_lingkungan === 'mencurigakan')
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Mencurigakan</span>
                                @else
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">{{ ucwords(str_replace('_', ' ', $item->kondisi_lingkungan)) }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted); max-width: 250px;">
                                {{ $item->catatan_temuan ?: '-' }}
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-heading);">
                                {{ $item->nama_petugas ?: 'Satpam' }}
                            </td>
                            <td style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon" title="Edit" onclick="editPatroli({{ json_encode($item) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.keamanan.patroli.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="Patroli {{ $item->rute_zona }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                <i class="fas fa-walking" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                                <p style="margin: 0;">Belum ada log patroli.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- TAB 2: INSIDEN --}}
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No Laporan & Waktu</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Insiden & Lokasi</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Urgensi</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kronologi / Tindakan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Penyelesaian</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($insidenList as $ins)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px;">
                                <div style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: 0.85rem;">{{ $ins->nomor_laporan }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    {{ \Carbon\Carbon::parse($ins->tanggal)->translatedFormat('d M Y') }} &bull; {{ substr($ins->jam_kejadian, 0, 5) }}
                                </div>
                            </td>
                            <td style="padding: 12px 18px;">
                                <div style="font-weight: 600; color: var(--text-heading);">{{ $ins->judul_insiden }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-map-marker-alt"></i> {{ $ins->lokasi_kejadian }}</div>
                            </td>
                            <td style="padding: 12px 18px;">
                                @if ($ins->tingkat_urgensi === 'darurat')
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4);">Darurat</span>
                                @elseif ($ins->tingkat_urgensi === 'tinggi')
                                    <span class="badge" style="background: rgba(249, 115, 22, 0.2); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.4);">Tinggi</span>
                                @elseif ($ins->tingkat_urgensi === 'sedang')
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4);">Sedang</span>
                                @else
                                    <span class="badge" style="background: rgba(107, 114, 128, 0.2); color: #6b7280;">Rendah</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.82rem; max-width: 250px;">
                                <div style="color: var(--text-heading); white-space: normal;">{{ Str::limit($ins->kronologi, 80) }}</div>
                                @if ($ins->tindakan_diambil)
                                    <div style="color: var(--text-muted); margin-top: 2px;"><em>Tindakan: {{ Str::limit($ins->tindakan_diambil, 60) }}</em></div>
                                @endif
                            </td>
                            <td style="padding: 12px 18px;">
                                @if ($ins->status_penyelesaian === 'selesai')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Selesai</span>
                                @else
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Dalam Penanganan</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon" title="Edit" onclick="editInsiden({{ json_encode($ins) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.keamanan.insiden.destroy', $ins->id) }}" method="POST" data-confirm="delete" data-name="Insiden {{ $ins->nomor_laporan }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 8px; color: #10b981; opacity: 0.6;"></i>
                                <p style="margin: 0;">Tidak ada laporan insiden.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Modal Log Patroli --}}
<div id="modalPatroli" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 550px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalPatroliTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Tambah Log Patroli</h3>
            <button type="button" onclick="closeModalPatroli()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formPatroli" method="POST" action="{{ route('dashboard.keamanan.patroli.store') }}"
              data-store-url="{{ route('dashboard.keamanan.patroli.store') }}"
              data-update-url="{{ url('/dashboard/keamanan/patroli') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="patroliMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="patroli_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Jam Patroli <span style="color:red;">*</span></label>
                    <input type="time" name="jam_patroli" id="patroli_jam" class="form-control" value="{{ date('H:i') }}" required>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Rute / Zona Patroli <span style="color:red;">*</span></label>
                <input type="text" name="rute_zona" id="patroli_rute" class="form-control" placeholder="Contoh: Gerbang Utama, Gedung A-B, Kantin & Parkir Belakang" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Kondisi Lingkungan <span style="color:red;">*</span></label>
                <select name="kondisi_lingkungan" id="patroli_kondisi" class="form-control" required>
                    <option value="aman_terkendali">Aman & Terkendali</option>
                    <option value="pintu_terbuka">Pintu / Jendela Ditemukan Terbuka</option>
                    <option value="lampu_mati">Lampu Padam / Rusak</option>
                    <option value="mencurigakan">Aktivitas Mencurigakan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Catatan Temuan</label>
                <textarea name="catatan_temuan" id="patroli_catatan" class="form-control" rows="3" placeholder="Catatan detail temuan saat patroli..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalPatroli()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Laporan Insiden --}}
<div id="modalInsiden" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 650px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalInsidenTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Laporkan Insiden Keamanan</h3>
            <button type="button" onclick="closeModalInsiden()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formInsiden" method="POST" action="{{ route('dashboard.keamanan.insiden.store') }}"
              data-store-url="{{ route('dashboard.keamanan.insiden.store') }}"
              data-update-url="{{ url('/dashboard/keamanan/insiden') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="insidenMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal Kejadian <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="insiden_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Jam Kejadian <span style="color:red;">*</span></label>
                    <input type="time" name="jam_kejadian" id="insiden_jam" class="form-control" value="{{ date('H:i') }}" required>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Judul Insiden <span style="color:red;">*</span></label>
                <input type="text" name="judul_insiden" id="insiden_judul" class="form-control" placeholder="Contoh: Kehilangan helm di parkir barat" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Lokasi Kejadian <span style="color:red;">*</span></label>
                    <input type="text" name="lokasi_kejadian" id="insiden_lokasi" class="form-control" placeholder="Area parkir / Lab / Kantin" required>
                </div>
                <div>
                    <label class="form-label">Tingkat Urgensi</label>
                    <select name="tingkat_urgensi" id="insiden_urgensi" class="form-control">
                        <option value="rendah">Rendah</option>
                        <option value="sedang" selected>Sedang</option>
                        <option value="tinggi">Tinggi</option>
                        <option value="darurat">Darurat</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Pihak Terlibat / Saksi</label>
                <input type="text" name="pihak_terlibat" id="insiden_pihak" class="form-control" placeholder="Nama siswa / staf / warga yang terlibat atau menyaksikan">
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Kronologi Kejadian <span style="color:red;">*</span></label>
                <textarea name="kronologi" id="insiden_kronologi" class="form-control" rows="3" placeholder="Jelaskan alur peristiwa secara runtut..." required></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Tindakan Diambil</label>
                <textarea name="tindakan_diambil" id="insiden_tindakan" class="form-control" rows="2" placeholder="Tindakan awal petugas satpam / sekolah..."></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Status Penyelesaian</label>
                <select name="status_penyelesaian" id="insiden_status" class="form-control">
                    <option value="dalam_penanganan">Dalam Penanganan</option>
                    <option value="selesai">Selesai</option>
                    <option value="diserahkan_ke_polsek">Diserahkan ke Pihak Berwajib</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalInsiden()">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Simpan Laporan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/keamanan-patroli.js') }}"></script>
@endpush
