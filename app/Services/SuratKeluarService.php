<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Persuratan;
use App\Models\SuratKeteranganPd;
use App\Models\PersuratanTemplate;


use App\Support\Table;

class SuratKeluarService
{
    /**
     * Sinkronisasi data SPT ke tabel persuratan agar terkoordinir penuh di bawah satu register surat keluar
     */
    public static function syncSptToPersuratan(): void
    {
        if (!Table::exists('gtk_spt')) {
            return;
        }

        $sptItems = DB::table('gtk_spt')->get();
        foreach ($sptItems as $sptRow) {
            if (!Persuratan::where('nomor_surat', $sptRow->nomor_spt)->exists()) {
                Persuratan::create([
                    'nomor_surat'     => $sptRow->nomor_spt,
                    'kode_indeks'     => '800',
                    'jenis_surat'     => 'keluar',
                    'perihal'         => "Surat Perintah Tugas (SPT): {$sptRow->nama_kegiatan}",
                    'pengirim_asal'   => $sptRow->pejabat_nama ?? 'Kepala Sekolah',
                    'tujuan_penerima' => $sptRow->lokasi_tujuan,
                    'tanggal_surat'   => $sptRow->tanggal_berangkat,
                    'status'          => 'selesai',
                    'keterangan'      => "SPT Kegiatan {$sptRow->nama_kegiatan}. Dasar: {$sptRow->dasar_penugasan}. Beban: {$sptRow->beban_anggaran}.",
                    'created_by'      => $sptRow->created_by ?: 'Petugas Persuratan',
                    'created_at'      => $sptRow->created_at ?: now(),
                    'updated_at'      => $sptRow->updated_at ?: now(),
                ]);
            }
        }
    }

    /**
     * Hitung statistik agregat dan nomor surat terakhir yang terkoordinasi
     */
    public static function buildStats(): array
    {
        $curMonth = date('Y-m');
        $nomorTerakhirKeluar = Persuratan::where('jenis_surat', 'keluar')->orderByDesc('id')->value('nomor_surat');
        if (!$nomorTerakhirKeluar) {
            $settingRow = DB::table('persuratan_settings')->first();
            $nomorTerakhirKeluar = $settingRow?->nomor_terakhir_surat_keluar ? PersuratanHddService::generateNomorSuratKeluar('KPG.11.01') : '-';
        }

        $latestKet = SuratKeteranganPd::orderByDesc('id')->value('nomor_surat') ?: '-';
        $latestSpt = (Table::exists('gtk_spt') ? DB::table('gtk_spt')->orderByDesc('id')->value('nomor_spt') : null) ?: '-';
        $latestTmpl = (Table::exists('persuratan_templates') ? DB::table('persuratan_templates')->orderByDesc('id')->value('nomor_surat') : null) ?: '-';

        return [
            'total'                     => Persuratan::where('jenis_surat', 'keluar')->count(),
            'surat_keterangan'          => SuratKeteranganPd::count(),
            'spt'                       => Table::exists('gtk_spt') ? DB::table('gtk_spt')->count() : 0,
            'template'                  => Table::exists('persuratan_templates') ? DB::table('persuratan_templates')->count() : 0,
            'ekspedisi'                 => Persuratan::where('jenis_surat', 'keluar')->count(),
            'bulan_ini'                 => Persuratan::where('jenis_surat', 'keluar')->where('tanggal_surat', 'like', "{$curMonth}%")->count(),
            'selesai'                   => Persuratan::where('jenis_surat', 'keluar')->where('status', 'selesai')->count(),
            'nomor_terakhir'            => $nomorTerakhirKeluar ?: '-',
            'nomor_terakhir_keterangan' => $latestKet,
            'nomor_terakhir_spt'        => $latestSpt,
            'nomor_terakhir_template'   => $latestTmpl,
        ];
    }

