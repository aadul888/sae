<?php

namespace App\Services;

use App\Models\JurusanMeta;
use App\Models\PesertaDidikMeta;
use App\Models\SekolahMeta;
use App\Support\SemesterHelper;
use Illuminate\Support\Facades\DB;
use App\Support\Table;

class KartuPelajarPrintService
{
    use KartuPelajarSelectedPrintTrait;

    /**
     * Dapatkan informasi dan logo sekolah.
     */
    public function getSekolahInfo(): array
    {
        $sekolah = null;
        if (Table::exists('sekolah')) {
            $sekolah = DB::table('sekolah')->first();
        }

        $sekolahMeta = null;
        if (Table::exists('sekolah_meta')) {
            $sekolahMeta = SekolahMeta::first();
        }

        $alamatLengkap = collect([
            $sekolah?->alamat_jalan,
            $sekolah?->desa_kelurahan,
            $sekolah?->kecamatan,
            $sekolah?->kabupaten_kota,
        ])->filter()->implode(', ');

        return [
            'nama' => $sekolah?->nama ?: 'SMK NEGERI 1 PAGELARAN',
            'npsn' => $sekolah?->npsn ?: '20252031',
            'alamat' => $alamatLengkap ?: 'Jl. Raya Pasirpari, Sindangkerta, Kec. Pagelaran, Kab. Cianjur',
            'telepon' => $sekolah?->nomor_telepon ?: '-',
            'email' => $sekolah?->email ?: 'info@sekolah.sch.id',
            'website' => $sekolah?->website ?: 'https://smkn1pagelaran.sch.id',
            'logo_url' => $sekolahMeta?->logo_url ?: asset('img/logo-icon.png'),
            'kop_url' => $sekolahMeta?->kop_url,
        ];
    }

    /**
     * Dapatkan metadata jurusan.
     */
    public function getJurusanMeta(?string $jurusanId): ?JurusanMeta
    {
        if (empty($jurusanId) || !Table::exists('jurusan_meta')) {
            return null;
        }

        $meta = JurusanMeta::where('jurusan_id', $jurusanId)->first();
        if (!$meta) {
            $meta = JurusanMeta::where('jurusan_id', 'LIKE', substr($jurusanId, 0, 5) . '%')->first();
        }

        return $meta;
    }

    /**
     * Dapatkan data lengkap satu peserta didik untuk kartu pelajar.
     */
    public function loadStudentCardData(string $nisn): ?array
    {
        if (!Table::exists('peserta_didik')) {
            return null;
        }

        $pd = DB::table('peserta_didik')
            ->where('nisn', $nisn)
            ->first();

        if (!$pd) {
            return null;
        }

        $rombel = null;
        if (!empty($pd->rombongan_belajar_id)) {
            $rombel = DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $pd->rombongan_belajar_id)
                ->first();
        }
        if (!$rombel && !empty($pd->nama_rombel)) {
            $rombel = DB::table('rombongan_belajar')
                ->where('nama', $pd->nama_rombel)
                ->first();
        }

        $jurusanId = $rombel?->jurusan_id;
        $jurusanMeta = $this->getJurusanMeta($jurusanId);

