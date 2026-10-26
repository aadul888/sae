<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul ARSIP DOKUMEN ADMINISTRASI BENGKEL/LAB (Ordner Digital).
 *
 * Indikator Tupoksi #7 — bukti fisik: Ordner Berita Acara Kerusakan Alat,
 * Kartu Peminjaman Alat Praktik, dan dokumen lain.
 *
 * Modul ini menjadi pusat indeks arsip: dokumen dari modul lain
 * (BA kerusakan, peminjaman, perawatan, permintaan, pemeriksaan,
 * inventaris, pengadaan, laporan) dapat didaftarkan/diarsipkan di sini.
 */
class LaboranArsipController extends LaboranBaseController
{
    public const KATEGORI = [
        'berita_acara_kerusakan' => 'Berita Acara Kerusakan Alat',
        'kartu_peminjaman'       => 'Kartu Peminjaman Alat',
        'kartu_perawatan'        => 'Kartu Perawatan Alat',
        'checklist_pemeriksaan'  => 'Checklist Pemeriksaan',
        'buku_inventaris'        => 'Buku Induk Inventaris',
        'usulan_pengadaan'       => 'Usulan Pengadaan',
        'laporan'                => 'Laporan',
        'administrasi'           => 'Administrasi Jurusan',
        'dokumentasi'            => 'Dokumentasi Kegiatan',
        'lainnya'                => 'Dokumen Lainnya',
    ];

    public const STATUS = [
        'aktif'       => 'Aktif',
        'inaktif'     => 'Inaktif',
        'dimusnahkan' => 'Dimusnahkan',
    ];

    public const AKSES = [
        'internal'  => 'Internal Jurusan',
        'terbatas'  => 'Terbatas',
        'publik'    => 'Publik',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $tab          = (string) $request->query('tab', 'arsip');
        if (!in_array($tab, ['arsip', 'indeks'], true)) {
            $tab = 'arsip';
        }

        $search       = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $statBase = DB::table('laboran_arsip');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal  = (clone $statBase)->count();
        $statAktif  = (clone $statBase)->where('status', 'aktif')->count();
        $statInaktif = (clone $statBase)->where('status', 'inaktif')->count();
        $statKategori = (clone $statBase)->distinct('kategori')->count('kategori');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        if ($tab === 'indeks') {
            // Indeks: rekap jumlah arsip per kategori (peta ordner)
            $indeksQuery = DB::table('laboran_arsip')
                ->select('kategori', DB::raw('count(*) as jumlah'),
                         DB::raw('MAX(tanggal_dokumen) as terakhir'));
            LaboranScope::apply($indeksQuery, 'jurusan_id', $context);
            $indeks = $indeksQuery->groupBy('kategori')->orderByDesc('jumlah')->get();

            $list = DB::table('laboran_arsip')->whereRaw('1=0')->paginate($perPage);

            return view('dashboard.laboran.arsip', array_merge(
                compact('tab', 'list', 'search', 'kategoriFilter', 'statusFilter', 'perPage',
                    'statTotal', 'statAktif', 'statInaktif', 'statKategori', 'indeks'),
                $this->abilities(),
                [
                    'context'        => $context,
                    'scopeBadge'     => LaboranScope::badge($context),
                    'kategoriList'   => self::KATEGORI,
                    'statusList'     => self::STATUS,
                    'aksesList'      => self::AKSES,
                    'jurusanOptions' => LaboranScope::jurusanOptions($context),
                    'sumberOptions'  => $this->sumberOptions($context),
                ]
            ));
        }

        $query = DB::table('laboran_arsip as a')
            ->leftJoin('jurusan_meta as jm', 'a.jurusan_id', '=', 'jm.jurusan_id')
            ->select('a.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'a.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('a.nama_dokumen', 'like', "%{$search}%")
                  ->orWhere('a.kode_arsip', 'like', "%{$search}%")
                  ->orWhere('a.nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('a.deskripsi', 'like', "%{$search}%")
                  ->orWhere('a.lokasi_fisik', 'like', "%{$search}%");
            });
        }
        if ($kategoriFilter !== '') $query->where('a.kategori', $kategoriFilter);
        if ($statusFilter !== '')   $query->where('a.status', $statusFilter);

