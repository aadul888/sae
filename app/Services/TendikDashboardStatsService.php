<?php

namespace App\Services;

use App\Models\User;
use App\Services\Tendik\TendikDashboardCharts;
use App\Services\Tendik\TendikDashboardMetrics;
use App\Services\Tendik\TendikDashboardSections;
use App\Services\Tendik\TendikDutyResolver;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi statistik & metrik 10 cluster bidang tugas Tendik (facade).
 * Detail resolusi tugas, metrik, section, dan chart ada di App\Services\Tendik\*.
 * ponytail: static-calculator; upgrade ke caching per-bidang jika akses melebihi 100 req/sec.
 */
class TendikDashboardStatsService
{
    /** Cek apakah user memiliki tugas tambahan tendik aktif. */
    public static function hasTendikDuty(mixed $userId, mixed $ptkId): bool
    {
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            return DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->whereIn('rtt.kode', TendikDutyResolver::DUTY_CODES)
                ->exists();
        }
        return false;
    }

    /** Kumpulkan seluruh dataset untuk tampilan dashboard Tendik. */
    public static function getStats(mixed $user, Request $request): array
    {
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        $gtk = null;
        if (Table::exists('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $fotoUrl = is_array($user) ? ($user['foto_url'] ?? null) : ($user->foto_url ?? null);
        if (!$fotoUrl && $userId) {
            $fotoUrl = User::where('pengguna_id', $userId)->first()?->foto_url;
        }
        if (!$fotoUrl && $ptkId) {
            $fotoUrl = User::where('ptk_id', $ptkId)->whereNotNull('foto_path')->first()?->foto_url;
        }
        if (!$fotoUrl && $gtk?->ptk_id) {
            $fotoUrl = User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        $dutyRecords = collect();
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $dutyRecords = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->select('rtt.kode', 'rtt.nama', 'rtt.icon', 'rtt.bidang')
                ->get();
        }

        $activeDuties = $dutyRecords->pluck('nama')->filter()->unique();
        $dutyCodes = $dutyRecords->pluck('kode')->filter()->unique()->values();

        $bagianTugas = TendikDutyResolver::bagianTugas($activeDuties, $gtk);
        $isKepalaTas = $dutyCodes->contains('KEPALA_TAS') || $userRole === 'admin';
        $primaryDuty = TendikDutyResolver::primaryDuty($dutyCodes, $isKepalaTas);

        $reqBidang = $request->query('bidang') ?: $request->get('bidang');
        [$currentDuty, $viewSection] = TendikDutyResolver::resolveViewSection($user, $reqBidang);

        $stats = TendikDashboardMetrics::stats();

        $aktivitas = TendikDashboardMetrics::aktivitas($userId, $ptkId);
        $aktivitasHariIniCount = $aktivitas['hariIniCount'];
        $aktivitasHariIniSelesai = $aktivitas['selesai'];
        $aktivitasHariIniProses = $aktivitas['proses'];
        $aktivitasHariIniTertunda = $aktivitas['tertunda'];
        $aktivitasBulanIniCount = $aktivitas['bulanIniCount'];
        $aktivitasSayaList = $aktivitas['list'];

        $pengumumanList = TendikDashboardMetrics::pengumuman();

        $daftar = TendikDashboardMetrics::daftarUmum();
        $stafTas = $daftar['stafTas'];
        $persuratanList = $daftar['persuratanList'];
        $persuratanTerbaru = $persuratanList->take(5);
        $rombelRekap = $daftar['rombelRekap'];
        $siswaTerbaru = $daftar['siswaTerbaru'];
        $gtkTugasList = $daftar['gtkTugasList'];
        $gtkList = $daftar['gtkList'];
        $ruangList = $daftar['ruangList'];
        $jadwalLab = $daftar['jadwalLab'];

        $administrasi_tugas = TendikDashboardMetrics::administrasiTugas();

        $piket = TendikDashboardSections::piket($stats['total_guru']);
        $piketStats = $piket['stats'];
        $recentIzinSiswa = $piket['recentIzinSiswa'];
        $recentAgendaKbm = $piket['recentAgendaKbm'];
        $recentPresensiGuru = $piket['recentPresensiGuru'];

        $teknisiSection = TendikDashboardSections::teknisi();
        $teknisiStats = $teknisiSection['stats'];
        $teknisiTupoksiRekap = $teknisiSection['tupoksiRekap'];
        $teknisiStatistik = $teknisiSection['statistik'];

        $keamananStats = TendikDashboardSections::keamanan();
        $penjagaStats = TendikDashboardSections::penjaga();
        $perpusStats = TendikDashboardSections::perpus();
        $laboranStats = TendikDashboardSections::laboran();

        $kepegawaianCharts = TendikDashboardCharts::kepegawaian();
        $kepegawaianAktivitasMultiPeriode = KepegawaianStatsService::buildMultiPeriodeData($stats['total_tendik'] ?: 0);

        $chartsUmum = TendikDashboardCharts::umum();
        $kesiswaanCharts = TendikDashboardCharts::kesiswaan($chartsUmum);
        $persuratanCharts = TendikDashboardCharts::persuratan();

        // Dashboard induk Sarpras: agregasi lintas modul (ruang, aset,
        // peminjaman, teknisi, laboran, penjaga, keamanan).
        $sarprasDashboard = SarprasDashboardService::build($user);
        $sarprasCharts = $sarprasDashboard['charts'];

        $laboranCharts = TendikDashboardCharts::laboran();
        $perpustakaanCharts = TendikDashboardCharts::perpustakaan();
        $keamananCharts = TendikDashboardCharts::keamanan();
        $penjagaCharts = TendikDashboardCharts::penjaga();
        $piketCharts = TendikDashboardCharts::piket();
        $teknisiCharts = TendikDashboardCharts::teknisi();
        $kepalaTasCharts = TendikDashboardCharts::kepalaTas();

        [$kepegawaianIndikatorList, $bidangMultiPeriode, $bidangIndikatorList] =
            TendikDashboardCharts::indikator($stats['total_tendik']);

        // Pecah hasil agregasi dashboard Sarpras menjadi variabel view
        // agar partial `dashboard.tendik.section-sarpras` tetap ringkas.
        $sarprasRingkasan       = $sarprasDashboard['ringkasan'];
        $sarprasDaftar          = $sarprasDashboard['daftar'];
        $sarprasLintasBidang    = $sarprasDashboard['lintasBidang'];
        $sarprasPenanggungJawab = $sarprasDashboard['penanggungJawab'];
        $sarprasPeringatan      = $sarprasDashboard['peringatan'];

        return compact(
            'stats',
            'administrasi_tugas',
            'gtk',
            'fotoUrl',
            'bagianTugas',
            'dutyCodes',
            'dutyRecords',
            'isKepalaTas',
            'primaryDuty',
            'currentDuty',
            'viewSection',
            'stafTas',
            'persuratanTerbaru',
            'persuratanList',
            'rombelRekap',
            'siswaTerbaru',
            'gtkTugasList',
            'gtkList',
            'ruangList',
            'jadwalLab',
            'piketStats',
            'recentIzinSiswa',
            'recentAgendaKbm',
            'recentPresensiGuru',
            'aktivitasHariIniCount',
            'aktivitasHariIniSelesai',
            'aktivitasHariIniProses',
            'aktivitasHariIniTertunda',
            'aktivitasBulanIniCount',
            'aktivitasSayaList',
            'pengumumanList',
            'kepegawaianCharts',
            'kepegawaianAktivitasMultiPeriode',
            'kepegawaianIndikatorList',
            'chartsUmum',
            'kesiswaanCharts',
            'persuratanCharts',
            'sarprasCharts',
            'sarprasDashboard',
            'sarprasRingkasan',
            'sarprasDaftar',
            'sarprasLintasBidang',
            'sarprasPenanggungJawab',
            'sarprasPeringatan',
            'laboranCharts',
            'perpustakaanCharts',
            'keamananCharts',
            'penjagaCharts',
            'piketCharts',
            'teknisiCharts',
            'kepalaTasCharts',
            'bidangMultiPeriode',
            'bidangIndikatorList',
            'teknisiStats',
            'teknisiTupoksiRekap',
            'teknisiStatistik',
            'keamananStats',
            'penjagaStats',
            'perpusStats',
            'laboranStats'
        );
    }
}
