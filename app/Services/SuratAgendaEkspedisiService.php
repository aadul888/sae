<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Persuratan;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Models\Gtk;

class SuratAgendaEkspedisiService
{
    /**
     * Susun Data Cetak Buku Agenda Kedinasan Resmi (Masuk atau Keluar)
     */
    public static function buildAgendaData(Request $request, string $jenisSurat = 'keluar'): array
    {
        $tahun = $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan', '');
        $tanggalMulai = $request->get('tanggal_mulai', '');
        $tanggalSelesai = $request->get('tanggal_selesai', '');
        $status = $request->get('status', '');
        $kodeIndeks = $request->get('kode_indeks', '');

        $query = Persuratan::where('jenis_surat', $jenisSurat);

        if ($jenisSurat === 'masuk') {
            $query->with(['latestDisposisi.ptk', 'disposisi.ptk']);
        }

        if (!empty($tanggalMulai) && !empty($tanggalSelesai)) {
            $query->whereBetween('tanggal_surat', [$tanggalMulai, $tanggalSelesai]);
        } elseif (!empty($tahun)) {
            $query->whereYear('tanggal_surat', $tahun);
            if (!empty($bulan)) {
                $query->whereMonth('tanggal_surat', $bulan);
            }
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($kodeIndeks)) {
            $query->where('kode_indeks', $kodeIndeks);
        }

        $items = $query->orderBy('tanggal_surat', 'asc')->orderBy('id', 'asc')->get();

        $sekolah = Sekolah::first() ?: DB::table('sekolah')->first();
        $sekolahMeta = SekolahMeta::first();

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $kepalaTas = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Tenaga Administrasi%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala TAS%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Tata Usaha%')
                  ->orWhere('jenis_ptk_id_str', 'like', '%Tata Usaha%');
            })
            ->first();

        $periodeText = !empty($tanggalMulai) && !empty($tanggalSelesai)
            ? Carbon::parse($tanggalMulai)->translatedFormat('d M Y') . ' s.d. ' . Carbon::parse($tanggalSelesai)->translatedFormat('d M Y')
            : (!empty($bulan) ? Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F Y') : "Tahun {$tahun}");

        $judul = $jenisSurat === 'masuk' ? 'BUKU AGENDA SURAT MASUK' : 'BUKU AGENDA SURAT KELUAR';

        return [
            'jenisSurat'     => $jenisSurat,
            'judul'          => $judul,
            'items'          => $items,
            'sekolah'        => $sekolah,
            'sekolahMeta'    => $sekolahMeta,
            'kepsek'         => $kepsek,
            'kepalaTas'      => $kepalaTas,
            'periodeText'    => $periodeText,
            'tahun'          => $tahun,
            'bulan'          => $bulan,
            'tanggalMulai'   => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'status'         => $status,
        ];
    }

    /**
     * Susun Data Cetak Lembar Ekspedisi / Tanda Terima Pengiriman
     */
    public static function buildEkspedisiData(Request $request, $id = null): array
    {
        $sekolah = Sekolah::first() ?: DB::table('sekolah')->first();
        $sekolahMeta = SekolahMeta::first();

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $kepalaTas = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Tenaga Administrasi%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala TAS%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Tata Usaha%')
                  ->orWhere('jenis_ptk_id_str', 'like', '%Tata Usaha%');
            })
            ->first();

        if ($id) {
            $surat = Persuratan::where('jenis_surat', 'keluar')->findOrFail($id);
            $items = collect([$surat]);
            $isSingle = true;
        } else {
            $tanggalMulai = $request->get('tanggal_mulai', '');
            $tanggalSelesai = $request->get('tanggal_selesai', '');
            $q = trim($request->get('q', ''));

            $query = Persuratan::where('jenis_surat', 'keluar');
            if (!empty($tanggalMulai) && !empty($tanggalSelesai)) {
                $query->whereBetween('tanggal_surat', [$tanggalMulai, $tanggalSelesai]);
            }
            if ($q !== '') {
                $query->where(function ($b) use ($q) {
                    $b->where('nomor_surat', 'like', "%{$q}%")
                      ->orWhere('perihal', 'like', "%{$q}%")
                      ->orWhere('tujuan_penerima', 'like', "%{$q}%");
                });
            }

            $items = $query->orderBy('tanggal_surat', 'desc')->orderBy('id', 'desc')->get();
            $surat = $items->first();
            $isSingle = false;
        }

        return compact(
            'items',
            'surat',
            'isSingle',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'kepalaTas'
        );
    }
}