    /**
     * Susun seluruh data untuk view index surat keluar
     */
    public static function buildIndexData(Request $request): array
    {
        self::syncSptToPersuratan();
        $stats = self::buildStats();

        $q          = trim($request->get('q', ''));
        $rawTab     = $request->get('tab', 'keluar');
        $tab        = match ($rawTab) {
            'template', 'autocrat' => 'template',
            'ekspedisi'            => 'ekspedisi',
            default                => 'keluar',
        };

        $status     = $request->get('status', '');
        $kodeIndeks = $request->get('kode_indeks', '');
        $sort       = $request->get('sort', 'tanggal_surat');
        $sortDir    = strtolower($request->get('sort_dir', $request->get('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';
        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage    = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

        if ($tab === 'template') {
            $tmplQuery = DB::table('persuratan_templates');
            if ($q !== '') {
                $tmplQuery->where(function ($b) use ($q) {
                    $b->where('nomor_surat', 'like', "%{$q}%")
                      ->orWhere('perihal', 'like', "%{$q}%")
                      ->orWhere('tujuan_penerima', 'like', "%{$q}%")
                      ->orWhere('template_type', 'like', "%{$q}%");
                });
            }
            $items = $tmplQuery->orderBy('tanggal_surat', 'desc')->orderByDesc('id')->paginate($perPage)->withQueryString();
        } else {
            $query = Persuratan::where('jenis_surat', 'keluar')
                ->with(['suratKeterangan.siswa', 'sarprasAset', 'spt']);

            if ($q !== '') {
                $query->where(function ($b) use ($q) {
                    $b->where('nomor_surat', 'like', "%{$q}%")
                      ->orWhere('perihal', 'like', "%{$q}%")
                      ->orWhere('tujuan_penerima', 'like', "%{$q}%")
                      ->orWhere('keterangan', 'like', "%{$q}%")
                      ->orWhere('kode_indeks', 'like', "%{$q}%");
                });
            }

            if ($status !== '') {
                $query->where('status', $status);
            }

            if ($kodeIndeks !== '') {
                $query->where('kode_indeks', $kodeIndeks);
            }

            $allowedSorts = ['nomor_surat', 'tanggal_surat', 'status', 'created_at', 'tujuan_penerima'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $sortDir);
            } else {
                $query->orderBy('tanggal_surat', 'desc');
            }

            $items = $query->paginate($perPage)->withQueryString();
        }

        $daftarAset = DB::table('sarpras_aset')
            ->orderBy('nama_barang')
            ->get(['id', 'kode_aset', 'nama_barang', 'merk_tipe', 'kondisi', 'kategori']);

        $indeksList = DB::table('ref_indeks_surat')->where('is_active', true)->orderBy('kode')->get();
        $hddStatus = PersuratanHddService::checkStatus();

        $suggestedNumber = PersuratanHddService::generateNomorSuratKeluar('KPG.11.01');
        $suggestedKetNumber = PersuratanHddService::generateNomorSuratKeterangan('KS.02.23');
        $suggestedTemplateNumber = PersuratanHddService::generateNomorSuratKeluar('HM.01.02');
        $templatePresets = PersuratanTemplate::getTemplatePresets();

        return compact(
            'stats',
            'items',
            'daftarAset',
            'q',
            'tab',
            'status',
            'kodeIndeks',
            'sort',
            'sortDir',
            'perPage',
            'indeksList',
            'hddStatus',
            'suggestedNumber',
            'suggestedKetNumber',
            'suggestedTemplateNumber',
            'templatePresets'
        );
    }

    /**
     * Simpan Surat Keluar Umum Baru ke Database dan Harddisk
     */
    public static function store(Request $request, $user): Persuratan
    {
        $nomorSurat = trim($request->nomor_surat);
        $isAutoNumber = empty($nomorSurat);

        if ($isAutoNumber) {
            $nomorSurat = PersuratanHddService::generateNomorSuratKeluar($request->kode_indeks);
        }

        $filePath = null;
        $fileSize = null;
        $fileNameOriginal = null;

        if ($request->hasFile('file_arsip')) {
            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'keluar');
            $filePath = $uploaded['relative_path'];
            $fileSize = $uploaded['file_size'];
            $fileNameOriginal = $uploaded['file_name_original'];
        }

        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Petugas')) : ($user->nama ?? ($user->name ?? 'Petugas'));

        $persuratan = Persuratan::create([
            'nomor_surat'        => $nomorSurat,
            'kode_indeks'        => $request->kode_indeks,
            'jenis_surat'        => 'keluar',
            'perihal'            => trim($request->perihal),
            'pengirim_asal'      => 'SMK SAE',
            'tujuan_penerima'    => trim($request->tujuan_penerima),
            'tanggal_surat'      => $request->tanggal_surat,
            'tanggal_diterima'   => null,
            'sarpras_aset_id'    => $request->sarpras_aset_id ?: null,
            'status'             => $request->status ?: 'selesai',
            'file_path'          => $filePath,
            'file_size'          => $fileSize,
            'file_name_original' => $fileNameOriginal,
            'keterangan'         => $request->keterangan ? trim($request->keterangan) : null,
            'created_by'         => $userName,
        ]);

        if ($isAutoNumber) {
            PersuratanHddService::incrementNomorCounter('keluar');
        }

        return $persuratan;
    }

    /**
     * Update Surat Keluar
     */
    public static function update(Request $request, Persuratan $surat): void
    {
        $data = [
            'nomor_surat'     => trim($request->nomor_surat),
            'kode_indeks'     => $request->kode_indeks,
            'perihal'         => trim($request->perihal),
            'tujuan_penerima' => trim($request->tujuan_penerima),
            'tanggal_surat'   => $request->tanggal_surat,
            'sarpras_aset_id' => $request->sarpras_aset_id ?: null,
            'status'          => $request->status ?: $surat->status,
            'keterangan'      => $request->keterangan ? trim($request->keterangan) : null,
        ];

        if ($request->hasFile('file_arsip')) {
            if ($surat->file_path) {
                PersuratanHddService::deleteFile($surat->file_path);
            }

            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'keluar');
            $data['file_path'] = $uploaded['relative_path'];
            $data['file_size'] = $uploaded['file_size'];
            $data['file_name_original'] = $uploaded['file_name_original'];
        }

        $surat->update($data);
    }

    /**
     * Hapus Surat Keluar beserta seluruh data turunan (SKET, SPT, Template)
     */
    public static function destroy(Persuratan $surat): void
    {
        if ($surat->file_path) {
            PersuratanHddService::deleteFile($surat->file_path);
        }

        SuratKeteranganPd::where('nomor_surat', $surat->nomor_surat)->delete();
        if (Table::exists('gtk_spt')) {
            DB::table('gtk_spt')->where('nomor_spt', $surat->nomor_surat)->delete();
        }
        if (Table::exists('persuratan_templates')) {
            PersuratanTemplate::where('nomor_surat', $surat->nomor_surat)->delete();
        }

        $surat->delete();
    }
}
