<?php

namespace App\Http\Middleware;

use App\Services\RealtimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastDataChanges
{
    /**
     * Handle an incoming request and broadcast event on successful state mutation.
     *
     * ponytail: automatic broadcast on HTTP mutation. Upgrade to granular model observers if specific payload diffs are needed per entity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $path = $request->path();
            $statusCode = $response->getStatusCode();

            // Hanya mutasi sukses (< 400) dan di luar auth / streaming
            if (
                $statusCode < 400 &&
                !str_contains($path, 'login') &&
                !str_contains($path, 'logout') &&
                !str_contains($path, 'realtime') &&
                !str_contains($path, 'aktivitas') &&
                !str_contains($path, 'target-capaian') &&
                !str_contains($path, 'laporan')
            ) {
                try {
                    RealtimeService::trigger('data.changed', [
                        'path'   => $path,
                        'method' => $request->method(),
                    ]);
                } catch (\Throwable) {
                    // Jangan gagalkan response utama jika caching bermasalah
                }

                // Otomatis rekam aktivitas riil tendik (pekerjaan per bidang diringkas per hari)
                // Tidak mencatat aktivitas login atau presensi harian
                try {
                    $sessionUser = session('user');
                    $role = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : ($sessionUser->role ?? '');

                    // Abaikan jika tidak login atau merupakan peserta didik / orang tua
                    $isBlockedRole = in_array(strtolower($role), ['peserta_didik', 'siswa', 'orang_tua', 'wali_murid'], true);

                    // Abaikan rute login, presensi harian, dan operasi teknis non-tugas
                    $isExcludedPath = str_contains($path, 'login') ||
                        str_contains($path, 'logout') ||
                        str_contains($path, 'presensi') ||
                        str_contains($path, 'absensi') ||
                        str_contains($path, 'toggle') ||
                        str_contains($path, 'unlock') ||
                        str_contains($path, 'rfid') ||
                        str_contains($path, 'execute') ||
                        str_contains($path, 'sync') ||
                        str_contains($path, 'mark-all-read') ||
                        str_contains($path, 'remove-module') ||
                        str_contains($path, 'reset') ||
                        str_contains($path, 'bulk-action') ||
                        str_contains($path, 'pengaturan') ||
                        str_contains($path, 'setting') ||
                        str_contains($path, 'installer') ||
                        str_contains($path, 'password') ||
                        str_contains($path, 'profil');

                    if ($sessionUser && !$isBlockedRole && !$isExcludedPath && \Illuminate\Support\Facades\Schema::hasTable('tendik_aktivitas')) {
                        $method = $request->method();
                        $baseAction = null;
                        $bidang = 'umum';
                        $unit = 'dokumen';
                        $template = null;

                        // 1. Modul Persuratan
                        if (str_contains($path, 'persuratan/masuk')) {
                            $bidang = 'persuratan';
                            $unit = 'dokumen';
                            if (str_contains($path, 'disposisi')) {
                                $baseAction = 'Disposisi surat masuk';
                                $template = 'Memproses lembar disposisi untuk %d dokumen surat masuk.';
                            } elseif ($method === 'POST') {
                                $baseAction = 'Input surat masuk';
                                $template = 'Melaksanakan input dan pencatatan %d dokumen surat masuk ke dalam buku agenda persuratan sekolah.';
                            } else {
                                $baseAction = 'Pembaruan arsip surat masuk';
                                $template = 'Memperbarui kelengkapan dan berkas %d dokumen surat masuk.';
                            }
                        } elseif (str_contains($path, 'persuratan/keluar')) {
                            $bidang = 'persuratan';
                            $unit = 'dokumen';
                            if (str_contains($path, 'keterangan')) {
                                $baseAction = 'Membuat surat keterangan';
                                $template = 'Menerbitkan dan mengesahkan %d dokumen surat keterangan siswa.';
                            } elseif (str_contains($path, 'spt')) {
                                $baseAction = 'Penerbitan surat tugas (SPT)';
                                $template = 'Menerbitkan %d dokumen Surat Perintah Tugas (SPT) kedinasan.';
                            } elseif (str_contains($path, 'template')) {
                                $baseAction = 'Pengelolaan template surat';
                                $unit = 'template';
                                $template = 'Mengatur format dan tata naskah %d template surat dinas sekolah.';
                            } elseif ($method === 'POST') {
                                $baseAction = 'Membuat surat keluar';
                                $template = 'Membuat dan menerbitkan nomor dinas untuk %d dokumen surat keluar.';
                            } else {
                                $baseAction = 'Pembaruan arsip surat keluar';
                                $template = 'Memperbarui arsip dan status pengiriman %d dokumen surat keluar.';
                            }
                        }
                        // 2. Modul Kesiswaan
                        elseif (str_contains($path, 'kesiswaan')) {
                            $bidang = 'kesiswaan';
                            if (str_contains($path, 'usulan')) {
                                $baseAction = 'Pengusulan data siswa';
                                $unit = 'siswa';
                                $template = 'Memproses pengusulan pembaruan data untuk %d peserta didik.';
                            } elseif (str_contains($path, 'berkas')) {
                                $baseAction = 'Verifikasi berkas siswa';
                                $unit = 'dokumen';
                                $template = 'Melakukan validasi dan verifikasi berkas digital %d dokumen siswa.';
                            } elseif (str_contains($path, 'mutasi')) {
                                $baseAction = 'Pemrosesan mutasi siswa';
                                $unit = 'berkas';
                                $template = 'Memproses kelengkapan berkas mutasi untuk %d berkas siswa.';
                            } elseif (str_contains($path, 'klaper')) {
                                $baseAction = 'Pembaruan buku klaper siswa';
                                $unit = 'data siswa';
                                $template = 'Melakukan pemutakhiran buku klaper untuk %d data siswa.';
                            } elseif (str_contains($path, 'kelulusan')) {
                                $baseAction = 'Pemrosesan kelulusan dan SKL';
                                $unit = 'dokumen';
                                $template = 'Menerbitkan dan memvalidasi %d dokumen SKL kesiswaan.';
                            } elseif (str_contains($path, 'pelanggaran')) {
                                $baseAction = 'Pencatatan pelanggaran siswa';
                                $unit = 'kasus';
                                $template = 'Mencatat dan memproses tindak lanjut %d kasus pelanggaran tata tertib.';
                            } elseif (str_contains($path, 'pembinaan')) {
                                $baseAction = 'Pencatatan pembinaan siswa';
                                $unit = 'kasus';
                                $template = 'Mendokumentasikan hasil pembinaan kedisiplinan terhadap %d kasus siswa.';
                            } elseif (str_contains($path, 'panggilan-wali')) {
                                $baseAction = 'Penerbitan surat panggilan wali';
                                $unit = 'dokumen';
                                $template = 'Menerbitkan %d dokumen surat panggilan orang tua / wali murid.';
                            } elseif (str_contains($path, 'organisasi') || str_contains($path, 'ekskul') || str_contains($path, 'agenda')) {
                                $baseAction = 'Pengelolaan kegiatan kesiswaan';
                                $unit = 'kegiatan';
                                $template = 'Mengelola administrasi dan agenda %d kegiatan kesiswaan.';
                            } elseif (str_contains($path, 'prestasi')) {
                                $baseAction = 'Pencatatan prestasi siswa';
                                $unit = 'prestasi';
                                $template = 'Mendata dan memvalidasi sertifikat %d prestasi siswa.';
                            }
                        }
                        // 3. Modul Kepegawaian GTK
                        elseif (str_contains($path, 'kepegawaian')) {
                            $bidang = 'kepegawaian';
                            if (str_contains($path, 'berkas')) {
                                $baseAction = 'Pengarsipan berkas pegawai';
                                $unit = 'dokumen';
                                $template = 'Mengarsipkan dan memvalidasi berkas digital %d dokumen GTK.';
                            } elseif (str_contains($path, 'kgb')) {
                                $baseAction = 'Pemrosesan usulan KGB pegawai';
                                $unit = 'usulan';
                                $template = 'Memproses usulan kenaikan gaji berkala (KGB) untuk %d berkas GTK.';
                            } elseif (str_contains($path, 'cuti')) {
                                $baseAction = 'Pengelolaan cuti pegawai';
                                $unit = 'dokumen';
                                $template = 'Memproses dan menerbitkan surat izin cuti untuk %d berkas pegawai.';
                            }
                        }
                        // 4. Modul Sarpras & Aset
                        elseif (str_contains($path, 'sarpras')) {
                            $bidang = 'sarpras';
                            if (str_contains($path, 'ruang')) {
                                $baseAction = 'Pendataan ruang dan bangunan';
                                $unit = 'ruangan';
                                $template = 'Memutakhirkan kondisi sarana pada %d unit ruangan sekolah.';
                            } elseif (str_contains($path, 'peminjaman')) {
                                $baseAction = 'Peminjaman sarana prasarana';
                                $unit = 'transaksi';
                                $template = 'Melayani dan memproses %d transaksi peminjaman sarpras sekolah.';
                            } else {
                                $baseAction = 'Inventarisasi sarana dan aset';
                                $unit = 'barang';
                                $template = 'Mencatat dan memperbarui kondisi %d unit barang/aset sekolah.';
                            }
                        }
                        // 5. Modul Laboratorium
                        elseif (str_contains($path, 'laboran')) {
                            $bidang = 'laboran';
                            if (str_contains($path, 'jadwal')) {
                                $baseAction = 'Penjadwalan laboratorium';
                                $unit = 'jadwal';
                                $template = 'Mengatur alokasi waktu dan jadwal untuk %d sesi praktikum lab.';
                            } else {
                                $baseAction = 'Inventarisasi peralatan lab';
                                $unit = 'item';
                                $template = 'Memeriksa dan memperbarui stok %d item peralatan laboratorium.';
                            }
                        }
                        // 6. Modul Perpustakaan
                        elseif (str_contains($path, 'perpustakaan')) {
                            $bidang = 'perpustakaan';
                            if (str_contains($path, 'sirkulasi')) {
                                $baseAction = 'Layanan sirkulasi perpustakaan';
                                $unit = 'transaksi';
                                $template = 'Memproses transaksi sirkulasi untuk %d transaksi buku perpustakaan.';
                            } elseif (str_contains($path, 'kunjungan')) {
                                $baseAction = 'Pencatatan kunjungan perpustakaan';
                                $unit = 'pengunjung';
                                $template = 'Merekap kehadiran pemustaka sebanyak %d pengunjung perpustakaan.';
                            } else {
                                $baseAction = 'Katalogisasi buku perpustakaan';
                                $unit = 'buku';
                                $template = 'Menginput dan memberi label untuk %d judul buku perpustakaan.';
                            }
                        }
                        // 7. Modul Teknisi
                        elseif (str_contains($path, 'teknisi')) {
                            $bidang = 'teknisi';
                            if (str_contains($path, 'work-order')) {
                                $baseAction = 'Penanganan tiket teknisi';
                                $unit = 'tiket';
                                $template = 'Menindaklanjuti dan menyelesaikan %d tiket perbaikan teknis.';
                            } else {
                                $baseAction = 'Pemeliharaan perangkat IT';
                                $unit = 'perangkat';
                                $template = 'Melaksanakan pengecekan rutin pada %d unit perangkat IT sekolah.';
                            }
                        }
                        // 8. Modul Keamanan Pos Gerbang
                        elseif (str_contains($path, 'keamanan')) {
                            $bidang = 'keamanan';
                            if (str_contains($path, 'buku-tamu')) {
                                $baseAction = 'Pencatatan buku tamu';
                                $unit = 'tamu';
                                $template = 'Mencatat identitas dan keperluan %d kunjungan tamu pos keamanan.';
                            } elseif (str_contains($path, 'patroli')) {
                                $baseAction = 'Patroli keamanan lingkungan';
                                $unit = 'putaran';
                                $template = 'Melaksanakan patroli kontrol lingkungan sekolah sebanyak %d putaran.';
                            } elseif (str_contains($path, 'insiden')) {
                                $baseAction = 'Penanganan insiden keamanan';
                                $unit = 'laporan';
                                $template = 'Mendokumentasikan penanganan terhadap %d laporan insiden keamanan.';
                            } elseif (str_contains($path, 'izin')) {
                                $baseAction = 'Pemeriksaan tiket izin gerbang';
                                $unit = 'siswa';
                                $template = 'Memeriksa tiket izin gerbang untuk %d peserta didik.';
                            }
                        }
                        // 9. Modul Penjaga & Fasilitas
                        elseif (str_contains($path, 'penjaga')) {
                            $bidang = 'penjaga';
                            if (str_contains($path, 'ronda')) {
                                $baseAction = 'Ronda malam dan penguncian gedung';
                                $unit = 'putaran';
                                $template = 'Melaksanakan kontrol keamanan gedung dan penguncian pintu sebanyak %d putaran.';
                            } else {
                                $baseAction = 'Pembersihan fasilitas sekolah';
                                $unit = 'area';
                                $template = 'Membersihkan dan merapikan sarana pada %d area fasilitas sekolah.';
                            }
                        }
                        // 10. Modul Piket Sekolah
                        elseif (str_contains($path, 'piket')) {
                            $bidang = 'umum';
                            if (str_contains($path, 'izin')) {
                                $baseAction = 'Penerbitan surat izin piket';
                                $unit = 'dokumen';
                                $template = 'Menerbitkan %d lembar surat izin meninggalkan kelas bagi siswa.';
                            } else {
                                $baseAction = 'Pengisian jurnal piket';
                                $unit = 'kegiatan';
                                $template = 'Mencatat %d kejadian operasional harian pada buku jurnal piket.';
                            }
                        }

                        // Hanya catat jika merupakan pekerjaan operasional tendik yang valid
                        if ($baseAction !== null) {
                            \App\Models\TendikAktivitas::recordAggregatedActivity(
                                $sessionUser,
                                $baseAction,
                                $bidang,
                                $unit,
                                1,
                                $template
                            );
                        }
                    }
                } catch (\Throwable) {
                    // Jangan mengganggu response utama
                }
            }
        }

        return $response;
    }
}
