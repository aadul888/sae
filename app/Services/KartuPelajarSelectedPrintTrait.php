<?php

namespace App\Services;

use App\Models\PesertaDidikMeta;
use App\Support\SemesterHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait KartuPelajarSelectedPrintTrait
{
    /**
     * Dapatkan kartu untuk antrean request cetak yang diceklist / dipilih (lintas rombel atau terfilter).
     */
    public function getSelectedRequestsCardsData(array $filters = []): array
    {
        if (!Schema::hasTable('kartu_pelajar_request')) {
            return ['error' => 'not_found', 'rombel' => null, 'cards' => [], 'sekolah' => []];
        }

        $query = DB::table('kartu_pelajar_request as r')
            ->join('peserta_didik as pd', function ($join) {
                $join->on('r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                    ->orWhere(function ($orJoin) {
                        $orJoin->whereNotNull('r.nisn')
                            ->whereColumn('r.nisn', 'pd.nisn');
                    });
            })
            ->leftJoin('rombongan_belajar as rb', function ($j) {
                $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->orWhereColumn('pd.nama_rombel', 'rb.nama');
            })
            ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id')
            ->select(
                'pd.*',
                'rb.jurusan_id',
                'rb.jurusan_id_str',
                'r.id as request_id',
                'r.status as request_status',
                'r.jenis_request',
                'r.created_at as request_created_at'
            )
            ->distinct();

        // 1. Jika ada ID antrean spesifik yang diceklist
        if (!empty($filters['request_ids']) && is_array($filters['request_ids'])) {
            $reqIds = array_map('intval', $filters['request_ids']);
            $query->whereIn('r.id', $reqIds);
        } elseif (!empty($filters['status_request'])) {
            $query->where('r.status', $filters['status_request']);
        }

        // 2. Filter opsional dari datatable request jika dipilih "Pilih Semua Sesuai Filter"
        if (!empty($filters['filter_rombel'])) {
            $query->where('pd.nama_rombel', $filters['filter_rombel']);
        }
        if (!empty($filters['filter_req_status'])) {
            $query->where('r.status', $filters['filter_req_status']);
        }
        if (!empty($filters['filter_status_bayar'])) {
            if ($filters['filter_status_bayar'] === 'lunas') {
                $query->where('kp.status_pembayaran', 'lunas');
            } elseif ($filters['filter_status_bayar'] === 'belum_bayar') {
                $query->where(function ($sq) {
                    $sq->whereNull('kp.status_pembayaran')
                        ->orWhere('kp.status_pembayaran', 'belum_bayar');
                });
            }
        }
        if (!empty($filters['filter_q'])) {
            $q = $filters['filter_q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                    ->orWhere('r.alasan', 'LIKE', "%{$q}%");
            });
        }

        // 3. Validasi foto wajib ada
        if (!empty($filters['hanya_ada_foto']) && Schema::hasTable('peserta_didik_meta')) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('peserta_didik_meta as pdm')
                    ->whereColumn('pdm.peserta_didik_id', 'pd.peserta_didik_id')
                    ->whereNotNull('pdm.foto_path')
                    ->where('pdm.foto_path', '!=', '');
            });
        }

        $students = $query->orderBy('pd.nama_rombel', 'asc')->orderBy('pd.nama', 'asc')->get();

        $sekolahInfo = $this->getSekolahInfo();
        $cards = [];
        $pdIds = $students->pluck('peserta_didik_id')->all();
        $photos = [];
        if (Schema::hasTable('peserta_didik_meta')) {
            $photos = PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)
                ->get()
                ->keyBy('peserta_didik_id');
        }

        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        foreach ($students as $pd) {
            $photoMeta = $photos[$pd->peserta_didik_id] ?? null;
            $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);
            $jurusanMeta = $this->getJurusanMeta($pd->jurusan_id);

            $cards[] = [
                'pd' => $pd,
                'nisn' => $pd->nisn,
                'nama' => $pd->nama,
                'rombel' => $pd->nama_rombel ?: '-',
                'jurusan' => $jurusanMeta?->nama_jurusan ?: ($pd->jurusan_id_str ?? 'Reguler'),
                'jurusan_logo_url' => $jurusanMeta?->logo_url,
                'foto_url' => $photoMeta?->foto_url,
                'tahun_pelajaran' => 'TP. ' . $semesterLabel,
                'status' => 'AKTIF',
                'qr_code_svg' => QrCodeService::generateSvg($verifyUrl, 100, 0),
                'barcode_svg' => BarcodeService::generateCode128Svg($pd->nisn ?: '0000000000', 36, 2),
                'verify_url' => $verifyUrl,
                'sekolah' => $sekolahInfo,
            ];
        }

        $rombelMock = (object) [
            'nama' => count($cards) > 0 ? ('Antrean Terpilih (' . count($cards) . ' Siswa)') : 'Antrean Cetak',
        ];

        return [
            'rombel' => $rombelMock,
            'cards' => $cards,
            'sekolah' => $sekolahInfo,
            'filter_status_request' => $filters['status_request'] ?? null,
            'filter_hanya_ada_foto' => !empty($filters['hanya_ada_foto']),
            'is_selected_only' => true,
        ];
    }
}
