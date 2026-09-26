<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TendikAktivitas extends Model
{
    use HasFactory;

    protected $table = 'tendik_aktivitas';

    protected $fillable = [
        'user_id',
        'ptk_id',
        'nama_pegawai',
        'bidang',
        'indikator_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'judul_aktivitas',
        'uraian_pekerjaan',
        'output_hasil',
        'status',
        'lampiran_path',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'indikator_id' => 'integer',
    ];

    public const STATUS_LABELS = [
        'selesai' => 'Selesai',
        'proses' => 'Sedang Proses',
        'tertunda' => 'Tertunda / Kendala',
    ];

    public const BIDANG_LABELS = [
        'kepala_tas' => 'Kepala TAS / Tata Usaha',
        'kesiswaan' => 'Kesiswaan',
        'kepegawaian' => 'Kepegawaian',
        'sarpras' => 'Sarana & Prasarana',
        'laboran' => 'Laboratorium',
        'perpustakaan' => 'Perpustakaan',
        'teknisi' => 'Teknisi IT',
        'keamanan' => 'Keamanan & Satpam',
        'penjaga' => 'Penjaga & Fasilitas',
        'persuratan' => 'Persuratan & Arsip',
        'umum' => 'Administrasi Umum',
    ];

    public const TUPOKSI_TEMPLATES = [
        'kepala_tas' => [
            ['judul' => 'Koordinasi dan Pembagian Tugas Staf Administrasi', 'uraian' => 'Melaksanakan pengarahan rutin dan pembagian tugas operasional harian bagi staf administrasi sekolah.', 'output' => 'Distribusi tugas staf tervalidasi'],
            ['judul' => 'Verifikasi dan Validasi Dokumen Dinas / SK', 'uraian' => 'Memeriksa keabsahan dan kelengkapan dokumen dinas serta usulan berkas sebelum diajukan ke pimpinan.', 'output' => 'Dokumen dinas terverifikasi'],
            ['judul' => 'Supervisi Kinerja dan Disiplin Staf Tendik', 'uraian' => 'Melakukan pemantauan kehadiran dan evaluasi pelaksanaan tugas harian staf tata usaha dan layanan khusus.', 'output' => 'Laporan supervisi harian'],
        ],
        'kepegawaian' => [
            ['judul' => 'Verifikasi dan Pengarsipan Berkas GTK', 'uraian' => 'Menghimpun, memeriksa, dan mengarsipkan dokumen berkas fisik dan digital pegawai (SK, Ijazah, KGB, Sertifikat).', 'output' => 'Berkas GTK terarsip rapi'],
            ['judul' => 'Pemantauan Usulan Kenaikan Gaji Berkala (KGB) & Pangkat', 'uraian' => 'Mengecek data masa kerja dan menyiapkan draft usulan KGB atau kenaikan pangkat pendidik dan tendik.', 'output' => 'Daftar usulan KGB/Pangkat'],
            ['judul' => 'Rekapitulasi Presensi & Cuti Pegawai', 'uraian' => 'Merekap kehadiran harian GTK serta mencatat permohonan izin sakit, cuti tahunan, atau dinas luar.', 'output' => 'Rekap presensi bulanan GTK'],
        ],
        'kesiswaan' => [
            ['judul' => 'Pembaruan Buku Induk & Buku Klaper Siswa', 'uraian' => 'Mencatat identitas nomor induk, NISN, NIK, dan data keluarga peserta didik ke dalam Buku Induk.', 'output' => 'Buku Induk termutakhirkan'],
            ['judul' => 'Pemrosesan Berkas Mutasi Peserta Didik', 'uraian' => 'Memeriksa kelengkapan surat permohonan mutasi masuk/keluar siswa dan menerbitkan surat keterangan mutasi.', 'output' => 'Surat mutasi diterbitkan'],
            ['judul' => 'Pengarsipan Dokumen Siswa Baru', 'uraian' => 'Menyusun berkas dokumen siswa (Akta Kelahiran, Kartu Keluarga, Ijazah/SKL jenjang sebelumnya).', 'output' => 'Arsip siswa tersimpan rapi'],
            ['judul' => 'Validasi Data Nominasi Peserta Didik Tingkat Akhir', 'uraian' => 'Mencocokkan data identitas siswa kelas akhir untuk keperluan penerbitan SKL dan blanko Ijazah.', 'output' => 'Daftar nominasi terverifikasi'],
        ],
        'persuratan' => [
            ['judul' => 'Pencatatan Agenda dan Disposisi Surat Masuk', 'uraian' => 'Menerima surat masuk, mencatat pada buku agenda persuratan, dan menyampaikan lembar disposisi ke pimpinan.', 'output' => 'Surat masuk terdisposisi'],
            ['judul' => 'Penerbitan Nomor dan Pengiriman Surat Keluar', 'uraian' => 'Menerbitkan nomor surat dinas resmi sekolah berdasarkan kode klasifikasi dan mendistribusikan ke tujuan.', 'output' => 'Surat keluar bernomor & terkirim'],
            ['judul' => 'Pengarsipan Surat Keputusan (SK) dan Dokumen Kedinasan', 'uraian' => 'Mengklasifikasikan dan menyimpan salinan SK, surat tugas, edaran, dan notula rapat ke filling cabinet.', 'output' => 'Arsip surat tersusun rapi'],
            ['judul' => 'Pelayanan Legalisir Ijazah dan Surat Keterangan', 'uraian' => 'Melayani pengesahan salinan ijazah/rapor alumni serta menerbitkan surat keterangan siswa/pegawai aktif.', 'output' => 'Dokumen terlegalisir'],
        ],
        'sarpras' => [
            ['judul' => 'Inventarisasi Aset Fisik dan Sarana Pembelajaran', 'uraian' => 'Mencatat pengadaan barang/peralatan baru ke dalam Buku Inventaris Barang dan memberi kode label barang.', 'output' => 'Aset terinventarisasi'],
            ['judul' => 'Pembaruan Kartu Inventaris Ruangan (KIR)', 'uraian' => 'Melakukan pengecekan fisik barang dan memperbarui lembar KIR di ruang kelas, laboratorium, dan kantor.', 'output' => 'Lembar KIR terpasang'],
            ['judul' => 'Pengendalian Barang Habis Pakai & ATK', 'uraian' => 'Mencatat stok masuk/keluar alat tulis kantor dan bahan kebersihan sekolah pada kartu persediaan.', 'output' => 'Buku stok ATK termutakhirkan'],
            ['judul' => 'Pencatatan Laporan Kerusakan & Jadwal Perbaikan', 'uraian' => 'Menerima laporan kerusakan fasilitas sekolah dan mengoordinasikan perbaikan dengan teknisi/tukang.', 'output' => 'Fasilitas dalam penanganan'],
        ],
        'laboran' => [
            ['judul' => 'Inventarisasi Alat dan Bahan Praktikum Lab', 'uraian' => 'Mengecek ketersediaan bahan dan kondisi alat praktikum di laboratorium/bengkel kerja.', 'output' => 'Daftar inventaris lab'],
            ['judul' => 'Penyiapan Ruang dan Alat untuk Praktikum KBM', 'uraian' => 'Menyiapkan alat dan bahan praktikum sesuai dengan jadwal KBM guru mata pelajaran terkait.', 'output' => 'Ruang praktikum siap pakai'],
            ['judul' => 'Pencatatan Log Peminjaman & Pengembalian Alat', 'uraian' => 'Mencatat siswa/guru yang meminjam alat lab serta memeriksa kelengkapan saat pengembalian.', 'output' => 'Buku peminjaman alat terisi'],
        ],
        'perpustakaan' => [
            ['judul' => 'Katalogisasi & Pelabelan Buku Perpustakaan', 'uraian' => 'Melakukan klasifikasi DDC, pencatatan barcode, dan penempelan label punggung buku pustaka baru.', 'output' => 'Buku terdaftar di katalog'],
            ['judul' => 'Layanan Sirkulasi Peminjaman & Pengembalian Buku', 'uraian' => 'Melayani transaksi sirkulasi buku teks pelajaran dan buku bacaan bagi peserta didik dan guru.', 'output' => 'Transaksi sirkulasi tercatat'],
            ['judul' => 'Rekapitulasi Kunjungan & Minat Baca Siswa', 'uraian' => 'Merekap daftar presensi pengunjung perpustakaan harian untuk laporan literasi sekolah.', 'output' => 'Laporan kunjungan perpustakaan'],
        ],
        'teknisi' => [
            ['judul' => 'Pemeliharaan Jaringan Internet & WiFi Sekolah', 'uraian' => 'Memeriksa akses point, router, dan bandwidth koneksi internet di seluruh area gedung sekolah.', 'output' => 'Koneksi internet stabil'],
            ['judul' => 'Perawatan Perangkat Komputer (PC/Laptop/Printer)', 'uraian' => 'Melakukan pembersihan berkala, pembaruan software, dan troubleshooting PC kantor dan laboratorium.', 'output' => 'Perangkat berfungsi normal'],
            ['judul' => 'Dukungan Teknis Kegiatan Asesmen / Ujian Digital', 'uraian' => 'Menyiapkan server CBT, jaringan lokal, dan konfigurasi client untuk pelaksanaan ujian sekolah.', 'output' => 'Sistem asesmen siap pakai'],
        ],
        'keamanan' => [
            ['judul' => 'Pengamanan dan Pengawasan Pintu Gerbang Sekolah', 'uraian' => 'Mengatur kelancaran lalu lintas keluar masuk warga sekolah dan tamu pada jam masuk/pulang.', 'output' => 'Area gerbang tertib dan aman'],
            ['judul' => 'Pencatatan Buku Tamu Kedinasan & Umum', 'uraian' => 'Menerima pengunjung, meminta identitas tamu, dan mengantar tamu ke pos layanan terkait.', 'output' => 'Buku tamu terisi lengkap'],
            ['judul' => 'Patroli Keamanan Lingkungan Gedung Sekolah', 'uraian' => 'Melakukan patroli keliling di seluruh sudut sekolah guna memastikan keamanan sarana dan lingkungan.', 'output' => 'Lingkungan sekolah kondusif'],
        ],
        'penjaga' => [
            ['judul' => 'Pembersihan dan Perawatan Ruang Publik / Kelas', 'uraian' => 'Membersihkan ruang kelas, selasar, toilet, dan halaman sekolah sebelum dan sesudah kegiatan belajar.', 'output' => 'Lingkungan bersih dan rapi'],
            ['judul' => 'Pengecekan dan Penguncian Seluruh Pintu Gedung', 'uraian' => 'Memastikan seluruh jendela, pintu ruang kelas/kantor terkunci dan lampu yang tidak terpakai dimatikan.', 'output' => 'Gedung aman terkunci'],
            ['judul' => 'Pemeliharaan Tanaman dan Kebun Sekolah', 'uraian' => 'Menyiram tanaman, merapikan rumput, dan merawat keasrian taman sekolah.', 'output' => 'Taman sekolah terawat'],
        ],
        'umum' => [
            ['judul' => 'Pelaksanaan Tugas Administrasi Umum', 'uraian' => 'Mendukung operasional perkantoran sekolah, penggandaan dokumen, dan layanan bantuan tata usaha.', 'output' => 'Tugas administrasi terselesaikan'],
        ],
    ];

    public function getDurasiMenitAttribute(): int
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return 60;
        }
        try {
            $start = \Carbon\Carbon::parse($this->jam_mulai);
            $end = \Carbon\Carbon::parse($this->jam_selesai);
            return max(1, $start->diffInMinutes($end));
        } catch (\Throwable $e) {
            return 60;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'pengguna_id');
    }

    public function indikator()
    {
        return $this->belongsTo(TendikIndikatorKinerja::class, 'indikator_id', 'id');
    }

    /**
     * Catat aktivitas sistem tendik secara otomatis (Login, CRUD, dsb).
     */
    public static function recordActivity($userOrId, string $judul, string $bidang = 'umum', ?string $uraian = null, string $status = 'selesai', ?string $output = null, ?int $indikatorId = null): ?self
    {
        try {
            $user = null;
            $userId = null;
            $ptkId = null;
            $nama = 'Tenaga Kependidikan';

            if ($userOrId instanceof User) {
                $user = $userOrId;
                $userId = $user->pengguna_id;
                $ptkId = $user->ptk_id;
                $nama = $user->nama ?: $user->name;
            } elseif (is_array($userOrId)) {
                $userId = $userOrId['pengguna_id'] ?? ($userOrId['id'] ?? null);
                $ptkId = $userOrId['ptk_id'] ?? null;
                $nama = $userOrId['nama'] ?? ($userOrId['name'] ?? 'Tenaga Kependidikan');
            } elseif (is_string($userOrId)) {
                $userId = $userOrId;
                $user = User::where('pengguna_id', $userId)->first();
                if ($user) {
                    $ptkId = $user->ptk_id;
                    $nama = $user->nama ?: $user->name;
                }
            } else {
                $sessionUser = session('user');
                if ($sessionUser) {
                    return self::recordActivity($sessionUser, $judul, $bidang, $uraian, $status, $output, $indikatorId);
                }
            }

            if (!$userId && !$ptkId) {
                return null;
            }

            $now = now();
            $jam = $now->format('H:i:s');
            $tanggal = $now->toDateString();

            // Format aktivitas otomatis agar bernuansa formal kedinasan
            if (stripos($judul, 'Login') !== false || stripos($judul, 'Autentikasi') !== false) {
                $judul = 'Pelaksanaan Presensi dan Pelaporan Kehadiran Mandiri';
                $uraian = 'Melaksanakan pelaporan kehadiran dinas serta verifikasi otentikasi akun kerja harian pada portal administrasi kepegawaian sekolah.';
                $output = '1 Sesi Pelaporan Tervalidasi';
            }

            // Cegah duplikasi log identik dalam interval 5 menit
            $existing = self::where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('user_id', $userId);
                    if ($ptkId) $q->orWhere('ptk_id', $ptkId);
                })
                ->whereDate('tanggal', $tanggal)
                ->where('judul_aktivitas', $judul)
                ->where('created_at', '>=', $now->copy()->subMinutes(5))
                ->first();

            if ($existing) {
                return $existing;
            }

            // Hubungkan secara cerdas ke indikator kinerja sasaran per bidang
            if (!$indikatorId && \Illuminate\Support\Facades\Schema::hasTable('tendik_indikator_kinerja')) {
                $indikatorId = TendikIndikatorKinerja::where('bidang', $bidang)
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->value('id');
            }

            return self::create([
                'user_id'          => $userId,
                'ptk_id'           => $ptkId,
                'nama_pegawai'     => $nama,
                'bidang'           => $bidang ?: 'umum',
                'indikator_id'     => $indikatorId,
                'tanggal'          => $tanggal,
                'jam_mulai'        => $jam,
                'jam_selesai'      => $jam,
                'judul_aktivitas'  => $judul,
                'uraian_pekerjaan' => $uraian ?: $judul,
                'output_hasil'     => $output ?: '1 Dokumen / Layanan Terlaksana',
                'status'           => $status ?: 'selesai',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal mencatat aktivitas otomatis: ' . $e->getMessage());
            return null;
        }
    }
}