        $list = $query->orderByDesc('a.tanggal_dokumen')->orderByDesc('a.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.laboran.arsip', array_merge(
            compact('tab', 'list', 'search', 'kategoriFilter', 'statusFilter', 'perPage',
                'statTotal', 'statAktif', 'statInaktif', 'statKategori'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'kategoriList'   => self::KATEGORI,
                'statusList'     => self::STATUS,
                'aksesList'      => self::AKSES,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
                'sumberOptions'  => $this->sumberOptions($context),
                'indeks'         => collect(),
            ]
        ));
    }

    /**
     * Daftar dokumen dari modul lain yang belum diarsipkan.
     * Memudahkan petugas mendaftarkan bukti fisik ke ordner digital.
     */
    protected function sumberOptions(array $context): array
    {
        $result = [];

        // BA Kerusakan
        $q = DB::table('laboran_berita_acara_kerusakan as ba')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('laboran_arsip as ar')
                    ->whereColumn('ar.sumber_id', 'ba.id')
                    ->where('ar.sumber_tabel', 'laboran_berita_acara_kerusakan');
            })
            ->select('ba.id', 'ba.nomor_ba as nomor', 'ba.nama_alat as judul', 'ba.tanggal_ba as tanggal', 'ba.jurusan_id')
            ->orderByDesc('ba.id')->limit(50);
        LaboranScope::apply($q, 'ba.jurusan_id', $context);
        $result['berita_acara_kerusakan'] = $q->get();

        // Peminjaman
        $q = DB::table('laboran_peminjaman as p')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('laboran_arsip as ar')
                    ->whereColumn('ar.sumber_id', 'p.id')
                    ->where('ar.sumber_tabel', 'laboran_peminjaman');
            })
            ->select('p.id', 'p.nomor_pinjam as nomor', 'p.peminjam_nama as judul', 'p.tanggal_pinjam as tanggal', 'p.jurusan_id')
            ->orderByDesc('p.id')->limit(50);
        LaboranScope::apply($q, 'p.jurusan_id', $context);
        $result['kartu_peminjaman'] = $q->get();

        // Pemeriksaan
        $q = DB::table('laboran_pemeriksaan as pr')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('laboran_arsip as ar')
                    ->whereColumn('ar.sumber_id', 'pr.id')
                    ->where('ar.sumber_tabel', 'laboran_pemeriksaan');
            })
            ->select('pr.id', 'pr.nomor_pemeriksaan as nomor', 'pr.ruang_lab_nama as judul', 'pr.tanggal_pemeriksaan as tanggal', 'pr.jurusan_id')
            ->orderByDesc('pr.id')->limit(50);
        LaboranScope::apply($q, 'pr.jurusan_id', $context);
        $result['checklist_pemeriksaan'] = $q->get();

        // Usulan pengadaan
        $q = DB::table('laboran_usulan_pengadaan as u')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('laboran_arsip as ar')
                    ->whereColumn('ar.sumber_id', 'u.id')
                    ->where('ar.sumber_tabel', 'laboran_usulan_pengadaan');
            })
            ->select('u.id', 'u.nomor_usulan as nomor', 'u.pemohon_nama as judul', 'u.tanggal_usulan as tanggal', 'u.jurusan_id')
            ->orderByDesc('u.id')->limit(50);
        LaboranScope::apply($q, 'u.jurusan_id', $context);
        $result['usulan_pengadaan'] = $q->get();

        return $result;
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('laboran_arsip')->insert([
            'jurusan_id'      => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'kode_arsip'      => $this->generateNomor('laboran_arsip', 'kode_arsip', 'ARS'),
            'nama_dokumen'    => trim($data['nama_dokumen']),
            'kategori'        => $data['kategori'],
            'nomor_dokumen'   => $data['nomor_dokumen'] ?? null,
            'tanggal_dokumen' => $data['tanggal_dokumen'] ?? null,
            'tahun_ajaran'    => $data['tahun_ajaran'] ?? null,
            'sumber_tabel'    => $data['sumber_tabel'] ?? null,
            'sumber_id'       => $data['sumber_id'] ?? null,
            'deskripsi'       => $data['deskripsi'] ?? null,
            'lokasi_fisik'    => $data['lokasi_fisik'] ?? null,
            'status'          => $data['status'] ?? 'aktif',
            'tingkat_akses'   => $data['tingkat_akses'] ?? 'internal',
            'petugas_ptk_id'  => $context['ptk_id'],
            'petugas_nama'    => $context['nama'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return $this->respond($request, true, 'Dokumen berhasil diarsipkan.');
    }

    /**
     * Arsipkan otomatis seluruh dokumen dari satu sumber modul.
     */
    public function arsipkanMassal(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'kategori' => 'required|string|max:60',
        ]);

        $kategori = $data['kategori'];
        $jumlah = 0;

        $map = [
            'berita_acara_kerusakan' => ['laboran_berita_acara_kerusakan', 'nomor_ba', 'nama_alat', 'tanggal_ba', 'jurusan_id'],
            'kartu_peminjaman'       => ['laboran_peminjaman', 'nomor_pinjam', 'peminjam_nama', 'tanggal_pinjam', 'jurusan_id'],
            'checklist_pemeriksaan'  => ['laboran_pemeriksaan', 'nomor_pemeriksaan', 'ruang_lab_nama', 'tanggal_pemeriksaan', 'jurusan_id'],
            'usulan_pengadaan'       => ['laboran_usulan_pengadaan', 'nomor_usulan', 'pemohon_nama', 'tanggal_usulan', 'jurusan_id'],
        ];

        if (!isset($map[$kategori])) {
            return $this->respond($request, false, 'Kategori arsip massal tidak didukung.', 422);
        }

        [$table, $kolomNomor, $kolomJudul, $kolomTanggal, $kolomJurusan] = $map[$kategori];

        $q = DB::table($table)->whereNotExists(function ($sub) use ($table) {
            $sub->select(DB::raw(1))->from('laboran_arsip as ar')
                ->whereColumn('ar.sumber_id', $table . '.id')
                ->where('ar.sumber_tabel', $table);
        });
        LaboranScope::apply($q, $kolomJurusan, $context);
        $rows = $q->limit(500)->get();

        foreach ($rows as $row) {
            DB::table('laboran_arsip')->insert([
                'jurusan_id'      => $row->{$kolomJurusan} ?? $context['jurusan_id'],
                'kode_arsip'      => $this->generateNomor('laboran_arsip', 'kode_arsip', 'ARS'),
                'nama_dokumen'    => (self::KATEGORI[$kategori] ?? $kategori) . ' — ' . ($row->{$kolomNomor} ?? ''),
                'kategori'        => $kategori,
                'nomor_dokumen'   => $row->{$kolomNomor} ?? null,
                'tanggal_dokumen' => $row->{$kolomTanggal} ?? null,
                'sumber_tabel'    => $table,
                'sumber_id'       => $row->id,
                'deskripsi'       => $row->{$kolomJudul} ?? null,
                'status'          => 'aktif',
                'tingkat_akses'   => 'internal',
                'petugas_ptk_id'  => $context['ptk_id'],
                'petugas_nama'    => $context['nama'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $jumlah++;
        }

        return $this->respond($request, true, $jumlah . ' dokumen berhasil diarsipkan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_arsip', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Arsip tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);

        DB::table('laboran_arsip')->where('id', $id)->update([
            'nama_dokumen'    => trim($data['nama_dokumen']),
            'kategori'        => $data['kategori'],
            'nomor_dokumen'   => $data['nomor_dokumen'] ?? null,
            'tanggal_dokumen' => $data['tanggal_dokumen'] ?? null,
            'tahun_ajaran'    => $data['tahun_ajaran'] ?? null,
            'deskripsi'       => $data['deskripsi'] ?? null,
            'lokasi_fisik'    => $data['lokasi_fisik'] ?? null,
            'status'          => $data['status'] ?? $row->status,
            'tingkat_akses'   => $data['tingkat_akses'] ?? $row->tingkat_akses,
            'updated_at'      => now(),
        ]);

        return $this->respond($request, true, 'Arsip berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_arsip', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Arsip tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_arsip')->where('id', $id)->delete();

        return $this->respond($request, true, 'Arsip berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'nama_dokumen'    => 'required|string|max:255',
            'kategori'        => 'required|string|max:60',
            'nomor_dokumen'   => 'nullable|string|max:100',
            'tanggal_dokumen' => 'nullable|date',
            'tahun_ajaran'    => 'nullable|string|max:20',
            'sumber_tabel'    => 'nullable|string|max:60',
            'sumber_id'       => 'nullable|integer',
            'deskripsi'       => 'nullable|string|max:2000',
            'lokasi_fisik'    => 'nullable|string|max:150',
            'status'          => 'nullable|in:aktif,inaktif,dimusnahkan',
            'tingkat_akses'   => 'nullable|in:internal,terbatas,publik',
            'jurusan_id'      => 'nullable|string|max:50',
        ], [
            'nama_dokumen.required' => 'Nama dokumen wajib diisi.',
            'kategori.required'     => 'Kategori arsip wajib dipilih.',
        ]);
    }
}
