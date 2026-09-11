<?php

namespace App\Http\Controllers;

use App\Models\JurusanMeta;
use App\Models\PesertaDidikMeta;
use App\Models\SekolahMeta;
use App\Services\BarcodeService;
use App\Services\QrCodeService;
use App\Support\SemesterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KartuPelajarController extends Controller
{
    /**
     * Halaman Publik Verifikasi Resmi Kartu Pelajar (Direct scan dari QR code)
     */
    public function verify(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            return view('kartu-pelajar.verifikasi-notfound', [
                'nisn' => $nisn,
                'sekolah' => $this->getSekolahInfo(),
            ]);
        }

        return view('kartu-pelajar.verifikasi', [
            'card' => $card,
            'verifiedAt' => now()->translatedFormat('d F Y, H:i:s') . ' WIB',
        ]);
    }

    /**
     * Data JSON / Preview untuk Modal Kartu Pelajar Interaktif
     */
    public function preview(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $user = session('user');
        if ($user) {
            $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
            $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

            // Jika peserta didik, hanya boleh melihat pratinjau kartu miliknya sendiri
            if ($role === 'peserta_didik' && $userNisn !== $nisn) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya berhak melihat kartu milik Anda sendiri.'], 403);
            }

            // Jika guru atau tendik, harus Administrator atau Wali Kelas
            if (in_array($role, ['guru', 'tendik']) && !\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang berwenang membuka kartu pelajar.'], 403);
            }
        }

        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'card' => $card,
                'html' => view('kartu-pelajar.template', ['card' => $card, 'isPrint' => false])->render(),
            ]);
        }

        return view('kartu-pelajar.single-preview', ['card' => $card]);
    }

    /**
     * Cetak Kartu Pelajar Tunggal
     */
    public function printSingle(Request $request, string $nisn)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $nisn = trim($nisn);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

        // Peserta didik hanya berwenang mencetak kartu miliknya sendiri
        if ($role === 'peserta_didik') {
            if ($userNisn !== $nisn) {
                abort(403, 'Akses ditolak: Anda hanya dapat mencetak kartu pelajar milik Anda sendiri.');
            }
        } elseif (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki hak akses cetak kartu pelajar.');
        }

        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        return view('kartu-pelajar.cetak-single', [
            'card' => $card,
        ]);
    }

    /**
     * Cetak Masal Kartu Pelajar per Rombongan Belajar (Rombel)
     */
    public function printRombel(Request $request, string $rombelId)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user, $rombelId) && !\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang diizinkan mencetak kartu pelajar masal.');
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        if (!$rombel) {
            // Cek jika yang dikirim adalah nama rombel
            $rombel = DB::table('rombongan_belajar')->where('nama', $rombelId)->first();
        }

        if (!$rombel) {
            abort(404, 'Rombongan belajar tidak ditemukan.');
        }

        $students = DB::table('peserta_didik')
            ->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->orderBy('nama', 'asc')
            ->get();

        if ($students->isEmpty()) {
            // Cek berdasarkan nama rombel
            $students = DB::table('peserta_didik')
                ->where('nama_rombel', $rombel->nama)
                ->orderBy('nama', 'asc')
                ->get();
        }

        $sekolahInfo = $this->getSekolahInfo();
        $jurusanMeta = $this->getJurusanMeta($rombel->jurusan_id);

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

        return view('kartu-pelajar.cetak-rombel', [
            'rombel' => $rombel,
            'cards' => $cards,
            'sekolah' => $sekolahInfo,
        ]);
    }

    /**
     * Dapatkan data lengkap satu peserta didik untuk kartu pelajar
     */
    private function loadStudentCardData(string $nisn): ?array
    {
        if (!Schema::hasTable('peserta_didik')) {
            return null;
        }

        $pd = DB::table('peserta_didik')
            ->where('nisn', $nisn)
            ->first();

        if (!$pd) {
            return null;
        }

        // Ambil rombel
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

        // Ambil jurusan & logo jurusan
        $jurusanId = $rombel?->jurusan_id;
        $jurusanMeta = $this->getJurusanMeta($jurusanId);

        // Ambil foto siswa
        $fotoUrl = null;
        if (Schema::hasTable('peserta_didik_meta')) {
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
     * Dapatkan metadata jurusan
     */
    private function getJurusanMeta(?string $jurusanId): ?JurusanMeta
    {
        if (empty($jurusanId) || !Schema::hasTable('jurusan_meta')) {
            return null;
        }

        $meta = JurusanMeta::where('jurusan_id', $jurusanId)->first();
        if (!$meta) {
            // Coba cari jika id jurusan memiliki prefix
            $meta = JurusanMeta::where('jurusan_id', 'LIKE', substr($jurusanId, 0, 5) . '%')->first();
        }

        return $meta;
    }

    /**
     * Dapatkan informasi dan logo sekolah
     */
    private function getSekolahInfo(): array
    {
        $sekolah = null;
        if (Schema::hasTable('sekolah')) {
            $sekolah = DB::table('sekolah')->first();
        }

        $sekolahMeta = null;
        if (Schema::hasTable('sekolah_meta')) {
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
}
