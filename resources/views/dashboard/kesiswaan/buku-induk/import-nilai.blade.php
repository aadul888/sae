@extends('layouts.dashboard')

@section('title', 'Pengaturan Buku Induk — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-sliders"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Pengaturan Buku Induk
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Tentukan tahun ajaran serta penandatangan lembar buku induk.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'nilai'])

    {{-- 3. Kartu Pengaturan (tahun ajaran & penandatangan lembar cetak) --}}
    <div class="card" style="padding: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-sliders"></i>
            </div>
            <div>
                <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Pengaturan Buku Induk</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Tahun ajaran &amp; penandatangan lembar cetak. Daftar mapel diatur di tab <strong>Maping Mata Pelajaran</strong>.</div>
            </div>
        </div>

        <form id="formPengaturan" action="{{ route('dashboard.kesiswaan.buku-induk.pengaturan.save') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Tahun Ajaran</label>
                    @php $taTerpilih = (string) ($form['tahun_ajaran'] ?? ''); @endphp
                    <select name="tahun_ajaran" class="bi-input">
                        @if ($taTerpilih !== '' && !in_array($taTerpilih, $tahunAjaranList, true))
                            <option value="{{ $taTerpilih }}" selected>{{ $taTerpilih }}</option>
                        @endif
                        @foreach ($tahunAjaranList as $ta)
                            <option value="{{ $ta }}" {{ $taTerpilih === $ta ? 'selected' : '' }}>
                                {{ $ta }}{{ $ta === \App\Services\BukuIndukNilaiService::tahunAjaranAktif() ? ' (aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 4px;">
                        Tahun ajaran yang dikelola. Tab Input Nilai hanya untuk kelas akhir (XII) pada tahun ajaran ini.
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah" class="bi-input" value="{{ $form['nama_kepala_sekolah'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah" class="bi-input" value="{{ $form['nip_kepala_sekolah'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Petugas Buku Induk</label>
                    <input type="text" name="nama_petugas" class="bi-input" value="{{ $form['nama_petugas'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">NIP Petugas</label>
                    <input type="text" name="nip_petugas" class="bi-input" value="{{ $form['nip_petugas'] ?? '' }}">
                </div>
            </div>

            @if ($can['update'])
                <button type="submit" class="btn btn-primary"
                    style="padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            @endif
        </form>
    </div>
@endsection

@include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
