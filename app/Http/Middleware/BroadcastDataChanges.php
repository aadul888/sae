<?php

namespace App\Http\Middleware;

use App\Services\RealtimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


use App\Support\Table;

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

                    if ($sessionUser && !$isBlockedRole && !$isExcludedPath && \App\Support\Table::exists('tendik_aktivitas')) {
                        $method = $request->method();
                        $baseAction = null;
                        $bidang = 'umum';
                        $unit = 'dokumen';
                        $template = null;

                        // 1. Modul Persuratan Dinas
                        if (str_contains($path, 'persuratan/masuk')) {
                            $bidang = 'persuratan';
                            $unit = 'dokumen';
                            if (str_contains($path, 'disposisi')) {
                                $baseAction = 'Pengelolaan Disposisi Surat Masuk Dinas';
                                $template = 'Memproses lembar disposisi pimpinan dan arahan tindak lanjut untuk total %d dokumen surat masuk dinas.';
                            } elseif ($method === 'POST') {
                                $baseAction = 'Pencatatan dan Pengagendaan Surat Masuk Dinas';
                                $template = 'Menerima, mencatat pada buku agenda persuratan, dan menyiapkan lembar disposisi untuk total %d dokumen surat masuk dinas.';
                            } else {
                                $baseAction = 'Pembaruan Arsip Surat Masuk Dinas';
                                $template = 'Memperbarui data dan kelengkapan fisik/digital untuk total %d dokumen surat masuk dinas.';
                            }
                        } elseif (str_contains($path, 'persuratan/keluar')) {
                            $bidang = 'persuratan';
                            $unit = 'dokumen';
                            if (str_contains($path, 'keterangan')) {
                                $baseAction = 'Penerbitan Surat Keterangan Peserta Didik';
                                $template = 'Memeriksa data pemohon, menyusun draft, dan menerbitkan total %d dokumen surat keterangan resmi peserta didik.';
                            } elseif (str_contains($path, 'spt')) {
                                $baseAction = 'Penerbitan Surat Perintah Tugas (SPT) Dinas';
                                $template = 'Menyusun dan menerbitkan total %d dokumen Surat Perintah Tugas (SPT) kedinasan sekolah.';
                            } elseif (str_contains($path, 'template')) {
                                $baseAction = 'Pengelolaan Format Template Naskah Dinas';
                                $unit = 'template';
                                $template = 'Memutakhirkan tata naskah dan format baku untuk total %d template surat resmi dinas sekolah.';
                            } elseif ($method === 'POST') {
                                $baseAction = 'Pembuatan dan Penerbitan Surat Keluar Dinas';
                                $template = 'Menyusun naskah dinas, menerbitkan nomor surat resmi, dan mendistribusikan total %d dokumen surat keluar pada agenda persuratan sekolah.';
                            } else {
                                $baseAction = 'Pembaruan Arsip Surat Keluar Dinas';
                                $template = 'Memperbarui arsip dan status pengiriman untuk total %d dokumen surat keluar dinas.';
                            }
                        }
                        // 2. Modul Kesiswaan Terpadu
                        elseif (str_contains($path, 'kesiswaan')) {
                            $bidang = 'kesiswaan';
                            if (str_contains($path, 'usulan')) {
                                $baseAction = 'Pengusulan dan Pemutakhiran Data Peserta Didik';
                                $unit = 'dokumen';
                                $template = 'Memeriksa berkas permohonan dan memproses usulan pembaruan data untuk total %d dokumen peserta didik.';
                            } elseif (str_contains($path, 'berkas')) {
                                $baseAction = 'Verifikasi dan Validasi Berkas Peserta Didik';
                                $unit = 'berkas';
                                $template = 'Melakukan pemeriksaan kelengkapan fisik dan validasi berkas digital untuk total %d berkas peserta didik.';
                            } elseif (str_contains($path, 'mutasi')) {
                                $baseAction = 'Pemrosesan Administrasi Mutasi Peserta Didik';
                                $unit = 'berkas';
                                $template = 'Memeriksa kelengkapan syarat dan memproses surat keterangan mutasi untuk total %d berkas peserta didik.';
                            } elseif (str_contains($path, 'klaper')) {
                                $baseAction = 'Pemutakhiran Buku Klaper Peserta Didik';
                                $unit = 'data siswa';
                                $template = 'Mencatat nomor induk, biodata, dan pembaruan buku klaper untuk total %d data siswa.';
                            } elseif (str_contains($path, 'kelulusan')) {
                                $baseAction = 'Pemrosesan Kelulusan dan Penerbitan SKL';
                                $unit = 'dokumen';
                                $template = 'Memvalidasi kelengkapan berkas akhir dan menerbitkan Surat Keterangan Lulus (SKL) untuk total %d dokumen peserta didik.';
                            } elseif (str_contains($path, 'pelanggaran')) {
                                $baseAction = 'Pencatatan Pelanggaran Tata Tertib Peserta Didik';
                                $unit = 'kasus';
                                $template = 'Mendokumentasikan laporan pelanggaran tata tertib dan poin kedisiplinan sebanyak total %d kasus peserta didik.';
                            } elseif (str_contains($path, 'pembinaan')) {
                                $baseAction = 'Pencatatan Bimbingan dan Pembinaan Peserta Didik';
                                $unit = 'kasus';
                                $template = 'Menyusun berita acara dan rekam pembinaan kedisiplinan sebanyak total %d kasus peserta didik.';
                            } elseif (str_contains($path, 'panggilan-wali')) {
                                $baseAction = 'Penerbitan Surat Panggilan Orang Tua / Wali Murid';
                                $unit = 'dokumen';
                                $template = 'Menerbitkan dan mendistribusikan surat panggilan kedinasan orang tua/wali sebanyak total %d dokumen.';
                            } elseif (str_contains($path, 'organisasi') || str_contains($path, 'ekskul') || str_contains($path, 'agenda')) {
                                $baseAction = 'Pengelolaan Administrasi Kegiatan & Ekstrakurikuler Siswa';
                                $unit = 'kegiatan';
                                $template = 'Mengelola administrasi dan agenda operasional untuk total %d kegiatan ekstrakurikuler/organisasi kesiswaan.';
                            } elseif (str_contains($path, 'prestasi')) {
                                $baseAction = 'Pendataan dan Validasi Prestasi Peserta Didik';
                                $unit = 'sertifikat';
                                $template = 'Memverifikasi keabsahan piagam penghargaan dan mencatat data prestasi untuk total %d sertifikat peserta didik.';
                            } else {
                                $baseAction = 'Pelayanan Administrasi Kesiswaan';
                                $unit = 'dokumen';
                                $template = 'Melaksanakan input dan pengelolaan dokumen kesiswaan sebanyak total %d dokumen.';
                            }
                        }
                        // 3. Modul Kepegawaian GTK
                        elseif (str_contains($path, 'kepegawaian')) {
                            $bidang = 'kepegawaian';
                            if (str_contains($path, 'berkas')) {
                                $baseAction = 'Pengarsipan dan Verifikasi Berkas Kepegawaian GTK';
                                $unit = 'berkas';
                                $template = 'Memeriksa kelengkapan dokumen kedinasan dan mengarsipkan secara digital total %d berkas pegawai GTK.';
                            } elseif (str_contains($path, 'kgb')) {
                                $baseAction = 'Pemrosesan Usulan Kenaikan Gaji Berkala (KGB)';
                                $unit = 'usulan';
                                $template = 'Menyiapkan berkas masa kerja dan draft surat pengantar kenaikan gaji berkala untuk total %d usulan pegawai.';
                            } elseif (str_contains($path, 'cuti')) {
                                $baseAction = 'Pelayanan Administrasi Cuti Pegawai GTK';
                                $unit = 'dokumen';
                                $template = 'Memproses permohonan, verifikasi sisa hak cuti, dan penerbitan izin cuti dinas untuk total %d dokumen pegawai.';
                            } else {
                                $baseAction = 'Pelayanan Administrasi Kepegawaian GTK';
                                $unit = 'dokumen';
                                $template = 'Melaksanakan pemrosesan dokumen administrasi kepegawaian sebanyak total %d dokumen.';
                            }
                        }
                        // 4. Modul Sarpras & Aset
                        elseif (str_contains($path, 'sarpras')) {
                            $bidang = 'sarpras';
                            if (str_contains($path, 'ruang')) {
                                $baseAction = 'Pendataan dan Pemeriksaan Kondisi Ruang Sarpras';
                                $unit = 'ruangan';
                                $template = 'Melakukan inspeksi fisik, pembaruan data inventaris ruangan (KIR), dan mencatat kondisi %d ruangan fasilitas sekolah.';
                            } elseif (str_contains($path, 'peminjaman')) {
                                $baseAction = 'Pelayanan Administrasi Peminjaman Sarpras';
                                $unit = 'transaksi';
                                $template = 'Melayani permohonan peminjaman, pencatatan keluar-masuk, dan pemeriksaan kondisi fisik untuk total %d transaksi sarpras.';
                            } else {
                                $baseAction = 'Inventarisasi dan Pencatatan Aset Sarana Sekolah';
                                $unit = 'barang';
                                $template = 'Mencatat pengadaan, pelabelan kode aset, dan pembukuan inventaris sarana prasarana sebanyak total %d barang/aset.';
                            }
                        }
                        // 5. Modul Laboratorium
                        elseif (str_contains($path, 'laboran')) {
                            $bidang = 'laboran';
                            if (str_contains($path, 'jadwal')) {
                                $baseAction = 'Penjadwalan dan Pengelolaan Ruang Laboratorium';
                                $unit = 'jadwal';
                                $template = 'Mengatur alokasi waktu pemakaian laboratorium dan kesiapan alat praktikum untuk total %d jadwal KBM.';
                            } else {
                                $baseAction = 'Inventarisasi Peralatan dan Bahan Praktikum Lab';
                                $unit = 'item';
                                $template = 'Memeriksa ketersediaan bahan, kalibrasi alat, dan mencatat buku inventaris lab sebanyak total %d item peralatan.';
                            }
                        }
                        // 6. Modul Perpustakaan
                        elseif (str_contains($path, 'perpustakaan')) {
                            $bidang = 'perpustakaan';
                            if (str_contains($path, 'sirkulasi')) {
                                $baseAction = 'Pelayanan Sirkulasi Peminjaman Buku Perpustakaan';
                                $unit = 'transaksi';
                                $template = 'Melayani transaksi peminjaman, pengembalian, dan pencatatan sirkulasi bahan pustaka sebanyak total %d transaksi pemustaka.';
                            } elseif (str_contains($path, 'kunjungan')) {
                                $baseAction = 'Pencatatan dan Rekapitulasi Kunjungan Perpustakaan';
                                $unit = 'pengunjung';
                                $template = 'Mencatat presensi kehadiran pemustaka dan menyusun statistik kunjungan perpustakaan sebanyak total %d pengunjung.';
                            } else {
                                $baseAction = 'Katalogisasi dan Pengolahan Bahan Pustaka';
                                $unit = 'buku';
                                $template = 'Melakukan klasifikasi DDC, pencatatan barcode, dan pelabelan punggung buku untuk total %d judul buku koleksi pustaka.';
                            }
                        }
                        // 7. Modul Teknisi
                        elseif (str_contains($path, 'teknisi')) {
                            $bidang = 'teknisi';
                            if (str_contains($path, 'work-order')) {
                                $baseAction = 'Penanganan Work Order dan Perbaikan Sarana IT';
                                $unit = 'tiket';
                                $template = 'Menindaklanjuti laporan kendala teknis, perbaikan hardware/software, dan menyelesaikan total %d tiket work order.';
                            } else {
                                $baseAction = 'Pemeliharaan Rutin Perangkat Komputer dan Jaringan';
                                $unit = 'perangkat';
                                $template = 'Melakukan pemeriksaan preventif berkala pada perangkat komputer, router, dan akses poin sebanyak total %d perangkat IT.';
                            }
                        }
                        // 8. Modul Keamanan Pos Gerbang
                        elseif (str_contains($path, 'keamanan')) {
                            $bidang = 'keamanan';
                            if (str_contains($path, 'buku-tamu')) {
                                $baseAction = 'Pencatatan Tamu Kedinasan Pos Keamanan';
                                $unit = 'tamu';
                                $template = 'Menerima tamu kedinasan, verifikasi identitas, dan mengisi buku agenda tamu sebanyak total %d kunjungan tamu.';
                            } elseif (str_contains($path, 'patroli')) {
                                $baseAction = 'Pelaksanaan Patroli Keamanan Lingkungan Sekolah';
                                $unit = 'putaran';
                                $template = 'Melaksanakan patroli kontrol situasi lingkungan, gerbang, dan batas area sekolah sebanyak total %d putaran patroli.';
                            } elseif (str_contains($path, 'insiden')) {
                                $baseAction = 'Penanganan dan Dokumentasi Insiden Keamanan';
                                $unit = 'laporan';
                                $template = 'Mendokumentasikan penanganan dan penyelesaian insiden ketertiban lingkungan sebanyak total %d laporan kejadian.';
                            } elseif (str_contains($path, 'izin')) {
                                $baseAction = 'Pemeriksaan Izin Keluar-Masuk Gerbang Siswa';
                                $unit = 'siswa';
                                $template = 'Memeriksa barcode tiket izin meninggalkan sekolah dan mencatat keluar-masuk gerbang untuk total %d peserta didik.';
                            }
                        }
                        // 9. Modul Penjaga & Fasilitas
                        elseif (str_contains($path, 'penjaga')) {
                            $bidang = 'penjaga';
                            if (str_contains($path, 'ronda')) {
                                $baseAction = 'Pengawasan Keamanan Gedung dan Penguncian Pintu';
                                $unit = 'putaran';
                                $template = 'Memeriksa seluruh pintu, jendela, saklar listrik ruang kelas dan kantor sebanyak total %d putaran pengawasan.';
                            } else {
                                $baseAction = 'Pemeliharaan Kebersihan dan Kenyamanan Lingkungan Sekolah';
                                $unit = 'area';
                                $template = 'Melaksanakan pembersihan ruang kelas, selasar, toilet, dan penataan taman sekolah pada total %d area fasilitas.';
                            }
                        }
                        // 10. Modul Piket Sekolah
                        elseif (str_contains($path, 'piket')) {
                            $bidang = 'umum';
                            if (str_contains($path, 'izin')) {
                                $baseAction = 'Penerbitan Surat Izin Keluar-Masuk Siswa Piket';
                                $unit = 'dokumen';
                                $template = 'Menerbitkan dan mengesahkan lembar izin keluar kelas dinas piket untuk total %d dokumen siswa.';
                            } else {
                                $baseAction = 'Pengisian Buku Jurnal Piket Harian Sekolah';
                                $unit = 'kegiatan';
                                $template = 'Mencatat rekapitulasi keterlambatan siswa dan dinamika operasional KBM pada total %d catatan jurnal piket.';
                            }
                        }
                        // 11. Modul Kartu Pelajar & Berkas
                        elseif (str_contains($path, 'kartu-pelajar')) {
                            $bidang = 'kesiswaan';
                            $unit = 'dokumen';
                            if (str_contains($path, 'klaim-garansi')) {
                                $baseAction = 'Pelayanan Klaim Penggantian Kartu Pelajar';
                                $template = 'Memverifikasi data dan memproses administrasi permohonan klaim kartu pelajar untuk total %d dokumen peserta didik.';
                            } else {
                                $baseAction = 'Pengelolaan dan Distribusi Kartu Pelajar';
                                $template = 'Mencatat pembukuan dan memproses distribusi kartu pelajar untuk total %d dokumen peserta didik.';
                            }
                        } elseif (str_contains($path, 'berkas')) {
                            $bidang = 'kesiswaan';
                            $unit = 'berkas';
                            $baseAction = 'Pengarsipan Berkas Digital Peserta Didik';
                            $template = 'Mengunggah dan mengarsipkan dokumen berkas persyaratan peserta didik ke dalam sistem arsip digital sebanyak total %d berkas.';
                        } elseif (str_contains($path, 'identitas')) {
                            $bidang = 'kesiswaan';
                            $unit = 'dokumen';
                            $baseAction = 'Konfirmasi dan Validasi Identitas Peserta Didik';
                            $template = 'Memeriksa dan mengonfirmasi keabsahan data identitas pokok peserta didik sebanyak total %d dokumen.';
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