        $fotoUrl = null;
        if (Table::exists('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
            $fotoUrl = $meta?->foto_url;
        }

        $sekolahInfo = $this->getSekolahInfo();
        $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);
        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        return [
            'pd' => $pd,
            'nisn' => $pd->nisn,
            'nipd' => $pd->nipd ?? '-',
            'nama' => $pd->nama,
            'rombel' => $pd->nama_rombel ?: ($rombel?->nama ?? '-'),
            'jurusan' => $jurusanMeta?->nama_jurusan ?: ($rombel?->jurusan_id_str ?? 'Umum'),
            'jurusan_logo_url' => $jurusanMeta?->logo_url,
            'foto_url' => $fotoUrl,
            'tahun_pelajaran' => 'TP. ' . $semesterLabel,
            'status' => 'AKTIF',
            'qr_code_svg' => QrCodeService::generateSvg($verifyUrl, 120, 0),
            'barcode_svg' => BarcodeService::generateCode128Svg($pd->nisn ?: '0000000000', 36, 2),
            'verify_url' => $verifyUrl,
            'sekolah' => $sekolahInfo,
        ];
    }

    /**
     * Dapatkan kartu massal untuk satu rombel, dengan opsi filter khusus (misal: hanya yang status request menunggu).
     */
    public function getRombelCardsData(string $rombelId, array $filters = []): array
    {
        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        if (!$rombel) {
            $rombel = DB::table('rombongan_belajar')->where('nama', $rombelId)->first();
        }

        if (!$rombel) {
            return ['error' => 'not_found', 'rombel' => null, 'cards' => [], 'sekolah' => []];
        }

        $query = DB::table('peserta_didik')
            ->where(function ($w) use ($rombel) {
                $w->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
                    ->orWhere('nama_rombel', $rombel->nama);
            });

        // Filter: hanya siswa yang memiliki request cetak berstatus tertentu (misal: 'menunggu')
        if (!empty($filters['status_request']) && Table::exists('kartu_pelajar_request')) {
            $stReq = $filters['status_request'];
            $query->where(function ($qReq) use ($stReq) {
                $qReq->whereExists(function ($sub) use ($stReq) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereColumn('kpr.peserta_didik_id', 'peserta_didik.peserta_didik_id')
                        ->where('kpr.status', $stReq);
                })
                ->orWhereExists(function ($sub) use ($stReq) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereNotNull('kpr.nisn')
                        ->whereColumn('kpr.nisn', 'peserta_didik.nisn')
                        ->where('kpr.status', $stReq);
                });
            });
        }

        // Filter: hanya ID request tertentu yang diceklist / dipilih
        if (!empty($filters['request_ids']) && is_array($filters['request_ids']) && Table::exists('kartu_pelajar_request')) {
            $reqIds = array_map('intval', $filters['request_ids']);
            $query->where(function ($qReq) use ($reqIds) {
                $qReq->whereExists(function ($sub) use ($reqIds) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereColumn('kpr.peserta_didik_id', 'peserta_didik.peserta_didik_id')
                        ->whereIn('kpr.id', $reqIds);
                })
                ->orWhereExists(function ($sub) use ($reqIds) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereNotNull('kpr.nisn')
                        ->whereColumn('kpr.nisn', 'peserta_didik.nisn')
                        ->whereIn('kpr.id', $reqIds);
                });
            });
        }

        // Filter: hanya peserta_didik_id tertentu
        if (!empty($filters['peserta_didik_ids']) && is_array($filters['peserta_didik_ids'])) {
            $query->whereIn('peserta_didik.peserta_didik_id', $filters['peserta_didik_ids']);
        }

        // Validasi: hanya siswa yang sudah ada fotonya (jika diminta atau saat cetak request)
        if (!empty($filters['hanya_ada_foto']) && Table::exists('peserta_didik_meta')) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('peserta_didik_meta as pdm')
                    ->whereColumn('pdm.peserta_didik_id', 'peserta_didik.peserta_didik_id')
                    ->whereNotNull('pdm.foto_path')
                    ->where('pdm.foto_path', '!=', '');
            });
        }

        $students = $query->orderBy('nama', 'asc')->get();

        $sekolahInfo = $this->getSekolahInfo();
        $jurusanMeta = $this->getJurusanMeta($rombel->jurusan_id);

        $cards = [];
        $pdIds = $students->pluck('peserta_didik_id')->all();
        $photos = [];
        if (Table::exists('peserta_didik_meta')) {
            $photos = PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)
                ->get()
                ->keyBy('peserta_didik_id');
        }

        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        foreach ($students as $pd) {
            $photoMeta = $photos[$pd->peserta_didik_id] ?? null;
            $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);

            $cards[] = [
                'pd' => $pd,
                'nisn' => $pd->nisn,
                'nama' => $pd->nama,
                'rombel' => $pd->nama_rombel ?: $rombel->nama,
                'jurusan' => $jurusanMeta?->nama_jurusan ?: ($rombel->jurusan_id_str ?? 'Reguler'),
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

        return [
            'rombel' => $rombel,
            'cards' => $cards,
            'sekolah' => $sekolahInfo,
            'filter_status_request' => $filters['status_request'] ?? null,
            'filter_hanya_ada_foto' => !empty($filters['hanya_ada_foto']),
            'is_selected_only' => !empty($filters['request_ids']) || !empty($filters['peserta_didik_ids']),
        ];
    }
}
